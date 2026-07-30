@extends('layouts-side-bar.master')

@section('content')
<div class="side-app">
    <div class="container-fluid mt-3">

        <h4 class="mb-3">Offline Sync — School Tokens</h4>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('issued_env_block'))
            <div class="card border-success mb-4">
                <div class="card-header bg-success text-white">
                    <i class="fa fa-check-circle"></i> Token created — copy this now, it won't be shown again
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Give this whole block to whoever is setting up that school's machine. On that machine
                        they open <strong>Offline Sync &rarr; Connect This Install</strong> and paste it in — no
                        typing, no <code>.env</code> editing.
                    </p>
                    <pre id="envBlock" class="bg-light p-3">{{ session('issued_env_block') }}</pre>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="copyEnvBlock()">
                        <i class="fa fa-copy"></i> Copy to clipboard
                    </button>
                </div>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-header">Issue a token for a new school / office install</div>
            <div class="card-body">
                <form method="POST" action="{{ route('sync.tokens.issue') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">School number</label>
                        <input type="text" name="school_number" class="form-control" placeholder="07-2026" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Device / install label</label>
                        <input type="text" name="device_name" class="form-control" placeholder="St. Mary's HQ Laptop" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fa fa-key"></i> Issue Token
                        </button>
                    </div>
                </form>
                @if($errors->any())
                    <div class="alert alert-danger mt-3 mb-0">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">Issued tokens</div>
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>School number</th>
                        <th>Device</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last used</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($devices as $device)
                        <tr>
                            <td>{{ $device->school_number }}</td>
                            <td>{{ $device->device_name }}</td>
                            <td>{{ $device->role }}</td>
                            <td>
                                @if($device->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Revoked</span>
                                @endif
                            </td>
                            <td>{{ $device->last_used_at ?? 'Never' }}</td>
                            <td>
                                @if($device->is_active)
                                    <form method="POST" action="{{ route('sync.tokens.revoke', $device->id) }}" onsubmit="return confirm('Revoke access for {{ $device->device_name }}? They will not be able to sync until reissued.')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger">Revoke</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No tokens issued yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
    </div>
</div>

<script>
function copyEnvBlock() {
    const text = document.getElementById('envBlock').innerText;
    navigator.clipboard.writeText(text);
}
</script>
@endsection
