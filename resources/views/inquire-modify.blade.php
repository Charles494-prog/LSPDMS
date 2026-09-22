@extends('layouts.app')

@section('title', 'Inquire / Modify Member')

@section('content')

<div class="reg-page">

    <div class="reg-hero">
        <h1>Member Registry</h1>
        <p>Look up a member by name or registry number to view or update their record.</p>
    </div>

    <div class="reg-search-card">
        <div class="reg-search-row">
            <svg class="reg-search-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6"/>
                <path d="M20 20L16.5 16.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
            <input
                type="text"
                id="reg-search-input"
                placeholder="Full name or registry number"
                autocomplete="off"
            >
            <button type="button" id="reg-search-btn">Search</button>
        </div>
        <div id="reg-search-error" class="reg-error" style="display:none;"></div>

        {{-- Remove this block once wired to the real database --}}
        <div class="reg-demo-note">
            <span>Demo data — try:</span>
            <button type="button" class="reg-chip" data-q="Charles Andrei Galvan">Charles Andrei Galvan</button>
            <button type="button" class="reg-chip" data-q="REG-2019-0087">REG-2019-0087</button>
            <button type="button" class="reg-chip" data-q="Renato Bautista">Renato Bautista</button>
        </div>
    </div>

</div>

{{-- ===================== RECORD PANEL ===================== --}}
<div id="reg-modal-overlay" class="reg-modal-overlay" style="display:none;">
    <div class="reg-modal">

        <div class="reg-modal-band">
            <div>
                <div class="reg-modal-name" id="rm-name-display">—</div>
                <div class="reg-modal-registry" id="rm-registry-display">—</div>
            </div>
            <button type="button" id="reg-modal-close" class="reg-close-btn" aria-label="Close">&times;</button>
        </div>

        <form id="reg-form">
            <input type="hidden" id="rm-id" name="id">

            <div class="reg-rows">

                <div class="reg-row">
                    <label for="rm-name">Name</label>
                    <input type="text" id="rm-name" name="name">
                </div>

                <div class="reg-row">
                    <label for="rm-registry">Registry Number</label>
                    <input type="text" id="rm-registry" name="registry_number">
                </div>

                <div class="reg-row">
                    <label for="rm-gender">Gender</label>
                    <select id="rm-gender" name="gender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <div class="reg-row">
                    <label for="rm-kapisanan">Kapisanan</label>
                    <input type="text" id="rm-kapisanan" name="kapisanan">
                </div>

                <div class="reg-row">
                    <label for="rm-purok">Purok</label>
                    <input type="text" id="rm-purok" name="purok">
                </div>

                <div class="reg-row">
                    <label for="rm-grupo">Grupo</label>
                    <input type="text" id="rm-grupo" name="grupo">
                </div>

                <div class="reg-row">
                    <label for="rm-baptism">Date of Baptism</label>
                    <input type="date" id="rm-baptism" name="date_of_baptism">
                </div>

                <div class="reg-row">
                    <label for="rm-evangelist">Evangelist</label>
                    <input type="text" id="rm-evangelist" name="evangelist">
                </div>

                <div class="reg-divider">
                    <span>Locked — office use only</span>
                </div>

                <div class="reg-row reg-row-locked">
                    <label>Date of Entry</label>
                    <div class="reg-locked-value">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="5" y="10" width="14" height="10" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.5"/></svg>
                        <span id="rm-entry-display">—</span>
                    </div>
                </div>

                <div class="reg-row reg-row-locked">
                    <label>R2-10 Eligibility</label>
                    <div class="reg-locked-value reg-secret-row">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="5" y="10" width="14" height="10" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.5"/></svg>
                        <span id="rm-r210-display">••••••••••</span>
                        <button type="button" id="rm-r210-toggle" class="reg-eye-btn" title="Show">&#128065;</button>
                    </div>
                </div>

                <div class="reg-row reg-row-locked">
                    <label>Status</label>
                    <div class="reg-locked-value">
                        <span id="rm-status-badge" class="reg-badge">—</span>
                    </div>
                </div>

            </div>

            <div class="reg-modal-actions">
                <span id="reg-save-msg" class="reg-save-msg"></span>
                <button type="button" id="reg-cancel-btn">Cancel</button>
                <button type="submit" id="reg-save-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root {
    --ink: #1C2B39;
    --muted: #64707C;
    --bg: #F3F5F7;
    --card: #FFFFFF;
    --line: #DCE1E6;
    --accent: #9C7A3C;
    --accent-deep: #6E5527;
    --danger: #B3261E;
    --success: #2E7D4F;
    --warn: #A5690B;
}

.reg-page { font-family: 'IBM Plex Sans', sans-serif; color: var(--ink); max-width: 640px; }

.reg-hero h1 {
    font-family: 'Fraunces', serif;
    font-weight: 600;
    font-size: 2.1rem;
    margin: 0 0 .35rem;
    letter-spacing: -.01em;
}
.reg-hero p { margin: 0 0 1.5rem; color: var(--muted); font-size: .97rem; max-width: 48ch; }

.reg-search-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 1.1rem 1.2rem;
}
.reg-search-row { display: flex; align-items: center; gap: .6rem; }
.reg-search-icon { width: 20px; height: 20px; color: var(--muted); flex-shrink: 0; }
.reg-search-row input {
    flex: 1;
    border: none;
    border-bottom: 1.5px solid var(--line);
    padding: .5rem .1rem;
    font-size: 1rem;
    font-family: inherit;
    color: var(--ink);
    background: transparent;
}
.reg-search-row input:focus {
    outline: none;
    border-bottom-color: var(--accent);
}
.reg-search-row button {
    background: var(--ink);
    color: #fff;
    border: none;
    border-radius: 7px;
    padding: .55rem 1.1rem;
    font-size: .92rem;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
}
.reg-search-row button:hover { background: var(--accent-deep); }

.reg-error {
    color: var(--danger);
    font-size: .87rem;
    margin-top: .7rem;
    padding-left: .1rem;
}

.reg-demo-note {
    margin-top: .9rem;
    padding-top: .8rem;
    border-top: 1px dashed var(--line);
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    align-items: center;
    font-size: .8rem;
    color: var(--muted);
}
.reg-chip {
    border: 1px solid var(--line);
    background: var(--bg);
    color: var(--ink);
    border-radius: 999px;
    padding: .25rem .7rem;
    font-size: .8rem;
    cursor: pointer;
    font-family: inherit;
}
.reg-chip:hover { border-color: var(--accent); color: var(--accent-deep); }

/* ---- Modal ---- */
.reg-modal-overlay {
    position: fixed; inset: 0; background: rgba(20, 26, 33, .55);
    display: flex; align-items: center; justify-content: center; z-index: 1000;
    padding: 1.2rem;
}
.reg-modal {
    background: var(--card);
    border-radius: 12px;
    width: 100%; max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    font-family: 'IBM Plex Sans', sans-serif;
    animation: reg-pop .16s ease-out;
    box-shadow: 0 20px 50px rgba(20,26,33,.25);
}
@keyframes reg-pop {
    from { opacity: 0; transform: translateY(6px) scale(.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.reg-modal-band {
    background: var(--ink);
    color: #fff;
    padding: 1.3rem 1.5rem;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.reg-modal-name { font-family: 'Fraunces', serif; font-size: 1.4rem; font-weight: 600; }
.reg-modal-registry { font-size: .85rem; color: #C9CFD6; margin-top: .2rem; letter-spacing: .02em; }
.reg-close-btn { background: none; border: none; color: #fff; font-size: 1.4rem; cursor: pointer; line-height: 1; opacity: .8; }
.reg-close-btn:hover { opacity: 1; }

.reg-rows { padding: 1.3rem 1.5rem .3rem; }

.reg-row {
    display: grid;
    grid-template-columns: 160px 1fr;
    align-items: center;
    gap: 1rem;
    padding: .6rem 0;
    border-bottom: 1px solid var(--line);
}
.reg-row label { font-size: .85rem; color: var(--muted); font-weight: 500; }
.reg-row input, .reg-row select {
    border: none;
    border-bottom: 1.5px solid transparent;
    padding: .3rem .1rem;
    font-size: .95rem;
    font-family: inherit;
    color: var(--ink);
    background: transparent;
    width: 100%;
}
.reg-row input:hover, .reg-row select:hover { border-bottom-color: var(--line); }
.reg-row input:focus, .reg-row select:focus {
    outline: none;
    border-bottom-color: var(--accent);
}

.reg-divider {
    margin: .9rem 0 .2rem;
    text-align: right;
}
.reg-divider span {
    font-size: .72rem;
    color: var(--muted);
    background: var(--bg);
    padding: .2rem .55rem;
    border-radius: 999px;
}

.reg-row-locked { background: var(--bg); margin: 0 -1.5rem; padding: .6rem 1.5rem; border-bottom: 1px solid var(--line); }
.reg-row-locked label { color: var(--muted); }
.reg-locked-value { display: flex; align-items: center; gap: .5rem; font-size: .95rem; color: var(--ink); }
.reg-locked-value svg { width: 15px; height: 15px; color: var(--muted); flex-shrink: 0; }

.reg-secret-row { letter-spacing: .05em; }
.reg-eye-btn {
    margin-left: auto;
    border: 1px solid var(--line);
    background: #fff;
    border-radius: 6px;
    padding: .15rem .45rem;
    cursor: pointer;
    font-size: .85rem;
}

.reg-badge {
    display: inline-block;
    font-size: .82rem;
    font-weight: 500;
    padding: .2rem .65rem;
    border-radius: 999px;
}
.reg-badge.is-active { background: #E4F3EA; color: var(--success); }
.reg-badge.is-inactive { background: #EAEDEF; color: var(--muted); }
.reg-badge.is-transferred { background: #FBF0DE; color: var(--warn); }

.reg-modal-actions {
    display: flex; align-items: center; gap: .6rem;
    padding: 1.1rem 1.5rem 1.4rem;
}
.reg-save-msg { font-size: .85rem; color: var(--success); margin-right: auto; }
.reg-modal-actions button {
    border: none; border-radius: 7px; padding: .55rem 1.1rem;
    font-size: .9rem; font-weight: 500; cursor: pointer; font-family: inherit;
}
#reg-cancel-btn { background: var(--bg); color: var(--ink); }
#reg-save-btn { background: var(--ink); color: #fff; }
#reg-save-btn:hover { background: var(--accent-deep); }
#reg-save-btn:disabled { opacity: .55; cursor: not-allowed; }

@media (max-width: 520px) {
    .reg-row { grid-template-columns: 1fr; gap: .15rem; }
    .reg-row-locked { margin: 0 -1.1rem; padding: .6rem 1.1rem; }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ---------------------------------------------------------------
    // DEMO DATA — delete this array once the search/save calls below
    // are pointed at your real /members endpoints.
    // ---------------------------------------------------------------
    const DEMO_MEMBERS = [
        {
            id: 1,
            name: 'Charles Andrei Galvan',
            registry_number: 'REG-2024-0113',
            gender: 'Male',
            kapisanan: 'Kapisanang Bayan',
            purok: 'Purok 3',
            grupo: 'Grupo A',
            date_of_baptism: '2010-06-12',
            evangelist: 'Ptr. Ramon Dela Cruz',
            date_of_entry: '2010-06-15',
            r2_10_eligibility: 'Eligible - Tier 2',
            status: 'Active',
        },
        {
            id: 2,
            name: 'Maria Fe Santos',
            registry_number: 'REG-2019-0087',
            gender: 'Female',
            kapisanan: 'Kapisanang Kabataan',
            purok: 'Purok 7',
            grupo: 'Grupo C',
            date_of_baptism: '2005-09-03',
            evangelist: 'Ptr. Isagani Ramos',
            date_of_entry: '2005-09-10',
            r2_10_eligibility: 'Not Eligible',
            status: 'Active',
        },
        {
            id: 3,
            name: 'Juan Miguel Tolentino',
            registry_number: 'REG-2015-0042',
            gender: 'Male',
            kapisanan: 'Kapisanang Bayan',
            purok: 'Purok 1',
            grupo: 'Grupo B',
            date_of_baptism: '1998-02-20',
            evangelist: 'Ptr. Ramon Dela Cruz',
            date_of_entry: '1998-02-25',
            r2_10_eligibility: 'Eligible - Tier 1',
            status: 'Inactive',
        },
        {
            id: 4,
            name: 'Angelica Reyes',
            registry_number: 'REG-2022-0159',
            gender: 'Female',
            kapisanan: 'Kapisanang Kabataan',
            purok: 'Purok 5',
            grupo: 'Grupo A',
            date_of_baptism: '2015-11-08',
            evangelist: 'Ptr. Isagani Ramos',
            date_of_entry: '2015-11-12',
            r2_10_eligibility: 'Under Review',
            status: 'Active',
        },
        {
            id: 5,
            name: 'Renato Bautista',
            registry_number: 'REG-2008-0021',
            gender: 'Male',
            kapisanan: 'Kapisanang Matanda',
            purok: 'Purok 2',
            grupo: 'Grupo D',
            date_of_baptism: '1990-04-17',
            evangelist: 'Ptr. Domingo Aquino',
            date_of_entry: '1990-04-20',
            r2_10_eligibility: 'Eligible - Tier 3',
            status: 'Transferred',
        },
    ];

    const searchInput = document.getElementById('reg-search-input');
    const searchBtn   = document.getElementById('reg-search-btn');
    const searchError = document.getElementById('reg-search-error');
    const overlay     = document.getElementById('reg-modal-overlay');
    const closeBtn    = document.getElementById('reg-modal-close');
    const cancelBtn   = document.getElementById('reg-cancel-btn');
    const form        = document.getElementById('reg-form');
    const saveBtn     = document.getElementById('reg-save-btn');
    const saveMsg     = document.getElementById('reg-save-msg');
    const r210Display = document.getElementById('rm-r210-display');
    const r210Toggle  = document.getElementById('rm-r210-toggle');

    const READONLY_FIELDS = ['date_of_entry', 'r2_10_eligibility', 'status'];
    let currentR210Value = '';
    let r210Visible = false;

    function openModal() { overlay.style.display = 'flex'; }
    function closeModal() {
        overlay.style.display = 'none';
        form.reset();
        saveMsg.textContent = '';
        r210Visible = false;
        r210Display.textContent = '••••••••••';
        r210Toggle.textContent = '\u{1F441}';
    }

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.style.display === 'flex') closeModal();
    });

    // ---- SEARCH ----
    // Swap this block for a fetch('/members/search?query=...') call once
    // the database is wired up. Keep the same fillForm() contract.
    async function runSearch(rawQuery) {
        const query = (rawQuery ?? searchInput.value).trim();
        searchError.style.display = 'none';

        if (!query) {
            searchError.textContent = 'Please enter a name or registry number.';
            searchError.style.display = 'block';
            return;
        }

        const needle = query.toLowerCase();
        const member = DEMO_MEMBERS.find(m =>
            m.name.toLowerCase().includes(needle) ||
            m.registry_number.toLowerCase().includes(needle)
        );

        if (!member) {
            searchError.textContent = 'No matching record found.';
            searchError.style.display = 'block';
            return;
        }

        fillForm(member);
        openModal();
    }

    searchBtn.addEventListener('click', () => runSearch());
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); runSearch(); }
    });
    document.querySelectorAll('.reg-chip').forEach(chip => {
        chip.addEventListener('click', function () {
            searchInput.value = chip.dataset.q;
            runSearch(chip.dataset.q);
        });
    });

    function fillForm(member) {
        document.getElementById('rm-id').value         = member.id ?? '';
        document.getElementById('rm-name').value       = member.name ?? '';
        document.getElementById('rm-registry').value   = member.registry_number ?? '';
        document.getElementById('rm-gender').value     = member.gender ?? '';
        document.getElementById('rm-kapisanan').value  = member.kapisanan ?? '';
        document.getElementById('rm-purok').value      = member.purok ?? '';
        document.getElementById('rm-grupo').value      = member.grupo ?? '';
        document.getElementById('rm-baptism').value    = member.date_of_baptism ?? '';
        document.getElementById('rm-evangelist').value = member.evangelist ?? '';

        document.getElementById('rm-name-display').textContent     = member.name ?? '—';
        document.getElementById('rm-registry-display').textContent = member.registry_number ?? '—';

        // locked fields — display only, never bound to an editable input
        document.getElementById('rm-entry-display').textContent = member.date_of_entry ?? '—';
        currentR210Value = member.r2_10_eligibility ?? '';

        const badge = document.getElementById('rm-status-badge');
        badge.textContent = member.status ?? '—';
        badge.className = 'reg-badge ' + statusClass(member.status);
    }

    function statusClass(status) {
        switch ((status || '').toLowerCase()) {
            case 'active': return 'is-active';
            case 'transferred': return 'is-transferred';
            default: return 'is-inactive';
        }
    }

    // ---- SHOW/HIDE R2-10 ----
    r210Toggle.addEventListener('click', function () {
        r210Visible = !r210Visible;
        r210Display.textContent = r210Visible ? currentR210Value : '••••••••••';
        r210Toggle.textContent = r210Visible ? '\u{1F576}' : '\u{1F441}';
        r210Toggle.title = r210Visible ? 'Hide' : 'Show';
    });

    // ---- SAVE ----
    // Swap the body of this handler for a fetch('/members/{id}', {method:'PUT', ...})
    // call once the database is wired up.
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const id = document.getElementById('rm-id').value;
        const payload = Object.fromEntries(new FormData(form).entries());
        READONLY_FIELDS.forEach(f => delete payload[f]);

        saveBtn.disabled = true;
        saveMsg.textContent = 'Saving...';

        // Demo: update the in-memory record so re-searching shows the edit.
        const member = DEMO_MEMBERS.find(m => String(m.id) === String(id));
        if (member) Object.assign(member, payload);

        setTimeout(function () {
            saveMsg.textContent = 'Saved.';
            saveBtn.disabled = false;
            setTimeout(closeModal, 600);
        }, 400);
    });
});
</script>
@endpush