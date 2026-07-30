@extends('layouts-side-bar.master')

@section('content')
<div class="side-app">
    <div class="container-fluid mt-3">

        <h4 class="mb-3">Sync Conflicts</h4>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @forelse($conflicts as $conflict)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <span>
                        <strong>{{ class_basename($conflict->model_type) }}</strong>
                        &mdash; {{ $conflict->school_number }} ({{ $conflict->device_name }})
                    </span>
                    <span class="text-muted small">{{ $conflict->created_at }}</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Incoming (offline edit)</h6>
                            <pre class="bg-light p-2">{{ json_encode($conflict->decodedIncoming(), JSON_PRETTY_PRINT) }}</pre>
                        </div>
                        <div class="col-md-6">
                            <h6>Current (central)</h6>
                            <pre class="bg-light p-2">{{ json_encode($conflict->decodedCurrent(), JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('sync.conflicts.resolve', $conflict->id) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="resolution" value="kept_incoming">
                        <button class="btn btn-sm btn-success" onclick="return confirm('Use the offline (incoming) value?')">
                            Keep incoming
                        </button>
                    </form>
                    <form method="POST" action="{{ route('sync.conflicts.resolve', $conflict->id) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="resolution" value="kept_current">
                        <button class="btn btn-sm btn-secondary" onclick="return confirm('Keep the current central value?')">
                            Keep current
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-muted">No pending conflicts.</p>
        @endforelse

        {{ $conflicts->links() }}
    </div>
</div>
@endsection
