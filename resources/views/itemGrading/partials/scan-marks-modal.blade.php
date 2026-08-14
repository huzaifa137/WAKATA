{{-- ═══════════════════════════════════════════════════════════════════════
     SCAN & AUTO-FILL MARKS
     Lets the user upload/photograph a printed or handwritten score sheet,
     runs it through ScoreScanController@scan (Tesseract OCR + Gemini vision
     fallback — ported from the wakata-scanner test project), fuzzy-matches
     each scanned name against the student roster for the subject currently
     open on the enter-marks screen, and drops the matched scores straight
     into that subject's marks table for review before saving.
     ═══════════════════════════════════════════════════════════════════════ --}}
<style>
    /* Bright, high-contrast styling so scanned names are easy to read/verify */
    #scanMarksModal .modal-content {
        border-radius: 14px;
        border: none;
    }

    #scanMarksModal .modal-header {
        background-color: #026837;
        color: #fff;
        border-radius: 14px 14px 0 0;
    }

    #scanMarksModal .modal-header .close,
    #scanMarksModal .modal-header .btn-close {
        color: #fff;
        opacity: .9;
        filter: none;
    }

    .scan-dropzone {
        border: 2.5px dashed #287C44;
        border-radius: 14px;
        background: #f6fbf8;
        padding: 30px 20px;
        text-align: center;
        cursor: pointer;
        transition: all .2s ease;
    }

    .scan-dropzone:hover,
    .scan-dropzone.drag-over {
        background: #eaf7ef;
        border-color: #1e5f33;
    }

    .scan-dropzone i {
        font-size: 2.2rem;
        color: #287C44;
    }

    .scan-type-toggle .btn {
        font-weight: 600;
    }

    /* Review table — deliberately high-contrast / large so handwriting
       mismatches jump out immediately */
    #scanReviewTable {
        font-size: 1rem;
        background: #ffffff;
    }

    #scanReviewTable th {
        background-color: #026837;
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
        font-weight: 500;
    }

    #scanReviewTable tbody tr:nth-child(odd) {
        background-color: #fbfdfc;
    }

    #scanReviewTable .ocr-name {
        font-weight: 700;
        font-size: 1.02rem;
        color: #0b3d24;
    }

    #scanReviewTable .score-pill {
        display: inline-block;
        background: #eef6f1;
        border: 1px solid #cfe8da;
        color: #0b3d24;
        border-radius: 8px;
        padding: 2px 8px;
        margin: 1px 3px 1px 0;
        font-weight: 700;
        font-size: .85rem;
    }

    .match-confidence {
        font-weight: 700;
        border-radius: 20px;
        padding: 4px 10px;
        font-size: .78rem;
        white-space: nowrap;
    }

    .match-confidence.high {
        background: #d8f5e1;
        color: #0b6b34;
    }

    .match-confidence.medium {
        background: #fff3cd;
        color: #7a5b00;
    }

    .match-confidence.low {
        background: #fde2e2;
        color: #9c1c1c;
    }

    .match-select {
        min-width: 240px;
        font-weight: 600;
        border: 1.5px solid #cfe0d6;
        border-radius: 8px;
    }

    .scan-review-wrap {
        max-height: 55vh;
        overflow-y: auto;
        border: 1px solid #e2e8e5;
        border-radius: 10px;
    }

    #scanCameraVideo {
        width: 100%;
        border-radius: 10px;
    }
</style>

<button type="button" class="btn btn-light btn-sm ms-2 fw-semibold" id="openScanMarksModalBtn"
        style="color:#026837;">
    <i class="fa fa-camera me-1"></i> Scan &amp; Auto-Fill Marks
</button>

<div class="modal fade" id="scanMarksModal" tabindex="-1" aria-labelledby="scanMarksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scanMarksModalLabel">
                    <i class="fa fa-camera me-2"></i> Scan &amp; Auto-Fill Marks
                    — <span id="scanTargetSubjectName">—</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                {{-- STEP 1: upload --}}
                <div id="scanUploadStep">
                    <p class="text-muted mb-3" style="font-size:.88rem">
                        Upload a photo, scan, or PDF of the printed score sheet for
                        <strong id="scanTargetSubjectNameInline">this subject</strong>.
                        Scanned names will be matched against the
                        <strong><span id="scanTargetStudentCount">0</span> student(s)</strong>
                        currently listed on this tab.
                    </p>

                    <div class="scan-type-toggle d-flex gap-2 mb-3">
                        <input type="radio" class="btn-check" name="scanMarksType" id="scanTypePdf" value="pdf" checked style="display:none">
                        <label class="btn btn-outline-success btn-sm px-3" for="scanTypePdf" onclick="setScanType('pdf')" id="scanTypePdfLabel">
                            <i class="fa fa-file-pdf-o me-1"></i> Softcopy PDF
                        </label>
                        <input type="radio" class="btn-check" name="scanMarksType" id="scanTypeImage" value="image" style="display:none">
                        <label class="btn btn-outline-success btn-sm px-3" for="scanTypeImage" onclick="setScanType('image')" id="scanTypeImageLabel">
                            <i class="fa fa-camera me-1"></i> Hardcopy Photo / Scan
                        </label>
                    </div>

                    <div class="scan-dropzone" id="scanDropZone" onclick="document.getElementById('scanFileInput').click()">
                        <input type="file" id="scanFileInput" accept=".pdf" style="display:none">
                        <i class="fa fa-cloud-upload d-block mb-2"></i>
                        <div class="fw-semibold" id="scanDropTitle">Click or drag &amp; drop your file here</div>
                        <div class="text-muted" style="font-size:.8rem">PDF, JPG, PNG, WEBP &nbsp;·&nbsp; Max 20&nbsp;MB</div>
                    </div>

                    <div id="scanFilePreview" class="mt-3 d-none">
                        <div class="d-flex align-items-center gap-3 p-2 bg-light rounded border">
                            <i class="fa fa-file-text-o fs-4 text-success"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="fw-semibold text-truncate" id="scanFileName" style="font-size:.85rem"></div>
                                <div class="text-muted" id="scanFileSize" style="font-size:.75rem"></div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearScanFile()">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div id="scanCameraSection" class="mt-3 d-none">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="openScanCamera()">
                            <i class="fa fa-camera me-1"></i> Or take a photo with your camera
                        </button>
                        <video id="scanCameraVideo" class="d-none mt-2" autoplay playsinline></video>
                        <button type="button" class="btn btn-success btn-sm mt-2 w-100 d-none" id="scanCaptureBtn" onclick="captureScanPhoto()">
                            <i class="fa fa-circle me-1"></i> Capture Photo
                        </button>
                        <canvas id="scanCameraCanvas" style="display:none"></canvas>
                    </div>

                    <div class="alert alert-success mt-3 py-2 px-3 mb-0" style="font-size:.8rem">
                        <i class="fa fa-shield me-1"></i>
                        Tesseract OCR runs first, for free. If it can't confidently read a
                        handwritten/low-quality sheet, it automatically falls back to AI
                        vision — only when a Gemini API key is configured on the server.
                    </div>
                </div>

                {{-- STEP 2: review + match --}}
                <div id="scanReviewStep" class="d-none">
                    <div id="scanReviewNotice" class="alert alert-warning d-none mb-3" style="font-size:.85rem"></div>

                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <div style="font-size:.85rem" class="text-muted">
                            <i class="fa fa-info-circle me-1"></i>
                            Review the matches below. Un-tick any row you don't want applied,
                            or use the dropdown to pick the correct student.
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="backToScanUpload()">
                            <i class="fa fa-arrow-left me-1"></i> Scan Another
                        </button>
                    </div>

                    <div class="scan-review-wrap">
                        <table class="table table-bordered mb-0" id="scanReviewTable">
                            <thead>
                                <tr>
                                    <th style="width:34px"></th>
                                    <th>Scanned Name</th>
                                    <th>Scanned Score(s)</th>
                                    <th>Matched Student</th>
                                    <th style="width:110px">Confidence</th>
                                </tr>
                            </thead>
                            <tbody id="scanReviewTbody"></tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <div id="scanUploadFooter">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn text-white" style="background-color:#287C44"
                            id="scanRunBtn" onclick="runScoreScan()" disabled>
                        <i class="fa fa-search me-1"></i> Run OCR &amp; Extract Data
                    </button>
                </div>
                <div id="scanReviewFooter" class="d-none w-100 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span style="font-size:.8rem" class="text-muted">
                        <span id="scanMatchedCount">0</span> row(s) will be applied
                    </span>
                    <div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn text-white" style="background-color:#287C44"
                                id="scanApplyBtn" onclick="applyScanMatches()">
                            <i class="fa fa-check-circle me-1"></i> Apply to Marks Table
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // This partial is included inside @section('content'), which renders
    // BEFORE jQuery is loaded (jQuery is pulled in later via
    // layouts-side-bar.footer-scripts). Waiting for DOMContentLoaded
    // guarantees jQuery/Bootstrap/SweetAlert2 have already loaded by the
    // time this code runs.
    document.addEventListener('DOMContentLoaded', function () {
    (function ($) {
        // ── STATE ────────────────────────────────────────────────────────
        let scanSelectedFile = null;
        let scanSelectedType = 'pdf';
        let scanCameraStream = null;
        let scanExtractedEntries = [];
        let scanCsrfToken = $('meta[name="csrf-token"]').attr('content');

        // ── OPEN MODAL (reads the currently active subject tab) ────────────
        $('#openScanMarksModalBtn').on('click', function () {
            const $activePane = $('.tab-pane.active');
            const subjectId = $activePane.data('subject-id');
            const subjectName = $activePane.find('.card-header h5').text().trim();
            const meta = (window.subjectMeta || {})[subjectId] || {studentIds: [], totalPapers: 1, name: subjectName};

            $('#scanTargetSubjectName').text(subjectName || '—');
            $('#scanTargetSubjectNameInline').text(subjectName || 'this subject');
            $('#scanTargetStudentCount').text((meta.studentIds || []).length);

            backToScanUpload();
            clearScanFile();
            $('#scanMarksModal').data('subject-id', subjectId).modal('show');
        });

        // ── SCAN TYPE TOGGLE ────────────────────────────────────────────
        window.setScanType = function (type) {
            scanSelectedType = type;
            $('#scanTypePdfLabel').toggleClass('active', type === 'pdf');
            $('#scanTypeImageLabel').toggleClass('active', type === 'image');
            $('#scanFileInput').attr('accept', type === 'pdf' ? '.pdf' : '.jpg,.jpeg,.png,.webp');
            $('#scanCameraSection').toggleClass('d-none', type === 'pdf');
            $('#scanDropTitle').text('Click or drag & drop your file here');
            clearScanFile();
        };

        // ── DRAG & DROP / FILE PICK ─────────────────────────────────────
        const dropZone = document.getElementById('scanDropZone');
        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
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

        // ── CAMERA ───────────────────────────────────────────────────────
        window.openScanCamera = async function () {
            try {
                scanCameraStream = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}});
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
                const file = new File([blob], 'camera_capture.jpg', {type: 'image/jpeg'});
                handleScanFile(file);
                if (scanCameraStream) scanCameraStream.getTracks().forEach(t => t.stop());
                document.getElementById('scanCameraVideo').classList.add('d-none');
                document.getElementById('scanCaptureBtn').classList.add('d-none');
            }, 'image/jpeg', 0.95);
        };

        // ── RUN SCAN ─────────────────────────────────────────────────────
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
                    headers: {'X-CSRF-TOKEN': scanCsrfToken, 'Accept': 'application/json'},
                    body: formData,
                });
                const result = await res.json();
                Swal.close();

                if (!result.success) {
                    Swal.fire('Extraction Failed', result.message || 'Unknown error', 'error');
                    return;
                }

                scanExtractedEntries = (result.data && result.data.entries) || [];
                showScanReview(result.data);
            } catch (err) {
                Swal.close();
                Swal.fire('Network Error', err.message, 'error');
            }
        };

        // ── FUZZY MATCH HELPERS ──────────────────────────────────────────
        function normalizeName(str) {
            return String(str || '')
                .toUpperCase()
                .replace(/[^A-Z\s]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function levenshtein(a, b) {
            const m = a.length, n = b.length;
            if (m === 0) return n;
            if (n === 0) return m;
            const d = Array.from({length: m + 1}, (_, i) => [i].concat(Array(n).fill(0)));
            for (let j = 0; j <= n; j++) d[0][j] = j;
            for (let i = 1; i <= m; i++) {
                for (let j = 1; j <= n; j++) {
                    const cost = a[i - 1] === b[j - 1] ? 0 : 1;
                    d[i][j] = Math.min(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + cost);
                }
            }
            return d[m][n];
        }

        // Token-sort ratio: order-independent similarity, so "OKELLO JOHN"
        // matches a roster entry stored as "JOHN OKELLO" just as well.
        function similarity(a, b) {
            const normA = normalizeName(a).split(' ').filter(Boolean).sort().join(' ');
            const normB = normalizeName(b).split(' ').filter(Boolean).sort().join(' ');
            if (!normA || !normB) return 0;
            if (normA === normB) return 1;
            const dist = levenshtein(normA, normB);
            const maxLen = Math.max(normA.length, normB.length);
            return maxLen === 0 ? 0 : 1 - (dist / maxLen);
        }

        function bestMatch(ocrName, roster) {
            let best = null;
            let bestScore = 0;
            roster.forEach(student => {
                const score = similarity(ocrName, student.name);
                if (score > bestScore) {
                    bestScore = score;
                    best = student;
                }
            });
            return {student: best, score: bestScore};
        }

        function confidenceClass(score) {
            if (score >= 0.82) return 'high';
            if (score >= 0.6) return 'medium';
            return 'low';
        }

        // ── REVIEW TABLE ─────────────────────────────────────────────────
        function showScanReview(data) {
            const subjectId = $('#scanMarksModal').data('subject-id');
            const meta = (window.subjectMeta || {})[subjectId] || {studentIds: [], totalPapers: 1};
            const namesMap = window.studentNamesMap || {};

            const roster = (meta.studentIds || []).map(id => ({id, name: namesMap[id] || id}));

            $('#scanUploadStep').addClass('d-none');
            $('#scanReviewStep').removeClass('d-none');
            $('#scanUploadFooter').addClass('d-none');
            $('#scanReviewFooter').removeClass('d-none');

            const notice = $('#scanReviewNotice');
            if (data.notice) {
                notice.text(data.notice).removeClass('d-none');
            } else {
                notice.addClass('d-none');
            }

            const tbody = $('#scanReviewTbody').empty();

            if (!scanExtractedEntries.length) {
                tbody.append(`<tr><td colspan="5" class="text-center text-muted py-4">
                    No rows could be read from this document. Try a clearer photo, or enter marks manually.
                </td></tr>`);
                updateScanMatchedCount();
                return;
            }

            scanExtractedEntries.forEach((entry, idx) => {
                const {student, score} = bestMatch(entry.candidate_name, roster);
                const confClass = confidenceClass(score);
                const scores = [entry.p1, entry.p2, entry.p3, entry.p4]
                    .map((v, i) => v !== null && v !== undefined ? `<span class="score-pill">P${i + 1}: ${v}</span>` : '')
                    .join('');
                const avgPill = entry.average !== null && entry.average !== undefined
                    ? `<span class="score-pill">Avg: ${entry.average}</span>` : '';

                const options = roster.map(s =>
                    `<option value="${s.id}" ${student && s.id === student.id ? 'selected' : ''}>${escapeHtml(s.name)}</option>`
                ).join('');

                const row = $(`
                    <tr data-index="${idx}">
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input scan-row-check" ${student ? 'checked' : ''}>
                        </td>
                        <td class="ocr-name">${escapeHtml(entry.candidate_name || '(blank)')}</td>
                        <td>${scores}${avgPill}</td>
                        <td>
                            <select class="form-select form-select-sm match-select">
                                <option value="">— Do not apply —</option>
                                ${options}
                            </select>
                        </td>
                        <td>
                            <span class="match-confidence ${confClass}">
                                ${student ? Math.round(score * 100) + '%' : 'No match'}
                            </span>
                        </td>
                    </tr>
                `);

                if (!student) row.find('.scan-row-check').prop('checked', false);
                tbody.append(row);
            });

            updateScanMatchedCount();
        }

        $(document).on('change', '.scan-row-check, .match-select', updateScanMatchedCount);

        function updateScanMatchedCount() {
            let count = 0;
            $('#scanReviewTbody tr').each(function () {
                const checked = $(this).find('.scan-row-check').is(':checked');
                const hasStudent = $(this).find('.match-select').val();
                if (checked && hasStudent) count++;
            });
            $('#scanMatchedCount').text(count);
        }

        window.backToScanUpload = function () {
            $('#scanReviewStep').addClass('d-none');
            $('#scanUploadStep').removeClass('d-none');
            $('#scanReviewFooter').addClass('d-none');
            $('#scanUploadFooter').removeClass('d-none');
        };

        // ── APPLY TO MARKS TABLE ────────────────────────────────────────
        window.applyScanMatches = function () {
            const subjectId = $('#scanMarksModal').data('subject-id');
            const meta = (window.subjectMeta || {})[subjectId] || {totalPapers: 1, allowedPapers: null};
            const $pane = $(`#subject-form-${subjectId}`);
            let applied = 0;

            $('#scanReviewTbody tr').each(function () {
                const $row = $(this);
                const idx = $row.data('index');
                if (idx === undefined) return;

                const checked = $row.find('.scan-row-check').is(':checked');
                const studentId = $row.find('.match-select').val();
                if (!checked || !studentId) return;

                const entry = scanExtractedEntries[idx];
                if (!entry) return;

                const totalPapers = meta.totalPapers || 1;

                if (totalPapers > 1) {
                    for (let p = 1; p <= totalPapers; p++) {
                        if (meta.allowedPapers && !meta.allowedPapers.includes(p)) continue;
                        const val = entry['p' + p];
                        if (val === null || val === undefined) continue;
                        const $input = $pane.find(`input[name="marks[${studentId}][${p}]"]`);
                        if ($input.length) {
                            $input.val(Math.round(val));
                            if (typeof window.updateMarkStatus === 'function') {
                                window.updateMarkStatus($input, $input.val());
                            }
                        }
                    }
                } else {
                    const val = entry.p1 !== null && entry.p1 !== undefined ? entry.p1 : entry.average;
                    if (val === null || val === undefined) return;
                    const $input = $pane.find(`input[name="marks[${studentId}]"]`);
                    if ($input.length) {
                        $input.val(Math.round(val));
                        if (typeof window.updateMarkStatus === 'function') {
                            window.updateMarkStatus($input, $input.val());
                        }
                    }
                }

                $row.css('background-color', '#eaf7ef');
                applied++;
            });

            $('#scanMarksModal').modal('hide');

            Swal.fire({
                icon: applied > 0 ? 'success' : 'warning',
                title: applied > 0 ? 'Marks Filled In' : 'Nothing Applied',
                html: applied > 0
                    ? `${applied} student(s) filled in. Review the highlighted (blue "unsaved") fields, then click <strong>Save</strong> for this subject.`
                    : 'No rows were selected/matched to apply.',
                confirmButtonText: 'OK'
            });
        };

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    })(jQuery);
    });
</script>