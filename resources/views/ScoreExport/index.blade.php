@extends('layouts-side-bar.master')

@section('css')
<style>
    /* ══════════ SCAN & EXPORT PAGE — same visual language as the
       "Scan & Auto-Fill Marks" modal on the Enter Marks screen
       (resources/views/itemGrading/results.blade.php) ══════════ */

    .scan-export-header {
        background-color: #043AA1;
        color: #fff;
        border-radius: 14px 14px 0 0;
    }

    .scan-dropzone {
        border: 2.5px dashed #0059ff;
        border-radius: 14px;
        background: #f2f7ff;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all .2s ease;
    }

    .scan-dropzone:hover,
    .scan-dropzone.drag-over {
        background: #e6f0ff;
        border-color: #043AA1;
    }

    .scan-dropzone i {
        font-size: 2.4rem;
        color: #0059ff;
    }

    .scan-type-toggle .btn.active {
        background-color: #0059ff;
        color: #fff;
        border-color: #0059ff;
    }

    #scanReviewTable {
        font-size: .95rem;
        background: #ffffff;
    }

    #scanReviewTable th {
        background-color: #043AA1;
        color: #ffffff;
        font-weight: 700;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    #scanReviewTable td {
        vertical-align: middle;
        color: #111827;
    }

    #scanReviewTable input {
        border: 1px solid #dde3ee;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: .88rem;
        width: 100%;
    }

    #scanReviewTable input:focus {
        outline: none;
        border-color: #0059ff;
        box-shadow: 0 0 0 2px rgba(0, 89, 255, .12);
    }

    #scanReviewTable tbody tr:nth-child(odd) {
        background-color: #fbfdfc;
    }

    .steps {
        display: flex;
        align-items: center;
        margin-bottom: 24px;
    }

    .steps .step {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #9ca3af;
        font-weight: 600;
        font-size: .85rem;
    }

    .steps .step.active,
    .steps .step.done {
        color: #043AA1;
    }

    .steps .step-num {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: #e5e7eb;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .8rem;
        font-weight: 700;
    }

    .steps .step.active .step-num,
    .steps .step.done .step-num {
        background: #043AA1;
        color: #fff;
    }

    .steps .step-line {
        flex: 1;
        height: 2px;
        background: #e5e7eb;
        margin: 0 12px;
    }

    .steps .step-line.done {
        background: #043AA1;
    }

    .del-row-btn {
        border: none;
        background: transparent;
        color: #dc3545;
    }

    .recent-import-row:hover {
        background-color: #f2f7ff;
    }
</style>
@endsection

@section('content')
<div class="side-app">
    <div class="container-fluid mt-4">

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header scan-export-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa fa-file-export me-2"></i> Scan &amp; Export Score Sheet</h5>
            </div>
            <div class="card-body p-3">

                {{-- STEP INDICATOR --}}
                <div class="steps">
                    <div class="step active" id="step1">
                        <div class="step-num">1</div><span>Upload File</span>
                    </div>
                    <div class="step-line" id="line1"></div>
                    <div class="step" id="step2">
                        <div class="step-num">2</div><span>Review &amp; Edit</span>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════════════════
                     STEP 1 — UPLOAD
                     ══════════════════════════════════════════════════════ --}}
                <div id="uploadSection">
                    <div class="row g-4">

                        <div class="col-lg-7">
                            <p class="text-muted mb-3" style="font-size:.88rem">
                                Upload a photo, scan, or PDF of a printed score sheet. Tesseract OCR runs first, for
                                free — if it can't confidently read a handwritten/low-quality sheet, it automatically
                                falls back to AI vision.
                            </p>

                            <div class="scan-type-toggle d-flex mb-3" style="gap: 10px;">
                                <!-- <button type="button" class="btn btn-outline-primary btn-sm"
                                    style="padding: 8px 16px;" onclick="setScanType('pdf')" id="scanTypePdfLabel">
                                    <i class="fa fa-file-pdf-o me-1"></i> Softcopy PDF
                                </button> -->
                                <button type="button" class="btn btn-outline-primary btn-sm active"
                                    style="padding: 8px 16px;" onclick="setScanType('image')" id="scanTypeImageLabel">
                                    <i class="fa fa-camera me-1"></i> Hardcopy Photo / Scan
                                </button>
                            </div>

                            <div class="scan-dropzone" id="scanDropZone"
                                onclick="document.getElementById('scanFileInput').click()">
                                <input type="file" id="scanFileInput" accept=".jpg,.jpeg,.png,.webp" style="display:none">
                                <i class="fa fa-cloud-upload d-block mb-2"></i>
                                <div class="fw-semibold" id="scanDropTitle">Click or drag &amp; drop your file here</div>
                                <div class="text-muted" style="font-size:.8rem">PDF, JPG, PNG, WEBP &nbsp;·&nbsp; Max 20&nbsp;MB</div>
                            </div>

                            <div id="scanFilePreview" class="mt-3 d-none">
                                <div class="d-flex align-items-center gap-3 p-2 bg-light rounded border">
                                    <i class="fa fa-file-text-o fs-4 text-primary"></i>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="fw-semibold text-truncate" id="scanFileName" style="font-size:.85rem"></div>
                                        <div class="text-muted" id="scanFileSize" style="font-size:.75rem"></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearScanFile()">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <div id="scanCameraSection" class="mt-3">
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="openScanCamera()">
                                    <i class="fa fa-camera me-1"></i> Or take a photo with your camera
                                </button>
                                <video id="scanCameraVideo" class="w-100 rounded d-none mt-2" autoplay playsinline></video>
                                <button type="button" class="btn btn-success btn-sm mt-2 w-100 d-none" id="scanCaptureBtn"
                                    onclick="captureScanPhoto()">
                                    <i class="fa fa-circle me-1"></i> Capture Photo
                                </button>
                                <canvas id="scanCameraCanvas" style="display:none"></canvas>
                            </div>

                            <button type="button" class="btn text-white w-100 mt-4" style="background-color:#0059ff"
                                id="scanRunBtn" onclick="runScoreScan()" disabled>
                                <i class="fa fa-search me-2"></i> Run OCR &amp; Extract Data
                            </button>
                        </div>

                        {{-- Recent imports --}}
                        <div class="col-lg-5">
                            <div class="card mb-0">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><i class="fa fa-history me-2"></i> Recent Imports</span>
                                </div>
                                <div class="card-body p-0">
                                    @forelse($recentSheets as $sheet)
                                        <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom recent-import-row">
                                            <i class="fa fa-file-text-o text-muted"></i> &nbsp; &nbsp;
                                            <div class="flex-grow-1 overflow-hidden">
                                                <div class="text-truncate fw-medium" style="font-size:.82rem">
                                                    {{ $sheet->school_name ?? 'Unknown School' }}
                                                </div>
                                                <div class="text-muted" style="font-size:.72rem">
                                                    {{ $sheet->subject ?? 'No subject' }} · {{ $sheet->entries_count }} entries
                                                </div>
                                            </div>
                                           <a href="{{ route('score.export.download.saved', $sheet->id) }}"
                                                class="btn btn-sm btn-outline-success"
                                                title="Download Excel">
                                                <i class="fa fa-file-excel-o"></i>
                                            </a>

                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                style="margin-left:12px;"
                                                onclick="deleteSavedSheet({{ $sheet->id }}, this)"
                                                title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    @empty
                                        <div class="text-center text-muted py-4" style="font-size:.85rem">
                                            No imports yet — scanned score sheets you save will show up here.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════════════════
                     STEP 2 — REVIEW & EDIT
                     ══════════════════════════════════════════════════════ --}}
                <div id="reviewSection" class="d-none">

                    <div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-2">
                        <div>
                            <h6 class="mb-0 fw-semibold">Review Extracted Data</h6>
                            <p class="text-muted mb-0" style="font-size:.82rem">
                                OCR has read your document. Edit any field directly in the table before saving or exporting.
                            </p>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="backToScanUpload()">
                            <i class="fa fa-arrow-left me-1"></i> Scan Another
                        </button>
                    </div>

                    <div id="scanReviewNotice" class="alert alert-warning d-none mb-3" style="font-size:.85rem"></div>

                    <div class="card mb-3">
                        <div class="card-header"><i class="fa fa-info-circle me-2"></i> Sheet Information</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" style="font-size:.8rem">School Name</label>
                                    <input class="form-control" id="meta_school_name" placeholder="School name">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" style="font-size:.8rem">Zone</label>
                                    <input class="form-control" id="meta_zone" placeholder="Zone">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" style="font-size:.8rem">REF No.</label>
                                    <input class="form-control" id="meta_ref_no" placeholder="Ref number">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" style="font-size:.8rem">Subject</label>
                                    <input class="form-control" id="meta_subject" placeholder="Subject">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" style="font-size:.8rem">Exam Year</label>
                                    <input class="form-control" id="meta_exam_year" placeholder="e.g. 2026">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span>
                                <i class="fa fa-table me-2"></i> Total Candidates
                                <span class="badge bg-primary ms-1 text-white" id="rowCountBadge">0</span>
                            </span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addScanRow()">
                                    <i class="fa fa-plus me-1"></i> Add Row
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 55vh;">
                                <table class="table table-bordered mb-0" id="scanReviewTable">
                                    <thead>
                                        <tr>
                                            <th style="width:36px">#</th>
                                            <th style="min-width:220px">Candidate Name</th>
                                            <th style="width:90px">P1</th>
                                            <th style="width:90px">P2</th>
                                            <th style="width:90px">P3</th>
                                            <th style="width:90px">P4</th>
                                            <th style="width:100px">Average</th>
                                            <th style="width:90px">Grade</th>
                                            <th style="width:40px"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="scanReviewTbody"></tbody>
                                </table>
                            </div>
                        </div>
                       <div class="card-footer bg-transparent d-flex justify-content-end py-3">
    <button type="button"
        class="btn btn-outline-success px-3"
        id="exportExcelBtn"
        onclick="exportScanToExcel()">
        <i class="fa fa-file-excel-o me-2"></i> Export to Excel
    </button>

    <button type="button"
        class="btn text-white px-4"
        style="background-color:#043AA1; margin-left:12px;"
        id="saveScanBtn"
        onclick="saveScanData()">
        <i class="fa fa-cloud-upload me-2"></i> Save (Import)
    </button>
</div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</div>
    </div>
</div>
@endsection

@section('js')
<script>
    // ══════════════════════════════════════════════════════════════
    // SCAN & EXPORT — standalone page version of the "Scan & Auto-Fill
    // Marks" modal logic used in itemGrading/results.blade.php, minus
    // the roster/fuzzy-match step (there's no marks-entry roster to
    // match against here — the whole point of this page is a raw
    // import/export of whatever the sheet says).
    // ══════════════════════════════════════════════════════════════
    let scanSelectedFile = null;
    let scanSelectedType = 'image';
    let scanCameraStream = null;
    const scanCsrfToken = $('meta[name="csrf-token"]').attr('content');

    // ── SCAN TYPE TOGGLE ────────────────────────────────────────
    window.setScanType = function (type) {
        scanSelectedType = type;
        $('#scanTypePdfLabel').toggleClass('active', type === 'pdf');
        $('#scanTypeImageLabel').toggleClass('active', type === 'image');
        $('#scanFileInput').attr('accept', type === 'pdf' ? '.pdf' : '.jpg,.jpeg,.png,.webp');
        $('#scanCameraSection').toggleClass('d-none', type === 'pdf');
        $('#scanDropTitle').text('Click or drag & drop your file here');
        clearScanFile();
    };

    // ── DRAG & DROP / FILE PICK ─────────────────────────────────
    const scanDropZoneEl = document.getElementById('scanDropZone');
    scanDropZoneEl.addEventListener('dragover', e => { e.preventDefault(); scanDropZoneEl.classList.add('drag-over'); });
    scanDropZoneEl.addEventListener('dragleave', () => scanDropZoneEl.classList.remove('drag-over'));
    scanDropZoneEl.addEventListener('drop', e => {
        e.preventDefault();
        scanDropZoneEl.classList.remove('drag-over');
        if (e.dataTransfer.files[0]) handleScanFile(e.dataTransfer.files[0]);
    });
    document.getElementById('scanFileInput').addEventListener('change', e => {
        if (e.target.files[0]) handleScanFile(e.target.files[0]);
    });

    function handleScanFile(file) {
        scanSelectedFile = file;
        $('#scanFileName').text(file.name);
        $('#scanFileSize').text((file.size / 1024 / 1024).toFixed(2) + ' MB');
        $('#scanFilePreview').removeClass('d-none');
        $('#scanRunBtn').prop('disabled', false);
        $('#scanDropTitle').text('File selected ✓');
    }

    window.clearScanFile = function () {
        scanSelectedFile = null;
        $('#scanFileInput').val('');
        $('#scanFilePreview').addClass('d-none');
        $('#scanRunBtn').prop('disabled', true);
        $('#scanDropTitle').text('Click or drag & drop your file here');
    };

    // ── CAMERA ───────────────────────────────────────────────────
    window.openScanCamera = async function () {
        try {
            scanCameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            const video = document.getElementById('scanCameraVideo');
            video.srcObject = scanCameraStream;
            video.classList.remove('d-none');
            document.getElementById('scanCaptureBtn').classList.remove('d-none');
        } catch (err) {
            Swal.fire('Camera Error', err.message, 'error');
        }
    };

    window.captureScanPhoto = function () {
        const video = document.getElementById('scanCameraVideo');
        const canvas = document.getElementById('scanCameraCanvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        canvas.toBlob(blob => {
            const file = new File([blob], 'camera_capture.jpg', { type: 'image/jpeg' });
            handleScanFile(file);
            if (scanCameraStream) scanCameraStream.getTracks().forEach(t => t.stop());
            document.getElementById('scanCameraVideo').classList.add('d-none');
            document.getElementById('scanCaptureBtn').classList.add('d-none');
        }, 'image/jpeg', 0.95);
    };

    // ── RUN SCAN — same endpoint as the Enter Marks "Scan & Auto-Fill"
    // modal (App\Http\Controllers\Scorescancontroller@scan) ────────
    window.runScoreScan = async function () {
        if (!scanSelectedFile) return;

        Swal.fire({
            title: 'Reading document…',
            html: 'Running OCR — this can take 10–20 seconds',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const formData = new FormData();
        formData.append('file', scanSelectedFile);
        formData.append('scan_type', scanSelectedType);

        try {
            const res = await fetch('{{ route("iteb.scan.score.sheet") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': scanCsrfToken, 'Accept': 'application/json' },
                body: formData,
            });
            const result = await res.json();
            Swal.close();

            if (!result.success) {
                Swal.fire('Extraction Failed', result.message || 'Unknown error', 'error');
                return;
            }

            populateScanReview(result.data, scanSelectedFile.name);
            showScanReviewSection();
        } catch (err) {
            Swal.close();
            Swal.fire('Network Error', err.message, 'error');
        }
    };

    let scanSourceFile = '';

    function populateScanReview(data, sourceFileName) {
        scanSourceFile = sourceFileName || '';
        const meta = data.sheet_meta || {};
        $('#meta_school_name').val(meta.school_name || '');
        $('#meta_zone').val(meta.zone || '');
        $('#meta_ref_no').val(meta.ref_no || '');
        $('#meta_subject').val(meta.subject || '');
        $('#meta_exam_year').val(meta.exam_year || '');

        const tbody = $('#scanReviewTbody').empty();
        (data.entries || []).forEach(entry => appendScanRow(entry));
        updateScanRowCount();

        const notice = $('#scanReviewNotice');
        if (data.notice) {
            notice.html('<i class="fa fa-exclamation-triangle me-2"></i>' + scanEscapeHtml(data.notice)).removeClass('d-none');
        } else {
            notice.addClass('d-none');
        }
    }

    function appendScanRow(entry = {}) {
        const tbody = document.getElementById('scanReviewTbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center text-muted row-num" style="font-size:.8rem"></td>
            <td><input type="text" class="name-input" value="${scanEscapeAttr(entry.candidate_name || '')}" placeholder="Candidate name"></td>
            <td><input type="number" step="0.01" class="score-input" value="${entry.p1 ?? ''}" placeholder="–"></td>
            <td><input type="number" step="0.01" class="score-input" value="${entry.p2 ?? ''}" placeholder="–"></td>
            <td><input type="number" step="0.01" class="score-input" value="${entry.p3 ?? ''}" placeholder="–"></td>
            <td><input type="number" step="0.01" class="score-input" value="${entry.p4 ?? ''}" placeholder="–"></td>
            <td><input type="number" step="0.01" class="avg-input" value="${entry.average ?? ''}" placeholder="–"></td>
            <td><input type="text" class="grade-input" value="${scanEscapeAttr(entry.grade || '')}" placeholder="–"></td>
            <td class="text-center">
                <button type="button" class="del-row-btn" onclick="deleteScanRow(this)" title="Remove">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        renumberScanRows();
    }

    window.addScanRow = function () {
        appendScanRow({});
        document.getElementById('scanReviewTbody').lastElementChild?.querySelector('input')?.focus();
    };

    window.deleteScanRow = function (btn) {
        btn.closest('tr').remove();
        renumberScanRows();
        updateScanRowCount();
    };

    function renumberScanRows() {
        document.querySelectorAll('#scanReviewTbody tr').forEach((tr, i) => {
            tr.querySelector('.row-num').textContent = i + 1;
        });
    }

    function updateScanRowCount() {
        $('#rowCountBadge').text($('#scanReviewTbody tr').length);
    }

    function collectScanTableData() {
        return Array.from(document.querySelectorAll('#scanReviewTbody tr')).map((tr, i) => ({
            serial_no: i + 1,
            candidate_name: tr.querySelector('.name-input').value.trim(),
            p1: tr.querySelector('.score-input:nth-of-type(1)') ? parseScanNum(tr.querySelectorAll('.score-input')[0].value) : null,
            p2: parseScanNum(tr.querySelectorAll('.score-input')[1]?.value),
            p3: parseScanNum(tr.querySelectorAll('.score-input')[2]?.value),
            p4: parseScanNum(tr.querySelectorAll('.score-input')[3]?.value),
            average: parseScanNum(tr.querySelector('.avg-input')?.value),
            grade: tr.querySelector('.grade-input').value.trim() || null,
        }));
    }

    function parseScanNum(val) {
        return val !== undefined && val !== null && val !== '' ? parseFloat(val) : null;
    }

    // ── SAVE (IMPORT) ────────────────────────────────────────────
    window.saveScanData = async function () {
        const entries = collectScanTableData().filter(e => e.candidate_name);
        if (!entries.length) {
            Swal.fire('Empty', 'Add at least one candidate row before saving.', 'warning');
            return;
        }

        const confirm = await Swal.fire({
            title: 'Save Score Sheet?',
            html: `Import <strong>${entries.length} candidate record(s)</strong> into the database?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Save',
            confirmButtonColor: '#043AA1',
            cancelButtonText: 'Review Again',
        });
        if (!confirm.isConfirmed) return;

        Swal.fire({ title: 'Saving…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const res = await fetch('{{ route("score.export.save") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': scanCsrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    school_name: $('#meta_school_name').val(),
                    zone: $('#meta_zone').val(),
                    ref_no: $('#meta_ref_no').val(),
                    subject: $('#meta_subject').val(),
                    exam_year: $('#meta_exam_year').val(),
                    source_file: scanSourceFile,
                    scan_type: scanSelectedType,
                    entries,
                }),
            });
            const result = await res.json();
            Swal.close();

            if (result.success) {
                const action = await Swal.fire({
                    title: '✅ Saved Successfully!',
                    html: `<strong>${result.saved_rows}</strong> records saved.`,
                    icon: 'success',
                    confirmButtonText: 'Refresh List',
                    showCancelButton: true,
                    cancelButtonText: 'Keep Editing',
                    confirmButtonColor: '#057a55',
                });
                if (action.isConfirmed) window.location.reload();
            } else {
                Swal.fire('Save Failed', result.message, 'error');
            }
        } catch (err) {
            Swal.close();
            Swal.fire('Error', err.message, 'error');
        }
    };

    // ── EXPORT TO EXCEL ─────────────────────────────────────────
    window.exportScanToExcel = async function () {
        const entries = collectScanTableData().filter(e => e.candidate_name);
        if (!entries.length) {
            Swal.fire('Empty', 'Add at least one candidate row before exporting.', 'warning');
            return;
        }

        Swal.fire({ title: 'Building Excel file…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const res = await fetch('{{ route("score.export.download") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': scanCsrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    school_name: $('#meta_school_name').val(),
                    subject: $('#meta_subject').val(),
                    entries,
                }),
            });
            Swal.close();

            if (!res.ok) {
                const result = await res.json().catch(() => ({}));
                Swal.fire('Export Failed', result.message || 'Could not generate the Excel file.', 'error');
                return;
            }

            const blob = await res.blob();
            const disposition = res.headers.get('Content-Disposition') || '';
            const match = disposition.match(/filename="?([^"]+)"?/);
            const filename = match ? match[1] : 'score_sheet.xlsx';

            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
        } catch (err) {
            Swal.close();
            Swal.fire('Error', err.message, 'error');
        }
    };

    // ── DELETE A SAVED RECORD FROM "RECENT IMPORTS" ──────────────
    window.deleteSavedSheet = async function (id, btn) {
        const confirm = await Swal.fire({
            title: 'Delete this record?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#dc3545',
        });
        if (!confirm.isConfirmed) return;

        try {
            const res = await fetch(`{{ url('score-sheet-export') }}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': scanCsrfToken, 'Accept': 'application/json' },
            });
            const result = await res.json();
            if (result.success) {
                $(btn).closest('.recent-import-row').fadeOut(200, function () { $(this).remove(); });
            } else {
                Swal.fire('Error', result.message || 'Could not delete.', 'error');
            }
        } catch (err) {
            Swal.fire('Error', err.message, 'error');
        }
    };

    // ── UI TRANSITIONS ───────────────────────────────────────────
    function showScanReviewSection() {
        $('#uploadSection').addClass('d-none');
        $('#reviewSection').removeClass('d-none');
        setScanStep(2);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    window.backToScanUpload = function () {
        $('#reviewSection').addClass('d-none');
        $('#uploadSection').removeClass('d-none');
        clearScanFile();
        setScanStep(1);
    };

    function setScanStep(n) {
        [1, 2].forEach(i => {
            const el = document.getElementById('step' + i);
            el.classList.remove('active', 'done');
            if (i < n) el.classList.add('done');
            if (i === n) el.classList.add('active');
        });
        document.getElementById('line1').classList.toggle('done', n > 1);
    }

    // ── UTILS ─────────────────────────────────────────────────────
    function scanEscapeHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function scanEscapeAttr(str) {
        return scanEscapeHtml(str).replace(/"/g, '&quot;');
    }
</script>
@endsection