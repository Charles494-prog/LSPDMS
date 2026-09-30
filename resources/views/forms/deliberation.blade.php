@extends('layouts.app')

@section('title', 'Deliberation Form')

@section('content')
<style>
    .deliberation-page { max-width:1000px; margin:0 auto; }
    .deliberation-hero { margin-bottom:1.2rem; padding:1.5rem clamp(1rem,3vw,2rem); border:1px solid color-mix(in srgb,var(--accent) 18%,var(--border)); border-radius:18px; background:radial-gradient(circle at 90% 15%,color-mix(in srgb,var(--accent) 17%,transparent),transparent 34%),var(--surface); }
    .deliberation-hero a { color:var(--accent); font-size:.84rem; font-weight:700; text-decoration:none; }
    .deliberation-hero h1 { margin:.75rem 0 .35rem; font-size:clamp(1.6rem,4vw,2.2rem); letter-spacing:-.04em; }
    .deliberation-hero p { margin:0; color:var(--text-muted); line-height:1.55; }
    .deliberation-form { padding:clamp(1rem,3vw,1.6rem); border:1px solid var(--border); border-radius:16px; background:var(--surface); box-shadow:0 8px 24px rgba(15,23,42,.05); }
    .deliberation-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
    .delib-field { min-width:0; }
    .delib-field.full { grid-column:1/-1; }
    .delib-field label,.delib-label { display:block; margin-bottom:.4rem; font-size:.85rem; font-weight:750; }
    .required-mark { color:var(--danger); font-weight:800; }
    .delib-field input:not([type=radio]):not([type=checkbox]),.delib-field select { width:100%; min-height:44px; padding:.65rem .75rem; border:1px solid var(--border); border-radius:9px; background:var(--bg); color:var(--text); font:inherit; }
    .delib-field input:focus,.delib-field select:focus { outline:2px solid color-mix(in srgb,var(--accent) 30%,transparent); border-color:var(--accent); }
    .delib-help { margin:.35rem 0 0; color:var(--text-muted); font-size:.76rem; line-height:1.45; }
    .delib-error { min-height:1.05rem; margin:.25rem 0 0; color:var(--danger); font-size:.75rem; }
    .registry-inputs { display:grid; grid-template-columns:repeat(13,minmax(0,1fr)); gap:.28rem; max-width:700px; }
    .registry-inputs input { width:100%; min-width:0; height:48px; padding:.2rem; border:1px solid var(--border); border-radius:7px; background:var(--bg); color:var(--text); font:700 1rem ui-monospace,Consolas,monospace; text-align:center; text-transform:uppercase; }
    .registry-inputs input:focus { outline:2px solid color-mix(in srgb,var(--accent) 30%,transparent); border-color:var(--accent); }
    .registry-options { display:flex; align-items:center; gap:.55rem; margin-top:.65rem; color:var(--text-muted); font-size:.82rem; }
    .registry-options input { width:16px; height:16px; accent-color:var(--accent); }
    .delib-choice-row { display:flex; flex-wrap:wrap; gap:.65rem; }
    .delib-choice { display:inline-flex; align-items:center; gap:.45rem; min-height:42px; padding:.55rem .8rem; border:1px solid var(--border); border-radius:9px; background:var(--bg); cursor:pointer; }
    .delib-choice input { width:16px; height:16px; accent-color:var(--accent); }
    .duplicate-panel { display:grid; gap:.65rem; margin:1rem 0; }
    .duplicate-panel:empty { display:none; }
    .duplicate-alert { padding:.8rem .9rem; border:1px solid #f3c56a; border-radius:10px; background:color-mix(in srgb,#f59e0b 9%,var(--surface)); color:var(--text); font-size:.84rem; line-height:1.5; }
    .duplicate-candidate { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:.75rem; padding:.85rem; border:1px solid var(--border); border-radius:10px; background:var(--bg); }
    .duplicate-candidate strong,.duplicate-candidate span { display:block; }
    .duplicate-candidate span { margin-top:.25rem; color:var(--text-muted); font-size:.78rem; }
    .reuse-controls { display:flex; align-items:center; gap:.45rem; }
    .reuse-controls select { min-height:40px; padding:.4rem; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font:inherit; }
    .reuse-button,.delib-submit { min-height:44px; padding:.65rem 1rem; border:0; border-radius:9px; background:var(--accent); color:#fff; font:inherit; font-weight:750; cursor:pointer; }
    .reuse-button:disabled,.delib-submit:disabled { opacity:.55; cursor:wait; }
    .delib-form-actions { display:flex; justify-content:flex-end; margin-top:1.2rem; padding-top:1rem; border-top:1px solid var(--border); }
    .delib-status { margin-top:.8rem; color:var(--text-muted); font-size:.84rem; }
    .delib-status.error { color:var(--danger); }
    .delib-status.success { color:#15803d; }
    .deliberation-tabs { display:flex; gap:.5rem; margin-bottom:1rem; padding:.35rem; border:1px solid var(--border); border-radius:12px; background:var(--surface); }
    .deliberation-tab { padding:.65rem 1rem; border:0; border-radius:8px; background:transparent; color:var(--text-muted); font:inherit; font-weight:750; cursor:pointer; }
    .deliberation-tab[aria-selected="true"] { background:var(--accent); color:#fff; }
    .subtract-panel { padding:clamp(1rem,3vw,1.6rem); border:1px solid var(--border); border-radius:16px; background:var(--surface); box-shadow:0 8px 24px rgba(15,23,42,.05); }
    .subtract-search { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:.6rem; }
    .subtract-search input { min-height:44px; padding:.65rem .75rem; border:1px solid var(--border); border-radius:9px; background:var(--bg); color:var(--text); font:inherit; }
    .subtract-search button { min-height:44px; padding:.65rem 1rem; border:0; border-radius:9px; background:var(--accent); color:#fff; font:inherit; font-weight:750; cursor:pointer; }
    .subtract-suggestions { display:grid; gap:.4rem; margin:.55rem 0 1rem; }
    .subtract-suggestion { width:100%; padding:.7rem .8rem; border:1px solid var(--border); border-radius:9px; background:var(--bg); color:var(--text); text-align:left; cursor:pointer; }
    .subtract-suggestion:hover { border-color:var(--accent); }
    .subtract-suggestion strong,.subtract-suggestion span { display:block; }
    .subtract-suggestion span { margin-top:.2rem; color:var(--text-muted); font-size:.78rem; }
    .subtract-record[hidden] { display:none; }
    .subtract-record-heading { margin:1.1rem 0 .75rem; font-size:1rem; }
    .subtract-record input[readonly] { background:color-mix(in srgb,var(--border) 35%,var(--surface)); cursor:not-allowed; }
    .subtract-form-actions { display:flex; justify-content:flex-end; margin-top:1rem; }
    .subtract-form-actions button:disabled { opacity:.55; cursor:not-allowed; }
    .delib-confirmation { width:min(560px,calc(100% - 2rem)); max-height:85vh; padding:0; overflow:auto; border:1px solid var(--border); border-radius:16px; background:var(--surface); color:var(--text); box-shadow:0 24px 70px rgba(15,23,42,.25); }
    .delib-confirmation::backdrop { background:rgba(15,23,42,.55); backdrop-filter:blur(2px); }
    .confirmation-content { padding:1.25rem; }
    .confirmation-content h2 { margin:0 0 .45rem; font-size:1.2rem; }
    .confirmation-content p { color:var(--text-muted); line-height:1.5; }
    .confirmation-details { display:grid; grid-template-columns:minmax(100px,.7fr) minmax(0,1.3fr); gap:.45rem .8rem; margin:.9rem 0; padding:.9rem; border-radius:10px; background:var(--bg); font-size:.84rem; }
    .confirmation-details dt { color:var(--text-muted); }
    .confirmation-details dd { margin:0; font-weight:650; overflow-wrap:anywhere; }
    .confirmation-actions { display:flex; justify-content:flex-end; gap:.55rem; margin-top:1rem; }
    .confirmation-actions button { min-height:42px; padding:.55rem .9rem; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font:inherit; font-weight:700; cursor:pointer; }
    .confirmation-actions button.confirm-primary { border-color:var(--accent); background:var(--accent); color:white; }
    @media(max-width:680px) {
        .deliberation-grid { grid-template-columns:1fr; }
        .delib-field.full { grid-column:auto; }
        .registry-inputs { gap:.17rem; }
        .registry-inputs input { height:40px; font-size:.83rem; }
        .duplicate-candidate { grid-template-columns:1fr; }
        .reuse-controls { justify-content:flex-start; }
    }
</style>

<div class="deliberation-page">
    <header class="deliberation-hero">
        <a href="{{ route('form-generator') }}">&larr; Back to Form Generator</a>
        <h1>Deliberation Form</h1>
        <p>Enter a member's information. Asterisks mark required fields. If the member is already recorded, reuse the existing record rather than creating a duplicate.</p>
    </header>

    <nav class="deliberation-tabs" aria-label="Deliberation action">
        <button class="deliberation-tab" type="button" id="addTab" aria-selected="true">Add</button>
        <button class="deliberation-tab" type="button" id="subtractTab" aria-selected="false">Subtract</button>
    </nav>

    <form class="deliberation-form" id="deliberationForm"
        data-duplicate-url="{{ route('forms.monthly.deliberation.duplicates') }}"
        data-store-url="{{ route('forms.monthly.deliberation.store') }}"
        data-subtract-search-url="{{ route('forms.monthly.deliberation.subtract.search') }}"
        data-subtract-url="{{ route('forms.monthly.deliberation.subtract') }}"
        data-csrf="{{ csrf_token() }}"
        data-puroks="{{ json_encode($puroks->map(fn ($purok): array => ['number' => (string) $purok->purok_number, 'name' => $purok->purok_name, 'grupo_start' => $purok->grupo_start, 'grupo_end' => $purok->grupo_end])->values()) }}">
        <div class="deliberation-grid">
            <div class="delib-field full">
                <label for="delib-full-name">Full Name <span class="required-mark" aria-label="required">*</span></label>
                <input id="delib-full-name" name="full_name" type="text" maxlength="150" autocomplete="name" required>
                <p class="delib-error" data-error="full_name"></p>
            </div>

            <div class="delib-field full">
                <span class="delib-label">Registry <span class="required-mark" aria-label="required">*</span></span>
                <div class="registry-inputs" id="registryInputs" aria-label="13-character registry number">
                    @for ($index = 0; $index < 13; $index++)
                        <input type="text" maxlength="1" autocomplete="off" aria-label="Registry character {{ $index + 1 }}" data-registry-character="{{ $index }}" {{ $index === 0 ? 'autofocus' : '' }}>
                    @endfor
                </div>
                <input id="delib-registry" name="registry" type="hidden">
                <label class="registry-options" for="delib-rfid">
                    <input id="delib-rfid" name="is_rfid" type="checkbox" value="1">
                    This member has RFID instead of a registry number
                </label>
                <p class="delib-help">Enter exactly 13 letters or numbers, or select RFID.</p>
                <p class="delib-error" data-error="registry"></p>
            </div>

            <div class="delib-field full">
                <span class="delib-label">Gender <span class="required-mark" aria-label="required">*</span></span>
                <div class="delib-choice-row">
                    <label class="delib-choice"><input type="radio" name="gender" value="Male" required> Male</label>
                    <label class="delib-choice"><input type="radio" name="gender" value="Female" required> Female</label>
                </div>
                <p class="delib-error" data-error="gender"></p>
            </div>

            <div class="delib-field">
                <label for="delib-purok">Purok <span class="required-mark" aria-label="required">*</span></label>
                <select id="delib-purok" name="purok" required>
                    <option value="">Select Purok</option>
                    @foreach ($puroks as $purok)
                        <option value="{{ $purok->purok_number }}">{{ $purok->purok_name ? $purok->purok_number.' - '.$purok->purok_name : 'Purok '.$purok->purok_number }}</option>
                    @endforeach
                </select>
                <p class="delib-error" data-error="purok"></p>
            </div>
            <div class="delib-field">
                <label for="delib-grupo">Grupo <span style="color:var(--text-muted);font-weight:500">(Optional)</span></label>
                <select id="delib-grupo" name="grupo" disabled><option value="">Select Purok first</option></select>
                <p class="delib-error" data-error="grupo"></p>
            </div>

            <div class="delib-field">
                <label for="delib-kapisanan">Kapisanan <span class="required-mark" aria-label="required">*</span></label>
                <select id="delib-kapisanan" name="kapisanan" required>
                    <option value="">Select Kapisanan</option>
                    <option value="Buklod">Buklod</option>
                    <option value="Kadiwa">Kadiwa</option>
                    <option value="Binhi">Binhi</option>
                </select>
                <p class="delib-help" id="kapisananHint">Kapisanan is auto-selected when Date of Birth and Married? are both answered.</p>
                <p class="delib-error" data-error="kapisanan"></p>
            </div>
            <div class="delib-field">
                <label for="delib-status">Status <span class="required-mark" aria-label="required">*</span></label>
                <select id="delib-status" name="status" required>
                    <option value="">Select Status</option>
                    <option value="MS">MS</option>
                    <option value="UWP">UWP</option>
                </select>
                <p class="delib-error" data-error="status"></p>
            </div>

            <div class="delib-field">
                <label for="delib-baptism">Date of Baptism <span style="color:var(--text-muted);font-weight:500">(Optional)</span></label>
                <input id="delib-baptism" name="date_of_baptism" type="date" max="{{ now()->toDateString() }}">
                <p class="delib-error" data-error="date_of_baptism"></p>
            </div>
            <div class="delib-field">
                <label for="delib-evangelist">Evangelist <span style="color:var(--text-muted);font-weight:500">(Optional)</span></label>
                <input id="delib-evangelist" name="evangelist" type="text" maxlength="150">
                <p class="delib-error" data-error="evangelist"></p>
            </div>
            <div class="delib-field">
                <label for="delib-birth">Date of Birth <span style="color:var(--text-muted);font-weight:500">(Optional)</span></label>
                <input id="delib-birth" name="date_of_birth" type="date" max="{{ now()->toDateString() }}">
                <p class="delib-error" data-error="date_of_birth"></p>
            </div>
            <div class="delib-field">
                <label for="delib-married">Married? <span style="color:var(--text-muted);font-weight:500">(Optional)</span></label>
                <select id="delib-married" name="married">
                    <option value="">Not specified</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                </select>
                <p class="delib-error" data-error="married"></p>
            </div>
        </div>

        <div class="duplicate-panel" id="duplicatePanel" aria-live="polite"></div>
        <div class="delib-status" id="delibStatus" role="status" aria-live="polite"></div>
        <div class="delib-form-actions">
            <button class="delib-submit" id="delibSubmit" type="submit">Add to Deliberation</button>
        </div>
    </form>

    <section class="subtract-panel" id="subtractPanel" hidden>
        <p class="delib-help">Search by full name or registry. Choose a suggestion to load the existing record; its information is read-only.</p>
        <div class="subtract-search">
            <input id="subtractSearchInput" type="search" maxlength="100" autocomplete="off" placeholder="Enter a name or registry">
            <button type="button" id="subtractSearchButton">Search</button>
        </div>
        <div class="subtract-suggestions" id="subtractSuggestions" role="listbox" aria-label="Matching records"></div>
        <div class="delib-status" id="subtractSearchStatus" role="status" aria-live="polite"></div>
        <form id="subtractRecordForm" class="subtract-record" hidden>
            <input type="hidden" id="subtractRecordId">
            <h2 class="subtract-record-heading">Selected member (read-only)</h2>
            <div class="deliberation-grid">
                <div class="delib-field"><label for="subtractName">Full Name</label><input id="subtractName" readonly></div>
                <div class="delib-field"><label for="subtractRegistry">Registry</label><input id="subtractRegistry" readonly></div>
                <div class="delib-field"><label for="subtractGender">Gender</label><input id="subtractGender" readonly></div>
                <div class="delib-field"><label for="subtractPurok">Purok</label><input id="subtractPurok" readonly></div>
                <div class="delib-field"><label for="subtractGrupo">Grupo</label><input id="subtractGrupo" readonly></div>
                <div class="delib-field"><label for="subtractKapisanan">Kapisanan</label><input id="subtractKapisanan" readonly></div>
                <div class="delib-field"><label for="subtractBirth">Date of Birth</label><input id="subtractBirth" readonly></div>
                <div class="delib-field"><label for="subtractBaptism">Date of Baptism</label><input id="subtractBaptism" readonly></div>
                <div class="delib-field"><label for="subtractEvangelist">Evangelist</label><input id="subtractEvangelist" readonly></div>
                <div class="delib-field"><label for="subtractStatus">Current Status</label><input id="subtractStatus" readonly></div>
                <div class="delib-field">
                    <label for="subtractCode">Removal Code</label>
                    <select id="subtractCode" required>
                        <option value="">Select code</option>
                        <option value="D">D</option>
                        <option value="DD">DD</option>
                        <option value="E">E</option>
                        <option value="G">G</option>
                        <option value="H">H</option>
                    </select>
                </div>
                <div class="delib-field full">
                    <label for="subtractDescription">Brief Description <span style="color:var(--text-muted);font-weight:500">(Optional)</span></label>
                    <input id="subtractDescription" type="text" maxlength="500" placeholder="Optional reason for changing the status">
                </div>
            </div>
            <div class="delib-status" id="subtractStatusMessage" role="status" aria-live="polite"></div>
            <div class="subtract-form-actions"><button type="submit" class="delib-submit" id="subtractSubmit">Mark INACTIVE</button></div>
        </form>
    </section>
</div>

<dialog class="delib-confirmation" id="addConfirmDialog" aria-labelledby="addConfirmTitle">
    <div class="confirmation-content">
        <h2 id="addConfirmTitle">Confirm Add</h2>
        <p>Are you sure you want to add this member with the following details?</p>
        <dl class="confirmation-details" id="addConfirmDetails"></dl>
        <div class="confirmation-actions">
            <button type="button" data-dialog-cancel>Cancel</button>
            <button type="button" class="confirm-primary" id="confirmAddButton">Yes, add member</button>
        </div>
    </div>
</dialog>
<dialog class="delib-confirmation" id="subtractConfirmDialog" aria-labelledby="subtractConfirmTitle">
    <div class="confirmation-content">
        <h2 id="subtractConfirmTitle">Confirm Subtract</h2>
        <p id="subtractConfirmText">Are you sure you want to make this record inactive?</p>
        <div class="confirmation-actions">
            <button type="button" data-dialog-cancel>No</button>
            <button type="button" class="confirm-primary" id="confirmSubtractButton">Yes</button>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('deliberationForm');
    const registryBoxes = [...document.querySelectorAll('[data-registry-character]')];
    const registryValue = document.getElementById('delib-registry');
    const rfid = document.getElementById('delib-rfid');
    const registryInputs = document.getElementById('registryInputs');
    const nameInput = document.getElementById('delib-full-name');
    const purokInput = document.getElementById('delib-purok');
    const grupoInput = document.getElementById('delib-grupo');
    const birthInput = document.getElementById('delib-birth');
    const marriedInput = document.getElementById('delib-married');
    const kapisananInput = document.getElementById('delib-kapisanan');
    const duplicatePanel = document.getElementById('duplicatePanel');
    const statusMessage = document.getElementById('delibStatus');
    const submitButton = document.getElementById('delibSubmit');
    const puroks = JSON.parse(form.dataset.puroks);
    let duplicates = [];
    let duplicateTimer;
    let requestVersion = 0;
    const subtractPanel = document.getElementById('subtractPanel');
    const addTab = document.getElementById('addTab');
    const subtractTab = document.getElementById('subtractTab');
    const subtractSearchInput = document.getElementById('subtractSearchInput');
    const subtractSearchButton = document.getElementById('subtractSearchButton');
    const subtractSuggestions = document.getElementById('subtractSuggestions');
    const subtractRecordForm = document.getElementById('subtractRecordForm');
    const subtractMessage = document.getElementById('subtractStatusMessage');
    const subtractSearchStatus = document.getElementById('subtractSearchStatus');
    const subtractSubmit = document.getElementById('subtractSubmit');
    const addConfirmDialog = document.getElementById('addConfirmDialog');
    const subtractConfirmDialog = document.getElementById('subtractConfirmDialog');
    let subtractSearchTimer;

    function selectPanel(panel) {
        const isSubtract = panel === 'subtract';
        form.hidden = isSubtract;
        subtractPanel.hidden = !isSubtract;
        addTab.setAttribute('aria-selected', String(!isSubtract));
        subtractTab.setAttribute('aria-selected', String(isSubtract));
        const url = new URL(window.location.href);
        if (isSubtract) url.searchParams.set('mode', 'subtract');
        else url.searchParams.delete('mode');
        window.history.replaceState({}, '', url);
    }

    function setStatus(message, kind = '') {
        statusMessage.textContent = message;
        statusMessage.className = `delib-status${kind ? ` ${kind}` : ''}`;
    }

    function setError(field, message) {
        const target = form.querySelector(`[data-error="${field}"]`);
        if (target) target.textContent = message || '';
    }

    function confirmDialog(dialog, confirmButton, confirmValue) {
        return new Promise(resolve => {
            const cancelButton = dialog.querySelector('[data-dialog-cancel]');
            const onConfirm = () => dialog.close(confirmValue);
            const onCancel = () => dialog.close('cancel');
            const onCancelEvent = event => {
                event.preventDefault();
                dialog.close('cancel');
            };
            const onClose = () => {
                confirmButton.removeEventListener('click', onConfirm);
                cancelButton.removeEventListener('click', onCancel);
                dialog.removeEventListener('cancel', onCancelEvent);
                resolve(dialog.returnValue === confirmValue);
            };
            dialog.addEventListener('close', onClose, { once: true });
            confirmButton.addEventListener('click', onConfirm);
            cancelButton.addEventListener('click', onCancel);
            dialog.addEventListener('cancel', onCancelEvent);
            dialog.showModal();
        });
    }

    function populateAddConfirmation() {
        syncRegistry();
        const gender = form.querySelector('[name="gender"]:checked')?.value || '';
        const values = [
            ['Full Name', nameInput.value.trim()],
            ['Registry', rfid.checked ? 'RFID' : registryValue.value],
            ['Gender', gender],
            ['Purok', purokInput.selectedOptions[0]?.textContent.trim() || ''],
            ['Grupo', grupoInput.value || 'Not provided'],
            ['Kapisanan', kapisananInput.value],
            ['Status', document.getElementById('delib-status').value],
            ['Date of Baptism', document.getElementById('delib-baptism').value || 'Not provided'],
            ['Evangelist', document.getElementById('delib-evangelist').value.trim() || 'Not provided'],
            ['Date of Birth', birthInput.value || 'Not provided'],
            ['Married?', marriedInput.value === 'yes' ? 'Yes' : marriedInput.value === 'no' ? 'No' : 'Not provided'],
        ];
        const details = document.getElementById('addConfirmDetails');
        details.replaceChildren();
        values.forEach(([label, value]) => {
            const term = document.createElement('dt');
            term.textContent = label;
            const description = document.createElement('dd');
            description.textContent = value;
            details.append(term, description);
        });
    }

    function syncRegistry() {
        const value = registryBoxes.map(box => box.value).join('').toUpperCase();
        registryValue.value = rfid.checked ? 'RFID' : value;
        return value;
    }

    function refreshKapisanan() {
        const birth = birthInput.value;
        const married = marriedInput.value;
        if (!birth || !married) {
            kapisananInput.disabled = false;
            document.getElementById('kapisananHint').textContent = 'Kapisanan is auto-selected when Date of Birth and Married? are both answered.';
            return;
        }
        if (married === 'yes') kapisananInput.value = 'Buklod';
        else {
            const today = new Date();
            const birthDate = new Date(`${birth}T00:00:00`);
            let age = today.getFullYear() - birthDate.getFullYear();
            const birthdayHasPassed = today.getMonth() > birthDate.getMonth()
                || (today.getMonth() === birthDate.getMonth() && today.getDate() >= birthDate.getDate());
            if (!birthdayHasPassed) age--;
            kapisananInput.value = age < 18 ? 'Binhi' : 'Kadiwa';
        }
        kapisananInput.disabled = true;
        document.getElementById('kapisananHint').textContent = `Automatically selected: ${kapisananInput.value}.`;
    }

    function renderDuplicates(found) {
        duplicates = found;
        duplicatePanel.replaceChildren();
        if (!found.length) {
            submitButton.disabled = false;
            return;
        }
        submitButton.disabled = true;
        const alert = document.createElement('div');
        alert.className = 'duplicate-alert';
        alert.textContent = 'This person is already in the system. Creating another record is disabled. Reuse a matching inactive record and change only its status to MS or UWP.';
        duplicatePanel.append(alert);

        found.forEach(record => {
            const card = document.createElement('div');
            card.className = 'duplicate-candidate';
            const identity = document.createElement('div');
            const name = document.createElement('strong');
            name.textContent = record.name || 'Unnamed record';
            const details = document.createElement('span');
            details.textContent = `Registry: ${record.registry || '—'} · Current status: ${record.status || 'Unspecified'}`;
            identity.append(name, details);
            card.append(identity);
            if (record.inactive) {
                const controls = document.createElement('div');
                controls.className = 'reuse-controls';
                const select = document.createElement('select');
                select.setAttribute('aria-label', `New status for ${record.name}`);
                select.innerHTML = '<option value="MS">MS</option><option value="UWP">UWP</option>';
                const reuse = document.createElement('button');
                reuse.type = 'button';
                reuse.className = 'reuse-button';
                reuse.textContent = 'Reuse existing data';
                reuse.addEventListener('click', () => submitRecord(record.id, select.value));
                controls.append(select, reuse);
                card.append(controls);
            } else {
                const blocked = document.createElement('span');
                blocked.textContent = 'This record cannot be reused until its status is INACTIVE.';
                card.append(blocked);
            }
            duplicatePanel.append(card);
        });
    }

    async function requestJson(url, method, payload) {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': form.dataset.csrf,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const validationErrors = data.errors || {};
            Object.entries(validationErrors).forEach(([field, messages]) => setError(field, messages[0]));
            throw new Error(Object.values(validationErrors).flat()[0] || data.message || 'Could not save the record.');
        }
        return data;
    }

    function clearSubtractSelection() {
        subtractRecordForm.hidden = true;
        subtractRecordForm.reset();
        document.getElementById('subtractRecordId').value = '';
        subtractSubmit.disabled = false;
        subtractMessage.textContent = '';
        subtractMessage.className = 'delib-status';
    }

    function chooseSubtractRecord(record) {
        const fields = {
            subtractName: record.full_name,
            subtractRegistry: record.registry || '—',
            subtractGender: record.gender || '—',
            subtractPurok: [record.purok, record.purok_name].filter(Boolean).join(' - ') || '—',
            subtractGrupo: record.grupo || '—',
            subtractKapisanan: record.kapisanan || '—',
            subtractBirth: record.date_of_birth || '—',
            subtractBaptism: record.date_of_baptism || '—',
            subtractEvangelist: record.evangelist || '—',
            subtractStatus: record.status || 'Unspecified',
        };
        Object.entries(fields).forEach(([id, value]) => {
            document.getElementById(id).value = value;
        });
        document.getElementById('subtractRecordId').value = record.id;
        subtractRecordForm.hidden = false;
        const allowed = ['MS', 'UWP'].includes(record.status);
        subtractSubmit.disabled = !allowed;
        if (record.status === 'INACTIVE') {
            subtractMessage.textContent = 'Already Inactive.';
            subtractMessage.className = 'delib-status error';
        } else if (record.status === 'MK') {
            subtractMessage.textContent = 'MK is not removable.';
            subtractMessage.className = 'delib-status error';
        } else if (!allowed) {
            subtractMessage.textContent = 'Only MS or UWP records can be marked inactive.';
            subtractMessage.className = 'delib-status error';
        } else {
            subtractMessage.textContent = '';
            subtractMessage.className = 'delib-status';
        }
    }

    async function searchSubtractRecords() {
        const query = subtractSearchInput.value.trim();
        clearSubtractSelection();
        subtractSuggestions.replaceChildren();
        if (query.length < 2) {
            subtractSearchStatus.textContent = 'Enter at least two characters to search.';
            return;
        }
        subtractSearchStatus.textContent = 'Searching…';
        subtractSearchStatus.className = 'delib-status';
        subtractSearchButton.disabled = true;
        try {
            const url = new URL(form.dataset.subtractSearchUrl, window.location.origin);
            url.searchParams.set('q', query);
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Could not search records.');
            if (!result.records.length) {
                subtractSearchStatus.textContent = 'No matching records found.';
                return;
            }
            result.records.forEach(record => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'subtract-suggestion';
                button.setAttribute('role', 'option');
                const name = document.createElement('strong');
                name.textContent = record.full_name || 'Unnamed record';
                const details = document.createElement('span');
                details.textContent = `Registry: ${record.registry || '—'} · Status: ${record.status || 'Unspecified'}`;
                button.append(name, details);
                button.addEventListener('click', () => chooseSubtractRecord(record));
                subtractSuggestions.append(button);
            });
            subtractSearchStatus.textContent = `${result.records.length} matching record${result.records.length === 1 ? '' : 's'} found. Select the correct member.`;
        } catch (error) {
            subtractSearchStatus.textContent = error.message;
            subtractSearchStatus.className = 'delib-status error';
        } finally {
            subtractSearchButton.disabled = false;
        }
    }

    async function checkDuplicates() {
        const name = nameInput.value.trim();
        const registry = syncRegistry();
        if (name.length < 2) return renderDuplicates([]);
        if (!rfid.checked && registry.length > 0 && registry.length < 13) return renderDuplicates([]);
        const version = ++requestVersion;
        try {
            const result = await requestJson(form.dataset.duplicateUrl, 'POST', {
                full_name: name,
                registry: rfid.checked ? 'RFID' : registry,
                is_rfid: rfid.checked,
            });
            if (version === requestVersion) renderDuplicates(result.duplicates);
        } catch (error) {
            if (version === requestVersion) setStatus(error.message, 'error');
        }
    }

    async function submitRecord(reuseRecordId = null, reuseStatus = null) {
        if (!form.reportValidity()) return;
        form.querySelectorAll('[data-error]').forEach(error => { error.textContent = ''; });
        syncRegistry();
        if (!rfid.checked && !/^[A-Z0-9]{13}$/.test(registryValue.value)) {
            setError('registry', 'Registry must contain exactly 13 letters or numbers, or select RFID.');
            setStatus('Complete the registry number or select RFID.', 'error');
            registryBoxes[0].focus();
            return;
        }
        const payload = Object.fromEntries(new FormData(form).entries());
        payload.is_rfid = rfid.checked;
        payload.registry = rfid.checked ? 'RFID' : registryValue.value;
        payload.kapisanan = kapisananInput.value;
        if (reuseRecordId) {
            payload.reuse_record_id = reuseRecordId;
            payload.status = reuseStatus;
        }
        submitButton.disabled = true;
        setStatus(reuseRecordId ? 'Reusing the existing record…' : 'Checking for duplicates…');
        try {
            const duplicateCheck = await requestJson(form.dataset.duplicateUrl, 'POST', {
                full_name: payload.full_name,
                registry: payload.registry,
                is_rfid: payload.is_rfid,
            });
            renderDuplicates(duplicateCheck.duplicates);
            if (duplicateCheck.duplicates.length && !reuseRecordId) {
                setStatus('Choose an inactive record to reuse; a duplicate cannot be added.', 'error');
                return;
            }
            if (!reuseRecordId) {
                populateAddConfirmation();
                const confirmed = await confirmDialog(addConfirmDialog, document.getElementById('confirmAddButton'), 'add');
                if (!confirmed) {
                    setStatus('Adding the member was cancelled.');
                    return;
                }
            }
            const result = await requestJson(form.dataset.storeUrl, 'POST', payload);
            renderDuplicates([]);
            setStatus(result.message, 'success');
            if (!result.reused) form.reset();
            registryBoxes.forEach(box => { box.value = ''; });
            registryValue.value = '';
            grupoInput.innerHTML = '<option value="">Select Purok first</option>';
            grupoInput.disabled = true;
            kapisananInput.disabled = false;
            document.getElementById('kapisananHint').textContent = 'Kapisanan is auto-selected when Date of Birth and Married? are both answered.';
        } catch (error) {
            setStatus(error.message, 'error');
            checkDuplicates();
        } finally {
            submitButton.disabled = duplicates.length > 0;
        }
    }

    registryBoxes.forEach((box, index) => {
        box.addEventListener('input', () => {
            box.value = box.value.replace(/[^a-z0-9]/gi, '').slice(-1).toUpperCase();
            syncRegistry();
            if (box.value && index < registryBoxes.length - 1) registryBoxes[index + 1].focus();
            clearTimeout(duplicateTimer);
            duplicateTimer = window.setTimeout(checkDuplicates, 250);
        });
        box.addEventListener('keydown', event => {
            if (event.key === 'Backspace' && !box.value && index > 0) registryBoxes[index - 1].focus();
            if (event.key === 'ArrowLeft' && index > 0) registryBoxes[index - 1].focus();
            if (event.key === 'ArrowRight' && index < registryBoxes.length - 1) registryBoxes[index + 1].focus();
        });
        box.addEventListener('paste', event => {
            const pasted = event.clipboardData?.getData('text')?.toUpperCase().replace(/[^A-Z0-9]/g, '') || '';
            if (!pasted) return;
            event.preventDefault();
            pasted.slice(0, 13).split('').forEach((character, characterIndex) => {
                registryBoxes[characterIndex].value = character;
            });
            syncRegistry();
            registryBoxes[Math.min(pasted.length, 12)].focus();
            checkDuplicates();
        });
    });

    rfid.addEventListener('change', () => {
        registryInputs.hidden = rfid.checked;
        registryBoxes.forEach(box => { box.disabled = rfid.checked; });
        syncRegistry();
        checkDuplicates();
    });
    nameInput.addEventListener('blur', checkDuplicates);
    nameInput.addEventListener('input', () => {
        clearTimeout(duplicateTimer);
        duplicateTimer = window.setTimeout(checkDuplicates, 350);
    });
    birthInput.addEventListener('change', refreshKapisanan);
    marriedInput.addEventListener('change', refreshKapisanan);
    purokInput.addEventListener('change', () => {
        const selected = puroks.find(purok => purok.number === purokInput.value);
        grupoInput.replaceChildren();
        if (!selected) {
            grupoInput.disabled = true;
            grupoInput.add(new Option('Select Purok first', ''));
            return;
        }
        grupoInput.disabled = false;
        grupoInput.add(new Option('Select Grupo', ''));
        for (let grupo = Number(selected.grupo_start); grupo <= Number(selected.grupo_end); grupo++) {
            grupoInput.add(new Option(String(grupo), String(grupo)));
        }
    });
    form.addEventListener('submit', event => {
        event.preventDefault();
        submitRecord();
    });

    addTab.addEventListener('click', () => selectPanel('add'));
    subtractTab.addEventListener('click', () => selectPanel('subtract'));
    subtractSearchButton.addEventListener('click', searchSubtractRecords);
    subtractSearchInput.addEventListener('input', () => {
        clearTimeout(subtractSearchTimer);
        clearSubtractSelection();
        if (subtractSearchInput.value.trim().length >= 2) {
            subtractSearchTimer = window.setTimeout(searchSubtractRecords, 300);
        } else {
            subtractSuggestions.replaceChildren();
            subtractSearchStatus.textContent = '';
        }
    });
    subtractSearchInput.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchSubtractRecords();
        }
    });
    subtractRecordForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!subtractRecordForm.reportValidity()) return;
        const recordId = document.getElementById('subtractRecordId').value;
        if (!recordId) return;
        subtractSubmit.disabled = true;
        subtractMessage.textContent = 'Updating the record…';
        subtractMessage.className = 'delib-status';
        try {
            document.getElementById('subtractConfirmText').textContent =
                `Are you sure you want to make ${document.getElementById('subtractName').value} inactive?`;
            const confirmed = await confirmDialog(subtractConfirmDialog, document.getElementById('confirmSubtractButton'), 'subtract');
            if (!confirmed) {
                subtractMessage.textContent = 'Subtract was cancelled.';
                subtractMessage.className = 'delib-status';
                subtractSubmit.disabled = false;
                return;
            }
            const result = await requestJson(form.dataset.subtractUrl, 'POST', {
                record_id: recordId,
                code: document.getElementById('subtractCode').value,
                description: document.getElementById('subtractDescription').value,
            });
            document.getElementById('subtractStatus').value = 'INACTIVE';
            subtractMessage.textContent = result.message;
            subtractMessage.className = 'delib-status success';
        } catch (error) {
            subtractMessage.textContent = error.message;
            subtractMessage.className = 'delib-status error';
            subtractSubmit.disabled = false;
        }
    });

    selectPanel(new URLSearchParams(window.location.search).get('mode') === 'subtract' ? 'subtract' : 'add');
})();
</script>
@endpush
