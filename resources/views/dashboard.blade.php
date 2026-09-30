@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="dash">
    <header class="dash-head">
        <div>
            <h1>Dashboard</h1>
            <p class="dash-lede">Active records, updating progress and monthly movement.</p>
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
                <figure class="gauge" data-value="28.96">
                    <svg viewBox="0 0 120 120" role="img" aria-label="R2-10 Pendings 28.96%"><circle class="g-track" cx="60" cy="60" r="50"/><circle class="g-bar" cx="60" cy="60" r="50" transform="rotate(-90 60 60)"/><text class="g-text" x="60" y="60">0%</text></svg>
                    <figcaption>R2-10 Pendings</figcaption>
                </figure>
                <figure class="gauge" data-value="0">
                    <button type="button" class="gauge-btn" id="open-registry" aria-haspopup="dialog">
                        <svg viewBox="0 0 120 120" role="img" aria-label="Registry Updating"><circle class="g-track" cx="60" cy="60" r="50"/><circle class="g-bar" cx="60" cy="60" r="50" transform="rotate(-90 60 60)"/><text class="g-text" x="60" y="60">0%</text></svg>
                        <span class="cap">Registry Updating</span>
                        <span class="cap-hint">See what's missing</span>
                    </button>
                </figure>
                <figure class="gauge" data-value="30.03">
                    <svg viewBox="0 0 120 120" role="img" aria-label="LSP / Locale S # 30.03%"><circle class="g-track" cx="60" cy="60" r="50"/><circle class="g-bar" cx="60" cy="60" r="50" transform="rotate(-90 60 60)"/><text class="g-text" x="60" y="60">0%</text></svg>
                    <figcaption>LSP / Locale S #</figcaption>
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
            <div class="chart-box" style="height:190px"><canvas id="locale-chart" role="img" aria-label="Records by locale group"></canvas></div>
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
                        <span>Month, 2025</span>
                        <button type="button" class="link-btn" id="month-clear">All periods</button>
                    </div>
                    <div class="chips months" id="tl-months" role="group" aria-label="Months of 2025"></div>
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
            <div><h2 id="reg-title">Registry updating monitoring</h2><p>Records with no data in each column. Birthday is not counted.</p></div>
            <button type="button" class="modal-x" data-close aria-label="Close">&times;</button>
        </div>
        <div class="modal-body" id="reg-body"></div>
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

    .reg { display: grid; grid-template-columns: minmax(0, 1fr) 330px; gap: 1.5rem; }
    .tbl-wrap { overflow-x: auto; }
    .reg .tbl th, .reg .tbl td { text-align: center; }
    .reg .tbl th:first-child, .reg .tbl td:first-child { text-align: left; font-weight: 700; }
    .reg .gap-cell { background: #fdecd9; color: #964508; font-weight: 700; }
    .reg .ok-cell { color: var(--off); }
    .reg .tot { background: var(--bg); font-weight: 700; }
    .reg tfoot td { border-top: 2px solid var(--ink); border-bottom: 0; font-weight: 700; }
    .reg-summary h3 { margin: 0 0 .8rem; font-size: 1rem; font-weight: 800; }
    .sum-item + .sum-item { margin-top: .8rem; }
    .sum-top { display: flex; justify-content: space-between; gap: .5rem; font-size: .85rem; }
    .sum-top span:last-child { font-weight: 700; }
    .meter { height: 8px; margin-top: .3rem; border-radius: 99px; background: var(--bg); overflow: hidden; }
    .meter span { display: block; height: 100%; border-radius: inherit; }
    @media (max-width: 800px) { .reg { grid-template-columns: 1fr; } }

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
    .seg { display: inline-flex; padding: 2px; border-radius: 8px; background: var(--bg); }
    .seg button { padding: .3rem .7rem; border: 0; border-radius: 6px; background: transparent; color: var(--muted); font: inherit; font-size: .78rem; font-weight: 700; cursor: pointer; }
    .seg button[aria-checked="true"] { background: var(--card); color: var(--ink); box-shadow: 0 1px 2px rgba(16,35,58,.15); }

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

    /* ---------- Sample data (reconciles to 442 active records) ---------- */
    const COLORS = { ms: '#0f4c81', uwp: '#e07a1f', mk: '#2a9d8f', off: '#9aa5b1' };
    const KEYS = ['ms', 'uwp', 'mk'], NAMES = { ms: 'MS', uwp: 'UWP', mk: 'MK' };
    const puroks = [
        { id: 1, ms: 51, uwp: 11, mk: 5 }, { id: 2, ms: 24, uwp: 3, mk: 3 }, { id: 3, ms: 66, uwp: 11, mk: 11 },
        { id: 4, ms: 71, uwp: 28, mk: 7 }, { id: 5, ms: 35, uwp: 6, mk: 1 }, { id: 6, ms: 84, uwp: 12, mk: 13 },
    ].map(p => ({ ...p, total: p.ms + p.uwp + p.mk }));
    const sum = k => puroks.reduce((a, p) => a + p[k], 0);
    const active = { ms: sum('ms'), uwp: sum('uwp'), mk: sum('mk') };
    const totalActive = active.ms + active.uwp + active.mk;
    const inactive = 157;
    const gender = { female: { ms: 156, uwp: 34, mk: 18 } };
    gender.male = { ms: active.ms - 156, uwp: active.uwp - 34, mk: active.mk - 18 };
    const locale = { MS: [70, 146, 115], UWP: [12, 38, 21], MK: [6, 22, 12] };
    const LOCALE = [['Binhi', '#6a4c93'], ['Kadiwa', '#1982c4'], ['Buklod', '#d1495b']];
    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const DATA_MONTHS = 10;
    const shape = [16, 16, 14, 11, 7, 7, 5, 2, 1, 0];
    const overtime = puroks.map(p => {
        const drop = Math.round(p.ms * 0.31);
        return { ms: shape.map(s => p.ms + Math.round(s * drop / 16)), uwp: Array(DATA_MONTHS).fill(p.uwp), mk: Array(DATA_MONTHS).fill(p.mk) };
    });

    /* ---------- Registry updating: missing entries per column, per purok (birthday excluded) ---------- */
    const REG_FIELDS = [
        ['Registry', [0, 0, 0, 4, 2, 4]], ['Gender', [0, 0, 0, 0, 0, 0]], ['Grupo', [0, 0, 0, 0, 0, 1]],
        ['Kapisanan', [0, 0, 0, 2, 2, 3]], ['Baptism date', [3, 1, 4, 10, 11, 8]], ['Evangelist', [4, 0, 2, 10, 11, 6]],
        ['Entry date', [0, 0, 0, 0, 0, 1]], ['Binhi', [8, 9, 12, 11, 4, 29]], ['Purok', [0, 0, 0, 0, 0, 0]],
    ];
    const add = a => a.reduce((x, y) => x + y, 0);
    const regTotals = puroks.map(p => p.total), regAll = add(regTotals);
    const regOverall = regAll * REG_FIELDS.length;
    const regPct = (regOverall - add(REG_FIELDS.map(f => add(f[1])))) / regOverall * 100;
    const regGauge = document.getElementById('open-registry').closest('.gauge');
    regGauge.dataset.value = regPct.toFixed(2);
    regGauge.querySelector('svg').setAttribute('aria-label', `Registry Updating ${regPct.toFixed(2)}%`);

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
        const nodes = parts.map(([label, n, color]) => { const s = el('span'); s.style.background = color; s.title = `${label}: ${n}`; return [s, n / base * 100]; });
        container.replaceChildren(...nodes.map(n => n[0]));
        requestAnimationFrame(() => requestAnimationFrame(() => nodes.forEach(([s, w]) => { s.style.width = w + '%'; })));
    }
    const statusItems = KEYS.map(k => [NAMES[k], COLORS[k]]);

    /* ---------- Hero ---------- */
    animateNumber($('hero-total'), totalActive, 0, '');
    animateNumber($('hero-inactive'), inactive, 0, '');
    segments($('split-bar'), KEYS.map(k => [NAMES[k], active[k], COLORS[k]]), totalActive);
    $('split-bar').setAttribute('aria-label', 'Active records: ' + KEYS.map(k => `${NAMES[k]} ${active[k]}`).join(', '));
    $('split-legend').replaceChildren(...KEYS.map(k => {
        const li = el('li'); li.append(swatch(COLORS[k]), NAMES[k] + ' ', el('strong', '', active[k])); return li;
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
            labels: puroks.map(p => `Purok ${p.id} · ${p.total}`),
            datasets: KEYS.map(k => ({ label: NAMES[k], data: puroks.map(p => p[k]), backgroundColor: COLORS[k], barPercentage: .7, borderSkipped: false })),
        },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            scales: {
                x: { stacked: true, display: false, min: 0, max: Math.max(...puroks.map(p => p.total)) },
                y: { stacked: true, grid: { display: false }, border: { display: false }, ticks: { color: INK, font: { size: 12, weight: '600' } } },
            },
            plugins: { legend: { display: false } },
        },
        plugins: [barLabels],
    });
    if (purokChart) purokChart.canvas.setAttribute('aria-label',
        'Active records by purok: ' + puroks.map(p => `Purok ${p.id} ${p.total} (MS ${p.ms}, UWP ${p.uwp}, MK ${p.mk})`).join('; '));

    /* ---------- Gauges ---------- */
    document.querySelectorAll('.gauge').forEach((g, i) => {
        const v = Number(g.dataset.value), c = 2 * Math.PI * 50;
        setTimeout(() => {
            g.querySelector('.g-bar').style.strokeDashoffset = String(c * (1 - v / 100));
            animateNumber(g.querySelector('.g-text'), v, 2, '%');
        }, reduceMotion ? 0 : 150 + i * 120);
    });

    /* ---------- Gender ---------- */
    ['female', 'male'].forEach(key => {
        const g = gender[key], n = g.ms + g.uwp + g.mk, pct = n / totalActive * 100;
        segments($(`bar-${key}`), KEYS.map(k => [NAMES[k], g[k], COLORS[k]]), totalActive);
        $(`bar-${key}`).setAttribute('aria-label', `${key}: ${pct.toFixed(2)}% of active records (MS ${g.ms}, UWP ${g.uwp}, MK ${g.mk})`);
        animateNumber($(`pct-${key}`), pct, 2, '%');
    });
    legend('gender-legend', statusItems);

    /* ---------- Doughnuts ---------- */
    const doughnut = (id, labels, data, colors) => makeChart(id, {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors, borderColor: '#fff', borderWidth: 2 }] },
        options: { cutout: '62%', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
    });

    const pieItems = [...KEYS.map(k => [NAMES[k], COLORS[k], active[k]]), ['Inactive', COLORS.off, inactive]];
    const pie = doughnut('pie-chart', pieItems.map(i => i[0]), pieItems.map(i => i[2]), pieItems.map(i => i[1]));
    valueLegend('pie-legend', pieItems, totalActive + inactive);
    if (pie) pie.canvas.setAttribute('aria-label', 'Records by kalagayan: ' + pieItems.map(i => `${i[0]} ${i[2]}`).join(', '));

    const localeChart = doughnut('locale-chart', LOCALE.map(l => l[0]), locale.MS, LOCALE.map(l => l[1]));
    function showLocale(kind) {
        const data = locale[kind], total = data.reduce((a, b) => a + b, 0);
        if (localeChart) {
            localeChart.data.datasets[0].data = data;
            localeChart.update();
            localeChart.canvas.setAttribute('aria-label', `${kind} records by locale group: ` + LOCALE.map((l, i) => `${l[0]} ${data[i]}`).join(', '));
        }
        valueLegend('locale-legend', LOCALE.map((l, i) => [l[0], l[1], data[i]]), total);
        document.querySelectorAll('#locale-seg button').forEach(b => b.setAttribute('aria-checked', String(b.dataset.k === kind)));
    }
    $('locale-seg').replaceChildren(...Object.keys(locale).map(k => {
        const b = el('button', '', k); b.type = 'button'; b.dataset.k = k; b.setAttribute('role', 'radio');
        b.addEventListener('click', () => showLocale(k));
        return b;
    }));
    showLocale('MS');

    /* ---------- Over time ---------- */
    legend('ot-legend', statusItems);
    const state = { s: 0, e: 11, puroks: new Set() }; // empty set = all puroks
    const allIds = puroks.map(p => p.id);
    const dataset = k => ({
        label: NAMES[k], data: [], borderColor: COLORS[k], backgroundColor: COLORS[k], borderWidth: 2.5, tension: .25,
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
        const series = k => idxs.map(m => ids.reduce((t, id) => t + overtime[id - 1][k][m], 0));
        const s = Object.fromEntries(KEYS.map(k => [k, series(k)]));
        otChart.data.labels = idxs.map(m => MONTHS[m]);
        KEYS.forEach((k, i) => { otChart.data.datasets[i].data = s[k]; });
        otChart.update();
        otChart.canvas.setAttribute('aria-label',
            `Records over time for ${ids.length === allIds.length ? 'all puroks' : 'Purok ' + ids.join(', ')}: ` +
            idxs.map((m, i) => `${MONTHS[m]} MS ${s.ms[i]}, UWP ${s.uwp[i]}, MK ${s.mk[i]}`).join('; '));
    }

    const monthsEl = $('tl-months');
    monthsEl.replaceChildren(...MONTHS.map((name, m) => {
        const b = el('button', 'chip', name); b.type = 'button';
        b.setAttribute('aria-label', `${name} 2025`);
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
            : state.s === state.e ? `Showing ${MONTHS[state.s]} 2025. Select another month to make a range.`
            : `Showing ${MONTHS[state.s]} to ${MONTHS[state.e]} 2025.`;
    }
    $('month-clear').addEventListener('click', () => { state.s = 0; state.e = 11; renderMonths(); renderOvertime(); });

    function renderPuroks() {
        $('purok-list').replaceChildren(...puroks.map(p => {
            const b = el('button', 'chip', `Purok ${p.id}`); b.type = 'button';
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
    const serverBaptisees = @json($newBaptisees ?? null);
    const daysAgo = n => { const d = new Date(); d.setDate(d.getDate() - n); return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; };
    const sampleBaptisees = [
        { name: 'Maria Lourdes Santos', purok: 3, grupo: 'Binhi', status: 'MS', evangelist: 'Ramon Dela Cruz', baptized_at: daysAgo(12) },
        { name: 'John Paul Reyes', purok: 1, grupo: 'Kadiwa', status: 'MS', evangelist: 'Elena Bautista', baptized_at: daysAgo(45) },
        { name: 'Angelica Mendoza', purok: 5, grupo: 'Buklod', status: 'UWP', evangelist: 'Ramon Dela Cruz', baptized_at: daysAgo(98) },
        { name: 'Christian Villanueva', purok: 6, grupo: 'Binhi', status: 'MS', evangelist: 'Joel Navarro', baptized_at: daysAgo(206) },
        { name: 'Rosario Aquino', purok: 4, grupo: 'Kadiwa', status: 'MK', evangelist: 'Elena Bautista', baptized_at: daysAgo(311) },
        { name: 'Long-time Member (excluded)', purok: 2, grupo: 'Buklod', status: 'MS', evangelist: 'Joel Navarro', baptized_at: daysAgo(500) },
    ];
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
        const list = (serverBaptisees || sampleBaptisees)
            .map(b => ({ ...b, span: since(b.baptized_at) }))
            .filter(b => b.span.months >= 0 && b.span.months < 12)
            .sort((x, y) => y.baptized_at.localeCompare(x.baptized_at));
        $('bap-count').textContent = list.length || '';
        $('bap-sub').textContent = list.length ? `Baptized within the last year: ${list.length} in the system.` : 'Baptized within the last year.';
        const body = $('bap-body');
        if (!list.length) {
            const box = el('div', 'empty');
            box.append(el('strong', '', "It's lonely here, but maybe it's better that way :)"), el('span', '', 'No one has been baptized in the last year.'));
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
            tag.style.background = COLORS[String(b.status).toLowerCase()] || COLORS.off;
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
    function renderRegistry() {
        const table = el('table', 'tbl'), head = el('tr');
        ['Data', ...puroks.map(p => `Purok ${p.id}`), 'Total'].forEach(h => head.append(el('th', '', h)));
        const thead = el('thead'); thead.append(head); table.append(thead);
        const tbody = el('tbody');
        REG_FIELDS.forEach(([label, miss]) => {
            const tr = el('tr', '', null);
            tr.append(el('td', '', label));
            miss.forEach(v => tr.append(el('td', v ? 'gap-cell' : 'ok-cell', v || '0')));
            tr.append(el('td', 'tot', add(miss)));
            tbody.append(tr);
        });
        const foot = el('tfoot'), fr = el('tr');
        fr.append(el('td', '', 'Encoded / all data'));
        regTotals.forEach((t, i) => fr.append(el('td', '', `${t * REG_FIELDS.length - add(REG_FIELDS.map(f => f[1][i]))} / ${t * REG_FIELDS.length}`)));
        fr.append(el('td', 'tot', `${Math.round(regPct / 100 * regOverall)} / ${regOverall}`));
        foot.append(fr);
        table.append(tbody, foot);
        const wrap = el('div', 'tbl-wrap'); wrap.append(table);

        const summary = el('div', 'reg-summary');
        summary.append(el('h3', '', 'Summary'));
        REG_FIELDS.forEach(([label, miss]) => {
            const done = regAll - add(miss), pct = done / regAll * 100;
            const item = el('div', 'sum-item'), top = el('div', 'sum-top'), meter = el('div', 'meter'), fill = el('span');
            fill.style.width = pct + '%';
            fill.style.background = pct >= 95 ? COLORS.mk : COLORS.uwp;
            meter.append(fill);
            top.append(el('span', '', `${label}: ${done} of ${regAll}`), el('span', '', pct.toFixed(2) + '%'));
            item.append(top, meter);
            summary.append(item);
        });
        const layout = el('div', 'reg'); layout.append(wrap, summary);
        $('reg-body').replaceChildren(layout);
    }

    [['open-baptisee', 'baptisee-modal'], ['open-registry', 'registry-modal']].forEach(([btn, dlgId]) => {
        const dlg = $(dlgId);
        $(btn).addEventListener('click', () => dlg.showModal());
        dlg.addEventListener('click', e => { if (e.target === dlg || e.target.closest('[data-close]')) dlg.close(); });
    });
    renderBaptisees();
    renderRegistry();

    renderMonths(); renderPuroks(); renderOvertime();
});
</script>
@endpush