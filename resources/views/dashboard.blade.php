@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="dash">
    <header class="dash-head">
        <div>
            <h1>Dashboard</h1>
            <p class="dash-lede">{{ $globalVariables?->locale ?: 'Records' }} · Active records and updating progress</p>
        </div>
        <button type="button" class="primary-btn" id="open-baptisee" aria-haspopup="dialog">
            New Baptisee <span class="badge" id="bap-count"></span>
        </button>
    </header>

    <!-- Hero: the one bold element -->
    <section class="hero" aria-labelledby="hero-label">
        <div class="hero-num">
            <span class="hero-value" id="hero-total">0</span>
            <span class="hero-label" id="hero-label">active records</span>
        </div>
        <div class="hero-split">
            <div class="split-bar" id="split-bar" role="img" aria-label="Active records by kalagayan"></div>
            <ul class="split-legend" id="split-legend"></ul>
        </div>
        <div class="hero-side">
            <span class="hero-side-value" id="hero-inactive">0</span>
            <span class="hero-side-label">inactive</span>
        </div>
    </section>

    <div class="grid">
        <section class="panel span-7" aria-labelledby="purok-title">
            <h2 id="purok-title">Active records by purok</h2>
            <div class="chart-box" style="height:250px"><canvas id="purok-chart" role="img" aria-label="Active records by purok"></canvas></div>
            <ul class="legend" id="status-legend"></ul>
        </section>

        <section class="panel span-5" aria-labelledby="monitor-title">
            <h2 id="monitor-title">Updating progress</h2>
            <div class="gauges">
                <figure class="gauge" data-value="{{ number_format($dashboardMetrics['r210Updating'], 2, '.', '') }}">
                    <svg viewBox="0 0 120 120" role="img" aria-label="R2-10 endorsement completion"><circle class="g-track" cx="60" cy="60" r="50"/><circle class="g-bar" cx="60" cy="60" r="50" transform="rotate(-90 60 60)"/><text class="g-text" x="60" y="60">0%</text></svg>
                    <figcaption>R2-10 Updating</figcaption>
                </figure>
                <figure class="gauge" data-value="{{ number_format($dashboardMetrics['registryCompletion'], 2, '.', '') }}">
                    <button type="button" class="gauge-btn" id="open-registry" aria-haspopup="dialog">
                        <svg viewBox="0 0 120 120" role="img" aria-label="Registry Updating"><circle class="g-track" cx="60" cy="60" r="50"/><circle class="g-bar" cx="60" cy="60" r="50" transform="rotate(-90 60 60)"/><text class="g-text" x="60" y="60">0%</text></svg>
                        <span class="cap">Registry Updating</span>
                        <span class="cap-hint">See what's missing</span>
                    </button>
                </figure>
                <figure class="gauge" data-value="{{ number_format($dashboardMetrics['localeActivePercentage'], 2, '.', '') }}">
                    <svg viewBox="0 0 120 120" role="img" aria-label="Active records as a percentage of Locale S Number"><circle class="g-track" cx="60" cy="60" r="50"/><circle class="g-bar" cx="60" cy="60" r="50" transform="rotate(-90 60 60)"/><text class="g-text" x="60" y="60">0%</text></svg>
                    <figcaption>LSP / Locale</figcaption>
                </figure>
            </div>
        </section>

        <section class="panel span-4" aria-labelledby="gender-title">
            <h2 id="gender-title">Active records by gender</h2>
            <div class="gender-row">
                <div class="gender-top"><span>Female</span><strong id="pct-female">0%</strong></div>
                <div class="gbar" id="bar-female" role="img" aria-label="Female share of active records"></div>
            </div>
            <div class="gender-row">
                <div class="gender-top"><span>Male</span><strong id="pct-male">0%</strong></div>
                <div class="gbar" id="bar-male" role="img" aria-label="Male share of active records"></div>
            </div>
            <ul class="legend" id="gender-legend"></ul>
        </section>

        <section class="panel span-4" aria-labelledby="pie-title">
            <h2 id="pie-title">Records by kalagayan</h2>
            <div class="chart-box" style="height:190px"><canvas id="pie-chart" role="img" aria-label="Records by kalagayan"></canvas></div>
            <ul class="legend legend-values" id="pie-legend"></ul>
        </section>

        <section class="panel span-4" aria-labelledby="locale-title">
            <div class="panel-row">
                <h2 id="locale-title">Records by CFO group</h2>
                <div class="seg" role="radiogroup" aria-label="Kalagayan" id="locale-seg"></div>
            </div>
            <div class="chart-box chart-doughnut" style="height:190px">
                <canvas id="locale-chart" role="img" aria-label="Records by CFO group"></canvas>
                <div class="chart-center" aria-hidden="true"><strong id="locale-total">0</strong><span>RECORDS</span></div>
            </div>
            <ul class="legend legend-values" id="locale-legend"></ul>
        </section>

        <section class="panel span-12" aria-labelledby="ot-title">
            <div class="panel-row">
                <h2 id="ot-title">Records over time</h2>
                <ul class="legend" id="ot-legend"></ul>
            </div>
            <div class="ot">
                <div class="ot-chart">
                    <canvas id="ot-chart" role="img" aria-label="Records over time"></canvas>
                    <p class="ot-empty" id="ot-empty" hidden>No data for the selected period.</p>
                </div>
                <aside class="filters">
                    <div class="filter-head">
                        <span>Month</span>
                        <button type="button" class="link-btn" id="month-clear">All periods</button>
                    </div>
                    <div class="chips months" id="tl-months" role="group" aria-label="Month filter"></div>
                    <p class="hint" id="month-hint">Select a month, then another to pick a range.</p>
                    <div class="filter-head">
                        <span>Purok</span>
                        <button type="button" class="link-btn" id="purok-clear">All puroks</button>
                    </div>
                    <div class="chips" id="purok-list" role="group" aria-label="Purok filter"></div>
                    <p class="hint">Select several puroks to combine them.</p>
                </aside>
            </div>
        </section>
    </div>

    <dialog class="modal" id="baptisee-modal" aria-labelledby="bap-title">
        <div class="modal-head">
            <div><h2 id="bap-title">New baptisees</h2><p id="bap-sub"></p></div>
            <button type="button" class="modal-x" data-close aria-label="Close">&times;</button>
        </div>
        <div class="modal-body" id="bap-body"></div>
    </dialog>

    <dialog class="modal modal-wide" id="registry-modal" aria-labelledby="reg-title">
        <div class="modal-head">
            <div><h2 id="reg-title">Registry updating monitoring</h2><p>MS, UWP, and MK records only. Blank fields and Registry values of RFID are counted as missing. Click any count to view the records.</p></div>
            <button type="button" class="modal-x" data-close aria-label="Close">&times;</button>
        </div>
        <div class="modal-body" id="reg-body"></div>
    </dialog>

    <dialog class="modal" id="registry-details-modal" aria-labelledby="registry-details-title">
        <div class="modal-head">
            <div><h2 id="registry-details-title">Records missing data</h2><p id="registry-details-sub"></p></div>
            <button type="button" class="modal-x" data-close aria-label="Close">&times;</button>
        </div>
        <div class="modal-body" id="registry-details-body"></div>
    </dialog>
</div>
@endsection

@push('styles')
<style>
    .dash {
        --bg: #eef1f4; --card: #fff; --ink: #10233a; --muted: #5b6b7c; --line: #dde3ea;
        --ms: #0f4c81; --uwp: #e07a1f; --mk: #2a9d8f; --off: #9aa5b1;
        max-width: 1500px; margin: 0 auto; padding: 1.25rem; border-radius: 18px;
        background: var(--bg); color: var(--ink); font-variant-numeric: tabular-nums;
    }
    .dash h1, .dash h2, .dash p, .dash ul, .dash figure { margin: 0; padding: 0; }
    .dash ul { list-style: none; }

    .dash-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .dash-head h1 { font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 800; letter-spacing: -.03em; line-height: 1.05; }
    .dash-lede { margin-top: .3rem; color: var(--muted); font-size: .95rem; }
    .primary-btn { display: inline-flex; align-items: center; gap: .6rem; padding: .7rem 1.2rem; border: 0; border-radius: 10px; background: var(--ink); color: #fff; font: inherit; font-size: .92rem; font-weight: 700; cursor: pointer; }
    .primary-btn:hover { background: #1b3a5c; }
    .badge { min-width: 1.5rem; padding: .05rem .45rem; border-radius: 99px; background: #fff; color: var(--ink); font-size: .78rem; text-align: center; }
    .badge:empty { display: none; }

    .gauge-btn { display: block; width: 100%; padding: .4rem; border: 1px solid transparent; border-radius: 10px; background: none; color: inherit; font: inherit; cursor: pointer; }
    .gauge-btn:hover { border-color: var(--line); background: var(--bg); }
    .cap { display: block; margin-top: .5rem; font-size: .85rem; font-weight: 650; }
    .cap-hint { display: block; margin-top: .1rem; color: var(--ms); font-size: .75rem; font-weight: 600; }

    /* Modals */
    .modal { width: min(900px, calc(100vw - 2rem)); max-height: calc(100vh - 2rem); padding: 0; border: 0; border-radius: 14px; background: var(--card); color: var(--ink); box-shadow: 0 24px 60px rgba(16,35,58,.35); overflow: hidden; }
    .modal-wide { width: min(1140px, calc(100vw - 2rem)); }
    .modal[open] { display: flex; flex-direction: column; }
    .modal::backdrop { background: rgba(16,35,58,.55); }
    .modal-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--line); }
    .modal-head h2 { font-size: 1.25rem; font-weight: 800; letter-spacing: -.02em; }
    .modal-head p { margin-top: .2rem; color: var(--muted); font-size: .85rem; }
    .modal-x { flex: none; width: 34px; height: 34px; border: 0; border-radius: 8px; background: var(--bg); color: var(--ink); font-size: 1.4rem; line-height: 1; cursor: pointer; }
    .modal-x:hover { background: var(--line); }
    .modal-body { padding: 1.1rem 1.4rem 1.4rem; overflow: auto; }

    .tbl { width: 100%; border-collapse: collapse; font-size: .88rem; }
    .tbl th { padding: .6rem .75rem; border-bottom: 1px solid var(--line); color: var(--muted); font-size: .78rem; font-weight: 700; text-align: left; white-space: nowrap; }
    .tbl td { padding: .7rem .75rem; border-bottom: 1px solid var(--line); }
    .tbl .name { font-weight: 700; }
    .tbl small { display: block; margin-top: .1rem; color: var(--muted); font-size: .75rem; }
    .tag { display: inline-block; padding: .15rem .6rem; border-radius: 99px; color: #fff; font-size: .75rem; font-weight: 700; }
    .empty { padding: 3rem 1rem; text-align: center; color: var(--muted); }
    .empty strong { display: block; margin-bottom: .3rem; color: var(--ink); font-size: 1.15rem; }

    .reg { display: block; }
    .tbl-wrap { overflow-x: auto; }
    .reg .tbl { min-width: 760px; table-layout: fixed; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
    .reg .tbl th, .reg .tbl td { width: auto; text-align: center; vertical-align: middle; }
    .reg .tbl th:first-child, .reg .tbl td:first-child { position: sticky; left: 0; width: 140px; background: var(--card); text-align: left; font-weight: 700; z-index: 1; }
    .reg .tbl thead th { position: sticky; top: 0; background: #f2f6fa; color: var(--ink); z-index: 2; }
    .reg .tbl thead th:first-child { background: #f2f6fa; z-index: 3; }
    .reg .tbl tbody tr:nth-child(even) td:not(.gap-cell):not(.ok-cell):not(.tot) { background: #fafbfd; }
    .reg .gap-cell { background: #fff4e8; color: #964508; font-weight: 700; }
    .reg .ok-cell { color: var(--off); }
    .reg-count { display: inline-grid; min-width: 2.2rem; min-height: 2rem; place-items: center; padding: .2rem .55rem; border: 1px solid #f3d4b6; border-radius: 999px; background: #fff; color: #964508; font: inherit; font-weight: 750; cursor: pointer; }
    .reg-count:hover { border-color: var(--ms); background: #eef5fb; color: var(--ms); }
    .reg-count.is-zero { border-color: transparent; background: transparent; color: #8995a2; }
    .reg-count.is-zero:hover { border-color: var(--line); background: #f5f8fb; color: var(--ms); }
    .reg .tot { background: #eef3f8; font-weight: 700; }
    .reg tfoot td { border-top: 2px solid #c8d3df; background: #f6f8fb; font-weight: 700; }
    .reg-summary { margin-top: 1.3rem; padding: 1rem; border: 1px solid var(--line); border-radius: 10px; background: #fafbfd; }
    .reg-summary h3 { margin: 0 0 .8rem; font-size: 1rem; font-weight: 800; }
    .reg-summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
    .sum-item { min-width: 0; padding: .7rem; border: 1px solid var(--line); border-radius: 8px; background: var(--card); }
    .sum-top { display: flex; justify-content: space-between; align-items: baseline; gap: .5rem; font-size: .82rem; }
    .sum-top span:first-child { overflow-wrap: anywhere; }
    .sum-top span:last-child { flex: none; font-weight: 750; }
    .meter { height: 7px; margin-top: .55rem; border-radius: 99px; background: var(--bg); overflow: hidden; }
    .meter span { display: block; height: 100%; border-radius: inherit; }
    @media (max-width: 900px) { .reg-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 560px) { .reg-summary-grid { grid-template-columns: 1fr; } }

    /* Hero */
    .hero {
        display: grid; grid-template-columns: auto minmax(0, 1fr) auto; align-items: center; gap: 2rem;
        padding: 1.6rem 1.8rem; margin-bottom: .75rem; border-radius: 14px; background: var(--ink); color: #fff;
    }
    .hero-num { display: grid; }
    .hero-value { font-size: clamp(3.4rem, 8vw, 5.6rem); font-weight: 800; line-height: .95; letter-spacing: -.04em; }
    .hero-label { margin-top: .4rem; color: #a9bbcf; font-size: .95rem; }
    .split-bar { display: flex; height: 26px; gap: 3px; }
    .split-bar span { display: block; height: 100%; width: 0; border-radius: 3px; transition: width .9s cubic-bezier(.2,.8,.2,1); }
    .split-legend { display: flex; flex-wrap: wrap; gap: .4rem 1.4rem; margin-top: .8rem; font-size: .88rem; color: #d6e0ea; }
    .split-legend li { display: flex; align-items: center; gap: .4rem; }
    .split-legend strong { color: #fff; }
    .hero-side { display: grid; justify-items: end; padding-left: 2rem; border-left: 1px solid rgba(255,255,255,.18); }
    .hero-side-value { font-size: 2rem; font-weight: 750; line-height: 1; }
    .hero-side-label { margin-top: .3rem; color: #a9bbcf; font-size: .85rem; }

    /* Grid + panels */
    .grid { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: .75rem; }
    .span-4 { grid-column: span 4; } .span-5 { grid-column: span 5; } .span-7 { grid-column: span 7; } .span-12 { grid-column: span 12; }
    .panel { min-width: 0; padding: 1.15rem 1.25rem; border: 1px solid var(--line); border-radius: 12px; background: var(--card); }
    .panel h2 { font-size: 1rem; font-weight: 700; letter-spacing: -.01em; }
    .panel > h2 { margin-bottom: .9rem; }
    .panel-row { display: flex; justify-content: space-between; align-items: center; gap: .75rem; flex-wrap: wrap; margin-bottom: .9rem; }
    .chart-box { position: relative; min-width: 0; }
    .chart-center { position: absolute; top: 50%; left: 50%; display: grid; justify-items: center; gap: 2px; min-width: 74px; padding: .15rem .3rem; transform: translate(-50%, -50%); border-radius: 8px; background: var(--card); pointer-events: none; }
    .chart-center strong { color: var(--ink); font-size: 1.3rem; font-weight: 800; line-height: 1.1; }
    .chart-center span { color: var(--muted); font-size: .58rem; font-weight: 750; letter-spacing: .04em; }

    .legend { display: flex; flex-wrap: wrap; gap: .3rem 1rem; margin-top: .7rem; font-size: .8rem; color: var(--muted); }
    .legend li { display: flex; align-items: center; gap: .4rem; }
    .panel-row .legend { margin-top: 0; }
    .legend i, .split-legend i { width: 10px; height: 10px; border-radius: 2px; display: inline-block; flex: none; }
    .legend-values { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .legend-values li { justify-content: space-between; padding: .25rem 0; border-bottom: 1px solid var(--line); }
    .legend-values li span { display: flex; align-items: center; gap: .4rem; }
    .legend-values strong { color: var(--ink); }

    /* Gauges */
    .gauges { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; }
    .gauge { text-align: center; }
    .gauge svg { width: min(100%, 140px); height: auto; }
    .gauge figcaption { margin-top: .5rem; font-size: .85rem; font-weight: 650; }
    .g-track { fill: none; stroke: var(--line); stroke-width: 10; }
    .g-bar { fill: none; stroke: var(--ms); stroke-width: 10; stroke-linecap: round; stroke-dasharray: 314.16; stroke-dashoffset: 314.16; transition: stroke-dashoffset 1.1s cubic-bezier(.2,.8,.2,1); }
    .g-text { fill: var(--ink); font-size: 17px; font-weight: 750; text-anchor: middle; dominant-baseline: central; }

    /* Gender */
    .gender-row + .gender-row { margin-top: 1rem; }
    .gender-top { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: .35rem; font-size: .9rem; }
    .gender-top strong { font-size: 1.5rem; letter-spacing: -.02em; }
    .gbar { display: flex; height: 16px; overflow: hidden; border-radius: 4px; background: var(--bg); }
    .gbar span { display: block; height: 100%; width: 0; transition: width .9s cubic-bezier(.2,.8,.2,1); }

    /* Segmented control + chips */
    .seg { display: inline-flex; gap: 3px; padding: 4px; border: 1px solid var(--line); border-radius: 10px; background: #f4f7fa; }
    .seg button { min-width: 42px; padding: .38rem .72rem; border: 0; border-radius: 7px; background: transparent; color: var(--muted); font: inherit; font-size: .78rem; font-weight: 750; cursor: pointer; transition: background .18s ease, color .18s ease, box-shadow .18s ease; }
    .seg button:hover { color: var(--ink); background: rgba(255,255,255,.7); }
    .seg button[aria-checked="true"] { background: var(--card); color: var(--ms); box-shadow: 0 1px 3px rgba(16,35,58,.16); }

    .ot { display: grid; grid-template-columns: minmax(0, 1fr) 260px; gap: 1.25rem; }
    .ot-chart { position: relative; height: 340px; min-width: 0; }
    .ot-empty { position: absolute; inset: 0; display: grid; place-items: center; color: var(--muted); font-size: .9rem; }
    .ot-empty[hidden] { display: none; }
    .filters { padding-left: 1.25rem; border-left: 1px solid var(--line); }
    .filter-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: .5rem; font-size: .82rem; font-weight: 700; }
    .link-btn { padding: 0; border: 0; background: none; color: var(--ms); font: inherit; font-size: .78rem; font-weight: 650; cursor: pointer; }
    .link-btn:hover { text-decoration: underline; }
    .chips { display: flex; flex-wrap: wrap; gap: .3rem; }
    .chips.months { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .chip { padding: .38rem .5rem; border: 1px solid var(--line); border-radius: 6px; background: var(--card); color: var(--ink); font: inherit; font-size: .78rem; cursor: pointer; }
    .chip:hover { border-color: var(--ink); }
    .chip[aria-pressed="true"] { border-color: var(--ink); background: var(--ink); color: #fff; }
    .chip:disabled { opacity: .4; cursor: default; }
    .hint { margin: .5rem 0 1.1rem; color: var(--muted); font-size: .75rem; }

    .chart-error { padding: 1rem; color: var(--muted); font-size: .83rem; text-align: center; }
    .dash a:focus-visible, .dash button:focus-visible { outline: 3px solid #5b9dff; outline-offset: 2px; }
    @media (prefers-reduced-motion: reduce) { .dash *, .dash *::before { transition: none !important; } }

    @media (max-width: 1100px) {
        .span-4 { grid-column: span 6; } .span-5, .span-7 { grid-column: span 12; }
        .grid > .panel:nth-child(5) { grid-column: span 12; }
    }
    @media (max-width: 800px) {
        .hero { grid-template-columns: 1fr; gap: 1.1rem; }
        .hero-side { justify-items: start; padding: 1rem 0 0; border-left: 0; border-top: 1px solid rgba(255,255,255,.18); }
        .ot { grid-template-columns: 1fr; }
        .filters { padding: 1rem 0 0; border-left: 0; border-top: 1px solid var(--line); }
    }
    @media (max-width: 640px) {
        .span-4 { grid-column: span 12; }
        .gauges { grid-template-columns: 1fr; }
        .ot-chart { height: 280px; }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const hasChart = typeof Chart !== 'undefined';
    const $ = id => document.getElementById(id);
    const css = getComputedStyle($('hero-total').closest('.dash'));
    const INK = css.getPropertyValue('--ink').trim(), MUTED = css.getPropertyValue('--muted').trim(), LINE = css.getPropertyValue('--line').trim();
    if (hasChart) {
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.color = MUTED;
        Chart.defaults.animation = reduceMotion ? false : { duration: 800, easing: 'easeOutQuart' };
    }

    /* ---------- Database-backed dashboard metrics ---------- */
    const DASHBOARD = @json($dashboardMetrics);
    const COLORS = { MS: '#0f4c81', UWP: '#e07a1f', MK: '#2a9d8f', INACTIVE: '#9aa5b1', UNSPECIFIED: '#c8ced5' };
    const EXTRA_COLORS = ['#6a4c93', '#1982c4', '#d1495b', '#4f7c5a', '#a65c2e'];
    const colorFor = (code, index = 0) => COLORS[code] || EXTRA_COLORS[index % EXTRA_COLORS.length];
    const KEYS = DASHBOARD.activeStatuses;
    const NAMES = Object.fromEntries(KEYS.map(code => [code, code]));
    const statuses = DASHBOARD.statuses;
    const active = Object.fromEntries(KEYS.map(code => [code, statuses.find(status => status.code === code)?.count || 0]));
    const totalActive = DASHBOARD.activeCount;
    const inactive = DASHBOARD.inactiveCount;
    const puroks = DASHBOARD.puroks.map(p => ({
        id: p.id,
        ...Object.fromEntries(KEYS.map(code => [code, p.counts[code] || 0])),
        total: p.total,
        recordTotal: p.recordTotal,
    }));
    const gender = DASHBOARD.gender;
    const locale = Object.fromEntries(KEYS.map(code => [code, DASHBOARD.groupLabels.map(label => DASHBOARD.groupCounts[code]?.[label] || 0)]));
    const LOCALE = DASHBOARD.groupLabels.map((label, index) => [label, ['#4169a1', '#e59a3a', '#3b9b88'][index]]);
    const MONTHS = DASHBOARD.monthLabels;
    const DATA_MONTHS = MONTHS.length;
    const overtime = DASHBOARD.monthlyPurokCounts;
    const REG_FIELDS = DASHBOARD.registryReport.map(field => [field.label, field.missing, field.details]);
    const add = a => a.reduce((x, y) => x + y, 0);
    const registryPuroks = DASHBOARD.registryPuroks;
    const regTotals = registryPuroks.map(p => p.recordTotal), regAll = DASHBOARD.registryRecordCount;
    const regOverall = regAll * REG_FIELDS.length;
    const regPct = DASHBOARD.registryCompletion;
    const regGauge = document.getElementById('open-registry').closest('.gauge');
    regGauge.dataset.value = regPct.toFixed(2);
    regGauge.querySelector('svg').setAttribute('aria-label', `Registry Updating ${regPct.toFixed(2)}%`);
    document.querySelectorAll('.gauge')[0].querySelector('svg').setAttribute('aria-label', `R2-10 Updating ${DASHBOARD.r210Updating.toFixed(2)}%`);
    document.querySelectorAll('.gauge')[2].querySelector('svg').setAttribute('aria-label', `LSP / Locale: ${DASHBOARD.activeCount} active records of ${DASHBOARD.localeSNumberTarget} S Number (${DASHBOARD.localeActivePercentage.toFixed(2)}%)`);
    document.querySelectorAll('.gauge')[2].dataset.value = DASHBOARD.localeActivePercentage.toFixed(2);
    document.querySelectorAll('.gauge')[0].dataset.value = DASHBOARD.r210Updating.toFixed(2);

    /* ---------- Helpers ---------- */
    const el = (tag, cls, text) => { const n = document.createElement(tag); if (cls) n.className = cls; if (text != null) n.textContent = text; return n; };
    const swatch = c => { const i = el('i'); i.style.background = c; return i; };
    function legend(id, items) {
        $(id).replaceChildren(...items.map(([name, color]) => { const li = el('li'); li.append(swatch(color), name); return li; }));
    }
    function valueLegend(id, items, total) {
        $(id).replaceChildren(...items.map(([name, color, v]) => {
            const li = el('li'), l = el('span'); l.append(swatch(color), name);
            li.append(l, el('strong', '', total ? `${v} · ${Math.round(v / total * 100)}%` : v));
            return li;
        }));
    }
    function makeChart(id, config) {
        const canvas = $(id);
        try {
            if (!hasChart) throw new Error('Chart.js missing');
            return new Chart(canvas, config);
        } catch (err) {
            console.error(`Chart "${id}" failed:`, err);
            canvas.parentElement.replaceChildren(el('p', 'chart-error', "The chart couldn't load. Reload the page to try again."));
            return null;
        }
    }
    function animateNumber(node, to, decimals, suffix) {
        if (reduceMotion) { node.textContent = to.toFixed(decimals) + suffix; return; }
        const t0 = performance.now();
        const tick = now => {
            const t = Math.min((now - t0) / 1000, 1);
            node.textContent = (to * (1 - Math.pow(1 - t, 3))).toFixed(decimals) + suffix;
            if (t < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    }
    function segments(container, parts, base) {
        const nodes = parts.map(([label, n, color]) => { const s = el('span'); s.style.background = color; s.title = `${label}: ${n}`; return [s, base ? n / base * 100 : 0]; });
        container.replaceChildren(...nodes.map(n => n[0]));
        requestAnimationFrame(() => requestAnimationFrame(() => nodes.forEach(([s, w]) => { s.style.width = w + '%'; })));
    }
    const statusItems = KEYS.map(k => [NAMES[k], colorFor(k)]);

    /* ---------- Hero ---------- */
    animateNumber($('hero-total'), totalActive, 0, '');
    animateNumber($('hero-inactive'), inactive, 0, '');
    segments($('split-bar'), KEYS.map(k => [NAMES[k], active[k], colorFor(k)]), totalActive);
    $('split-bar').setAttribute('aria-label', 'Active records: ' + KEYS.map(k => `${NAMES[k]} ${active[k]}`).join(', '));
    $('split-legend').replaceChildren(...KEYS.map(k => {
        const li = el('li'); li.append(swatch(colorFor(k)), NAMES[k] + ' ', el('strong', '', active[k])); return li;
    }));

    /* ---------- By purok ---------- */
    legend('status-legend', statusItems);
    const barLabels = {
        id: 'barLabels',
        afterDatasetsDraw(chart) {
            const { ctx } = chart; ctx.save();
            ctx.font = '600 11px ' + Chart.defaults.font.family; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            chart.data.datasets.forEach((ds, i) => chart.getDatasetMeta(i).data.forEach((bar, idx) => {
                const v = ds.data[idx];
                if (!v || Math.abs(bar.x - bar.base) < 16) return;
                ctx.fillStyle = '#fff';
                ctx.fillText(String(v), (bar.x + bar.base) / 2, bar.y);
            }));
            ctx.restore();
        },
    };
    const purokChart = makeChart('purok-chart', {
        type: 'bar',
        data: {
            labels: puroks.map(p => `${p.id === 'Unassigned' ? 'Unassigned' : `Purok ${p.id}`} · ${p.total}`),
            datasets: KEYS.map(k => ({ label: NAMES[k], data: puroks.map(p => p[k]), backgroundColor: colorFor(k), barPercentage: .7, borderSkipped: false })),
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            scales: {
                x: { stacked: true, display: false, min: 0, max: Math.max(1, ...puroks.map(p => p.total)) },
                y: { stacked: true, grid: { display: false }, border: { display: false }, ticks: { color: INK, font: { size: 12, weight: '600' } } },
            },
            plugins: { legend: { display: false } },
        },
        plugins: [barLabels],
    });
    if (purokChart) purokChart.canvas.setAttribute('aria-label',
        'Active records by purok: ' + puroks.map(p => `${p.id === 'Unassigned' ? 'Unassigned' : `Purok ${p.id}`} ${p.total} (${KEYS.map(k => `${k} ${p[k]}`).join(', ')})`).join('; '));

    /* ---------- Gauges ---------- */
    document.querySelectorAll('.gauge').forEach((g, i) => {
        const v = Number(g.dataset.value), c = 2 * Math.PI * 50;
        setTimeout(() => {
            g.querySelector('.g-bar').style.strokeDashoffset = String(c * (1 - Math.min(Math.max(v, 0), 100) / 100));
            animateNumber(g.querySelector('.g-text'), v, 2, '%');
        }, reduceMotion ? 0 : 150 + i * 120);
    });

    /* ---------- Gender ---------- */
    ['female', 'male'].forEach(key => {
        const g = gender[key], n = add(KEYS.map(k => g[k] || 0)), pct = totalActive ? n / totalActive * 100 : 0;
        segments($(`bar-${key}`), KEYS.map(k => [NAMES[k], g[k] || 0, colorFor(k)]), totalActive);
        $(`bar-${key}`).setAttribute('aria-label', `${key}: ${pct.toFixed(2)}% of active records (${KEYS.map(k => `${k} ${g[k] || 0}`).join(', ')})`);
        animateNumber($(`pct-${key}`), pct, 2, '%');
    });
    legend('gender-legend', statusItems);

    /* ---------- Doughnuts ---------- */
    const doughnutCenter = {
        id: 'doughnutCenter',
        afterDraw(chart) {
            if (chart.canvas.id === 'locale-chart') return;
            if (!chart.chartArea) return;
            const values = chart.data.datasets[0]?.data || [];
            const total = chart.$centerTotal ?? values.reduce((sum, value) => sum + (Number(value) || 0), 0);
            const { left, right, top, bottom } = chart.chartArea;
            const x = (left + right) / 2, y = (top + bottom) / 2;
            const { ctx } = chart;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = INK;
            ctx.font = `750 20px ${Chart.defaults.font.family}`;
            ctx.fillText(String(total), x, y - 5);
            ctx.fillStyle = MUTED;
            ctx.font = `700 9px ${Chart.defaults.font.family}`;
            ctx.fillText('RECORDS', x, y + 13);
            ctx.restore();
        },
    };
    const doughnut = (id, labels, data, colors) => makeChart(id, {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors, borderColor: '#fff', borderWidth: 3, borderRadius: 4, hoverOffset: 6, spacing: 2 }] },
        options: {
            cutout: '68%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const value = Number(context.raw) || 0;
                            const total = context.dataset.data.reduce((sum, item) => sum + (Number(item) || 0), 0);
                            const percent = total ? ` (${(value / total * 100).toFixed(1)}%)` : '';
                            return `${context.label}: ${value}${percent}`;
                        },
                    },
                },
            },
        },
        plugins: [doughnutCenter],
    });

    const pieItems = ['MS', 'MK', 'UWP'].map(status => [
        status,
        colorFor(status),
        DASHBOARD.kalagayanCounts[status] || 0,
    ]);
    const pie = doughnut('pie-chart', pieItems.map(i => i[0]), pieItems.map(i => i[2]), pieItems.map(i => i[1]));
    valueLegend('pie-legend', pieItems, add(pieItems.map(i => i[2])));
    if (pie) pie.canvas.setAttribute('aria-label', 'Records by kalagayan: ' + pieItems.map(i => `${i[0]} ${i[2]}`).join(', '));

    const GROUP_KEYS = ['MS', 'MK', 'UWP'];
    const allGroupCounts = LOCALE.map((_, index) => GROUP_KEYS.reduce((sum, key) => sum + (locale[key]?.[index] || 0), 0));
    locale.ALL = allGroupCounts;
    const localeChart = doughnut('locale-chart', LOCALE.map(l => l[0]), allGroupCounts, LOCALE.map(l => l[1]));
    if (localeChart) localeChart.$centerTotal = add(allGroupCounts);
    function showLocale(kind) {
        const data = locale[kind] || LOCALE.map(() => 0);
        const groupTotal = add(data);
        $('locale-total').textContent = String(groupTotal);
        if (localeChart) {
            localeChart.data.datasets[0].data = data;
            localeChart.$centerTotal = groupTotal;
            localeChart.update('none');
            const statusLabel = kind === 'ALL' ? 'All MS, MK, and UWP' : kind;
            localeChart.canvas.setAttribute('aria-label', `${statusLabel}: ${groupTotal} records in Binhi, Kadiwa, and Buklod; Kapisanan breakdown: ` + LOCALE.map((l, i) => `${l[0]} ${data[i]}`).join(', '));
        }
        valueLegend('locale-legend', LOCALE.map((l, i) => [l[0], l[1], data[i]]), groupTotal);
        document.querySelectorAll('#locale-seg button').forEach(b => b.setAttribute('aria-checked', String(b.dataset.k === kind)));
    }
    $('locale-seg').replaceChildren(...['ALL', ...GROUP_KEYS].map(k => {
        const b = el('button', '', k); b.type = 'button'; b.dataset.k = k; b.setAttribute('role', 'radio');
        b.addEventListener('click', () => showLocale(k));
        return b;
    }));
    if (GROUP_KEYS.some(key => Object.hasOwn(locale, key))) showLocale('ALL');
    else {
        valueLegend('locale-legend', LOCALE.map(([label, color]) => [label, color, 0]), 0);
        $('locale-seg').replaceChildren(el('span', 'hint', 'No active status data.'));
    }

    /* ---------- Over time ---------- */
    legend('ot-legend', statusItems);
    const state = { s: 0, e: 11, puroks: new Set() }; // empty set = all puroks
    const allIds = puroks.map(p => p.id);
    const dataset = k => ({
        label: NAMES[k], data: [], borderColor: colorFor(k), backgroundColor: colorFor(k), borderWidth: 2.5, tension: .25,
        pointRadius: 4, pointHoverRadius: 6, pointBackgroundColor: '#fff', pointBorderWidth: 2,
    });
    const otChart = makeChart('ot-chart', {
        type: 'line',
        data: { labels: [], datasets: KEYS.map(dataset) },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { grid: { display: false }, border: { color: LINE } },
                y: { min: 0, grid: { color: LINE }, border: { display: false }, title: { display: true, text: 'Records' } },
            },
            plugins: { legend: { display: false }, tooltip: { padding: 10, boxPadding: 4 } },
        },
    });

    function renderOvertime() {
        const ids = state.puroks.size ? [...state.puroks] : allIds;
        const idxs = [];
        for (let m = state.s; m <= Math.min(state.e, DATA_MONTHS - 1); m++) idxs.push(m);
        $('ot-empty').hidden = idxs.length > 0;
        if (!otChart) return;
        const series = k => idxs.map(m => ids.reduce((t, id) => t + (overtime[m]?.[id]?.[k] || 0), 0));
        const s = Object.fromEntries(KEYS.map(k => [k, series(k)]));
        otChart.data.labels = idxs.map(m => MONTHS[m]);
        KEYS.forEach((k, i) => { otChart.data.datasets[i].data = s[k]; });
        otChart.update();
        otChart.canvas.setAttribute('aria-label',
            `Records over time for ${ids.length === allIds.length ? 'all puroks' : 'Purok ' + ids.join(', ')}: ` +
            idxs.map((m, i) => `${MONTHS[m]} ${KEYS.map(k => `${k} ${s[k][i]}`).join(', ')}`).join('; '));
    }

    const monthsEl = $('tl-months');
    monthsEl.replaceChildren(...MONTHS.map((name, m) => {
        const b = el('button', 'chip', name); b.type = 'button';
        b.setAttribute('aria-label', name);
        b.addEventListener('click', () => {
            const all = state.s === 0 && state.e === 11, single = state.s === state.e;
            if (all) state.s = state.e = m;
            else if (single && state.s === m) { state.s = 0; state.e = 11; }
            else if (single) { const a = state.s; state.s = Math.min(a, m); state.e = Math.max(a, m); }
            else state.s = state.e = m;
            renderMonths(); renderOvertime();
        });
        return b;
    }));
    function renderMonths() {
        const all = state.s === 0 && state.e === 11;
        monthsEl.querySelectorAll('.chip').forEach((b, m) => b.setAttribute('aria-pressed', String(!all && m >= state.s && m <= state.e)));
        $('month-hint').textContent = all ? 'Select a month, then another to pick a range.'
            : state.s === state.e ? `Showing ${MONTHS[state.s]}. Select another month to make a range.`
            : `Showing ${MONTHS[state.s]} to ${MONTHS[state.e]}.`;
    }
    $('month-clear').addEventListener('click', () => { state.s = 0; state.e = 11; renderMonths(); renderOvertime(); });

    function renderPuroks() {
        $('purok-list').replaceChildren(...puroks.map(p => {
            const label = p.id === 'Unassigned' ? 'Unassigned' : `Purok ${p.id}`;
            const b = el('button', 'chip', label); b.type = 'button';
            b.setAttribute('aria-pressed', String(state.puroks.has(p.id)));
            b.addEventListener('click', () => {
                if (state.puroks.has(p.id)) state.puroks.delete(p.id); else state.puroks.add(p.id);
                renderPuroks(); renderOvertime();
            });
            return b;
        }));
    }
    $('purok-clear').addEventListener('click', () => { state.puroks = new Set(); renderPuroks(); renderOvertime(); });

    /* ---------- New baptisees (baptized less than 1 year ago) ---------- */
    // Pass $newBaptisees from the controller: [{name, purok, grupo, status, evangelist, baptized_at: 'YYYY-MM-DD'}]
    const serverBaptisees = @json($newBaptisees);
    function since(from) {
        const a = new Date(from + 'T00:00:00'), now = new Date(), b = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        let months = (b.getFullYear() - a.getFullYear()) * 12 + b.getMonth() - a.getMonth();
        let anchor = new Date(a.getFullYear(), a.getMonth() + months, a.getDate());
        if (anchor > b) { months--; anchor = new Date(a.getFullYear(), a.getMonth() + months, a.getDate()); }
        return { months, days: Math.round((b - anchor) / 86400000) };
    }
    const plural = (n, w) => `${n} ${w}${n === 1 ? '' : 's'}`;
    const spanText = ({ months, days }) => months && days ? `${plural(months, 'month')} and ${plural(days, 'day')}` : months ? plural(months, 'month') : plural(days, 'day');

    function renderBaptisees() {
        const list = serverBaptisees
            .map(b => ({ ...b, span: since(b.baptized_at) }))
            .sort((x, y) => y.baptized_at.localeCompare(x.baptized_at));
        $('bap-count').textContent = list.length || '';
        $('bap-sub').textContent = list.length ? `Baptized within the last year: ${list.length} in the system.` : 'Baptized within the last year.';
        const body = $('bap-body');
        if (!list.length) {
            const box = el('div', 'empty');
            box.append(el('strong', '', 'No newly baptized records were found in the past year.'), el('span', '', 'Filtered by kalagayan: MS, UWP, and MK.'));
            body.replaceChildren(box);
            return;
        }
        const table = el('table', 'tbl');
        const head = el('tr');
        ['Name', 'Purok / Grupo', 'Status', 'Evangelist', 'Days baptized'].forEach(h => head.append(el('th', '', h)));
        const thead = el('thead'); thead.append(head); table.append(thead);
        const tbody = el('tbody');
        list.forEach(b => {
            const tr = el('tr'), status = el('td'), tag = el('span', 'tag', b.status), days = el('td', '', spanText(b.span));
            tag.style.background = colorFor(String(b.status).toUpperCase());
            status.append(tag);
            days.append(el('small', '', 'Baptized ' + new Date(b.baptized_at + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })));
            tr.append(el('td', 'name', b.name), el('td', '', `Purok ${b.purok} / ${b.grupo}`), status, el('td', '', b.evangelist || 'None'), days);
            tbody.append(tr);
        });
        table.append(tbody);
        const wrap = el('div', 'tbl-wrap'); wrap.append(table);
        body.replaceChildren(wrap);
    }

    /* ---------- Registry updating monitoring ---------- */
    const registryDetailsDialog = $('registry-details-modal');
    function showRegistryDetails(label, group, entries) {
        $('registry-details-title').textContent = `Missing ${label} - ${group}`;
        $('registry-details-sub').textContent = `${entries.length} record${entries.length === 1 ? '' : 's'}`;
        const body = $('registry-details-body');
        if (!entries.length) {
            body.replaceChildren(el('div', 'empty', 'No records are missing this field in this group.'));
        } else {
            const table = el('table', 'tbl'), head = el('tr');
            ['Name', 'Purok', 'Kalagayan'].forEach(value => head.append(el('th', '', value)));
            const thead = el('thead'); thead.append(head); table.append(thead);
            const tbody = el('tbody');
            entries.forEach(entry => {
                const row = el('tr');
                row.append(el('td', 'name', entry.name), el('td', '', entry.purok), el('td', '', entry.status));
                tbody.append(row);
            });
            table.append(tbody);
            const wrap = el('div', 'tbl-wrap'); wrap.append(table);
            body.replaceChildren(wrap);
        }
        registryDetailsDialog.showModal();
    }
    function renderRegistry() {
        const table = el('table', 'tbl'), head = el('tr');
        ['Data', ...registryPuroks.map(p => p.id === 'Unassigned' ? 'Unassigned' : `Purok ${p.id}`), 'Total'].forEach(h => head.append(el('th', '', h)));
        const thead = el('thead'); thead.append(head); table.append(thead);
        const tbody = el('tbody');
        const countButton = (value, label, group, entries) => {
            const button = el('button', `reg-count${value ? '' : ' is-zero'}`, String(value));
            button.type = 'button';
            button.setAttribute('aria-label', `${value} records missing ${label} in ${group}`);
            button.addEventListener('click', () => showRegistryDetails(label, group, entries));
            return button;
        };
        REG_FIELDS.forEach(([label, miss, details]) => {
            const tr = el('tr', '', null);
            tr.append(el('td', '', label));
            miss.forEach((value, index) => {
                const cell = el('td', value ? 'gap-cell' : 'ok-cell');
                const group = registryPuroks[index].id === 'Unassigned' ? 'Unassigned' : `Purok ${registryPuroks[index].id}`;
                cell.append(countButton(value, label, group, details[index].map(entry => ({ ...entry, purok: group }))));
                tr.append(cell);
            });
            const allEntries = details.flatMap((entries, index) => {
                const group = registryPuroks[index].id === 'Unassigned' ? 'Unassigned' : `Purok ${registryPuroks[index].id}`;
                return entries.map(entry => ({ ...entry, purok: group }));
            });
            const totalCell = el('td', 'tot');
            totalCell.append(countButton(add(miss), label, 'all puroks', allEntries));
            tr.append(totalCell);
            tbody.append(tr);
        });
        const foot = el('tfoot'), fr = el('tr');
        fr.append(el('td', '', 'Encoded / active data'));
        regTotals.forEach((t, i) => fr.append(el('td', '', `${t * REG_FIELDS.length - add(REG_FIELDS.map(f => f[1][i]))} / ${t * REG_FIELDS.length}`)));
        fr.append(el('td', 'tot', `${DASHBOARD.registryEncoded} / ${regOverall}`));
        foot.append(fr);
        table.append(tbody, foot);
        const wrap = el('div', 'tbl-wrap'); wrap.append(table);

        const summary = el('div', 'reg-summary'), summaryGrid = el('div', 'reg-summary-grid');
        summary.append(el('h3', '', 'Summary'));
        REG_FIELDS.forEach(([label, miss]) => {
            const done = regAll - add(miss), pct = regAll ? done / regAll * 100 : 0;
            const item = el('div', 'sum-item'), top = el('div', 'sum-top'), meter = el('div', 'meter'), fill = el('span');
            fill.style.width = pct + '%';
            fill.style.background = pct >= 95 ? colorFor('MK') : colorFor('UWP');
            meter.append(fill);
            top.append(el('span', '', `${label}: ${done} of ${regAll}`), el('span', '', pct.toFixed(2) + '%'));
            item.append(top, meter);
            summaryGrid.append(item);
        });
        summary.append(summaryGrid);
        const layout = el('div', 'reg'); layout.append(wrap, summary);
        $('reg-body').replaceChildren(layout);
    }

    [['open-baptisee', 'baptisee-modal'], ['open-registry', 'registry-modal']].forEach(([btn, dlgId]) => {
        const dlg = $(dlgId);
        $(btn).addEventListener('click', () => dlg.showModal());
        dlg.addEventListener('click', e => { if (e.target === dlg || e.target.closest('[data-close]')) dlg.close(); });
    });
    registryDetailsDialog.addEventListener('click', e => {
        if (e.target === registryDetailsDialog || e.target.closest('[data-close]')) registryDetailsDialog.close();
    });
    renderBaptisees();
    renderRegistry();

    renderMonths(); renderPuroks(); renderOvertime();
});
</script>
@endpush