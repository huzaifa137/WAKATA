<?php

namespace App\Http\Controllers;

use App\Services\GeminiKeyRotator;
use Illuminate\Http\Request;

class GeminiKeyMonitorController extends Controller
{
    public function index(Request $request, GeminiKeyRotator $rotator)
    {
        $hours = (int) $request->query('hours', 24);
        $stats = $rotator->stats($hours);

        if ($request->wantsJson()) {
            return response()->json(['hours' => $hours, 'keys' => $stats]);
        }

        return view('gemini-key-monitor', compact('stats', 'hours'));
    }
}