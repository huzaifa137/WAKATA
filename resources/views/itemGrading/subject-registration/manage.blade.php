@extends('layouts-side-bar.master')

@section('content')
    <div class="side-app">
        <div class="container-fluid mt-3">

    <style>
    .sr-table-wrap {
        overflow-x: auto;
        max-width: 100%;
    }

    .sr-table {
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.85rem;
        white-space: nowrap;
        width: 100%;
    }

    .sr-table th,
    .sr-table td {
        padding: 8px 10px;
        border: 1px solid #e8e8e8;
        text-align: center;
        vertical-align: middle;
    }

    .sr-table thead th {
        background: #043AA1;
        color: white;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .sr-table th.subject-col {
        min-width: 90px;
        max-width: 120px;
        white-space: normal;
    }

    /* Combination column (UACE) — the only column with no fixed width,
       so on wide screens it grows to absorb the table's leftover width
       (the table itself is width:100%) instead of leaving blank space
       to the right of the table. */
    .sr-table th.combo-col,
    .sr-table td.combo-col {
        min-width: 340px;
        white-space: normal;
    }

    .sr-table th.subject-compulsory {
        background: #038f16;
    }

    /* Plain (non-sticky) identity columns — just fixed widths so the
       table renders predictably and scrolls as one simple block */
    .sr-table td.id-col,
    .sr-table th.id-col {
        background: #fff;
        text-align: left;
        box-sizing: border-box;
    }

    .sr-table thead th.id-col {
        background: #025c30;
    }

    .sr-table tbody tr:nth-child(even) td.id-col {
        background: #fafafa;
    }

    .sr-table .id-col-1 {
        width: 40px;
        min-width: 40px;
        max-width: 40px;
    }

    .sr-table .id-col-2 {
        width: 140px;
        min-width: 140px;
        max-width: 140px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sr-table .id-col-3 {
        width: 150px;
        min-width: 150px;
        max-width: 150px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        border-right: 2px solid #d5d5d5;
    }

    .subject-check {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .subject-check:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    /* Searchable combination picker (plain JS, no jQuery plugin). There
       can be a lot of combinations, so this is a type-to-filter text box
       + dropdown instead of a long <select> the user has to scroll
       through. A single dropdown panel (#comboPickerPortal, built in JS)
       is reused for every row and positioned under whichever input is
       focused, so it always renders above the table and is never clipped
       by the table's horizontal scroll area. */
    .combo-picker {
        position: relative;
        min-width: 320px;
    }

    .combo-picker-input {
        width: 100%;
        box-sizing: border-box;
        padding: 6px 26px 6px 10px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 0.85rem;
        white-space: normal;
        background: #fff;
    }

    .combo-picker-input:focus {
        outline: none;
        border-color: #043AA1;
        box-shadow: 0 0 0 2px rgba(2, 104, 55, 0.15);
    }

    .combo-picker-input:disabled {
        background: #f3f3f3;
        opacity: 0.8;
    }

    .combo-picker-input.combo-picker-saving {
        opacity: 0.6;
    }

    .combo-picker-clear {
        position: absolute;
        right: 4px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        color: #999;
        font-size: 15px;
        line-height: 1;
        cursor: pointer;
        padding: 2px 5px;
    }

    .combo-picker-clear:hover {
        color: #d33;
    }

    #comboPickerPortal {
        position: fixed;
        z-index: 99999;
        background: #fff;
        border: 1px solid #ced4da;
        border-radius: 6px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
        max-height: 260px;
        overflow-y: auto;
        display: none;
    }

    .combo-picker-option {
        padding: 8px 12px;
        font-size: 0.85rem;
        text-align: left;
        white-space: normal;
        cursor: pointer;
        border-bottom: 1px solid #f1f1f1;
    }

    .combo-picker-option:last-child {
        border-bottom: none;
    }

    .combo-picker-option:hover {
        background: #eaf6ee;
    }

    .combo-picker-empty {
        padding: 10px 12px;
        font-size: 0.85rem;
        color: #888;
        text-align: center;
    }

    .compulsory-tag {
        font-size: 0.65rem;
        display: block;
        opacity: 0.85;
    }

    .toolbar-actions > * {
        margin-right: 10px;
    }

    .toolbar-actions > *:last-child {
        margin-right: 0;
    }

    /* Search bar — matches bulk import page */
    .sr-search-wrap {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .sr-search-box {
        position: relative;
        max-width: 320px;
        width: 100%;
    }

    .sr-search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #9aa5a0;
        font-size: 14px;
        pointer-events: none;
        transition: color 0.2s ease;
    }

    .sr-search-input {
        width: 100%;
        padding: 10px 38px 10px 38px;
        border: 1.5px solid #e2e8e5;
        border-radius: 10px;
        font-size: 14px;
        color: #333;
        background: #fff;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        outline: none;
    }

    .sr-search-input::placeholder {
        color: #a3aca8;
    }

    .sr-search-input:focus {
        border-color: #043AA1;
        box-shadow: 0 0 0 3px rgba(2, 104, 55, 0.12);
    }

    .sr-search-input:focus ~ .sr-search-icon {
        color: #043AA1;
    }

    .sr-search-clear {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: #eef1f0;
        color: #666;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s ease, color 0.2s ease;
        padding: 0;
    }

    .sr-search-clear:hover {
        background: #d33;
        color: #fff;
    }

    .sr-search-count {
        font-size: 13px;
        color: #6c7570;
        font-weight: 500;
        white-space: nowrap;
    }

    #srNoResults {
        display: none;
    }

    /* Compulsory subjects are hidden by default; toggled via #srToggleCompulsoryBtn */
    .sr-table.hide-compulsory .compulsory-col {
        display: none;
    }

    /* Full toolbar responsive — stacks and enlarges on mobile */
@media (max-width: 768px) {
    .d-flex.flex-wrap.gap-3.justify-content-between.align-items-center {
        flex-direction: column;
        align-items: stretch !important;
        gap: 12px !important;
    }

    .d-flex.flex-wrap.gap-3.justify-content-between.align-items-center > * {
        width: 100%;
    }

    .toolbar-actions {
        flex-direction: column;
        width: 100%;
        gap: 8px;
    }

    .toolbar-actions .btn,
    .toolbar-actions a {
        width: 100%;
        justify-content: center;
        padding: 10px 16px;
        border-radius: 10px;
        height: 48px;
        min-height: 48px;
    }
}
</style>

<div class="card shadow-lg border-0">
    <div class="card-header text-white d-flex justify-content-between align-items-center flex-wrap gap-2"
        style="background-color:#043AA1;">
        <h4 class="mb-0">
            <i class="fa fa-list-check me-2"></i>
            {{ $category }} Subject Registration — {{ $schoolNumber }} ({{ $schoolName }}) — {{ $year }}
        </h4>
        <span class="badge bg-light text-dark">
            <i class="fa fa-users me-1"></i> {{ $students->count() }} Students
        </span>
    </div>

                <div class="card-body">

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            @if (session('success'))
                                Swal.fire({ icon: 'success', title: 'Success!', text: @json(session('success')), confirmButtonColor: '#043AA1' });
                            @endif

                            @if (session('import_skipped') && count(session('import_skipped')))
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Some rows were skipped',
                                    html: `<ul style="text-align:left;">{!! collect(session('import_skipped'))->map(fn($m) => '<li>' . e($m) . '</li>')->join('') !!}</ul>`,
                                    confirmButtonColor: '#043AA1'
                                });
                            @endif

                            @if ($errors->any())
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Import Error',
                                    html: `{!! implode('<br>', $errors->all()) !!}`,
                                    confirmButtonColor: '#d33'
                                });
                            @endif
                        });
                    </script>

{{-- Toolbar: legend / search / download template / import filled sheet --}}
<div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4 p-3 bg-light rounded-3 shadow-sm">
    <div class="text-muted small">
        <i class="fa fa-circle" style="color:#043AA1;font-size:8px;"></i> Compulsory (auto-registered) &nbsp;
        <i class="fa fa-square" style="color:#bd059e;font-size:8px;"></i> Optional (tick what each student sat)
    </div>

    <div class="sr-search-wrap">
        <div class="sr-search-box">
            <i class="fa fa-search sr-search-icon"></i>
            <input type="text" id="srStudentSearchInput" class="sr-search-input"
                placeholder="Search by student name or ID...">
            <button type="button" id="srSearchClear" class="sr-search-clear" style="display:none;" aria-label="Clear search">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <span class="sr-search-count" id="srSearchCount"></span>
    </div>

    <div class="d-flex flex-wrap gap-2 align-items-center toolbar-actions">
        <a class="btn btn-outline-dark btn-sm rounded-pill px-3"
            href="{{ route('subject.registration.template', ['year' => $year, 'category' => $category, 'school_number' => $schoolNumber]) }}">
            <i class="fa fa-download me-2"></i> Download Excel Template
        </a>

        <button type="button" class="btn btn-sm text-white rounded-pill px-4 shadow-sm" style="background-color:#043AA1;"
            data-toggle="modal" data-target="#importModal">
            <i class="fa fa-upload me-2"></i> Import Filled Template
        </button>

        <button type="button" id="srToggleCompulsoryBtn" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fa fa-eye me-2"></i> <span id="srToggleCompulsoryLabel">Show Compulsory Subjects</span>
        </button>


<a href="{{ url('combination-management') }}" class="btn btn-sm rounded-pill px-3" style="background-color: #0d6efd; color: white; border-color: #0d6efd;"><i class="fa fa-eye me-2"></i> <span>Add a Combination</span></a>            
    </div>
</div>

                    @if ($students->count() > 0)
                        <div class="sr-table-wrap">
                            @php
                                $compulsorySubjects = $subjects->where('md_misc1', 'Compulsory');
                                $optionalSubjects = $subjects->where('md_misc1', '!=', 'Compulsory');
                                $combinationsById = $category === 'UACE'
                                    ? collect($combinationsList ?? [])->keyBy('id')
                                    : collect();
                            @endphp

                            @if ($category === 'UACE')
                                {{-- All combinations, shared by every row's search picker (see JS
                                     below). Sent once as JSON rather than repeating <option> tags
                                     hundreds of times in the HTML. --}}
                                <script id="srCombinationsData" type="application/json">
                                    {!! collect($combinationsList ?? [])
                                        ->map(fn ($c) => [
                                            'id' => $c->id,
                                            'text' => $c->code . ' — ' . $c->name,
                                        ])
                                        ->values()
                                        ->toJson() !!}
                                </script>
                            @endif

                            <table class="sr-table hide-compulsory" id="srTable">
                                <thead>
                                    <tr>
                                        <th class="id-col id-col-1">#</th>
                                        <th class="id-col id-col-2" style="text-align:center;">Auto Student ID</th>
                                        <th class="id-col id-col-3" style="text-align:center;">Student Full Name</th>
                                        @foreach ($compulsorySubjects as $subject)
                                            <th class="subject-col subject-compulsory compulsory-col">
                                                {{ $subject->md_name }}
                                                <span class="compulsory-tag">{{ $subject->md_misc1 }}</span>
                                            </th>
                                        @endforeach
                                        @if ($category === 'UACE')
                                            <th class="subject-col combo-col">
                                                Combination
                                                <span class="compulsory-tag">Optional (principal subjects)</span>
                                            </th>
                                        @else
                                            @foreach ($optionalSubjects as $subject)
                                                <th class="subject-col">
                                                    {{ $subject->md_name }}
                                                    <span class="compulsory-tag">{{ $subject->md_misc1 }}</span>
                                                </th>
                                            @endforeach
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($students as $index => $student)
                                        @php
                                            $studentId = $student->Student_ID;
                                            $registeredIds = $registrations->get($studentId, collect());
                                        @endphp
                                        <tr data-search="{{ strtolower($studentId . ' ' . ($names[$studentId] ?? '')) }}">
                                            <td class="id-col id-col-1">{{ $index + 1 }}</td>
                                            <td class="id-col id-col-2">{{ $studentId }}</td>
                                            <td class="id-col id-col-3">{{ $names[$studentId] ?? '—' }}</td>
                                            @foreach ($compulsorySubjects as $subject)
                                                <td class="compulsory-col">
                                                    <input type="checkbox" class="subject-check"
                                                        data-student="{{ $studentId }}"
                                                        data-subject="{{ $subject->md_id }}"
                                                        checked disabled>
                                                </td>
                                            @endforeach
                                            @if ($category === 'UACE')
                                                @php
                                                    $currentComboId = $studentCombinations[$studentId] ?? null;
                                                    $currentCombo = $currentComboId ? $combinationsById->get($currentComboId) : null;
                                                    $currentComboText = $currentCombo
                                                        ? ($currentCombo->code . ' — ' . $currentCombo->name)
                                                        : '';
                                                @endphp
                                                <td class="combo-col">
                                                    <div class="combo-picker" data-student="{{ $studentId }}">
                                                        <input type="text"
                                                            class="combo-picker-input"
                                                            autocomplete="off"
                                                            placeholder="Search combination..."
                                                            value="{{ $currentComboText }}"
                                                            data-selected-id="{{ $currentComboId }}">
                                                        <button type="button" class="combo-picker-clear"
                                                            title="Clear"
                                                            style="{{ $currentComboId ? '' : 'display:none;' }}">&times;</button>
                                                    </div>
                                                </td>
                                            @else
                                                @foreach ($optionalSubjects as $subject)
                                                    <td>
                                                        <input type="checkbox" class="subject-check"
                                                            data-student="{{ $studentId }}"
                                                            data-subject="{{ $subject->md_id }}"
                                                            {{ $registeredIds->has($subject->md_id) ? 'checked' : '' }}>
                                                    </td>
                                                @endforeach
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div id="srNoResults" class="alert alert-warning text-center mt-3">
                                <i class="fa fa-circle-exclamation me-2"></i> No students match your search.
                            </div>
                        </div>
                    @else
                        <div class="alert alert-danger text-center">
                            <i class="fa fa-exclamation-triangle me-2"></i> No students found for this school/year/category.
                            Make sure students have been registered first.
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- Import Modal --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('subject.registration.import') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="category" value="{{ $category }}">
                    <input type="hidden" name="school_number" value="{{ $schoolNumber }}">

                    <div class="modal-header text-white" style="background-color:#043AA1;">
                        <h5 class="modal-title"><i class="fa fa-upload me-2"></i> Import Subject Registrations</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">
                            @if ($category === 'UACE')
                                Upload the same Excel file you downloaded. It has one <strong>Combination</strong>
                                column — enter each student's combination code (e.g. <strong>PCM</strong>) from the
                                dropdown list in the sheet. Compulsory subjects like General Paper aren't on the
                                sheet; they're registered automatically for every student.
                            @else
                                Upload the same Excel file you downloaded. It only lists <strong>optional</strong>
                                subjects — mark each one <strong>YES</strong> or <strong>NO</strong> from the
                                dropdown for every student. Compulsory subjects aren't on the sheet at all; they're
                                registered automatically for every student.
                            @endif
                        </p>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn text-white" style="background-color:#043AA1;">
                            <i class="fa fa-check me-1"></i> Import
                        </button>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>


    <script>
        $(document).ready(function () {
            $(document).on('change', '.subject-check', function () {
                const checkbox = $(this);
                const checked = checkbox.is(':checked');

                $.ajax({
                    url: '{{ route('subject.registration.toggle') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        student_id: checkbox.data('student'),
                        subject_id: checkbox.data('subject'),
                        year: '{{ $year }}',
                        category: '{{ $category }}',
                        checked: checked ? 1 : 0,
                    },
                    error: function (xhr) {
                        checkbox.prop('checked', !checked); // revert on failure
                        Swal.fire({
                            icon: 'error',
                            title: 'Could not update',
                            text: xhr.responseJSON?.message || 'Please try again.',
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            });

            // Searchable combination picker — plain JS, no jQuery plugin
            // involved, so it can't run into jQuery-version conflicts.
            // There can be a lot of combinations, so this lets the user
            // type the code or name (e.g. "PCM" or "Physics") to filter
            // instead of scrolling a long list. One dropdown panel is
            // built once and reused for every row, positioned under
            // whichever input is currently focused.
            (function () {
                const dataEl = document.getElementById('srCombinationsData');
                const combinations = dataEl ? JSON.parse(dataEl.textContent || '[]') : [];
                if (combinations.length === 0 && !dataEl) return; // not a UACE page

                const portal = document.createElement('div');
                portal.id = 'comboPickerPortal';
                document.body.appendChild(portal);

                let activeInput = null;

                function closePortal() {
                    portal.style.display = 'none';
                    portal.innerHTML = '';
                    activeInput = null;
                }

                function positionPortal(input) {
                    const rect = input.getBoundingClientRect();
                    portal.style.left = rect.left + 'px';
                    portal.style.top = (rect.bottom + 4) + 'px';
                    portal.style.width = Math.max(rect.width, 220) + 'px';
                }

                function renderOptions(input) {
                    const term = input.value.trim().toLowerCase();
                    const matches = term === ''
                        ? combinations.slice(0, 50)
                        : combinations.filter(c => c.text.toLowerCase().indexOf(term) > -1).slice(0, 50);

                    portal.innerHTML = '';

                    if (matches.length === 0) {
                        const empty = document.createElement('div');
                        empty.className = 'combo-picker-empty';
                        empty.textContent = 'No matching combinations.';
                        portal.appendChild(empty);
                        return;
                    }

                    matches.forEach(function (combo) {
                        const opt = document.createElement('div');
                        opt.className = 'combo-picker-option';
                        opt.textContent = combo.text;
                        // mousedown (not click) fires before the input's blur
                        // event, so we can act on the selection before the
                        // blur handler snaps the text back / closes things.
                        opt.addEventListener('mousedown', function (e) {
                            e.preventDefault();
                            selectCombo(input, combo);
                        });
                        portal.appendChild(opt);
                    });
                }

                function openPortal(input) {
                    activeInput = input;
                    positionPortal(input);
                    renderOptions(input);
                    portal.style.display = 'block';
                }

                function toggleClearButton(input) {
                    const wrap = input.closest('.combo-picker');
                    const btn = wrap ? wrap.querySelector('.combo-picker-clear') : null;
                    if (btn) btn.style.display = input.dataset.selectedId ? '' : 'none';
                }

                function selectCombo(input, combo) {
                    const previousId = input.dataset.savedId ?? '';
                    const previousText = input.dataset.savedText ?? '';

                    input.value = combo.text;
                    input.dataset.selectedId = combo.id;
                    // Set these immediately (optimistic update). The input
                    // loses focus right after this runs, which fires the
                    // focusout handler a moment later — if savedText/savedId
                    // weren't already updated here, that handler would see
                    // the old (blank) saved value and snap the box back to
                    // it before the AJAX call below even finishes.
                    input.dataset.savedId = combo.id;
                    input.dataset.savedText = combo.text;
                    toggleClearButton(input);
                    closePortal();
                    saveCombination(input, combo.id, previousId, previousText);
                }

                function clearCombo(input) {
                    const previousId = input.dataset.savedId ?? '';
                    const previousText = input.dataset.savedText ?? '';

                    input.value = '';
                    input.dataset.selectedId = '';
                    input.dataset.savedId = '';
                    input.dataset.savedText = '';
                    toggleClearButton(input);
                    saveCombination(input, '', previousId, previousText);
                }

                function saveCombination(input, combinationId, previousId, previousText) {
                    const wrap = input.closest('.combo-picker');
                    const studentId = wrap.dataset.student;

                    // A CSS class only (not the `disabled` property) — setting
                    // an input's `disabled` property while it has focus forces
                    // an immediate browser blur, which was the actual cause of
                    // the "reverts to blank" bug.
                    input.classList.add('combo-picker-saving');

                    $.ajax({
                        url: '{{ route('subject.registration.set.combination') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            student_id: studentId,
                            combination_id: combinationId,
                            year: '{{ $year }}',
                            category: '{{ $category }}',
                            school_number: '{{ $schoolNumber }}',
                        },
                        error: function (xhr) {
                            // The optimistic update above was wrong — put
                            // everything back the way it was.
                            input.value = previousText;
                            input.dataset.selectedId = previousId;
                            input.dataset.savedId = previousId;
                            input.dataset.savedText = previousText;
                            toggleClearButton(input);
                            Swal.fire({
                                icon: 'error',
                                title: 'Could not update combination',
                                text: xhr.responseJSON?.message || 'Please try again.',
                                confirmButtonColor: '#d33'
                            });
                        },
                        complete: function () {
                            input.classList.remove('combo-picker-saving');
                        }
                    });
                }

                document.querySelectorAll('.combo-picker-input').forEach(function (input) {
                    input.dataset.savedId = input.dataset.selectedId || '';
                    input.dataset.savedText = input.value || '';
                });

                document.addEventListener('input', function (e) {
                    if (!e.target.classList.contains('combo-picker-input')) return;
                    openPortal(e.target);
                });

                document.addEventListener('focusin', function (e) {
                    if (!e.target.classList.contains('combo-picker-input')) return;
                    openPortal(e.target);
                });

                document.addEventListener('focusout', function (e) {
                    if (!e.target.classList.contains('combo-picker-input')) return;
                    const input = e.target;
                    // Delay so a mousedown on a dropdown option (above) has
                    // already run before we decide whether to snap the text
                    // back to the last saved selection.
                    setTimeout(function () {
                        if (activeInput === input) closePortal();
                        const savedText = input.dataset.savedText || '';
                        if (input.value !== savedText) {
                            input.value = savedText;
                        }
                    }, 150);
                });

                document.addEventListener('click', function (e) {
                    if (!e.target.classList.contains('combo-picker-clear')) return;
                    const input = e.target.closest('.combo-picker').querySelector('.combo-picker-input');
                    clearCombo(input);
                });

                window.addEventListener('scroll', function () {
                    if (activeInput) closePortal();
                }, true);

                window.addEventListener('resize', function () {
                    if (activeInput) closePortal();
                });
            })();

            document.querySelector('#importModal form').addEventListener('submit', function () {
                Swal.fire({
                    title: 'Importing…',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading()
                });
            });

            // Live search: filter table rows by student name or ID
            $('#srStudentSearchInput').on('keyup', function () {
                const value = $(this).val().toLowerCase().trim();
                let visibleCount = 0;

                $('#srTable tbody tr').each(function () {
                    const haystack = $(this).data('search') || '';
                    const isMatch = String(haystack).indexOf(value) > -1;
                    $(this).toggle(isMatch);
                    if (isMatch) visibleCount++;
                });

                $('#srNoResults').toggle(visibleCount === 0);
            });


            // Live search: filter table rows by student name or ID
const srSearchInput = document.getElementById('srStudentSearchInput');
const srSearchClear = document.getElementById('srSearchClear');
const srSearchCount = document.getElementById('srSearchCount');
const srTotalRows = document.querySelectorAll('#srTable tbody tr').length;

function srUpdateCount(visibleCount, term) {
    if (!srSearchCount) return;
    srSearchCount.textContent = term === '' ? '' : `${visibleCount} of ${srTotalRows} match${srTotalRows === 1 ? '' : 'es'}`;
}

if (srSearchInput) {
    srSearchInput.addEventListener('keyup', function () {
        const value = this.value.toLowerCase().trim();
        let visibleCount = 0;

        document.querySelectorAll('#srTable tbody tr').forEach(function (row) {
            const haystack = row.dataset.search || '';
            const isMatch = haystack.indexOf(value) > -1;
            row.style.display = isMatch ? '' : 'none';
            if (isMatch) visibleCount++;
        });

        document.getElementById('srNoResults').style.display = visibleCount === 0 ? 'block' : 'none';
        srSearchClear.style.display = value === '' ? 'none' : 'flex';
        srUpdateCount(visibleCount, value);
    });
}

if (srSearchClear) {
    srSearchClear.addEventListener('click', function () {
        srSearchInput.value = '';
        srSearchInput.dispatchEvent(new Event('keyup'));
        srSearchInput.focus();
    });
}

// Show/Hide compulsory subject columns (hidden by default)
const srToggleBtn = document.getElementById('srToggleCompulsoryBtn');
if (srToggleBtn) {
    srToggleBtn.addEventListener('click', function () {
        const srTable = document.getElementById('srTable');
        const nowHidden = srTable.classList.toggle('hide-compulsory');
        const label = document.getElementById('srToggleCompulsoryLabel');
        const icon = srToggleBtn.querySelector('i');

        label.textContent = nowHidden ? 'Show Compulsory Subjects' : 'Hide Compulsory Subjects';
        icon.className = nowHidden ? 'fa fa-eye me-2' : 'fa fa-eye-slash me-2';
    });
}
        });
    </script>
@endsection