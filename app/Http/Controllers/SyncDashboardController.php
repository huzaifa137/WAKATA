<?php

namespace App\Http\Controllers;

use App\Models\SyncConflict;
use App\Models\SyncDevice;
use App\Models\SyncOutbox;
use App\Models\SyncState;
use App\Services\Sync\SyncTokenIssuer;
use App\Support\Sync\EnvWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SyncDashboardController extends Controller
{
    /**
     * The "Sync Now" screen — shown on school/offline installs.
     */
    public function index()
    {
        $pending = SyncOutbox::whereNull('synced_at')->count();
        $conflicted = SyncOutbox::whereNotNull('synced_at')->whereNotNull('last_error')->count();

        $recent = SyncOutbox::whereNotNull('synced_at')
            ->orderByDesc('synced_at')
            ->limit(15)
            ->get();

        $lastPush = SyncState::where('model_type', 'outbox')->value('last_pushed_at');
        $lastPull = SyncState::orderByDesc('last_pulled_at')->value('last_pulled_at');

        return view('sync.dashboard', [
            'role' => config('sync.role'),
            'configured' => $this->isConfigured(),
            'pending' => $pending,
            'conflicted' => $conflicted,
            'recent' => $recent,
            'lastPush' => $lastPush,
            'lastPull' => $lastPull,
        ]);
    }

    /**
     * AJAX: run push then pull right now, return a short summary.
     */
    public function run(Request $request)
    {
        if (config('sync.role') !== 'school') {
            return response()->json(['message' => 'This install is not configured as a school.'], 422);
        }

        Artisan::call('sync:push');
        $pushOutput = Artisan::output();

        Artisan::call('sync:pull');
        $pullOutput = Artisan::output();

        return response()->json([
            'push_output' => trim($pushOutput),
            'pull_output' => trim($pullOutput),
            'pending' => SyncOutbox::whereNull('synced_at')->count(),
        ]);
    }

    /**
     * Central-only: list pending conflicts for review.
     */
    public function conflicts()
    {
        $conflicts = SyncConflict::where('status', 'pending')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('sync.conflicts', ['conflicts' => $conflicts]);
    }

    /**
     * Central-only: resolve a conflict by keeping either the incoming
     * (school's offline edit) or current (central) version.
     */
    public function resolveConflict(Request $request, SyncConflict $conflict)
    {
        $request->validate(['resolution' => 'required|in:kept_incoming,kept_current']);

        if ($request->resolution === 'kept_incoming') {
            $modelType = $conflict->model_type;
            $modelType::updateOrCreate($conflict->decodedKey(), $conflict->decodedIncoming());
        }
        // kept_current: central data is already correct, nothing to write.

        $conflict->update([
            'status' => $request->resolution,
            'resolved_by' => session('LoggedAdmin'),
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Conflict resolved.');
    }

    /**
     * GET /sync/setup — the one-time "connect this install" wizard.
     * Shown on a school/office machine before it's configured; once
     * SYNC_ROLE + SYNC_TOKEN are set it shows a locked "already
     * configured" state instead, with an explicit unlock to redo it.
     */
    public function setupForm(Request $request)
    {
        return view('sync.setup', [
            'configured' => $this->isConfigured(),
            'forceEdit' => $request->boolean('edit'),
            'current' => [
                'role' => config('sync.role'),
                'central_url' => config('sync.central_url'),
                'school_number' => config('sync.school_number'),
                'device_name' => config('sync.device_name'),
            ],
        ]);
    }

    /**
     * POST /sync/setup — either paste the whole block issued by
     * /sync/tokens, or fill the fields in one at a time. Only ever
     * writes the 5 whitelisted SYNC_* keys to .env — nothing else.
     */
    public function saveSetup(Request $request)
    {
        $pasted = trim((string) $request->input('pasted_block'));

        if ($pasted !== '') {
            $parsed = $this->parseEnvBlock($pasted);
            $request->merge($parsed);
        }

        $validated = $request->validate([
            'sync_role' => 'required|in:school,central',
            'sync_central_url' => 'required|url',
            'sync_school_number' => 'required|string|max:20',
            'sync_device_name' => 'required|string|max:100',
            'sync_token' => 'required|string|min:20',
        ]);

        EnvWriter::set([
            'SYNC_ROLE' => $validated['sync_role'],
            'SYNC_CENTRAL_URL' => rtrim($validated['sync_central_url'], '/'),
            'SYNC_SCHOOL_NUMBER' => $validated['sync_school_number'],
            'SYNC_DEVICE_NAME' => $validated['sync_device_name'],
            'SYNC_TOKEN' => $validated['sync_token'],
        ]);

        Artisan::call('config:clear');

        return redirect()->route('sync.dashboard')
            ->with('success', 'This install is now connected. Try "Sync Now" once you have internet.');
    }

    /**
     * Turns a pasted block like:
     *   SYNC_ROLE=school
     *   SYNC_CENTRAL_URL=https://example.com
     *   ...
     * into ['sync_role' => 'school', 'sync_central_url' => '...', ...]
     * so the same form fields validate whether typed or pasted.
     */
    protected function parseEnvBlock(string $block): array
    {
        $parsed = [];

        foreach (preg_split('/\r\n|\r|\n/', $block) as $line) {
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = strtolower(trim($key));
            $value = trim($value, " \t\"'");

            $map = [
                'sync_role' => 'sync_role',
                'sync_central_url' => 'sync_central_url',
                'sync_school_number' => 'sync_school_number',
                'sync_device_name' => 'sync_device_name',
                'sync_token' => 'sync_token',
            ];

            if (isset($map[$key])) {
                $parsed[$map[$key]] = $value;
            }
        }

        return $parsed;
    }

    protected function isConfigured(): bool
    {
        return (bool) config('sync.central_url') && (bool) config('sync.token') && (bool) config('sync.school_number');
    }

    /**
     * GET /sync/tokens — central-only: issue + manage per-school tokens.
     */
    public function tokens()
    {
        abort_unless(config('sync.role') === 'central', 404);

        $devices = SyncDevice::orderByDesc('created_at')->get();

        return view('sync.tokens', ['devices' => $devices]);
    }

    /**
     * POST /sync/tokens — issue a new token, shown once on the resulting
     * page as a ready-to-copy .env block.
     */
    public function issueToken(Request $request, SyncTokenIssuer $issuer)
    {
        abort_unless(config('sync.role') === 'central', 404);

        $validated = $request->validate([
            'school_number' => 'required|string|max:20',
            'device_name' => 'required|string|max:100',
        ]);

        $result = $issuer->issue($validated['school_number'], $validated['device_name']);

        $envBlock = implode("\n", [
            'SYNC_ROLE=school',
            'SYNC_CENTRAL_URL=' . rtrim(config('app.url'), '/'),
            'SYNC_SCHOOL_NUMBER=' . $validated['school_number'],
            'SYNC_DEVICE_NAME="' . $validated['device_name'] . '"',
            'SYNC_TOKEN=' . $result['plain_token'],
        ]);

        return redirect()->route('sync.tokens')->with('issued_env_block', $envBlock);
    }

    /**
     * POST /sync/tokens/{device}/revoke — central-only.
     */
    public function revokeToken(SyncDevice $device)
    {
        abort_unless(config('sync.role') === 'central', 404);

        $device->update(['is_active' => false]);

        return back()->with('success', "Access revoked for {$device->device_name}.");
    }
}
