@extends('layouts-side-bar.master')

@section('content')
    <div class="side-app">
        <div class="dash-wrap">

            <style>
                :root {
                    --primary: #043AA1;
                    --primary-light: #1E7A3D;
                    --primary-lighter: #adbdd9;
                    --primary-dark: #014425;
                    --ink: #10241A;
                    --muted: #6B7B72;
                    --border: #E7EDE9;
                    --surface: #FFFFFF;
                    --page: #F5F8F6;
                    --success: #1E9E52;
                    --warning: #C98A0A;
                    --warning-bg: #FFF6E3;
                    --danger: #D64545;
                    --info: #1D7FBF;
                    --radius: 14px;
                    --radius-sm: 10px;
                    --shadow: 0 1px 2px rgba(16, 36, 26, .04), 0 1px 12px rgba(16, 36, 26, .05);
                    --shadow-hover: 0 10px 24px rgba(16, 36, 26, .10);
                }

                .dash-wrap {
                    background: var(--page);
                    padding: 22px 22px 40px;
                    font-family: inherit;
                    color: var(--ink);
                }

                .dash-wrap * {
                    box-sizing: border-box;
                }

                .dash-grid {
                    display: grid;
                    gap: 18px;
                }

                /* ---------- Header ---------- */
                .dash-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 16px;
                    flex-wrap: wrap;
                    margin-bottom: 22px;
                }

                .dash-greeting h1 {
                    font-size: 1.5rem;
                    font-weight: 800;
                    margin: 0 0 4px;
                    color: var(--ink);
                }

                .dash-greeting p {
                    margin: 0;
                    color: var(--muted);
                    font-size: .9rem;
                }

                .dash-header-right {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    flex-wrap: wrap;
                }

                .pill {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 7px 14px;
                    border-radius: 999px;
                    font-size: .78rem;
                    font-weight: 700;
                    background: var(--surface);
                    border: 1px solid var(--border);
                    color: var(--ink);
                }

                .pill.brand {
                    background: var(--primary);
                    color: #fff;
                    border-color: var(--primary);
                }

                .pill.year {
                    background: var(--primary-lighter);
                    color: var(--primary-dark);
                    border-color: #cfe9d8;
                }

                .pill.warn {
                    background: var(--warning-bg);
                    color: #8a5b00;
                    border-color: #f3dfa8;
                }

                .bell-btn {
                    position: relative;
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: var(--surface);
                    border: 1px solid var(--border);
                    color: var(--ink);
                    text-decoration: none;
                    transition: .15s;
                }

                .bell-btn:hover {
                    background: var(--primary-lighter);
                    color: var(--primary-dark);
                }

                .bell-badge {
                    position: absolute;
                    top: -4px;
                    right: -4px;
                    background: var(--danger);
                    color: #fff;
                    font-size: .65rem;
                    font-weight: 800;
                    min-width: 18px;
                    height: 18px;
                    border-radius: 999px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 0 4px;
                    border: 2px solid var(--page);
                }

                /* ---------- KPI cards ---------- */
                .kpi-grid {
                    grid-template-columns: repeat(4, 1fr);
                }

                @media (max-width: 1100px) {
                    .kpi-grid {
                        grid-template-columns: repeat(2, 1fr);
                    }
                }

                @media (max-width: 560px) {
                    .kpi-grid {
                        grid-template-columns: 1fr;
                    }
                }

                .kpi-card {
                    background: var(--surface);
                    border-radius: var(--radius);
                    padding: 18px 20px;
                    box-shadow: var(--shadow);
                    border: 1px solid var(--border);
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    transition: .15s;
                }

                .kpi-card:hover {
                    box-shadow: var(--shadow-hover);
                    transform: translateY(-2px);
                }

                .kpi-top {
                    display: flex;
                    align-items: flex-start;
                    justify-content: space-between;
                }

                .kpi-icon {
                    width: 42px;
                    height: 42px;
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.1rem;
                    color: #fff;
                    background: var(--primary);
                    flex-shrink: 0;
                }

                .kpi-icon.blue {
                    background: var(--info);
                }

                .kpi-icon.amber {
                    background: var(--warning);
                }

                .kpi-icon.green {
                    background: var(--success);
                }

                .kpi-trend {
                    font-size: .72rem;
                    font-weight: 700;
                    padding: 3px 9px;
                    border-radius: 999px;
                }

                .kpi-trend.up {
                    background: #E7F7ED;
                    color: var(--success);
                }

                .kpi-trend.flat {
                    background: #F1F3F2;
                    color: var(--muted);
                }

                .kpi-value {
                    font-size: 1.85rem;
                    font-weight: 800;
                    line-height: 1;
                    color: var(--ink);
                }

                .kpi-label {
                    font-size: .82rem;
                    color: var(--muted);
                    font-weight: 600;
                }

                .kpi-sub {
                    font-size: .76rem;
                    color: var(--muted);
                    margin-top: -2px;
                }

                .kpi-sub b {
                    color: var(--ink);
                }

                /* ---------- Generic card ---------- */
                .card-panel {
                    background: var(--surface);
                    border-radius: var(--radius);
                    border: 1px solid var(--border);
                    box-shadow: var(--shadow);
                    padding: 20px 22px;
                    display: flex;
                    flex-direction: column;
                }

                /* Wraps a chart that should absorb any leftover height a
                   grid-stretched card-panel ends up with (e.g. the doughnut
                   panel next to a taller bar+line chart panel), centering
                   the chart in that space instead of leaving it stranded
                   at the bottom. */
                .chart-center {
                    flex: 1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 0;
                }

                /* Like .chart-center, but stretches the chart to fill the
                   leftover height instead of just centering it — for
                   charts (like bars) that read fine taller, rather than
                   ones (like a doughnut) that would look distorted if
                   stretched off their natural aspect ratio. */
                .chart-grow {
                    flex: 1;
                    min-height: 0;
                    position: relative;
                }

                .chart-grow canvas {
                    position: absolute;
                    inset: 0;
                    width: 100% !important;
                    height: 100% !important;
                }

                .panel-head {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 14px;
                    gap: 10px;
                    flex-wrap: wrap;
                }

                .panel-title {
                    font-size: 1rem;
                    font-weight: 800;
                    color: var(--ink);
                    margin: 0;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .panel-title i {
                    color: var(--primary);
                }

                .panel-link {
                    font-size: .8rem;
                    font-weight: 700;
                    color: var(--primary);
                    text-decoration: none;
                }

                .panel-link:hover {
                    text-decoration: underline;
                }

                .panel-sub {
                    font-size: .78rem;
                    color: var(--muted);
                    margin: -8px 0 14px;
                }

                /* ---------- Two/three column layout ---------- */
                .row-2 {
                    grid-template-columns: 1.15fr .85fr;
                }

                .row-2b {
                    grid-template-columns: 1fr 1fr;
                }

                .row-3 {
                    grid-template-columns: 1fr 1fr 1fr;
                }

                @media (max-width: 992px) {

                    .row-2,
                    .row-2b,
                    .row-3 {
                        grid-template-columns: 1fr;
                    }
                }

                canvas {
                    max-width: 100%;
                }

                /* ---------- Progress rows (registration/marks completion) ---------- */
                .progress-row {
                    margin-bottom: 16px;
                }

                .progress-row:last-child {
                    margin-bottom: 0;
                }

                .progress-row-top {
                    display: flex;
                    justify-content: space-between;
                    align-items: baseline;
                    margin-bottom: 6px;
                }

                .progress-row-top .name {
                    font-weight: 700;
                    font-size: .86rem;
                    color: var(--ink);
                }

                .progress-row-top .frac {
                    font-size: .76rem;
                    color: var(--muted);
                }

                .progress-track {
                    height: 9px;
                    border-radius: 999px;
                    background: #EEF2EF;
                    overflow: hidden;
                }

                .progress-fill {
                    height: 100%;
                    border-radius: 999px;
                    background: linear-gradient(90deg, var(--primary), #043AA1);
                }

                .progress-fill.amber {
                    background: linear-gradient(90deg, #E0A215, var(--warning));
                }

                /* ---------- Table-ish lists ---------- */
                .list-clean {
                    list-style: none;
                    margin: 0;
                    padding: 0;
                }

                .list-clean li {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 11px 0;
                    border-bottom: 1px solid var(--border);
                }

                .list-clean li:last-child {
                    border-bottom: none;
                    padding-bottom: 0;
                }

                .list-avatar {
                    width: 36px;
                    height: 36px;
                    border-radius: 10px;
                    background: var(--primary-lighter);
                    color: var(--primary-dark);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 800;
                    font-size: .8rem;
                    flex-shrink: 0;
                }

                .list-main {
                    flex: 1;
                    min-width: 0;
                }

                .list-main .t1 {
                    font-weight: 700;
                    font-size: .86rem;
                    color: var(--ink);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .list-main .t2 {
                    font-size: .74rem;
                    color: var(--muted);
                }

                .list-tag {
                    font-size: .7rem;
                    font-weight: 700;
                    padding: 3px 9px;
                    border-radius: 999px;
                    background: var(--primary-lighter);
                    color: var(--primary-dark);
                    white-space: nowrap;
                }

                .list-tag.gray {
                    background: #F1F3F2;
                    color: var(--muted);
                }

                .empty-state {
                    text-align: center;
                    padding: 30px 10px;
                    color: var(--muted);
                    font-size: .85rem;
                }

                .empty-state i {
                    font-size: 1.8rem;
                    display: block;
                    margin-bottom: 8px;
                    color: #C9D3CD;
                }

                /* ---------- Combination bars ---------- */
                .combo-row {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 9px 0;
                }

                .combo-code {
                    width: 54px;
                    flex-shrink: 0;
                    text-align: center;
                    font-weight: 800;
                    font-size: .75rem;
                    background: var(--primary);
                    color: #fff;
                    border-radius: 8px;
                    padding: 5px 0;
                }

                .combo-body {
                    flex: 1;
                    min-width: 0;
                }

                .combo-body .name {
                    font-size: .78rem;
                    color: var(--muted);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    margin-bottom: 4px;
                }

                .combo-count {
                    width: 34px;
                    text-align: right;
                    font-weight: 800;
                    font-size: .85rem;
                    color: var(--ink);
                    flex-shrink: 0;
                }

                /* ---------- Quick actions ---------- */
                .qa-grid {
                    display: grid;
                    grid-template-columns: repeat(6, 1fr);
                    gap: 10px;
                }

                @media (max-width: 900px) {
                    .qa-grid {
                        grid-template-columns: repeat(3, 1fr);
                    }
                }

                @media (max-width: 560px) {
                    .qa-grid {
                        grid-template-columns: 1fr 1fr;
                    }
                }

                .qa-btn {
                    display: flex;
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                    padding: 14px;
                    border-radius: var(--radius-sm);
                    border: 1px solid var(--border);
                    text-decoration: none;
                    color: var(--ink);
                    background: var(--page);
                    transition: .15s;
                }

                .qa-btn:hover {
                    background: var(--primary-lighter);
                    border-color: #cfe9d8;
                    color: var(--primary-dark);
                    transform: translateY(-1px);
                }

                .qa-btn i {
                    font-size: 1.15rem;
                    color: var(--primary);
                }

                .qa-btn span {
                    font-size: .78rem;
                    font-weight: 700;
                    line-height: 1.2;
                }

                /* ---------- Announcements ---------- */
                .announce-item {
                    padding: 12px 0;
                    border-bottom: 1px solid var(--border);
                    display: flex;
                    gap: 10px;
                }

                .announce-item:last-child {
                    border-bottom: none;
                    padding-bottom: 0;
                }

                .announce-dot {
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    margin-top: 6px;
                    flex-shrink: 0;
                    background: var(--info);
                }

                .announce-dot.urgent {
                    background: var(--danger);
                }

                .announce-dot.important {
                    background: var(--warning);
                }

                .announce-body .t1 {
                    font-weight: 700;
                    font-size: .84rem;
                    color: var(--ink);
                }

                .announce-body .t2 {
                    font-size: .73rem;
                    color: var(--muted);
                    margin-top: 2px;
                }

                .no-year-banner {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    background: var(--warning-bg);
                    border: 1px solid #f3dfa8;
                    color: #8a5b00;
                    border-radius: var(--radius-sm);
                    padding: 12px 16px;
                    font-size: .83rem;
                    font-weight: 600;
                    margin-bottom: 18px;
                }
            </style>

            {{-- ============== Header ============== --}}
            <div class="dash-header">
                <div class="dash-greeting">
                    @php
                        $__hour = now()->format('H');
                        $__greeting = $__hour < 12 ? 'GOOD MORNING' : ($__hour < 17 ? 'GOOD AFTERNOON' : 'GOOD EVENING');
                        $__name = trim($currentUser->firstname . ' ' . $currentUser->lastname) ?: ($currentUser->name ?? 'there');
                    @endphp
                    <h1>{{ $__greeting }}, {{ $__name }} <i class="fas fa-hand-paper text-primary"></i></h1>

                    <p>
                        @if($isSystemAdmin)
                            Here's what's happening across all WAKATA schools today.
                        @else
                            Here's what's happening at <strong>{{ $school->House ?? 'your school' }}</strong> today.
                        @endif
                    </p>
                </div>
                <div class="dash-header-right">
                    <span class="pill brand"><i class="fa fa-user-shield"></i> {{ $roleLabel }}</span>
                    @if($activeYear)
                        <span class="pill year"><i class="fa fa-calendar"></i> {{ $activeYear }} Academic Year</span>
                    @else
                        <span class="pill warn"><i class="fa fa-triangle-exclamation"></i> No active year set</span>
                    @endif
                    @if(!$isSystemAdmin && $school)
                        <span class="pill"><i class="fa fa-hashtag"></i> {{ $school->Number }}</span>
                    @endif
                    <a href="{{ route('notifications.inbox') }}" class="bell-btn" title="Notifications">
                        <i class="fa fa-bell"></i>
                        @if($unreadMessages > 0)
                            <span class="bell-badge">{{ $unreadMessages > 9 ? '9+' : $unreadMessages }}</span>
                        @endif
                    </a>
                </div>
            </div>

            @unless($activeYear)
                <div class="no-year-banner">
                    <i class="fa fa-triangle-exclamation"></i>
                    No academic year is currently marked <strong>Active</strong>, so registration/marks-entry progress below
                    can't be calculated.
                    @if($isSystemAdmin)
                        <a href="{{ route('academic.years') }}"
                            style="color:#8a5b00; text-decoration:underline; font-weight:700;">Set one now &rarr;</a>
                    @endif
                </div>
            @endunless

            {{-- ============== KPI Cards ============== --}}
            <div class="dash-grid kpi-grid" style="margin-bottom: 18px;">

                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-icon"><i class="fa fa-user-graduate"></i></div>
                    </div>
                    <div class="kpi-value">{{ number_format($totalStudents) }}</div>
                    <div class="kpi-label">{{ $isSystemAdmin ? 'Total Students (All Schools)' : 'Total Students' }}</div>
                    <div class="kpi-sub">
                        <b>{{ number_format($studentsByCategory['UCE']) }}</b> UCE &nbsp;·&nbsp;
                        <b>{{ number_format($studentsByCategory['UACE']) }}</b> UACE
                        @if($studentsByCategory['PLE'] > 0)
                            &nbsp;·&nbsp; <b>{{ number_format($studentsByCategory['PLE']) }}</b> PLE
                        @endif
                    </div>
                </div>

                @php
                    $__regAvg = count($registrationProgress) ? (int) round(collect($registrationProgress)->avg('pct')) : 0;
                    $__markAvg = count($marksEntryProgress) ? (int) round(collect($marksEntryProgress)->avg('pct')) : 0;
                @endphp

                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-icon blue"><i class="fa fa-tasks"></i>
</div>
                        <span class="kpi-trend {{ $__regAvg >= 70 ? 'up' : 'flat' }}">{{ $__regAvg }}%</span>
                    </div>
                    <div class="kpi-value">{{ $__regAvg }}%</div>
                    <div class="kpi-label">Subject Registration Complete</div>
                    <div class="kpi-sub">Across UCE &amp; UACE for {{ $activeYear ?? 'the active year' }}</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-top">
                        <div class="kpi-icon amber"><i class="fa fa-pen-to-square"></i></div>
                        <span class="kpi-trend {{ $__markAvg >= 70 ? 'up' : 'flat' }}">{{ $__markAvg }}%</span>
                    </div>
                    <div class="kpi-value">{{ $__markAvg }}%</div>
                    <div class="kpi-label">Marks Entry Complete</div>
                    <div class="kpi-sub">Of all registered subject entries</div>
                </div>

                @if($isSystemAdmin)
                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-icon green"><i class="fa fa-school"></i></div>
                        </div>
                        <div class="kpi-value">{{ number_format($totalSchools) }}</div>
                        <div class="kpi-label">Registered Schools</div>
                        <div class="kpi-sub"><b>{{ number_format($activeSchoolsCount) }}</b> active &nbsp;·&nbsp;
                            <b>{{ number_format($totalSchools - $activeSchoolsCount) }}</b> inactive</div>
                    </div>
                @else
                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-icon green"><i class="fa fa-layer-group"></i></div>
                        </div>
                        <div class="kpi-value">{{ $combinationBreakdown->sum('students_count') }}</div>
                        <div class="kpi-label">Students on a Combination</div>
                        <div class="kpi-sub">Across <b>{{ $combinationBreakdown->count() }}</b> UACE combinations</div>
                    </div>
                @endif
            </div>

            {{-- ============== Charts row ============== --}}
            <div class="dash-grid row-2" style="margin-bottom: 18px;">
                <div class="card-panel">
                    <div class="panel-head">
                        <h3 class="panel-title"><i class="fa fa-chart-column"></i> Registration &amp; Marks-Entry Progress
                        </h3>
                    </div>
                    <p class="panel-sub">% of {{ $activeYear ?? 'this year\'s' }} students registered for subjects vs. how
                        many of those subjects have marks recorded.</p>
                    <div class="chart-grow">
                        <canvas id="progressChart" height="130"></canvas>
                    </div>
                </div>

                <div class="card-panel">
                    <div class="panel-head">
                        <h3 class="panel-title"><i class="fa fa-chart-pie"></i> Students by Category</h3>
                    </div>
                    <p class="panel-sub">{{ $isSystemAdmin ? 'System-wide split.' : 'Split at your school.' }}</p>
                    <div class="chart-center">
                        <canvas id="categoryChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            {{-- ============== Combination breakdown + Recent list ============== --}}
            <div class="dash-grid row-2" style="margin-bottom: 18px;">

                <div class="card-panel">
                    <div class="panel-head">
                        <h3 class="panel-title"><i class="fa fa-layer-group"></i> UACE Combinations</h3>
                        <a href="{{ route('combination.management.index') }}" class="panel-link">Manage &rarr;</a>
                    </div>
                    @if($combinationBreakdown->isEmpty())
                        <div class="empty-state">
                            <i class="fa fa-layer-group"></i>
                            No UACE combinations defined yet.
                            <div style="margin-top:8px;"><a href="{{ route('combination.management.index') }}"
                                    class="panel-link">Create the first one &rarr;</a></div>
                        </div>
                    @else
                        @php $__maxCombo = max(1, $combinationBreakdown->max('students_count')); @endphp
                        @foreach($combinationBreakdown as $combo)
                            <div class="combo-row">
                                <div class="combo-code">{{ $combo->code }}</div>
                                <div class="combo-body">
                                    <div class="name">{{ $combo->name }}</div>
                                    <div class="progress-track">
                                        <div class="progress-fill"
                                            style="width: {{ $combo->students_count > 0 ? max(4, round($combo->students_count / $__maxCombo * 100)) : 0 }}%;">
                                        </div>
                                    </div>
                                </div>
                                <div class="combo-count">{{ $combo->students_count }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>

                @if($isSystemAdmin)
                    <div class="card-panel">
                        <div class="panel-head">
                            <h3 class="panel-title"><i class="fa fa-school"></i> Recently Registered Schools</h3>
                            <a href="{{ route('school.allSchools') }}" class="panel-link">View all &rarr;</a>
                        </div>
                        @if($recentSchools->isEmpty())
                            <div class="empty-state"><i class="fa fa-school"></i> No schools registered yet.</div>
                        @else
                            <ul class="list-clean">
                                @foreach($recentSchools as $s)
                                    <li>
                                        <div class="list-avatar">{{ strtoupper(substr($s->House, 0, 2)) }}</div>
                                        <div class="list-main">
                                            <div class="t1">{{ $s->House }}</div>
                                            <div class="t2">{{ $s->Number }} &middot; {{ $s->Location }}</div>
                                        </div>
                                        <span
                                            class="list-tag {{ $s->school_status ? '' : 'gray' }}">{{ $s->school_status ? 'Active' : 'Inactive' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @else
                    <div class="card-panel">
                        <div class="panel-head">
                            <h3 class="panel-title"><i class="fa fa-user-plus"></i> Recently Added Students</h3>
                            <a href="{{ route('students.all.students') }}" class="panel-link">View all &rarr;</a>
                        </div>
                        @if($recentStudents->isEmpty())
                            <div class="empty-state">
                                <i class="fa fa-user-plus"></i> No students added yet.
                                <div style="margin-top:8px;"><a href="{{ route('students.add.new.student') }}"
                                        class="panel-link">Register your first student &rarr;</a></div>
                            </div>
                        @else
                            <ul class="list-clean">
                                @foreach($recentStudents as $st)
                                    <li>
                                        <div class="list-avatar">{{ strtoupper(substr($st->Student_Name ?? '?', 0, 2)) }}</div>
                                        <div class="list-main">
                                            <div class="t1">{{ $st->Student_Name }}</div>
                                            <div class="t2">{{ $st->Student_ID }}</div>
                                        </div>
                                        <span
                                            class="list-tag">{{ \Illuminate\Support\Str::contains($st->Student_ID, '-UACE-') ? 'UACE' : (\Illuminate\Support\Str::contains($st->Student_ID, '-UCE-') ? 'UCE' : 'PLE') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ============== Quick Actions — full width ============== --}}
            <div class="card-panel" style="margin-bottom: 18px;">
                <div class="panel-head">
                    <h3 class="panel-title"><i class="fa fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="qa-grid">
                    <a href="{{ route('students.add.new.student') }}" class="qa-btn"><i
                            class="fa fa-user-plus"></i><span>Register Student</span></a>
                    <a href="{{ route('subject.registration.index') }}" class="qa-btn"><i
                            class="fa fa-clipboard-list"></i><span>Subject Registration</span></a>
                    <a href="{{ route('enter.marks') }}" class="qa-btn"><i class="fa fa-pen-to-square"></i><span>Enter
                            Marks</span></a>
                    <a href="{{ route('reports.dashboard') }}" class="qa-btn"><i
                            class="fa fa-chart-line"></i><span>Reports</span></a>
                    @if($isSystemAdmin)
                        <a href="{{ route('combination.management.index') }}" class="qa-btn"><i
                                class="fa fa-layer-group"></i><span>Combinations</span></a>
                        <a href="{{ route('subject.management.index') }}" class="qa-btn"><i
                                class="fa fa-book"></i><span>Subjects</span></a>
                        <a href="{{ route('users.school.register') }}" class="qa-btn"><i
                                class="fa fa-school-flag"></i><span>Register School</span></a>
                    @else
                        <a href="{{ route('subject.management.index') }}" class="qa-btn"><i
                                class="fa fa-book"></i><span>Subjects</span></a>
                    @endif
                </div>
            </div>

            {{-- ============== Announcements + Top Schools by Enrollment — 50/50 ============== --}}
            <div class="dash-grid {{ $isSystemAdmin ? 'row-2b' : '' }}">

                <div class="card-panel">
                    <div class="panel-head">
                        <h3 class="panel-title"><i class="fa fa-bullhorn"></i> Announcements</h3>
                        <a href="{{ route('notifications.inbox') }}" class="panel-link" style="text-decoration:none;">Inbox &rarr;</a>
                    </div>
                    @if($recentBroadcasts->isEmpty())
                        <div class="empty-state"><i class="fa fa-bullhorn"></i> No announcements yet.</div>
                    @else
                        @foreach($recentBroadcasts as $msg)
                            <div class="announce-item">
                                <div class="announce-dot {{ $msg->priority }}"></div>
                                <div class="announce-body">
                                    <div class="t1">{{ $msg->subject }}</div>
                                    <div class="t2">{{ optional($msg->sender)->name ?? 'WAKATA' }} &middot;
                                        {{ \Carbon\Carbon::parse($msg->created_at)->diffForHumans() }}</div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                @if($isSystemAdmin)
                    <div class="card-panel">
                        <div class="panel-head">
                            <h3 class="panel-title"><i class="fa fa-trophy"></i> Top Schools by Enrollment</h3>
                        </div>
                        @if($topSchools->isEmpty())
                            <div class="empty-state"><i class="fa fa-trophy"></i> No student data yet.</div>
                        @else
                            <ul class="list-clean">
                                @foreach($topSchools as $i => $ts)
                                    <li>
                                        <div class="list-avatar">{{ $i + 1 }}</div>
                                        <div class="list-main">
                                            <div class="t1">{{ $ts->House ?? 'Unassigned' }}</div>
                                        </div>
                                        <span class="list-tag">{{ $ts->total }} students</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
    </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const progressCtx = document.getElementById('progressChart');
        new Chart(progressCtx, {
            type: 'bar',
            data: {
                labels: @json(collect($registrationProgress)->pluck('label')->values()),
                datasets: [
                    {
                        label: 'Registration Complete %',
                        data: @json(collect($registrationProgress)->pluck('pct')->values()),
                        backgroundColor: '#043AA1',
                        borderRadius: 6,
                        maxBarThickness: 46,
                    },
                    {
                        label: 'Marks Entry Complete %',
                        data: @json(collect($marksEntryProgress)->pluck('pct')->values()),
                        backgroundColor: '#E0A215',
                        borderRadius: 6,
                        maxBarThickness: 46,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } }
            }
        });

        const categoryCtx = document.getElementById('categoryChart');
        const categoryLabels = ['UCE', 'UACE'@if($studentsByCategory['PLE'] > 0), 'PLE'@endif];
        const categoryData = [{{ $studentsByCategory['UCE'] }}, {{ $studentsByCategory['UACE'] }}@if($studentsByCategory['PLE'] > 0), {{ $studentsByCategory['PLE'] }}@endif];

        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryData,
                    backgroundColor: ['#043AA1', '#1D7FBF', '#E0A215'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                cutout: '68%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }
            }
        });

    </script>
@endsection