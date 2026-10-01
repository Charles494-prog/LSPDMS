@extends('layouts.app')

@section('title', 'Monthly Modify')

@section('content')
<style>
    .monthly-modify { max-width:900px; margin:0 auto; }
    .monthly-modify-hero,.monthly-modify-form { padding:clamp(1rem,3vw,1.6rem); border:1px solid var(--border); border-radius:16px; background:var(--surface); }
    .monthly-modify-hero { margin-bottom:1rem; }
    .monthly-modify-hero a { color:var(--accent); font-size:.84rem; font-weight:700; text-decoration:none; }
    .monthly-modify-hero h1 { margin:.6rem 0 .35rem; }
    .monthly-modify-hero p,.monthly-modify-help { color:var(--text-muted); line-height:1.5; }
    .monthly-modify-search { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:.6rem; }
    .monthly-modify input,.monthly-modify select,.monthly-modify button { min-height:44px; padding:.65rem .75rem; border:1px solid var(--border); border-radius:9px; background:var(--bg); color:var(--text); font:inherit; }
    .monthly-modify button { border:0; background:var(--accent); color:#fff; font-weight:750; cursor:pointer; }
    .monthly-modify button:disabled { opacity:.55; cursor:not-allowed; }
    .monthly-modify-suggestions { display:grid; gap:.4rem; margin:.55rem 0 1rem; }
    .monthly-modify-suggestions button { display:block; width:100%; background:var(--bg); color:var(--text); text-align:left; }
    .monthly-modify-suggestions button span,.monthly-modify-suggestions button strong { display:block; }
    .monthly-modify-suggestions button span { margin-top:.2rem; color:var(--text-muted); font-size:.8rem; }
    .monthly-modify-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; margin-top:1rem; }
    .monthly-modify-fields label { display:grid; gap:.4rem; font-size:.85rem; font-weight:700; }
    .monthly-modify-fields .wide { grid-column:1/-1; }
    .monthly-modify-fields input[readonly] { opacity:.8; }
    .monthly-modify-actions { display:flex; justify-content:flex-end; margin-top:1rem; }
    .monthly-modify-status { min-height:1.25rem; margin-top:.8rem; font-size:.85rem; }
    .monthly-modify-status.error { color:var(--danger); }
    .monthly-modify-status.success { color:#15803d; }
    @media(max-width:600px) { .monthly-modify-fields { grid-template-columns:1fr; } .monthly-modify-fields .wide { grid-column:auto; } }
</style>
<main class="monthly-modify">
    <section class="monthly-modify-hero">
        <a href="{{ route('form-generator') }}">&lt; Form Generator</a>
        <h1>Monthly Modify</h1>
        <p>Search for a member to change their status or transfer them to a different configured Purok. Inactive records can be found but cannot be changed.</p>
    </section>
    <section class="monthly-modify-form">
        <label for="monthlyModifySearch"><strong>Find a member</strong></label>
        <div class="monthly-modify-search">
            <input id="monthlyModifySearch" type="search" minlength="2" maxlength="100" autocomplete="off" placeholder="Name or registry number">
            <button id="monthlyModifySearchButton" type="button">Search</button>
        </div>
        <div class="monthly-modify-suggestions" id="monthlyModifySuggestions" role="listbox"></div>
        <form id="monthlyModifyForm" hidden>
            @csrf
            <input type="hidden" name="record_id" id="monthlyModifyRecordId">
            <div class="monthly-modify-fields">
                <label class="wide">Selected member
                    <input id="monthlyModifyMember" type="text" readonly>
                </label>
                <label>Current Purok / Grupo
                    <input id="monthlyModifyCurrent" type="text" readonly>
                </label>
                <label>Current status
                    <input id="monthlyModifyStatus" type="text" readonly>
                </label>
                <label class="wide">Change type
                    <select id="monthlyModifyAction" name="action" required>
                        <option value="">Select an action</option>
                        <option value="status">Change status</option>
                        <option value="transfer">Transfer to another Purok</option>
                    </select>
                </label>
                <label id="monthlyModifyStatusField" hidden>New status
                    <select id="monthlyModifyNewStatus" name="status" disabled>
                        <option value="">Select status</option>
                        <option value="MS">MS</option>
                        <option value="UWP">UWP</option>
                        <option value="MK">MK</option>
                    </select>
                </label>
                <label class="wide" id="monthlyModifyEndorsementReasonField" hidden>
                    Reason for changing status to MK <span aria-hidden="true">*</span>
                    <textarea id="monthlyModifyEndorsementReason" name="endorsement_reason" maxlength="2000" rows="3" placeholder="Enter the reason for this R2-10/MK status change" disabled></textarea>
                </label>
                <label id="monthlyModifyPurokField" hidden>Destination Purok
                    <select id="monthlyModifyPurok" name="purok_id" required>
                        <option value="">Select a Purok</option>
                        @foreach ($puroks as $purok)
                            <option value="{{ $purok->id }}" data-min="{{ $purok->grupo_start }}" data-max="{{ $purok->grupo_end }}">
                                {{ $purok->purok_name ?: 'Purok '.$purok->purok_number }} ({{ $purok->grupo_start }}-{{ $purok->grupo_end }})
                            </option>
                        @endforeach
                    </select>
                </label>
                <label id="monthlyModifyGrupoField" hidden>Destination Grupo
                    <input id="monthlyModifyGrupo" name="grupo" type="number" min="1" max="24" step="1" required disabled>
                </label>
                <label class="wide" id="monthlyModifyDescriptionField" hidden>Optional description
                    <input name="description" type="text" maxlength="500" placeholder="Reason or note for the change" disabled>
                </label>
            </div>
            <p class="monthly-modify-help" id="monthlyModifyRangeHelp">Choose the destination Purok to see its allowed Grupo range.</p>
            <div class="monthly-modify-actions"><button id="monthlyModifySave" type="submit" disabled>Review change</button></div>
        </form>
        <div class="monthly-modify-status" id="monthlyModifyMessage" role="status" aria-live="polite"></div>
    </section>
</main>
<dialog class="monthly-modify-dialog" id="monthlyModifyConfirmDialog" aria-labelledby="monthlyModifyConfirmTitle">
    <div class="monthly-modify-dialog-content">
        <h2 id="monthlyModifyConfirmTitle">Confirm record modification</h2>
        <p id="monthlyModifyConfirmText"></p>
        <div class="monthly-modify-dialog-actions">
            <button type="button" class="secondary" id="monthlyModifyCancelConfirm">Cancel</button>
            <button type="button" id="monthlyModifyConfirmButton">Confirm change</button>
        </div>
    </div>
</dialog>
<dialog class="monthly-modify-dialog monthly-modify-success" id="monthlyModifySuccessDialog" aria-labelledby="monthlyModifySuccessTitle">
    <div class="monthly-modify-dialog-content">
        <div class="monthly-modify-check" aria-hidden="true">✓</div>
        <h2 id="monthlyModifySuccessTitle">Record modified</h2>
        <p id="monthlyModifySuccessText">The record was updated successfully.</p>
        <div class="monthly-modify-dialog-actions">
            <button type="button" id="monthlyModifySuccessClose">Done</button>
        </div>
    </div>
</dialog>
<style>
    .monthly-modify-dialog {
        width: min(440px, calc(100vw - 2rem));
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 0;
        color: var(--text);
        background: var(--surface);
        box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
    }
    .monthly-modify-dialog::backdrop { background: rgba(15, 23, 42, .55); backdrop-filter: blur(2px); }
    .monthly-modify-dialog-content { padding: 1.5rem; }
    .monthly-modify-dialog-content h2 { margin: 0 0 .6rem; font-size: 1.2rem; }
    .monthly-modify-dialog-content p { margin: 0; line-height: 1.5; color: var(--text-muted); }
    .monthly-modify-dialog-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.4rem; }
    .monthly-modify-dialog-actions button { min-height: 42px; padding: .6rem 1rem; border: 0; border-radius: 9px; background: var(--accent); color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
    .monthly-modify-dialog-actions button.secondary { border: 1px solid var(--border); background: var(--bg); color: var(--text); }
    .monthly-modify-check { display: grid; place-items: center; width: 3.5rem; height: 3.5rem; margin: 0 auto 1rem; border-radius: 50%; background: #e8f8ee; color: #15803d; font-size: 2rem; font-weight: 800; }
    .monthly-modify-success .monthly-modify-dialog-content { text-align: center; }
</style>
<script>
(() => {
    const searchInput = document.getElementById('monthlyModifySearch');
    const searchButton = document.getElementById('monthlyModifySearchButton');
    const suggestions = document.getElementById('monthlyModifySuggestions');
    const form = document.getElementById('monthlyModifyForm');
    const message = document.getElementById('monthlyModifyMessage');
    const purok = document.getElementById('monthlyModifyPurok');
    const grupo = document.getElementById('monthlyModifyGrupo');
    const save = document.getElementById('monthlyModifySave');
    const action = document.getElementById('monthlyModifyAction');
    const statusField = document.getElementById('monthlyModifyStatusField');
    const statusSelect = document.getElementById('monthlyModifyNewStatus');
    const endorsementReasonField = document.getElementById('monthlyModifyEndorsementReasonField');
    const endorsementReasonInput = document.getElementById('monthlyModifyEndorsementReason');
    const purokContainer = document.getElementById('monthlyModifyPurokField');
    const grupoContainer = document.getElementById('monthlyModifyGrupoField');
    const descriptionField = document.getElementById('monthlyModifyDescriptionField');
    const descriptionInput = form.elements.description;
    const rangeHelp = document.getElementById('monthlyModifyRangeHelp');
    const confirmDialog = document.getElementById('monthlyModifyConfirmDialog');
    const confirmText = document.getElementById('monthlyModifyConfirmText');
    const confirmButton = document.getElementById('monthlyModifyConfirmButton');
    const cancelConfirmButton = document.getElementById('monthlyModifyCancelConfirm');
    const successDialog = document.getElementById('monthlyModifySuccessDialog');
    const successText = document.getElementById('monthlyModifySuccessText');
    const closeSuccessButton = document.getElementById('monthlyModifySuccessClose');
    let selectedRecord = null;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('[name="_token"]').value;
    const setMessage = (text, type = '') => {
        message.textContent = text;
        message.className = `monthly-modify-status ${type}`;
    };
    const confirmModification = text => new Promise(resolve => {
        confirmText.textContent = text;
        const cleanup = () => {
            confirmButton.removeEventListener('click', onConfirm);
            cancelConfirmButton.removeEventListener('click', onCancel);
            confirmDialog.removeEventListener('cancel', onCancelEvent);
        };
        const onConfirm = () => confirmDialog.close('confirm');
        const onCancel = () => confirmDialog.close('cancel');
        const onCancelEvent = event => {
            event.preventDefault();
            confirmDialog.close('cancel');
        };
        confirmDialog.addEventListener('close', () => {
            cleanup();
            resolve(confirmDialog.returnValue === 'confirm');
        }, { once: true });
        confirmButton.addEventListener('click', onConfirm);
        cancelConfirmButton.addEventListener('click', onCancel);
        confirmDialog.addEventListener('cancel', onCancelEvent);
        confirmDialog.showModal();
    });
    const resetModifyForm = () => {
        selectedRecord = null;
        form.reset();
        form.hidden = true;
        searchInput.value = '';
        suggestions.replaceChildren();
        save.disabled = true;
        grupo.min = '1';
        grupo.max = '24';
        setMessage('');
    };
    const refreshAction = () => {
        const unavailable = !selectedRecord
            || (selectedRecord.status || '').toUpperCase() === 'INACTIVE'
            || Boolean(selectedRecord.monthly_code)
            || !['MS', 'UWP', 'MK'].includes((selectedRecord.status || '').toUpperCase());
        const transferring = action.value === 'transfer';
        const changingStatus = action.value === 'status';
        const samePurok = transferring && Boolean(selectedRecord?.purok_id)
            && purok.value === String(selectedRecord.purok_id);
        action.disabled = unavailable;
        statusField.hidden = !changingStatus;
        statusSelect.disabled = unavailable || !changingStatus;
        const changingToMk = changingStatus && statusSelect.value === 'MK';
        endorsementReasonField.hidden = unavailable || !changingToMk;
        endorsementReasonInput.disabled = unavailable || !changingToMk;
        endorsementReasonInput.required = !unavailable && changingToMk;
        purokContainer.hidden = !transferring;
        purok.disabled = unavailable || !transferring;
        grupoContainer.hidden = !transferring;
        grupo.disabled = unavailable || !transferring;
        descriptionField.hidden = unavailable || (!transferring && !changingStatus);
        descriptionInput.disabled = unavailable || (!transferring && !changingStatus);
        [...purok.options].forEach(option => {
            option.disabled = Boolean(option.value && selectedRecord?.purok_id && option.value === String(selectedRecord.purok_id));
        });
        if (unavailable) {
            rangeHelp.textContent = selectedRecord?.monthly_code
                ? `Blocked: this record already has monthly code "${selectedRecord.monthly_code}". Clear it in Inquire / Modify before proceeding.`
                : (selectedRecord?.status || '').toUpperCase() === 'INACTIVE'
                    ? 'Inactive records can be searched but cannot be changed.'
                    : 'Only MS, UWP, and MK records can be changed.';
        } else if (samePurok) {
            rangeHelp.textContent = 'Select a different configured Purok. For a Grupo-only correction, use Inquire / Modify.';
        }
        save.disabled = unavailable || (!transferring && !changingStatus)
            || samePurok
            || (transferring && (!purok.value || !grupo.value || !grupo.checkValidity()))
            || (changingStatus && (!statusSelect.value
                || statusSelect.value === selectedRecord?.status
                || (changingToMk && !endorsementReasonInput.value.trim())));
        save.textContent = changingStatus ? 'Review status change' : 'Review transfer';
    };
    const showRecord = record => {
        selectedRecord = record;
        document.getElementById('monthlyModifyRecordId').value = record.id;
        document.getElementById('monthlyModifyMember').value = `${record.name}${record.registry ? ` (${record.registry})` : ''}`;
        document.getElementById('monthlyModifyCurrent').value = `${record.purok_name} / Grupo ${record.grupo || 'Not set'}`;
        document.getElementById('monthlyModifyStatus').value = record.status || 'Not set';
        action.value = '';
        statusSelect.value = '';
        purok.value = '';
        grupo.value = '';
        descriptionInput.value = '';
        endorsementReasonInput.value = '';
        form.hidden = false;
        suggestions.replaceChildren();
        searchInput.value = record.name;
        refreshAction();
        setMessage('');
    };
    const search = async () => {
        const q = searchInput.value.trim();
        suggestions.replaceChildren();
        form.hidden = true;
        selectedRecord = null;
        save.disabled = true;
        setMessage('');
        if (q.length < 2) {
            setMessage('Enter at least two characters to search.', 'error');
            return;
        }
        searchButton.disabled = true;
        try {
            const response = await fetch(`{{ route('forms.monthly.modify.search') }}?q=${encodeURIComponent(q)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Could not search records.');
            if (!payload.records.length) {
                setMessage('No matching records found.');
                return;
            }
            payload.records.forEach(record => {
                const button = document.createElement('button');
                button.type = 'button';
                button.setAttribute('role', 'option');
                const name = document.createElement('strong');
                name.textContent = record.name;
                const detail = document.createElement('span');
                detail.textContent = `${record.registry || 'No registry'} | ${record.purok_name} / Grupo ${record.grupo || 'Not set'} | ${record.status || 'No status'}${record.monthly_code ? ` | Monthly code: ${record.monthly_code}` : ''}`;
                button.append(name, detail);
                button.addEventListener('click', () => showRecord(record));
                suggestions.append(button);
            });
            setMessage('Select the correct member from the results.');
        } catch (error) {
            setMessage(error.message || 'Search failed.', 'error');
        } finally {
            searchButton.disabled = false;
        }
    };
    const updateRange = () => {
        const option = purok.selectedOptions[0];
        const min = option?.dataset.min || '1';
        const max = option?.dataset.max || '24';
        grupo.min = min;
        grupo.max = max;
        grupo.step = '1';
        if (grupo.value && (Number(grupo.value) < Number(min) || Number(grupo.value) > Number(max))) {
            grupo.value = '';
        }
        if (option) rangeHelp.textContent = `Grupo must be between ${min} and ${max} for the selected Purok.`;
        else rangeHelp.textContent = 'Choose the destination Purok to see its allowed Grupo range.';
        refreshAction();
    };
    searchButton.addEventListener('click', search);
    searchInput.addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); search(); }
    });
    purok.addEventListener('change', updateRange);
    action.addEventListener('change', refreshAction);
    statusSelect.addEventListener('change', refreshAction);
    endorsementReasonInput.addEventListener('input', refreshAction);
    grupo.addEventListener('input', refreshAction);
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!selectedRecord) return;
        const changeStatus = action.value === 'status';
        const isMkDemotion = changeStatus && selectedRecord.status === 'MK' && statusSelect.value !== 'MK';
        const confirmationText = isMkDemotion
            ? `This member is MK, a higher class than MS and UWP. Are you sure you want to change this status to ${statusSelect.value}?`
            : changeStatus
                ? `Are you sure? ${selectedRecord.name} will change from ${selectedRecord.status} to ${statusSelect.value}. The change will be saved to the database.`
                : `Are you sure? ${selectedRecord.name} will transfer from ${selectedRecord.purok_name} / Grupo ${selectedRecord.grupo} to ${purok.selectedOptions[0].textContent.trim()} / Grupo ${grupo.value}. The change will be saved to the database.`;
        if (!await confirmModification(confirmationText)) return;
        save.disabled = true;
        setMessage('Saving change...');
        try {
            const formData = new FormData();
            formData.set('record_id', String(selectedRecord.id));
            formData.set('action', action.value);
            if (changeStatus) {
                formData.set('status', statusSelect.value);
                formData.set('confirmed_mk_demotion', isMkDemotion ? '1' : '0');
                if (statusSelect.value === 'MK') {
                    formData.set('endorsement_reason', endorsementReasonInput.value.trim());
                }
            } else {
                formData.set('purok_id', purok.value);
                formData.set('grupo', grupo.value);
            }
            if (descriptionInput.value.trim()) formData.set('description', descriptionInput.value.trim());
            const response = await fetch(`{{ route('forms.monthly.modify.save') }}`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: formData
            });
            const result = await response.json();
            if (!response.ok) {
                const errors = Object.values(result.errors || {}).flat();
                throw new Error(errors.join(' ') || result.message || 'Could not save the transfer.');
            }
            successText.textContent = result.message || 'The record was updated successfully.';
            resetModifyForm();
            successDialog.showModal();
        } catch (error) {
            setMessage(error.message || 'Could not save the transfer.', 'error');
            save.disabled = false;
        }
    });
    closeSuccessButton.addEventListener('click', () => successDialog.close());
    successDialog.addEventListener('close', () => searchInput.focus());
})();
</script>
@endsection
