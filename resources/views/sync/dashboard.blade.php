@extends('layouts-side-bar.master')

@section('content')
    <div class="side-app">
        <div class="container-fluid mt-3">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Offline Sync</h4>
                @if($role === 'central')
                    <div>
                        <a href="{{ route('sync.tokens') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fa fa-key"></i> Manage School Tokens
                        </a>
                        <a href="{{ route('sync.conflicts') }}" class="btn btn-outline-warning btn-sm">
                            <i class="fa fa-exclamation-triangle"></i> Review Conflicts
                        </a>
                    </div>
                @endif
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(!$configured)
                <div class="card mb-3">
                    <div class="card-header">Get this install connected</div>
                    <div class="card-body">
                        <ol class="mb-3">
                            <li>Ask the central server admin for a token under <strong>Offline Sync &rarr; Manage
                                    Tokens</strong> (or they can hand you one directly).</li>
                            <li>Come back here and click below to paste it in — no <code>.env</code> editing needed.</li>
                            <li>Once connected, use the students/subjects already on this machine, or run an initial sync while
                                you still have internet so this machine has the latest data before heading offline.</li>
                            <li>Enter marks / registrations as normal, even with no internet at all.</li>
                            <li>When you're back online, come back here and press <strong>Sync Now</strong>.</li>
                        </ol>
                        <a href="{{ route('sync.setup') }}" class="btn btn-success btn-lg">
                            <i class="fa fa-plug"></i> Connect This Install
                        </a>
                    </div>
                </div>
            @else
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card p-3 text-center">
                            <div class="text-muted small">Waiting to push</div>
                            <div class="h3 mb-0" id="pendingCount">{{ $pending }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center">
                            <div class="text-muted small">Flagged conflicts</div>
                            <div class="h3 mb-0 {{ $conflicted > 0 ? 'text-danger' : '' }}">{{ $conflicted }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center">
                            <div class="text-muted small">Last push</div>
                            <div class="h6 mb-0">{{ $lastPush ?? 'Never' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center">
                            <div class="text-muted small">Last pull</div>
                            <div class="h6 mb-0">{{ $lastPull ?? 'Never' }}</div>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-success btn-lg" id="syncNowBtn">
                    <i class="fa fa-refresh"></i> Sync Now
                </button>
                <a href="{{ route('sync.setup', ['edit' => 1]) }}" class="btn btn-outline-secondary btn-lg">
                    Connection Settings
                </a>
                <div id="syncStatus" class="mt-3"></div>

                @if($conflicted > 0)
                    <div class="alert alert-info mt-3">
                        {{ $conflicted }} change(s) were flagged for review on the central server. Ask the registrar to
                        check the conflicts screen there.
                    </div>
                @endif

                <h5 class="mt-4">Recently synced</h5>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Operation</th>
                            <th>Synced at</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent as $row)
                            <tr>
                                <td>{{ class_basename($row->model_type) }}</td>
                                <td>{{ $row->operation }}</td>
                                <td>{{ $row->synced_at }}</td>
                                <td>
                                    @if($row->last_error)
                                        <span class="text-danger">{{ $row->last_error }}</span>
                                    @else
                                        <span class="text-success">OK</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Nothing synced yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif

        </div>
    </div>
    </div>
    </div>

    <script>
        document.getElementById('syncNowBtn')?.addEventListener('click', function () {
            const btn = this;
            const status = document.getElementById('syncStatus');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Syncing...';
            status.innerHTML = '';

            fetch("{{ route('sync.run') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json',
                },
            })
                .then(r => r.json())
                .then(data => {
                    status.innerHTML = '<div class="alert alert-success">Sync finished. Pending: ' + data.pending + '</div>';
                    document.getElementById('pendingCount').innerText = data.pending;
                })
                .catch(() => {
                    status.innerHTML = '<div class="alert alert-danger">Could not reach the central server. Will retry next time.</div>';
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-refresh"></i> Sync Now';
                });
        });
    </script>
@endsection