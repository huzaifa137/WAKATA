@extends('layouts-side-bar.master')

@section('content')
<div class="side-app">
    <div class="container-fluid mt-3">

        <h4 class="mb-3">Connect This Install</h4>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($configured && !$forceEdit)
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-success mb-3">
                        <i class="fa fa-check-circle"></i> This install is already connected and ready to sync.
                    </div>
                    <table class="table table-sm table-borderless mb-3">
                        <tr><th style="width:200px">Role</th><td>{{ $current['role'] }}</td></tr>
                        <tr><th>Central server</th><td>{{ $current['central_url'] }}</td></tr>
                        <tr><th>School number</th><td>{{ $current['school_number'] }}</td></tr>
                        <tr><th>Device name</th><td>{{ $current['device_name'] }}</td></tr>
                    </table>
                    <a href="{{ route('sync.dashboard') }}" class="btn btn-success">
                        <i class="fa fa-refresh"></i> Go to Sync Now
                    </a>
                    <a href="{{ route('sync.setup', ['edit' => 1]) }}" class="btn btn-outline-secondary">
                        Reconfigure this install
                    </a>
                </div>
            </div>
        @else
            <div class="card mb-3">
                <div class="card-body">
                    <h6>What you need before starting</h6>
                    <p class="text-muted">
                        Ask whoever manages the central WAKATA server for a sync token for this school —
                        it's created on their side under <strong>Offline Sync &rarr; Manage Tokens</strong> and
                        looks like a block of 5 lines starting with <code>SYNC_ROLE=</code>. You only need to
                        do this once per machine.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#pasteTab">Paste the block (fastest)</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#manualTab">Fill in fields manually</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="pasteTab">
                            <form method="POST" action="{{ route('sync.setup.save') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Paste the block you were given</label>
                                    <textarea name="pasted_block" rows="6" class="form-control" placeholder="SYNC_ROLE=school&#10;SYNC_CENTRAL_URL=https://your-central-domain.example&#10;SYNC_SCHOOL_NUMBER=07-2026&#10;SYNC_DEVICE_NAME=&quot;St. Mary's HQ Laptop&quot;&#10;SYNC_TOKEN=8f2a1e9c..."></textarea>
                                </div>
                                @if($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-plug"></i> Connect This Install
                                </button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="manualTab">
                            <form method="POST" action="{{ route('sync.setup.save') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">This install is a...</label>
                                    <select name="sync_role" class="form-select">
                                        <option value="school" selected>School / field office (offline install)</option>
                                        <option value="central">Central server</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Central server address</label>
                                    <input type="url" name="sync_central_url" class="form-control" placeholder="https://your-central-domain.example" value="{{ old('sync_central_url') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">School number</label>
                                    <input type="text" name="sync_school_number" class="form-control" placeholder="07-2026" value="{{ old('sync_school_number') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Device name</label>
                                    <input type="text" name="sync_device_name" class="form-control" placeholder="St. Mary's HQ Laptop" value="{{ old('sync_device_name') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sync token</label>
                                    <input type="text" name="sync_token" class="form-control" placeholder="the long token you were given" value="{{ old('sync_token') }}">
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-plug"></i> Connect This Install
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
</div>
                </div>
            </div>
@endsection
