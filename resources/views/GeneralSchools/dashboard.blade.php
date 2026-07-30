@extends('layouts-side-bar.master')

@section('content')
    <div class="side-app">
        <div class="container-fluid mt-3">

            <style>
                /* ===== BASE RESPONSIVE RESET ===== */
                * {
                    box-sizing: border-box;
                }

                /* ===== HERO SECTION ===== */
                .sd-hero {
                    background: linear-gradient(135deg, #0b6b3a 0%, #0f8a4d 100%);
                    border-radius: 14px;
                    color: #fff;
                    padding: 28px 30px;
                    margin-bottom: 26px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 16px;
                }

                .sd-hero h3 {
                    font-weight: 700;
                    margin-bottom: 4px;
                    font-size: 1.5rem;
                }

                .sd-hero p {
                    opacity: .9;
                    margin-bottom: 0;
                    font-size: 0.95rem;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    max-width: 100%;
                }

                .sd-hero .sd-badge {
                    background: rgba(255, 255, 255, .16);
                    border: 1px solid rgba(255, 255, 255, .3);
                    border-radius: 50px;
                    padding: 8px 18px;
                    font-weight: 600;
                    font-size: .9rem;
                    backdrop-filter: blur(4px);
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    flex-wrap: wrap;
                    max-width: 100%;
                    word-break: break-word;
                }

                .sd-hero .sd-badge .school-name {
                    max-width: 200px;
                    display: inline-block;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                /* ===== STAT CARDS ===== */
                .sd-stat {
                    border: 1px solid #e7ebf0;
                    border-radius: 16px;
                    background: #fff;
                    padding: 20px 22px;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    gap: 16px;
                    transition: all .18s ease;
                }

                .sd-stat:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 12px 26px rgba(15, 138, 77, .12);
                    border-color: #0f8a4d;
                }

                .sd-stat .sd-icon {
                    width: 52px;
                    height: 52px;
                    min-width: 52px;
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 22px;
                    color: #fff;
                }

                .sd-icon.bg-students {
                    background: linear-gradient(135deg, #1c6fd6, #0d4ea3);
                }
                .sd-icon.bg-graded {
                    background: linear-gradient(135deg, #0f8a4d, #0b6b3a);
                }
                .sd-icon.bg-pending {
                    background: linear-gradient(135deg, #e08e0b, #b56d02);
                }
                .sd-icon.bg-average {
                    background: linear-gradient(135deg, #8e44ad, #5b2c6f);
                }

                .sd-stat .sd-number {
                    font-size: 1.6rem;
                    font-weight: 700;
                    line-height: 1.15;
                    color: #1e293b;
                }

                .sd-stat .sd-label {
                    font-size: .78rem;
                    text-transform: uppercase;
                    letter-spacing: .04em;
                    color: #6b7785;
                    font-weight: 600;
                }

                /* ===== CARDS ===== */
                .sd-card {
                    border: 1px solid #e7ebf0;
                    border-radius: 16px;
                    background: #fff;
                    overflow: hidden;
                }

                .sd-card .sd-card-header {
                    padding: 18px 24px;
                    border-bottom: 1px solid #e7ebf0;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    flex-wrap: wrap;
                }

                .sd-card .sd-card-header h5 {
                    margin: 0;
                    font-weight: 700;
                    color: #1e293b;
                    font-size: 1.05rem;
                }

                .sd-card .sd-card-header i {
                    color: #0f8a4d;
                }

                .sd-card .sd-card-body {
                    padding: 24px;
                }

                /* ===== FORM ELEMENTS ===== */
                .form-label {
                    font-size: 0.85rem;
                    margin-bottom: 0.35rem;
                    font-weight: 600;
                    color: #495057;
                }

                .form-select,
                .form-control {
                    border-radius: 0.6rem;
                    border: 1px solid #dee2e6;
                    padding: 0.55rem 0.75rem;
                    font-size: 0.95rem;
                    width: 100%;
                }

                .form-select:focus,
                .form-control:focus {
                    border-color: #0f8a4d;
                    box-shadow: 0 0 0 0.2rem rgba(15, 138, 77, .12);
                }

                .sd-submit-btn {
                    background: linear-gradient(135deg, #0f8a4d, #0b6b3a);
                    border: none;
                    color: #fff;
                    font-weight: 600;
                    border-radius: 50px;
                    padding: 0.7rem 2rem;
                    box-shadow: 0 6px 16px rgba(15, 138, 77, .28);
                    transition: all .18s ease;
                    width: 100%;
                }

                .sd-submit-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 10px 22px rgba(15, 138, 77, .34);
                    color: #fff;
                }

                .sd-submit-btn i {
                    margin-right: 8px;
                }

                /* ===== RESPONSIVE BREAKPOINTS ===== */

                /* Tablets & Small Laptops (≤ 992px) */
                @media (max-width: 992px) {
                    .sd-hero h3 {
                        font-size: 1.3rem;
                    }
                    
                    .sd-hero p {
                        font-size: 0.9rem;
                    }

                    .sd-stat .sd-number {
                        font-size: 1.4rem;
                    }
                }

                /* Mobile Landscape & Small Tablets (≤ 768px) */
                @media (max-width: 768px) {
                    /* Hero */
                    .sd-hero {
                        padding: 20px 18px;
                        flex-direction: column;
                        align-items: flex-start;
                        text-align: left;
                    }

                    .sd-hero h3 {
                        font-size: 1.2rem;
                    }

                    .sd-hero p {
                        font-size: 0.85rem;
                    }

                    .sd-hero .sd-badge {
                        font-size: 0.8rem;
                        padding: 6px 14px;
                        width: 100%;
                        justify-content: center;
                        white-space: normal;
                    }

                    .sd-hero .sd-badge .school-name {
                        max-width: 150px;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }

                    /* Stats - 2 columns on tablet */
                    .sd-stat {
                        padding: 16px 18px;
                        gap: 12px;
                    }

                    .sd-stat .sd-icon {
                        width: 44px;
                        height: 44px;
                        min-width: 44px;
                        font-size: 18px;
                    }

                    .sd-stat .sd-number {
                        font-size: 1.3rem;
                    }

                    .sd-stat .sd-label {
                        font-size: 0.7rem;
                    }

                    /* Cards */
                    .sd-card .sd-card-header {
                        padding: 14px 18px;
                    }

                    .sd-card .sd-card-header h5 {
                        font-size: 0.95rem;
                    }

                    .sd-card .sd-card-body {
                        padding: 18px;
                    }
                }

                /* Mobile Portrait (≤ 576px) - STATS STACK VERTICALLY */
                @media (max-width: 576px) {
                    /* Hero - full width badge */
                    .sd-hero {
                        padding: 16px 14px;
                        border-radius: 12px;
                    }

                    .sd-hero h3 {
                        font-size: 1.1rem;
                    }

                    .sd-hero h3 i {
                        font-size: 0.9rem;
                    }

                    .sd-hero p {
                        font-size: 0.8rem;
                    }

                    .sd-hero .sd-badge {
                        font-size: 0.75rem;
                        padding: 6px 12px;
                        flex-wrap: wrap;
                        gap: 4px;
                    }

                    .sd-hero .sd-badge .school-name {
                        max-width: 120px;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }

                    /* CRITICAL FIX: Stats stack vertically - full width each */
                    .row.g-3.mb-4 > [class*="col-"] {
                        flex: 0 0 100%;
                        max-width: 100%;
                        width: 100%;
                    }

                    .sd-stat {
                        padding: 14px 16px;
                        gap: 14px;
                        border-radius: 12px;
                        width: 100%;
                    }

                    .sd-stat .sd-icon {
                        width: 48px;
                        height: 48px;
                        min-width: 48px;
                        font-size: 20px;
                    }

                    .sd-stat .sd-number {
                        font-size: 1.5rem;
                    }

                    .sd-stat .sd-label {
                        font-size: 0.75rem;
                    }

                    /* Card */
                    .sd-card {
                        border-radius: 12px;
                    }

                    .sd-card .sd-card-header {
                        padding: 12px 16px;
                        gap: 8px;
                    }

                    .sd-card .sd-card-header h5 {
                        font-size: 0.9rem;
                    }

                    .sd-card .sd-card-header i {
                        font-size: 0.9rem;
                    }

                    .sd-card .sd-card-body {
                        padding: 16px;
                    }

                    /* Form */
                    .form-label {
                        font-size: 0.8rem;
                    }

                    .form-select,
                    .form-control {
                        font-size: 0.9rem;
                        padding: 0.45rem 0.65rem;
                        border-radius: 0.5rem;
                    }

                    .sd-submit-btn {
                        padding: 0.6rem 1.5rem;
                        font-size: 0.95rem;
                        border-radius: 40px;
                    }

                    /* Adjust button row */
                    .row.justify-content-center.mt-4 {
                        margin-top: 1.5rem !important;
                    }
                }

                /* Extra Small Devices (≤ 400px) */
                @media (max-width: 400px) {
                    .sd-hero {
                        padding: 14px 12px;
                    }

                    .sd-hero h3 {
                        font-size: 1rem;
                    }

                    .sd-hero p {
                        font-size: 0.75rem;
                    }

                    .sd-hero .sd-badge {
                        font-size: 0.7rem;
                        padding: 4px 10px;
                    }

                    .sd-hero .sd-badge .school-name {
                        max-width: 80px;
                    }

                    .sd-stat {
                        padding: 12px 14px;
                        gap: 12px;
                    }

                    .sd-stat .sd-icon {
                        width: 40px;
                        height: 40px;
                        min-width: 40px;
                        font-size: 16px;
                        border-radius: 10px;
                    }

                    .sd-stat .sd-number {
                        font-size: 1.3rem;
                    }

                    .sd-stat .sd-label {
                        font-size: 0.65rem;
                    }

                    .sd-card .sd-card-body {
                        padding: 14px;
                    }

                    .sd-card .sd-card-header h5 {
                        font-size: 0.85rem;
                    }

                    .form-select,
                    .form-control {
                        font-size: 0.85rem;
                        padding: 0.4rem 0.6rem;
                    }

                    .sd-submit-btn {
                        padding: 0.5rem 1.2rem;
                        font-size: 0.9rem;
                    }
                }

                /* Fix for very small text on any device */
                .text-truncate-mobile {
                    white-space: normal;
                    word-break: break-word;
                }

                /* Improve touch targets on mobile */
                @media (pointer: coarse) {
                    .form-select,
                    .form-control,
                    .sd-submit-btn {
                        min-height: 44px;
                    }
                }

                /* Smooth transitions for all interactive elements */
                .sd-stat,
                .sd-submit-btn,
                .form-select,
                .form-control {
                    transition: all 0.2s ease-in-out;
                }

                /* Additional fix for badge on very small screens */
                @media (max-width: 480px) {
                    .sd-hero .sd-badge {
                        font-size: 0.7rem;
                        padding: 5px 10px;
                        border-radius: 30px;
                    }
                }
            </style>

            {{-- Hero --}}
            <div class="sd-hero">
                <div style="flex: 1; min-width: 0;">
                    <h3><i class="fas fa-school me-2"></i> School Dashboard</h3>
                    <p class="text-truncate-mobile">Grading &amp; examination overview for <strong>{{ session('LoggedSchoolName') ?? 'your school' }}</strong></p>
                </div>
                <div class="sd-badge">
                    <i class="fas fa-shield-halved"></i>
                    <span class="school-name">{{ session('LoggedSchoolName') ?? 'School' }}</span>
                    <span style="opacity: 0.5;">&middot;</span>
                    <span>{{ session('LoggedSchoolCode') }}</span>
                </div>
            </div>

            {{-- Stats - Now stacks vertically on small screens --}}
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="sd-stat">
                        <div class="sd-icon bg-students"><i class="fas fa-user-graduate"></i></div>
                        <div>
                            <div class="sd-number">{{ number_format($totalStudents ?? 0) }}</div>
                            <div class="sd-label">Total Students</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="sd-stat">
                        <div class="sd-icon bg-graded"><i class="fas fa-circle-check"></i></div>
                        <div>
                            <div class="sd-number">{{ number_format($gradedSoFar ?? 0) }}</div>
                            <div class="sd-label">Graded So Far</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="sd-stat">
                        <div class="sd-icon bg-pending"><i class="fas fa-hourglass-half"></i></div>
                        <div>
                            <div class="sd-number">{{ number_format($pendingGrading ?? 0) }}</div>
                            <div class="sd-label">Pending Grading</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="sd-stat">
                        <div class="sd-icon bg-average"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <div class="sd-number">{{ number_format($avgPerformance ?? 0, 1) }}%</div>
                            <div class="sd-label">Avg. Performance</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Grading Report Form --}}
            <div class="sd-card">
                <div class="sd-card-header">
                    <i class="fas fa-calculator"></i>
                    <h5>Generate Grading Report</h5>
                </div>
                <div class="sd-card-body">
                    <form action="{{ route('school.process.grading') }}" method="POST" id="gradingFilterForm">
                        @csrf

                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Year <span class="text-danger">*</span></label>
                                <select name="year" class="form-select select2" required>
                                    <option value="">-- Select Year --</option>
                                    @foreach ($academicYears as $academicYear)
                                        <option value="{{ $academicYear->year_en }}" {{ $academicYear->year_en == $activeYear ? 'selected' : '' }}>
                                            {{ $academicYear->year_en }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-select select2" required>
                                    <option value="">-- Select Category --</option>
                                    @foreach ($categories as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <input type="hidden" name="level" id="levelInput">
                        </div>

                        <div class="row justify-content-center mt-4">
                            <div class="col-12 col-md-6 col-lg-4">
                                <button type="submit" class="btn sd-submit-btn">
                                    <i class="fas fa-magnifying-glass-chart"></i>
                                    Generate School Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('gradingFilterForm');
            const categorySelect = document.querySelector('select[name="category"]');
            const levelInput = document.getElementById('levelInput');

            function setLevelBasedOnCategory() {
                if (!categorySelect || !levelInput) return;
                const selectedCategory = categorySelect.value;
                if (selectedCategory === 'UACE') {
                    levelInput.value = 'A';
                } else if (selectedCategory === 'UCE') {
                    levelInput.value = 'O';
                } else {
                    levelInput.value = '';
                }
            }

            if (categorySelect) {
                categorySelect.addEventListener('change', setLevelBasedOnCategory);
            }
            setLevelBasedOnCategory();

            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Generating grading report. Please wait.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => Swal.showLoading(),
                    });
                    setTimeout(() => form.submit(), 300);
                });
            }
        });
    </script>
@endsection