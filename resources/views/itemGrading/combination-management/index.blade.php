@extends('layouts-side-bar.master')

@section('content')
<div class="side-app">
    <div class="container-fluid mt-3">

        <style>
            .cm-table th, .cm-table td {
                padding: 8px 10px;
                border: 1px solid #e8e8e8;
                text-align: center;
                vertical-align: middle;
            }
            .cm-table thead th { background: #026837; color: #fff; }
            .cm-table tbody tr:nth-child(even) { background: #fafafa; }
            .cm-table tbody tr.inactive-row { opacity: .55; }

            .active-pill {
                padding: 3px 10px; border-radius: 12px; font-size: .75rem; font-weight: 600;
            }
            .active-pill.active { background-color: #e6f7ea; color: #1e7e34; border: 1px solid #1e7e34; }
            .active-pill.inactive { background-color: #f8e6e6; color: #a71d2a; border: 1px solid #a71d2a; }

            .subject-chip {
                display: inline-block;
                padding: 2px 9px;
                margin: 2px;
                border-radius: 12px;
                background: #eef3ff;
                color: #1c4ea3;
                font-size: .78rem;
                font-weight: 600;
            }

            .cm-actions { display: flex; align-items: center; justify-content: center; gap: 8px; }

            #combinationSubjectsContainer {
                max-height: 260px;
                overflow-y: auto;
                border: 1px solid #e8e8e8;
                border-radius: 8px;
                padding: 10px 14px;
            }
            #combinationSubjectsContainer .form-check { margin-bottom: 4px; }
        </style>

        <div class="card shadow-lg border-0">
            <div class="card-header d-flex justify-content-between align-items-center" style="background:#026837;">
                <h5 class="text-white mb-0"><i class="fa fa-layer-group me-2"></i> UACE Combinations</h5>
                <button class="btn btn-light btn-sm" onclick="cmOpenAddModal()">
                    <i class="fa fa-plus me-1"></i> Add Combination
                </button>
            </div>

            <div class="card-body">
                <p class="text-muted mb-3">
                    Define the standardized UACE combinations (e.g. PCM, HEG) and which principal subjects belong
                    to each. Once defined here, schools pick a combination per student on the Subject Registration
                    screen instead of ticking individual subjects.
                </p>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table cm-table mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Subjects</th>
                                <th>Students Assigned</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($combinations['UACE'] as $combination)
                                <tr class="{{ $combination->status === 'Inactive' ? 'inactive-row' : '' }}">
                                    <td><strong>{{ $combination->code }}</strong></td>
                                    <td class="text-start">{{ $combination->name }}</td>
                                    <td class="text-start">
                                        @foreach($combination->subjects as $subject)
                                            <span class="subject-chip">
                                                {{ $subject->md_name }}@if($subject->md_misc4 === 'Subsidiary') <em>(Sub)</em>@endif
                                            </span>
                                        @endforeach
                                    </td>
                                    <td>{{ $combination->student_count }}</td>
                                    <td>
                                        <span class="active-pill {{ strtolower($combination->status) }}">{{ $combination->status }}</span>
                                    </td>
                                    <td>
                                        <div class="cm-actions">
                                            <button class="btn btn-sm btn-outline-primary" title="Edit"
                                                onclick='cmOpenEditModal(
                                                    {{ $combination->id }},
                                                    {{ json_encode($combination->code) }},
                                                    {{ json_encode($combination->subjects->where("md_misc4", "!=", "Subsidiary")->pluck("md_id")->values()) }},
                                                    {{ json_encode(optional($combination->subjects->firstWhere("md_misc4", "Subsidiary"))->md_id) }}
                                                )'>
                                                <i class="fa fa-pen"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" title="Toggle Active/Inactive"
                                                onclick="cmToggleStatus({{ $combination->id }})">
                                                <i class="fa fa-toggle-on"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete"
                                                onclick="cmDeleteCombination({{ $combination->id }}, {{ json_encode($combination->code) }}, {{ $combination->student_count }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted py-4">
                                        No combinations defined yet. Click "Add Combination" to create the first one (e.g. PCM).
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Add / Edit modal --}}
<div class="modal fade" id="combinationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="combinationForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="combinationFormMethod" value="POST">
                <input type="hidden" name="category" value="UACE">
                <div class="modal-header" style="background:#026837;">
                    <h5 class="modal-title text-white" id="combinationModalTitle">Add Combination</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Code</label>
                        <input type="text" name="code" id="combinationCode" class="form-control" placeholder="e.g. PCM" maxlength="10" value="{{ old('code') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" id="combinationNamePreview" class="form-control" disabled placeholder="Select subjects below to build the name">
                        <small class="text-muted">Generated automatically from the subjects you pick.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Principal Subjects <span id="combinationPrincipalCount" class="badge bg-secondary text-white">0 / 3</span>
                        </label>
                        <div id="combinationPrincipalContainer">
                            @forelse($availableSubjects['UACE']['principal'] as $subject)
                                <div class="form-check">
                                    <input class="form-check-input combination-principal-checkbox" type="checkbox"
                                        name="principal_subject_ids[]" value="{{ $subject->md_id }}" id="subj-p-{{ $subject->md_id }}"
                                        data-name="{{ $subject->md_name }}"
                                        {{ in_array($subject->md_id, old('principal_subject_ids', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="subj-p-{{ $subject->md_id }}">
                                        {{ $subject->md_name }}
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">
                                    No Principal subjects yet — add some under
                                    <a href="{{ route('subject.management.index') }}" target="_blank">Subject Management</a>
                                    (UACE, Optional, role = Principal).
                                </p>
                            @endforelse
                        </div>
                        <small class="text-muted">Pick exactly 3 — e.g. Physics, Chemistry, Mathematics.</small>
                    </div>

                    <div class="mb-1">
                        <label class="form-label fw-semibold">Subsidiary Subject <span class="text-muted fw-normal">(optional, pick at most 1)</span></label>
                        <div id="combinationSubsidiaryContainer">
                            <div class="form-check">
                                <input class="form-check-input combination-subsidiary-radio" type="radio"
                                    name="subsidiary_subject_id" value="" id="subj-s-none" checked>
                                <label class="form-check-label text-muted" for="subj-s-none">None</label>
                            </div>
                            @forelse($availableSubjects['UACE']['subsidiary'] as $subject)
                                <div class="form-check">
                                    <input class="form-check-input combination-subsidiary-radio" type="radio"
                                        name="subsidiary_subject_id" value="{{ $subject->md_id }}" id="subj-s-{{ $subject->md_id }}"
                                        data-name="{{ $subject->md_name }}"
                                        {{ old('subsidiary_subject_id') == $subject->md_id ? 'checked' : '' }}>
                                    <label class="form-check-label" for="subj-s-{{ $subject->md_id }}">
                                        {{ $subject->md_name }}
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">
                                    No Subsidiary subjects yet — add some under
                                    <a href="{{ route('subject.management.index') }}" target="_blank">Subject Management</a>
                                    (UACE, Optional, role = Subsidiary), e.g. Sub Math, Sub ICT.
                                </p>
                            @endforelse
                        </div>
                        <small class="text-muted">Only Principal/Subsidiary subjects can belong to a combination — compulsory subjects like General Paper are registered automatically for everyone.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-save me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function cmResetCheckboxes() {
        document.querySelectorAll('.combination-principal-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('subj-s-none').checked = true;
        cmUpdatePreview();
    }

    // Cap principal picks at 3, and keep the live name preview + counter in sync.
    function cmUpdatePreview() {
        const principalBoxes = Array.from(document.querySelectorAll('.combination-principal-checkbox'));
        const checkedPrincipals = principalBoxes.filter(cb => cb.checked);

        document.getElementById('combinationPrincipalCount').textContent = checkedPrincipals.length + ' / 3';

        principalBoxes.forEach(cb => {
            cb.disabled = !cb.checked && checkedPrincipals.length >= 3;
        });

        const subsidiary = document.querySelector('.combination-subsidiary-radio:checked');
        const names = checkedPrincipals.map(cb => cb.dataset.name);
        if (subsidiary && subsidiary.value) {
            names.push(subsidiary.dataset.name);
        }

        document.getElementById('combinationNamePreview').value = names.join(', ');
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.combination-principal-checkbox, .combination-subsidiary-radio')
            .forEach(el => el.addEventListener('change', cmUpdatePreview));
        cmUpdatePreview();
    });

    function cmOpenAddModal() {
        document.getElementById('combinationModalTitle').innerHTML = '<i class="fa fa-plus me-2"></i> Add Combination';
        document.getElementById('combinationForm').action = "{{ route('combination.management.store') }}";
        document.getElementById('combinationFormMethod').value = 'POST';
        document.getElementById('combinationCode').value = '';
        cmResetCheckboxes();
        $('#combinationModal').modal('show');
    }

    function cmOpenEditModal(id, code, principalIds, subsidiaryId) {
        document.getElementById('combinationModalTitle').innerHTML = '<i class="fa fa-pen me-2"></i> Edit Combination';
        document.getElementById('combinationForm').action = "{{ url('combination-management') }}/" + id;
        document.getElementById('combinationFormMethod').value = 'PUT';
        document.getElementById('combinationCode').value = code;
        cmResetCheckboxes();
        principalIds.forEach(sid => {
            const cb = document.getElementById('subj-p-' + sid);
            if (cb) cb.checked = true;
        });
        const subsidiaryInput = subsidiaryId ? document.getElementById('subj-s-' + subsidiaryId) : null;
        if (subsidiaryInput) {
            subsidiaryInput.checked = true;
        } else {
            document.getElementById('subj-s-none').checked = true;
        }
        cmUpdatePreview();
        $('#combinationModal').modal('show');
    }

    function cmToggleStatus(id) {
        fetch("{{ url('combination-management') }}/" + id + "/toggle-status", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'Could not update', text: data.message || 'Please try again.', confirmButtonColor: '#d33' });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Something went wrong', text: 'Please try again.', confirmButtonColor: '#d33' });
            });
    }

    function cmDeleteCombination(id, code, studentCount) {
        if (studentCount > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Cannot delete "' + code + '"',
                html: 'This combination already has <strong>' + studentCount + '</strong> student(s) assigned to it.<br><br>Deactivate it instead to keep historic records intact.',
                confirmButtonColor: '#026837'
            });
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Delete "' + code + '"?',
            text: 'This cannot be undone.',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            confirmButtonColor: '#d33',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            fetch("{{ url('combination-management') }}/" + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Deleted', text: data.message, confirmButtonColor: '#026837' })
                            .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Could not delete', text: data.message || 'Please try again.', confirmButtonColor: '#d33' });
                    }
                })
                .catch(() => {
                    Swal.fire({ icon: 'error', title: 'Something went wrong', text: 'Please try again.', confirmButtonColor: '#d33' });
                });
        });
    }

    @if ($errors->any() && !session('success'))
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('combinationForm').action = "{{ route('combination.management.store') }}";
            document.getElementById('combinationFormMethod').value = 'POST';
            $('#combinationModal').modal('show');
        });
    @endif
</script>
@endsection