@extends('layouts.app')

@section('title', 'De-Listment')

@section('content')
<div class="delist-page">
    <div class="delist-heading">
        <div>
            <p class="delist-eyebrow">Member overview</p>
            <h1>De-Listment</h1>
            <p class="delist-subtitle">Review R2-10 eligibility and report progress at a glance.</p>
        </div>
        <button type="button" class="missing-btn" id="missing-btn" aria-haspopup="dialog">
            <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 2.2 14.3 13H1.7L8 2.2Z"/><path d="M8 6.6v3M8 11.6v.1"/></svg>
            See entries with missing data
            <span class="missing-count" id="missing-count">0</span>
        </button>
    </div>

    <section class="delist-stats" aria-label="Summary">
        <button type="button" class="delist-stat stat-button" id="total-card" aria-haspopup="dialog">
            <span class="stat-label">Total Entry (MS + UWP + MK)</span>
            <strong id="member-total">0</strong>
            <span class="stat-note">Click to see breakdown</span>
        </button>
        <article class="delist-stat">
            <span class="stat-label">Created &amp; reported</span>
            <strong id="reported-total">0</strong>
            <span class="stat-note" id="reported-note">0% of eligibles have been reported (R2-10)</span>
            <span class="stat-bar" aria-hidden="true"><span id="reported-bar"></span></span>
        </article>
    </section>

    <div class="chart-grid">
        <section class="delist-panel chart-panel" aria-labelledby="eligibility-heading">
            <div class="panel-heading">
                <div>
                    <h2 id="eligibility-heading">R2-10 eligibility</h2>
                    <p id="chart-description">Eligible and not eligible members.</p>
                </div>
                <div class="segmented" role="group" aria-label="Chart detail">
                    <button type="button" class="seg-btn is-active" data-view="overview" aria-pressed="true">Overview</button>
                    <button type="button" class="seg-btn" data-view="detailed" aria-pressed="false">Detailed</button>
                </div>
            </div>
            <div class="chart-content">
                <div class="donut-wrap">
                    <canvas id="eligibility-chart" role="img" aria-label="Eligibility chart"></canvas>
                </div>
                <div class="chart-legend" id="chart-legend" aria-live="polite"></div>
            </div>
        </section>

        <section class="delist-panel chart-panel" aria-labelledby="progress-heading">
            <div class="panel-heading">
                <div>
                    <h2 id="progress-heading">Report progress by kalagayan</h2>
                    <p>Created &amp; reported versus not yet done.</p>
                </div>
            </div>
            <div class="bar-wrap">
                <canvas id="progress-chart" role="img" aria-label="Report progress by kalagayan"></canvas>
            </div>
            <div class="mini-legend">
                <span class="mini-item"><i style="background: #17875b"></i>Created &amp; reported</span>
                <span class="mini-item"><i style="background: var(--pending)"></i>Not yet done</span>
            </div>
        </section>
    </div>

    <section class="delist-panel table-panel" aria-labelledby="members-heading">
        <div class="panel-heading table-heading">
            <div>
                <h2 id="members-heading">R2-10 report status</h2>
                <p>Only eligible members can be marked as created &amp; reported. This can't be undone.</p>
            </div>
            <span class="record-count" id="record-count">0 members</span>
        </div>

        <div class="table-toolbar">
            <label class="search-field">
                <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="m13.5 13.5 3.5 3.5"/></svg>
                <input type="search" id="member-search" placeholder="Search members" autocomplete="off" aria-label="Search members">
            </label>
            <div class="filter-group" role="group" aria-label="Filter by eligibility">
                <button type="button" class="chip is-active" data-filter="all">All</button>
                <button type="button" class="chip" data-filter="eligible">Eligible</button>
                <button type="button" class="chip" data-filter="not-eligible">Not eligible</button>
            </div>
            <div class="filter-group" role="group" aria-label="Filter by status">
                <button type="button" class="chip is-active" data-status="all">Any status</button>
                <button type="button" class="chip" data-status="pending">Not yet done</button>
                <button type="button" class="chip" data-status="done">Reported</button>
            </div>
            <select id="kalagayan-filter" class="select-field" aria-label="Filter by kalagayan">
                <option value="all">All kalagayan</option>
                <option value="MS">MS</option>
                <option value="UWP">UWP</option>
                <option value="MK">MK</option>
            </select>
            <div class="filter-group" role="group" aria-label="Filter by age in system">
                <button type="button" class="chip is-active" data-age="all">Any age</button>
                <button type="button" class="chip" data-age="under">Under 1 year</button>
                <button type="button" class="chip" data-age="over">1 year or more</button>
            </div>
            <select id="age-sort" class="select-field" aria-label="Sort by age in system">
                <option value="">Sort by age</option>
                <option value="newest">Newest first</option>
                <option value="longest">Longest first</option>
            </select>
        </div>

        <div class="table-scroll">
            <table class="member-table">
                <thead>
                    <tr>
                        <th scope="col" aria-sort="none"><button type="button" class="sort-btn" data-sort="name">Name <i></i></button></th>
                        <th scope="col" aria-sort="none"><button type="button" class="sort-btn" data-sort="kalagayan">Kalagayan <i></i></button></th>
                        <th scope="col" aria-sort="none"><button type="button" class="sort-btn" data-sort="eligible">R2-10 eligibility <i></i></button></th>
                        <th scope="col" aria-sort="none"><button type="button" class="sort-btn" data-sort="reported">Status <i></i></button></th>
                        <th scope="col" aria-sort="none"><button type="button" class="sort-btn" data-sort="age">Age in system <i></i></button></th>
                    </tr>
                </thead>
                <tbody id="member-rows"></tbody>
            </table>
            <div class="empty-state" id="empty-state" hidden>
                <strong>No members match</strong>
                <span>Try a different search or clear a filter.</span>
                <button type="button" class="chip" id="clear-filters">Clear filters</button>
            </div>
        </div>

        <div class="table-footer">
            <span class="page-info" id="page-info"></span>
            <div class="pager">
                <button type="button" class="pager-btn" id="prev-page">Previous</button>
                <span id="page-label" class="page-label"></span>
                <button type="button" class="pager-btn" id="next-page">Next</button>
            </div>
        </div>
    </section>

    <!-- Entry breakdown popup -->
    <dialog class="modal" id="breakdown-dialog" aria-labelledby="breakdown-title">
        <div class="modal-body">
            <div class="modal-head">
                <h3 id="breakdown-title">Entry breakdown</h3>
                <button type="button" class="modal-close" data-close aria-label="Close">
                    <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m4 4 8 8M12 4l-8 8"/></svg>
                </button>
            </div>
            <p class="modal-lead" id="breakdown-summary"></p>
            <div class="breakdown-rows" id="breakdown-rows"></div>
            <div class="modal-actions">
                <button type="button" class="btn" data-close>Close</button>
            </div>
        </div>
    </dialog>

    <!-- R2-10 report form -->
    <dialog class="modal" id="report-dialog" aria-labelledby="report-title">
        <form class="modal-body" id="report-form" novalidate>
            <div class="modal-head">
                <div>
                    <h3 id="report-title">Mark R2-10 as created &amp; reported</h3>
                    <p class="modal-sub" id="report-member"></p>
                </div>
                <button type="button" class="modal-close" data-close aria-label="Close">
                    <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m4 4 8 8M12 4l-8 8"/></svg>
                </button>
            </div>

            <label class="field-label" for="report-note">Brief description of R2-10 endorsement</label>
            <textarea id="report-note" rows="4" maxlength="500" placeholder="Briefly describe what was endorsed in this R2-10."></textarea>
            <span class="field-count" id="report-count">0 / 500</span>

            <label class="check-row" for="report-assure">
                <input type="checkbox" id="report-assure">
                <span>I, Secretary for LSP of the locale, assure that the sensitive information I have endorsed and submitted is true and correct, with no errors or omissions.</span>
            </label>

            <p class="modal-warn">Once confirmed, this can't be undone.</p>

            <div class="modal-actions">
                <button type="button" class="btn" data-close>Cancel</button>
                <button type="submit" class="btn btn-primary" id="report-submit" disabled>Confirm &amp; mark as reported</button>
            </div>
        </form>
    </dialog>

    <!-- Entries with missing data -->
    <dialog class="modal modal-wide" id="missing-dialog" aria-labelledby="missing-title">
        <div class="modal-body">
            <div class="modal-head">
                <div>
                    <h3 id="missing-title">Entries with missing data</h3>
                    <p class="modal-sub" id="missing-sub"></p>
                </div>
                <button type="button" class="modal-close" data-close aria-label="Close">
                    <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m4 4 8 8M12 4l-8 8"/></svg>
                </button>
            </div>
            <p class="modal-lead">The system can't check R2-10 eligibility for these entries because required dates are empty in their records. Fill them in so eligibility can be generated automatically.</p>

            <div class="table-scroll missing-scroll">
                <table class="member-table missing-table" id="missing-table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Registry number</th>
                            <th scope="col">Date of baptism</th>
                            <th scope="col">Date of entry</th>
                        </tr>
                    </thead>
                    <tbody id="missing-rows"></tbody>
                </table>
                <div class="empty-state" id="missing-empty" hidden>
                    <strong>All entries are complete</strong>
                    <span>The system can now generate eligibility for every entry.</span>
                </div>
            </div>

            <div class="modal-actions">
                <span class="save-hint" id="missing-hint"></span>
                <button type="button" class="btn" data-close>Close</button>
                <button type="button" class="btn btn-primary" id="missing-save" disabled>Save changes</button>
            </div>
        </div>
    </dialog>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>
</div>
@endsection

@push('styles')
<style>
    .delist-page {
        --delist-ink: var(--text);
        --delist-muted: var(--text-muted);
        --delist-border: var(--border);
        --delist-surface: var(--surface);
        --ok: #17875b;
        --ok-soft: #e9f7ef;
        --bad: #a54835;
        --bad-soft: #fff0ed;
        --warn: #9a6a0a;
        --warn-soft: #fff5dc;
        --k-ms: #17875b;
        --k-uwp: #e8781a;
        --k-mk: #e2ae00;
        --pending: #dc4c4c;
        max-width: 1180px;
        margin: 0 auto;
    }
    html[data-theme="dark"] .delist-page {
        --ok: #75d5a2; --ok-soft: #16372a;
        --bad: #ff9b88; --bad-soft: #3a2424;
        --warn: #f2c66d;
        --warn-soft: #3a2f16;
    }

    .delist-heading,
    .panel-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1.25rem;
    }
    .delist-heading { margin-bottom: 1.5rem; }
    .delist-eyebrow { margin: 0 0 .45rem; color: var(--accent); font-size: .82rem; font-weight: 700; }
    .delist-heading h1 { margin: 0; color: var(--delist-ink); font-size: clamp(1.65rem, 3vw, 2.1rem); letter-spacing: -.04em; }
    .delist-subtitle,
    .panel-heading p { margin: .45rem 0 0; color: var(--delist-muted); font-size: .92rem; line-height: 1.55; }

    /* Stats */
    .delist-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1rem; }
    .delist-stat,
    .delist-panel {
        border: 1px solid var(--delist-border);
        border-radius: 14px;
        background: var(--delist-surface);
        box-shadow: 0 4px 18px rgba(16, 24, 40, .035);
    }
    .delist-stat { display: flex; flex-direction: column; padding: 1.1rem 1.25rem; }
    .stat-label { color: var(--delist-muted); font-size: .82rem; font-weight: 600; }
    .delist-stat strong { margin: .35rem 0 .2rem; color: var(--delist-ink); font-size: 1.8rem; letter-spacing: -.04em; font-variant-numeric: tabular-nums; }
    .stat-note { color: var(--delist-muted); font-size: .78rem; }
    .stat-bar { display: block; height: 5px; margin-top: .65rem; border-radius: 99px; background: var(--delist-border); overflow: hidden; }
    .stat-bar span { display: block; height: 100%; width: 0; border-radius: inherit; background: var(--ok); transition: width .7s cubic-bezier(.2,.8,.2,1); }

    .stat-button { width: 100%; font: inherit; text-align: left; color: inherit; cursor: pointer; transition: border-color .15s ease, transform .1s ease; }
    .stat-button:hover { border-color: var(--accent); }
    .stat-button:active { transform: scale(.99); }
    .stat-button .stat-note { color: var(--accent); font-weight: 600; }

    /* Panels */
    .delist-panel { padding: 1.35rem; margin-bottom: 1rem; }
    .panel-heading { align-items: center; }
    .panel-heading h2 { margin: 0; color: var(--delist-ink); font-size: 1.08rem; letter-spacing: -.02em; }
    .panel-heading p { font-size: .83rem; }

    .chart-grid { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); gap: 1rem; margin-bottom: 1rem; }
    .chart-grid .delist-panel { margin-bottom: 0; }

    .segmented { display: inline-flex; padding: 3px; border: 1px solid var(--delist-border); border-radius: 10px; background: var(--bg); flex: 0 0 auto; }
    .seg-btn {
        padding: .42rem .75rem; border: 0; border-radius: 7px; background: transparent;
        color: var(--delist-muted); font: inherit; font-size: .78rem; font-weight: 650; cursor: pointer;
        transition: background .2s ease, color .2s ease, box-shadow .2s ease;
    }
    .seg-btn.is-active { background: var(--delist-surface); color: var(--delist-ink); box-shadow: 0 1px 3px rgba(16,24,40,.14); }

    .seg-btn:focus-visible, .chip:focus-visible, .pager-btn:focus-visible, .sort-btn:focus-visible, .status-btn:focus-visible,
    .stat-button:focus-visible, .btn:focus-visible, .modal-close:focus-visible,
    .search-field input:focus-visible, .select-field:focus-visible, .modal textarea:focus-visible, .check-row input:focus-visible {
        outline: 3px solid color-mix(in srgb, var(--accent) 35%, transparent);
        outline-offset: 2px;
    }

    .chart-content { display: grid; grid-template-columns: minmax(170px, 230px) minmax(0, 1fr); align-items: center; gap: clamp(1.25rem, 4vw, 3rem); margin: 1.25rem 0 .25rem; }
    .donut-wrap { position: relative; width: 100%; max-width: 230px; aspect-ratio: 1; justify-self: center; }
    .bar-wrap { position: relative; height: 230px; margin-top: 1.25rem; }
    .chart-legend { display: grid; gap: .1rem; }
    .legend-row {
        display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 1rem;
        padding: .65rem .5rem; margin: 0 -.5rem; border-radius: 8px; cursor: default; transition: background .15s ease;
    }
    .legend-row:hover { background: var(--bg); }
    .legend-label { display: flex; align-items: center; gap: .65rem; color: var(--delist-ink); font-size: .84rem; }
    .legend-swatch { width: 11px; height: 11px; flex: 0 0 auto; border-radius: 3px; }
    .legend-value { color: var(--delist-muted); font-size: .81rem; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .legend-value strong { margin-right: .35rem; color: var(--delist-ink); font-size: .87rem; }

    .mini-legend { display: flex; flex-wrap: wrap; gap: .5rem 1.5rem; justify-content: center; margin-top: .9rem; color: var(--delist-muted); font-size: .76rem; }
    .mini-group { display: inline-flex; align-items: center; flex-wrap: wrap; gap: .3rem .7rem; }
    .mini-item { display: inline-flex; align-items: center; gap: .35rem; color: var(--delist-ink); font-weight: 600; }
    .mini-item i { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }

    /* Table */
    .table-panel { padding-bottom: 1rem; }
    .table-heading { margin-bottom: 1rem; }
    .record-count { color: var(--delist-muted); font-size: .78rem; white-space: nowrap; }

    .table-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; margin-bottom: 1rem; }
    .search-field { position: relative; flex: 1 1 200px; max-width: 260px; }
    .search-field svg { position: absolute; left: .7rem; top: 50%; width: 16px; height: 16px; transform: translateY(-50%); fill: none; stroke: var(--delist-muted); stroke-width: 1.7; stroke-linecap: round; pointer-events: none; }
    .search-field input {
        width: 100%; padding: .58rem .8rem .58rem 2.15rem; border: 1px solid var(--delist-border); border-radius: 9px;
        background: var(--bg); color: var(--delist-ink); font: inherit; font-size: .82rem;
    }
    .filter-group { display: inline-flex; gap: .3rem; padding: 3px; border: 1px solid var(--delist-border); border-radius: 10px; background: var(--bg); }
    .chip {
        padding: .38rem .7rem; border: 0; border-radius: 7px; background: transparent; color: var(--delist-muted);
        font: inherit; font-size: .76rem; font-weight: 650; cursor: pointer; transition: background .2s ease, color .2s ease;
    }
    .chip:hover { color: var(--delist-ink); }
    .chip.is-active { background: var(--delist-surface); color: var(--delist-ink); box-shadow: 0 1px 3px rgba(16,24,40,.14); }
    .select-field {
        padding: .55rem .7rem; border: 1px solid var(--delist-border); border-radius: 9px; background: var(--bg);
        color: var(--delist-ink); font: inherit; font-size: .78rem; font-weight: 600;
    }

    .table-scroll { overflow-x: auto; border: 1px solid var(--delist-border); border-radius: 12px; }
    .member-table { width: 100%; border-collapse: collapse; text-align: left; }
    .member-table th { padding: .2rem .5rem; border-bottom: 1px solid var(--delist-border); background: var(--bg); white-space: nowrap; }
    .sort-btn {
        display: inline-flex; align-items: center; gap: .4rem; padding: .6rem .5rem; border: 0; border-radius: 6px;
        background: transparent; color: var(--delist-muted); font: inherit; font-size: .76rem; font-weight: 700; cursor: pointer;
    }
    .sort-btn:hover { color: var(--delist-ink); }
    .sort-btn i { width: 8px; height: 12px; position: relative; opacity: .45; }
    .sort-btn i::before, .sort-btn i::after { content: ""; position: absolute; left: 0; border: 4px solid transparent; }
    .sort-btn i::before { top: -1px; border-bottom-color: currentColor; border-top: 0; }
    .sort-btn i::after { bottom: -1px; border-top-color: currentColor; border-bottom: 0; }
    th[aria-sort="ascending"] .sort-btn, th[aria-sort="descending"] .sort-btn { color: var(--delist-ink); }
    th[aria-sort="ascending"] .sort-btn i, th[aria-sort="descending"] .sort-btn i { opacity: 1; }
    th[aria-sort="ascending"] .sort-btn i::after { opacity: .2; }
    th[aria-sort="descending"] .sort-btn i::before { opacity: .2; }

    .member-table td { padding: .8rem 1rem; border-bottom: 1px solid var(--delist-border); color: var(--delist-ink); font-size: .84rem; }
    .member-table tbody tr { transition: background .15s ease; }
    .member-table tbody tr:hover { background: var(--bg); }
    .member-table tbody tr:last-child td { border-bottom: 0; }
    .member-cell { display: flex; align-items: center; gap: .75rem; font-weight: 650; }
    .avatar { display: grid; place-items: center; width: 32px; height: 32px; flex: 0 0 auto; border-radius: 50%; color: #fff; font-size: .7rem; font-weight: 700; }

    .table-badge { display: inline-flex; align-items: center; gap: .38rem; padding: .34rem .58rem; border-radius: 999px; font-size: .72rem; font-weight: 650; white-space: nowrap; }
    .table-badge::before { width: 6px; height: 6px; border-radius: 50%; background: currentColor; content: ""; }
    .badge-eligible { color: var(--ok); background: var(--ok-soft); }
    .badge-not-eligible { color: var(--bad); background: var(--bad-soft); }

    .kalagayan-badge { display: inline-flex; min-width: 3.3rem; justify-content: center; padding: .34rem .58rem; border-radius: 7px; font-size: .72rem; font-weight: 700; }
    .k-MS { color: #146b47; background: #e2f5eb; }
    .k-UWP { color: #b35208; background: #ffeedb; }
    .k-MK { color: #7d5f00; background: #fff4c7; }
    html[data-theme="dark"] .k-MS { color: #7fdcae; background: #16372a; }
    html[data-theme="dark"] .k-UWP { color: #ffb36b; background: #3d2a16; }
    html[data-theme="dark"] .k-MK { color: #f5d24a; background: #3a3213; }

    .status-btn {
        display: inline-flex; align-items: center; gap: .5rem; min-width: 172px; padding: .4rem .75rem;
        border: 1px dashed var(--delist-border); border-radius: 999px; background: var(--delist-surface);
        color: var(--warn); font: inherit; font-size: .74rem; font-weight: 650; white-space: nowrap; cursor: pointer;
        transition: border-color .2s ease, background .2s ease, color .2s ease, transform .1s ease;
    }
    .status-btn::before { content: ""; width: 12px; height: 12px; border: 2px solid currentColor; border-radius: 50%; flex: 0 0 auto; }
    .status-btn .on-hover { display: none; }
    .status-btn:hover { border-style: solid; border-color: var(--ok); background: var(--ok-soft); color: var(--ok); }
    .status-btn:hover .on-idle { display: none; }
    .status-btn:hover .on-hover { display: inline; }
    .status-btn:active { transform: scale(.97); }
    .status-btn.is-locked { color: var(--delist-muted); cursor: not-allowed; }
    .status-btn.is-locked::before { display: none; }
    .status-btn.is-locked:hover { border-style: dashed; border-color: var(--delist-border); background: var(--delist-surface); color: var(--delist-muted); }
    .status-btn.is-locked:active { transform: none; }
    .status-btn svg { width: 13px; height: 13px; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; }

    .status-done {
        display: inline-flex; align-items: center; gap: .45rem; min-width: 172px; padding: .4rem .75rem;
        border-radius: 999px; color: var(--ok); background: var(--ok-soft); font-size: .74rem; font-weight: 650; white-space: nowrap;
    }
    .status-done svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }
    .status-done.just-done { animation: pop .45s cubic-bezier(.2,1.4,.4,1); }
    .status-done.just-done svg path { stroke-dasharray: 20; stroke-dashoffset: 20; animation: draw .4s .1s ease forwards; }
    @keyframes pop { 0% { transform: scale(.85); } 100% { transform: scale(1); } }
    @keyframes draw { to { stroke-dashoffset: 0; } }

    .age-value { font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .age-empty { color: var(--delist-muted); }

    .empty-state { display: grid; justify-items: center; gap: .4rem; padding: 2.5rem 1rem; color: var(--delist-muted); font-size: .83rem; text-align: center; }
    .empty-state strong { color: var(--delist-ink); font-size: .95rem; }
    .empty-state .chip { border: 1px solid var(--delist-border); margin-top: .4rem; }
    .empty-state[hidden] { display: none; }

    .table-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: .9rem; }
    .page-info, .page-label { color: var(--delist-muted); font-size: .78rem; }
    .pager { display: flex; align-items: center; gap: .6rem; }
    .pager-btn {
        padding: .45rem .8rem; border: 1px solid var(--delist-border); border-radius: 8px; background: var(--delist-surface);
        color: var(--delist-ink); font: inherit; font-size: .78rem; font-weight: 650; cursor: pointer;
    }
    .pager-btn:hover:not(:disabled) { border-color: var(--accent); }
    .pager-btn:disabled { opacity: .4; cursor: not-allowed; }

    /* Dialogs */
    .modal {
        width: min(92vw, 460px); padding: 0; border: 1px solid var(--delist-border); border-radius: 16px;
        background: var(--delist-surface); color: var(--delist-ink); box-shadow: 0 24px 60px rgba(16, 24, 40, .28);
    }
    .modal::backdrop { background: rgba(15, 23, 42, .5); }
    .modal[open] { animation: modal-in .22s cubic-bezier(.2,.9,.3,1); }
    @keyframes modal-in { from { opacity: 0; transform: translateY(10px) scale(.98); } to { opacity: 1; transform: none; } }
    .modal-body { display: grid; gap: .85rem; padding: 1.4rem; margin: 0; }
    .modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
    .modal-head h3 { margin: 0; color: var(--delist-ink); font-size: 1.08rem; letter-spacing: -.02em; }
    .modal-sub { margin: .3rem 0 0; color: var(--delist-muted); font-size: .83rem; }
    .modal-lead { margin: 0; color: var(--delist-muted); font-size: .9rem; line-height: 1.55; }
    .modal-close {
        display: grid; place-items: center; width: 30px; height: 30px; flex: 0 0 auto; padding: 0;
        border: 0; border-radius: 8px; background: transparent; color: var(--delist-muted); cursor: pointer;
    }
    .modal-close:hover { background: var(--bg); color: var(--delist-ink); }
    .modal-close svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; }

    .field-label { color: var(--delist-ink); font-size: .82rem; font-weight: 650; }
    .modal textarea {
        width: 100%; min-height: 96px; padding: .7rem .8rem; resize: vertical; border: 1px solid var(--delist-border);
        border-radius: 10px; background: var(--bg); color: var(--delist-ink); font: inherit; font-size: .85rem; line-height: 1.5;
    }
    .field-count { justify-self: end; margin-top: -.55rem; color: var(--delist-muted); font-size: .72rem; }
    .check-row {
        display: flex; align-items: flex-start; gap: .7rem; padding: .8rem; border: 1px solid var(--delist-border);
        border-radius: 10px; background: var(--bg); font-size: .8rem; line-height: 1.5; cursor: pointer;
    }
    .check-row input { flex: 0 0 auto; width: 16px; height: 16px; margin-top: .2rem; accent-color: var(--accent); }
    .modal-warn { margin: 0; color: var(--warn); font-size: .78rem; font-weight: 600; }
    .modal-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: .25rem; }
    .btn {
        padding: .6rem .95rem; border: 1px solid var(--delist-border); border-radius: 9px; background: var(--delist-surface);
        color: var(--delist-ink); font: inherit; font-size: .82rem; font-weight: 650; cursor: pointer;
    }
    .btn:hover:not(:disabled) { border-color: var(--accent); }
    .btn-primary { background: #17875b; border-color: #17875b; color: #fff; }
    .btn-primary:hover:not(:disabled) { border-color: #17875b; filter: brightness(1.08); }
    .btn:disabled { opacity: .45; cursor: not-allowed; }

    .breakdown-rows { display: grid; gap: .95rem; margin: .25rem 0; }
    .bd-top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: .4rem; }
    .bd-bar { height: 8px; border-radius: 99px; background: var(--delist-border); overflow: hidden; }
    .bd-bar span { display: block; width: 0; height: 100%; border-radius: inherit; transition: width .7s cubic-bezier(.2,.8,.2,1); }

    .toast {
        position: fixed; left: 50%; bottom: 1.5rem; max-width: 92vw; padding: .65rem 1rem; border-radius: 10px;
        background: var(--delist-ink); color: var(--delist-surface); font-size: .8rem; font-weight: 600;
        opacity: 0; pointer-events: none; transform: translate(-50%, 12px); transition: opacity .25s ease, transform .25s ease; z-index: 2000;
    }
    .toast.show { opacity: 1; transform: translate(-50%, 0); }

    /* Missing-data button + dialog */
    .missing-btn {
        display: inline-flex; align-items: center; gap: .55rem; flex: 0 0 auto; padding: .6rem .85rem;
        border: 1px solid var(--delist-border); border-radius: 10px; background: var(--delist-surface);
        color: var(--delist-ink); font: inherit; font-size: .82rem; font-weight: 650; cursor: pointer;
        transition: border-color .15s ease, background .15s ease;
    }
    .missing-btn:hover { border-color: var(--warn); background: var(--warn-soft); }
    .missing-btn:focus-visible { outline: 3px solid color-mix(in srgb, var(--accent) 35%, transparent); outline-offset: 2px; }
    .missing-btn svg { width: 16px; height: 16px; fill: none; stroke: var(--warn); stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; }
    .missing-count {
        min-width: 1.35rem; padding: .05rem .42rem; border-radius: 99px; background: var(--warn-soft); color: var(--warn);
        font-size: .72rem; font-weight: 700; text-align: center; font-variant-numeric: tabular-nums;
    }
    .missing-count[hidden] { display: none; }
    .modal-wide { width: min(96vw, 760px); }
    .missing-scroll { max-height: min(50vh, 360px); overflow: auto; }
    .missing-table { min-width: 620px; }
    .missing-table th { position: sticky; top: 0; z-index: 1; padding: .7rem 1rem; color: var(--delist-muted); font-size: .76rem; font-weight: 700; }
    .missing-table tr.is-complete td { background: var(--ok-soft); transition: background .3s ease; }
    .missing-table tr.is-complete .date-input { border-color: var(--ok); background: var(--delist-surface); }
    .registry { color: var(--delist-muted); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .date-input {
        width: 100%; min-width: 150px; padding: .5rem .6rem; border: 1px solid var(--delist-border); border-radius: 8px;
        background: var(--delist-surface); color: var(--delist-ink); font: inherit; font-size: .82rem;
    }
    .date-input.is-missing { border-color: var(--warn); background: var(--warn-soft); }
    .date-input:focus-visible { outline: 3px solid color-mix(in srgb, var(--accent) 35%, transparent); outline-offset: 2px; }
    html[data-theme="dark"] .date-input { color-scheme: dark; }
    .save-hint { margin-right: auto; align-self: center; color: var(--delist-muted); font-size: .78rem; }
    .chart-error { margin: 0; padding: 1rem; color: var(--delist-muted); font-size: .83rem; text-align: center; }

    @media (prefers-reduced-motion: reduce) {
        .delist-page *, .delist-page *::before, .delist-page *::after { animation: none !important; transition: none !important; }
    }

    @media (max-width: 960px) {
        .chart-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 700px) {
        .delist-panel { padding: 1rem; }
        .search-field { max-width: none; flex-basis: 100%; }
    }
    @media (max-width: 520px) {
        .delist-heading { flex-direction: column; gap: .8rem; }
        .delist-stats { grid-template-columns: 1fr; gap: .65rem; }
        .delist-stat { padding: .9rem; }
        .panel-heading { align-items: flex-start; flex-direction: column; }
        .segmented { width: 100%; }
        .seg-btn { flex: 1; }
        .chart-content { grid-template-columns: 1fr; gap: 1rem; }
        .donut-wrap { max-width: 210px; }
        .table-heading { flex-direction: row; }
        .member-table { min-width: 860px; }
        .table-footer { flex-direction: column; align-items: stretch; text-align: center; }
        .pager { justify-content: space-between; }
        .modal-body { padding: 1.1rem; }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const STORAGE_KEY = 'delist-reported-v2';
    const PAGE_SIZE = 5;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const members = [
        { id: 1,  name: 'Sample Member 01', eligible: true,  endorsed: true,  kalagayan: 'MS',  reported: true,  reportedAt: '2022-01-15' },
        { id: 2,  name: 'Sample Member 02', eligible: true,  endorsed: false, kalagayan: 'UWP', reported: false },
        { id: 3,  name: 'Sample Member 03', eligible: false, endorsed: false, kalagayan: 'MS',  reported: false },
        { id: 4,  name: 'Sample Member 04', eligible: true,  endorsed: true,  kalagayan: 'MK',  reported: true,  reportedAt: '2025-03-02' },
        { id: 5,  name: 'Sample Member 05', eligible: false, endorsed: true,  kalagayan: 'UWP', reported: false },
        { id: 6,  name: 'Sample Member 06', eligible: true,  endorsed: false, kalagayan: 'MS',  reported: false },
        { id: 7,  name: 'Sample Member 07', eligible: true,  endorsed: false, kalagayan: 'MK',  reported: false },
        { id: 8,  name: 'Sample Member 08', eligible: true,  endorsed: true,  kalagayan: 'UWP', reported: true,  reportedAt: '2026-06-20' },
        { id: 9,  name: 'Sample Member 09', eligible: false, endorsed: true,  kalagayan: 'MS',  reported: false },
        { id: 10, name: 'Sample Member 10', eligible: true,  endorsed: false, kalagayan: 'MS',  reported: false },
    ];

    /* ---------- Dates ---------- */
    const pad = n => String(n).padStart(2, '0');
    const parseISO = iso => { const [y, m, d] = iso.split('-').map(Number); return new Date(y, m - 1, d); };
    const startOfToday = () => { const n = new Date(); return new Date(n.getFullYear(), n.getMonth(), n.getDate()); };
    const toISO = date => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const plural = (n, word) => `${n} ${word}${n === 1 ? '' : 's'}`;

    function diffYMD(from, to) {
        if (from > to) from = to;
        let y = to.getFullYear() - from.getFullYear();
        let m = to.getMonth() - from.getMonth();
        let d = to.getDate() - from.getDate();
        if (d < 0) { m--; d += new Date(to.getFullYear(), to.getMonth(), 0).getDate(); }
        if (m < 0) { y--; m += 12; }
        return { y, m, d };
    }
    function formatAge(iso) {
        const { y, m, d } = diffYMD(parseISO(iso), startOfToday());
        const parts = [];
        if (y) parts.push(plural(y, 'Year'));
        if (m) parts.push(plural(m, 'Month'));
        if (d) parts.push(plural(d, 'Day'));
        return parts.length ? parts.join(', ') : 'Today';
    }
    const ageYears = member => diffYMD(parseISO(member.reportedAt), startOfToday()).y;
    const ageDays = member => member.reported && member.reportedAt
        ? Math.round((startOfToday() - parseISO(member.reportedAt)) / 86400000)
        : null;

    /* ---------- Rules + persistence ---------- */
    members.forEach(m => {
        m.reportedAt = m.reportedAt || null;
        m.note = m.note || '';
        if (m.kalagayan === 'MK') m.eligible = true;            // MK is automatically eligible
        if (!m.eligible) { m.reported = false; m.reportedAt = null; } // not eligible can never be reported
    });

    try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
        members.forEach(m => {
            const s = saved[m.id];
            if (s && m.eligible) {
                m.reported = true;
                m.reportedAt = s.at || m.reportedAt || toISO(startOfToday());
                m.note = s.note || '';
            }
        });
    } catch (e) { /* storage unavailable: fall back to sample data */ }

    const persist = () => {
        try {
            const out = {};
            members.filter(m => m.reported).forEach(m => { out[m.id] = { at: m.reportedAt, note: m.note }; });
            localStorage.setItem(STORAGE_KEY, JSON.stringify(out));
        } catch (e) {}
    };

    /* ---------- Shared ---------- */
    const css = name => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    const kalagayanTypes = ['MS', 'UWP', 'MK'];
    const kColors = { MS: '#17875b', UWP: '#e8781a', MK: '#e2ae00' };
    const colors = {
        eligible: '#1677d2', notEligible: '#94a3b8',
        eligibleNotEndorsed: '#12a1a1', eligibleEndorsed: '#17875b',
        notEligibleNotEndorsed: '#e47a5f', notEligibleEndorsed: '#7856b5',
        mk: kColors.MK, done: '#17875b', pending: '#dc4c4c',
    };
    const avatarColors = ['#1677d2', '#17875b', '#7856b5', '#e47a5f', '#c2851a', '#0f8a9d'];
    const total = members.length;

    const toast = document.getElementById('toast');
    let toastTimer;
    function showToast(message) {
        toast.textContent = message;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 2600);
    }

    /* ---------- Stats ---------- */
    function countUp(el, to) {
        const from = Number(el.textContent) || 0;
        if (reduceMotion || from === to) { el.textContent = to; return; }
        const start = performance.now(), duration = 700;
        const tick = now => {
            const t = Math.min((now - start) / duration, 1);
            el.textContent = Math.round(from + (to - from) * (1 - Math.pow(1 - t, 3)));
            if (t < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    }
    function renderStats() {
        const eligibles = members.filter(m => m.eligible);
        const reported = eligibles.filter(m => m.reported).length;
        const pct = eligibles.length ? Math.round(reported / eligibles.length * 100) : 0;
        countUp(document.getElementById('member-total'), total);
        countUp(document.getElementById('reported-total'), reported);
        document.getElementById('reported-note').textContent = `${pct}% of eligibles have been reported (R2-10)`;
        requestAnimationFrame(() => { document.getElementById('reported-bar').style.width = pct + '%'; });
        document.getElementById('record-count').textContent = `${total} members`;
    }

    /* ---------- Dialog helpers ---------- */
    document.querySelectorAll('.modal').forEach(dialog => {
        dialog.querySelectorAll('[data-close]').forEach(btn => btn.addEventListener('click', () => dialog.close()));
        dialog.addEventListener('click', e => { if (e.target === dialog) dialog.close(); }); // backdrop click
    });

    /* ---------- Entry breakdown popup ---------- */
    const breakdownDialog = document.getElementById('breakdown-dialog');
    document.getElementById('total-card').addEventListener('click', () => {
        const counts = kalagayanTypes.map(k => ({ k, n: members.filter(m => m.kalagayan === k).length }));
        const byKey = Object.fromEntries(counts.map(c => [c.k, c.n]));
        document.getElementById('breakdown-summary').textContent =
            `Of the ${total} entries, ${byKey.MS} are MS, ${byKey.UWP} are UWP and ${byKey.MK} are MK.`;

        const bars = [];
        document.getElementById('breakdown-rows').replaceChildren(...counts.map(({ k, n }) => {
            const pct = total ? Math.round(n / total * 100) : 0;
            const row = document.createElement('div');
            const top = document.createElement('div');
            top.className = 'bd-top';
            const label = document.createElement('span');
            label.className = 'legend-label';
            const swatch = document.createElement('span');
            swatch.className = 'legend-swatch';
            swatch.style.backgroundColor = kColors[k];
            const name = document.createElement('span');
            name.textContent = k;
            label.append(swatch, name);
            const value = document.createElement('span');
            value.className = 'legend-value';
            const strong = document.createElement('strong');
            strong.textContent = n;
            value.append(strong, document.createTextNode(`${pct}%`));
            top.append(label, value);
            const track = document.createElement('div');
            track.className = 'bd-bar';
            const fill = document.createElement('span');
            fill.style.background = kColors[k];
            track.append(fill);
            bars.push([fill, pct]);
            row.append(top, track);
            return row;
        }));

        breakdownDialog.showModal();
        requestAnimationFrame(() => requestAnimationFrame(() => bars.forEach(([el, pct]) => { el.style.width = pct + '%'; })));
    });

    /* ---------- Charts ---------- */
    let chartsOK = typeof Chart !== 'undefined'; // false if the CDN script didn't load
    if (chartsOK) {
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.animation = reduceMotion ? false : { duration: 900, easing: 'easeOutQuart' };
    }
    const chartFont = () => (chartsOK ? Chart.defaults.font.family : 'sans-serif');

    function showChartError() {
        if (typeof progressChart !== 'undefined' && progressChart) { try { progressChart.destroy(); } catch (e) {} progressChart = null; }
        document.querySelectorAll('.donut-wrap, .bar-wrap').forEach(el => {
            const msg = document.createElement('p');
            msg.className = 'chart-error';
            msg.textContent = "The chart couldn't load. Reload the page to try again.";
            el.replaceChildren(msg);
        });
    }

    const categories = detailed => detailed
        ? [
            { label: 'Not eligible · No endorsement', color: colors.notEligibleNotEndorsed, matches: m => !m.eligible && !m.endorsed && m.kalagayan !== 'MK' },
            { label: 'Not eligible · Endorsed', color: colors.notEligibleEndorsed, matches: m => !m.eligible && m.endorsed && m.kalagayan !== 'MK' },
            { label: 'Eligible · No endorsement', color: colors.eligibleNotEndorsed, matches: m => m.eligible && !m.endorsed && m.kalagayan !== 'MK' },
            { label: 'Eligible · Endorsed', color: colors.eligibleEndorsed, matches: m => m.eligible && m.endorsed && m.kalagayan !== 'MK' },
            { label: 'MK', color: colors.mk, matches: m => m.kalagayan === 'MK' },
        ]
        : [
            { label: 'Eligible', color: colors.eligible, matches: m => m.eligible },
            { label: 'Not eligible', color: colors.notEligible, matches: m => !m.eligible },
        ];

    // Eligible share in the middle of the donut.
    const centerText = {
        id: 'centerText',
        afterDraw(chart) {
            if (!chart.ctx || !chart.chartArea) return;
            const { ctx, chartArea: { left, right, top, bottom } } = chart;
            const cx = (left + right) / 2, cy = (top + bottom) / 2;
            const eligible = members.filter(m => m.eligible).length;
            const pct = total ? Math.round(eligible / total * 100) : 0;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = css('--text') || '#111';
            ctx.font = '700 26px ' + Chart.defaults.font.family;
            ctx.fillText(pct + '%', cx, cy - 8);
            ctx.fillStyle = css('--text-muted') || '#667085';
            ctx.font = '600 11px ' + Chart.defaults.font.family;
            ctx.fillText('eligible', cx, cy + 14);
            ctx.restore();
        },
    };

    const surface = () => css('--surface') || '#fff';
    const legend = document.getElementById('chart-legend');
    const description = document.getElementById('chart-description');
    let detailed = false;
    let eligibilityChart = null;

    function buildEligibilityChart(cats) {
        if (!chartsOK) return;
        if (eligibilityChart) { eligibilityChart.destroy(); eligibilityChart = null; }
        try {
        eligibilityChart = new Chart(document.getElementById('eligibility-chart'), {
            type: 'doughnut',
            data: {
                labels: cats.map(c => c.label),
                datasets: [{ data: cats.map(c => c.count), backgroundColor: cats.map(c => c.color), borderWidth: 3, borderColor: surface(), hoverOffset: 8 }],
            },
            options: {
                cutout: '68%',
                responsive: true,
                maintainAspectRatio: false,
                animation: reduceMotion ? false : { animateRotate: true, animateScale: true, duration: 1000, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} (${total ? Math.round(ctx.parsed / total * 100) : 0}%)` } },
                },
            },
            plugins: [centerText],
        });
        } catch (err) {
            console.error('Eligibility chart failed:', err);
            chartsOK = false;
            showChartError();
        }
    }

    function renderEligibility() {
        const cats = categories(detailed).map(c => ({ ...c, count: members.filter(c.matches).length }));
        buildEligibilityChart(cats); // fresh chart = animated sweep with every slice colour
        if (eligibilityChart) eligibilityChart.canvas.setAttribute('aria-label',
            `Doughnut chart showing ${cats.map(c => `${c.label}: ${c.count}`).join(', ')}`);

        legend.replaceChildren(...cats.map((c, i) => {
            const row = document.createElement('div');
            row.className = 'legend-row';
            const label = document.createElement('span');
            label.className = 'legend-label';
            const swatch = document.createElement('span');
            swatch.className = 'legend-swatch';
            swatch.style.backgroundColor = c.color;
            const text = document.createElement('span');
            text.textContent = c.label;
            label.append(swatch, text);
            const value = document.createElement('span');
            value.className = 'legend-value';
            const strong = document.createElement('strong');
            strong.textContent = c.count;
            value.append(strong, document.createTextNode(`${total ? Math.round(c.count / total * 100) : 0}%`));
            row.append(label, value);
            row.addEventListener('mouseenter', () => {
                if (!eligibilityChart || !eligibilityChart.ctx) return;
                eligibilityChart.setActiveElements([{ datasetIndex: 0, index: i }]);
                eligibilityChart.update('none');
            });
            row.addEventListener('mouseleave', () => {
                if (!eligibilityChart || !eligibilityChart.ctx) return;
                eligibilityChart.setActiveElements([]);
                eligibilityChart.update('none');
            });
            return row;
        }));

        description.textContent = detailed
            ? 'Eligibility split by endorsement, with MK shown separately.'
            : 'Eligible and not eligible members.';
    }

    // Numbers inside each segment, plus the reported % after the bar.
    const segmentLabels = {
        id: 'segmentLabels',
        afterDatasetsDraw(chart) {
            if (!chart.ctx || !chart.chartArea) return;
            const { ctx } = chart;
            const font = chartFont();
            ctx.save();
            ctx.textBaseline = 'middle';
            chart.data.datasets.forEach((ds, i) => {
                const meta = chart.getDatasetMeta(i);
                if (meta.hidden) return;
                meta.data.forEach((bar, idx) => {
                    const v = ds.data[idx];
                    if (!v || !Number.isFinite(bar.x) || !Number.isFinite(bar.base)) return;
                    if (Math.abs(bar.x - bar.base) < 20) return; // too narrow to fit a number yet
                    ctx.font = '700 13px ' + font;
                    ctx.fillStyle = '#fff';
                    ctx.textAlign = 'center';
                    ctx.fillText(String(v), (bar.x + bar.base) / 2, bar.y);
                });
            });
            const done = chart.data.datasets[0].data;
            const pending = chart.data.datasets[1].data;
            chart.getDatasetMeta(1).data.forEach((bar, idx) => {
                const sum = (done[idx] || 0) + (pending[idx] || 0);
                if (!sum || !Number.isFinite(bar.x)) return;
                ctx.font = '600 11px ' + font;
                ctx.fillStyle = css('--text-muted') || '#667085';
                ctx.textAlign = 'left';
                ctx.fillText(Math.round((done[idx] || 0) / sum * 100) + '%', bar.x + 10, bar.y);
            });
            ctx.restore();
        },
    };

    let progressChart = null;
    if (chartsOK) {
        try {
            const segment = color => ({
                backgroundColor: color, borderColor: surface(), borderWidth: 2,
                borderRadius: 10, borderSkipped: false, barThickness: 34,
            });
            progressChart = new Chart(document.getElementById('progress-chart'), {
                type: 'bar',
                data: {
                    labels: kalagayanTypes,
                    datasets: [
                        { label: 'Created & reported', data: [], ...segment(colors.done) },
                        { label: 'Not yet done', data: [], ...segment(colors.pending) },
                    ],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { right: 44 } },
                    animation: reduceMotion ? false : { duration: 900, easing: 'easeOutQuart', delay: ctx => ctx.type === 'data' ? ctx.dataIndex * 120 : 0 },
                    scales: {
                        x: { stacked: true, display: false, min: 0 },
                        y: { stacked: true, grid: { display: false }, border: { display: false }, ticks: { font: { weight: '700', size: 13 } } },
                    },
                    plugins: { legend: { display: false } },
                },
                plugins: [segmentLabels],
            });
        } catch (err) {
            console.error('Progress chart failed:', err);
            chartsOK = false;
        }
    }
    if (!chartsOK) showChartError();

    function renderProgress() {
        if (!progressChart) return;
        const done = kalagayanTypes.map(k => members.filter(m => m.kalagayan === k && m.reported).length);
        const pending = kalagayanTypes.map(k => members.filter(m => m.kalagayan === k && !m.reported).length);
        progressChart.data.datasets[0].data = done;
        progressChart.data.datasets[1].data = pending;
        progressChart.data.datasets.forEach(d => { d.borderColor = surface(); });
        progressChart.options.scales.x.max = Math.max(1, ...kalagayanTypes.map((k, i) => done[i] + pending[i]));
        progressChart.options.scales.y.ticks.color = css('--text') || '#111';
        progressChart.update();
        progressChart.canvas.setAttribute('aria-label',
            `Stacked bar chart of reported versus not yet done by kalagayan: ${kalagayanTypes.map((k, i) => `${k} ${done[i]} reported, ${pending[i]} not yet done`).join('; ')}`);
    }

    document.querySelectorAll('.seg-btn').forEach(btn => btn.addEventListener('click', () => {
        detailed = btn.dataset.view === 'detailed';
        document.querySelectorAll('.seg-btn').forEach(b => {
            const active = b === btn;
            b.classList.toggle('is-active', active);
            b.setAttribute('aria-pressed', String(active));
        });
        renderEligibility();
    }));

    /* ---------- Report form ---------- */
    const reportDialog = document.getElementById('report-dialog');
    const reportForm = document.getElementById('report-form');
    const reportNote = document.getElementById('report-note');
    const reportAssure = document.getElementById('report-assure');
    const reportSubmit = document.getElementById('report-submit');
    let pendingMember = null;

    function updateReportState() {
        document.getElementById('report-count').textContent = `${reportNote.value.length} / 500`;
        reportSubmit.disabled = !(reportNote.value.trim() && reportAssure.checked);
    }
    reportNote.addEventListener('input', updateReportState);
    reportAssure.addEventListener('change', updateReportState);
    reportDialog.addEventListener('close', () => { pendingMember = null; });

    function openReport(member) {
        pendingMember = member;
        document.getElementById('report-member').textContent = `${member.name} · ${member.kalagayan}`;
        reportNote.value = '';
        reportAssure.checked = false;
        updateReportState();
        reportDialog.showModal();
        reportNote.focus();
    }

    reportForm.addEventListener('submit', e => {
        e.preventDefault();
        if (reportSubmit.disabled || !pendingMember) return;
        const member = pendingMember;
        const note = reportNote.value.trim();
        reportDialog.close();
        markReported(member, note);
    });

    /* ---------- Table ---------- */
    const state = { search: '', eligibility: 'all', status: 'all', kalagayan: 'all', age: 'all', sortKey: null, sortDir: 'asc', page: 1 };
    const rowsEl = document.getElementById('member-rows');
    const emptyEl = document.getElementById('empty-state');
    const ageSort = document.getElementById('age-sort');
    let justDoneId = null;

    const ageMatches = m => state.age === 'all'
        || (m.reported && m.reportedAt && (state.age === 'under' ? ageYears(m) === 0 : ageYears(m) >= 1));

    function filtered() {
        const q = state.search.trim().toLowerCase();
        let list = members.filter(m =>
            (!q || m.name.toLowerCase().includes(q) || m.kalagayan.toLowerCase().includes(q)) &&
            (state.eligibility === 'all' || (state.eligibility === 'eligible') === m.eligible) &&
            (state.status === 'all' || (state.status === 'done') === m.reported) &&
            (state.kalagayan === 'all' || m.kalagayan === state.kalagayan) &&
            ageMatches(m)
        );
        if (state.sortKey) {
            const dir = state.sortDir === 'asc' ? 1 : -1;
            list = [...list].sort((a, b) => {
                if (state.sortKey === 'age') {
                    // asc = newest first, desc = longest first; unreported always last
                    const av = ageDays(a), bv = ageDays(b);
                    if (av === null && bv === null) return 0;
                    if (av === null) return 1;
                    if (bv === null) return -1;
                    return (av - bv) * dir;
                }
                const av = a[state.sortKey], bv = b[state.sortKey];
                if (typeof av === 'string') return av.localeCompare(bv, undefined, { numeric: true }) * dir;
                return (Number(av) - Number(bv)) * dir;
            });
        }
        return list;
    }

    function makeBadge(text, className, title) {
        const badge = document.createElement('span');
        badge.className = `table-badge ${className}`;
        badge.textContent = text;
        if (title) badge.title = title;
        return badge;
    }

    const lockSvg = '<svg viewBox="0 0 16 16" aria-hidden="true"><rect x="3.5" y="7" width="9" height="6.5" rx="1.5"/><path d="M5.5 7V5.2a2.5 2.5 0 0 1 5 0V7"/></svg>';

    function statusCell(member) {
        if (member.reported) {
            const done = document.createElement('span');
            done.className = 'status-done' + (member.id === justDoneId ? ' just-done' : '');
            if (member.note) done.title = member.note;
            done.innerHTML = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="m3.5 8.5 3 3 6-7"/></svg>';
            done.append(document.createTextNode('Created & Reported'));
            return done;
        }
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'status-btn';

        if (!member.eligible) {
            btn.classList.add('is-locked');
            btn.setAttribute('aria-disabled', 'true');
            btn.title = "Not eligible for R2-10, so it can't be marked as created & reported";
            btn.setAttribute('aria-label', `Not yet done. ${member.name} is not eligible, so R2-10 can't be reported.`);
            btn.innerHTML = lockSvg;
            btn.append(document.createTextNode('Not yet done'));
            btn.addEventListener('click', () => showToast(`${member.name} is not eligible, so R2-10 can't be reported.`));
            return btn;
        }

        btn.setAttribute('aria-haspopup', 'dialog');
        btn.setAttribute('aria-label', `Mark R2-10 as created and reported for ${member.name}`);
        const idle = document.createElement('span');
        idle.className = 'on-idle';
        idle.textContent = 'Not yet done';
        const hover = document.createElement('span');
        hover.className = 'on-hover';
        hover.textContent = 'Mark as reported';
        btn.append(idle, hover);
        btn.addEventListener('click', () => openReport(member));
        return btn;
    }

    function markReported(member, note) {
        if (member.reported || !member.eligible) return; // permanent + eligible only
        member.reported = true;
        member.reportedAt = toISO(startOfToday());
        member.note = note;
        justDoneId = member.id;
        persist();
        renderTable();
        renderStats();
        renderProgress();
        showToast(`${member.name}: R2-10 marked as created & reported`);
        setTimeout(() => { justDoneId = null; }, 600);
    }

    function renderTable() {
        const list = filtered();
        const pages = Math.max(1, Math.ceil(list.length / PAGE_SIZE));
        state.page = Math.min(state.page, pages);
        const start = (state.page - 1) * PAGE_SIZE;
        const visible = list.slice(start, start + PAGE_SIZE);

        rowsEl.replaceChildren(...visible.map(member => {
            const row = document.createElement('tr');

            const name = document.createElement('td');
            const cell = document.createElement('div');
            cell.className = 'member-cell';
            const avatar = document.createElement('span');
            avatar.className = 'avatar';
            avatar.style.background = avatarColors[member.id % avatarColors.length];
            avatar.setAttribute('aria-hidden', 'true');
            avatar.textContent = member.name.replace(/\D/g, '') || member.name[0];
            cell.append(avatar, document.createTextNode(member.name));
            name.append(cell);

            const kalagayan = document.createElement('td');
            const kBadge = document.createElement('span');
            kBadge.className = `kalagayan-badge k-${member.kalagayan}`;
            kBadge.textContent = member.kalagayan;
            kalagayan.append(kBadge);

            const eligibility = document.createElement('td');
            eligibility.append(member.kalagayan === 'MK'
                ? makeBadge('Eligible (auto)', 'badge-eligible', 'MK is automatically eligible')
                : makeBadge(member.eligible ? 'Eligible' : 'Not eligible', member.eligible ? 'badge-eligible' : 'badge-not-eligible'));

            const status = document.createElement('td');
            status.append(statusCell(member));

            const age = document.createElement('td');
            const ageEl = document.createElement('span');
            if (member.reported && member.reportedAt) {
                ageEl.className = 'age-value';
                ageEl.textContent = formatAge(member.reportedAt);
                ageEl.title = 'Reported on ' + parseISO(member.reportedAt).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
            } else {
                ageEl.className = 'age-empty';
                ageEl.textContent = '—';
                ageEl.setAttribute('aria-label', 'Not reported yet');
            }
            age.append(ageEl);

            row.append(name, kalagayan, eligibility, status, age);
            return row;
        }));

        emptyEl.hidden = list.length !== 0;
        document.getElementById('page-info').textContent = list.length
            ? `Showing ${start + 1}–${start + visible.length} of ${list.length}`
            : 'No results';
        document.getElementById('page-label').textContent = `Page ${state.page} of ${pages}`;
        document.getElementById('prev-page').disabled = state.page <= 1;
        document.getElementById('next-page').disabled = state.page >= pages;
    }

    const resetPageAndRender = () => { state.page = 1; renderTable(); };

    document.getElementById('member-search').addEventListener('input', e => { state.search = e.target.value; resetPageAndRender(); });
    document.getElementById('kalagayan-filter').addEventListener('change', e => { state.kalagayan = e.target.value; resetPageAndRender(); });

    function bindChips(selector, key, attr) {
        document.querySelectorAll(selector).forEach(chip => chip.addEventListener('click', () => {
            state[key] = chip.dataset[attr];
            document.querySelectorAll(selector).forEach(c => c.classList.toggle('is-active', c === chip));
            resetPageAndRender();
        }));
    }
    bindChips('[data-filter]', 'eligibility', 'filter');
    bindChips('[data-status]', 'status', 'status');
    bindChips('[data-age]', 'age', 'age');

    document.getElementById('clear-filters').addEventListener('click', () => {
        Object.assign(state, { search: '', eligibility: 'all', status: 'all', kalagayan: 'all', age: 'all' });
        document.getElementById('member-search').value = '';
        document.getElementById('kalagayan-filter').value = 'all';
        document.querySelectorAll('[data-filter]').forEach(c => c.classList.toggle('is-active', c.dataset.filter === 'all'));
        document.querySelectorAll('[data-status]').forEach(c => c.classList.toggle('is-active', c.dataset.status === 'all'));
        document.querySelectorAll('[data-age]').forEach(c => c.classList.toggle('is-active', c.dataset.age === 'all'));
        resetPageAndRender();
    });

    // Sorting: the column headers and the "Sort by age" select stay in sync.
    function setSort(key, dir) {
        state.sortKey = key;
        state.sortDir = dir;
        document.querySelectorAll('.member-table th').forEach(th => th.setAttribute('aria-sort', 'none'));
        if (key) {
            document.querySelector(`[data-sort="${key}"]`).closest('th')
                .setAttribute('aria-sort', dir === 'asc' ? 'ascending' : 'descending');
        }
        ageSort.value = key === 'age' ? (dir === 'asc' ? 'newest' : 'longest') : '';
        resetPageAndRender();
    }
    document.querySelectorAll('.sort-btn').forEach(btn => btn.addEventListener('click', () => {
        const key = btn.dataset.sort;
        setSort(key, state.sortKey === key && state.sortDir === 'asc' ? 'desc' : 'asc');
    }));
    ageSort.addEventListener('change', () => {
        if (ageSort.value === 'newest') setSort('age', 'asc');
        else if (ageSort.value === 'longest') setSort('age', 'desc');
        else setSort(null, 'asc');
    });

    document.getElementById('prev-page').addEventListener('click', () => { state.page--; renderTable(); });
    document.getElementById('next-page').addEventListener('click', () => { state.page++; renderTable(); });

    /* ---------- Entries with missing data ---------- */
    const MISSING_KEY = 'delist-missing-v1';
    const missingRecords = [
        { id: 'm1', name: 'Sample Member 11', registry: 'REG-2019-0142', baptism: '',           entry: '' },
        { id: 'm2', name: 'Sample Member 12', registry: 'REG-2021-0387', baptism: '2003-05-18', entry: '' },
        { id: 'm3', name: 'Sample Member 13', registry: 'REG-2016-0059', baptism: '',           entry: '2016-09-04' },
        { id: 'm4', name: 'Sample Member 14', registry: 'REG-2023-0721', baptism: '',           entry: '' },
        { id: 'm5', name: 'Sample Member 15', registry: 'REG-2018-0210', baptism: '1998-11-22', entry: '' },
    ];
    try {
        const saved = JSON.parse(localStorage.getItem(MISSING_KEY) || '{}');
        missingRecords.forEach(r => { const s = saved[r.id]; if (s) { r.baptism = s.baptism || ''; r.entry = s.entry || ''; } });
    } catch (e) {}
    const persistMissing = () => {
        try {
            const out = {};
            missingRecords.forEach(r => { out[r.id] = { baptism: r.baptism, entry: r.entry }; });
            localStorage.setItem(MISSING_KEY, JSON.stringify(out));
        } catch (e) {}
    };

    const missingDialog = document.getElementById('missing-dialog');
    const missingRowsEl = document.getElementById('missing-rows');
    const missingSave = document.getElementById('missing-save');
    const missingHint = document.getElementById('missing-hint');
    const isComplete = r => Boolean(r.baptism && r.entry);
    const stillMissing = () => missingRecords.filter(r => !isComplete(r));
    let drafts = {}; // record id -> { baptism, entry } while editing

    function renderMissingCount() {
        const n = stillMissing().length;
        const badge = document.getElementById('missing-count');
        badge.textContent = n;
        badge.hidden = n === 0;
        document.getElementById('missing-sub').textContent = n
            ? `${n} ${n === 1 ? 'entry needs' : 'entries need'} updating`
            : 'Nothing left to update';
    }

    function changedIds() {
        return Object.keys(drafts).filter(id => {
            const r = missingRecords.find(x => x.id === id);
            return drafts[id].baptism !== r.baptism || drafts[id].entry !== r.entry;
        });
    }
    function updateSaveState() {
        const n = changedIds().length;
        missingSave.disabled = n === 0;
        missingHint.textContent = n ? `${n} unsaved ${n === 1 ? 'change' : 'changes'}` : '';
    }

    function makeDateInput(record, field, label) {
        const input = document.createElement('input');
        input.type = 'date';
        input.className = 'date-input';
        input.max = toISO(startOfToday());
        input.value = drafts[record.id] ? drafts[record.id][field] : record[field];
        input.setAttribute('aria-label', `${label} for ${record.name}`);
        const flag = () => input.classList.toggle('is-missing', !input.value);
        flag();
        input.addEventListener('input', () => {
            drafts[record.id] = { baptism: record.baptism, entry: record.entry, ...(drafts[record.id] || {}), [field]: input.value };
            flag();
            updateSaveState();
        });
        return input;
    }

    function renderMissingRows() {
        const list = stillMissing();
        document.getElementById('missing-table').hidden = list.length === 0;
        document.getElementById('missing-empty').hidden = list.length !== 0;
        missingRowsEl.replaceChildren(...list.map(record => {
            const row = document.createElement('tr');
            row.dataset.id = record.id;
            const name = document.createElement('td');
            name.textContent = record.name;
            name.style.fontWeight = '650';
            const registry = document.createElement('td');
            registry.className = 'registry';
            registry.textContent = record.registry;
            const baptism = document.createElement('td');
            baptism.append(makeDateInput(record, 'baptism', 'Date of baptism'));
            const entry = document.createElement('td');
            entry.append(makeDateInput(record, 'entry', 'Date of entry'));
            row.append(name, registry, baptism, entry);
            return row;
        }));
    }

    document.getElementById('missing-btn').addEventListener('click', () => {
        drafts = {};
        renderMissingCount();
        renderMissingRows();
        updateSaveState();
        missingDialog.showModal();
    });
    missingDialog.addEventListener('close', () => { drafts = {}; });

    missingSave.addEventListener('click', () => {
        const ids = changedIds();
        if (!ids.length) return;
        const today = toISO(startOfToday());
        if (ids.some(id => drafts[id].baptism > today || drafts[id].entry > today)) {
            showToast("Dates can't be in the future.");
            return;
        }
        const completed = [];
        ids.forEach(id => {
            const r = missingRecords.find(x => x.id === id);
            const wasComplete = isComplete(r);
            r.baptism = drafts[id].baptism;
            r.entry = drafts[id].entry;
            if (!wasComplete && isComplete(r)) completed.push(r.id);
        });
        drafts = {};
        persistMissing();
        renderMissingCount();
        updateSaveState();

        if (completed.length) {
            // Flash completed rows green, then let them leave the list.
            missingRowsEl.querySelectorAll('tr').forEach(tr => { if (completed.includes(tr.dataset.id)) tr.classList.add('is-complete'); });
            setTimeout(renderMissingRows, reduceMotion ? 0 : 900);
            showToast(`${completed.length} ${completed.length === 1 ? 'entry' : 'entries'} completed. Eligibility will be generated automatically.`);
        } else {
            renderMissingRows();
            showToast('Changes saved.');
        }
    });

    /* ---------- Init ---------- */
    renderStats();
    renderEligibility();
    renderProgress();
    renderTable();
    renderMissingCount();

    // Re-theme charts when the light/dark switch changes.
    new MutationObserver(() => { renderEligibility(); renderProgress(); })
        .observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
});
</script>
@endpush