@php($strengthInputId = $inputId)
<div class="password-strength" data-password-strength-for="{{ $strengthInputId }}">
    <div class="password-strength-track" role="progressbar" aria-label="Password strength" aria-valuemin="0" aria-valuemax="4" aria-valuenow="0">
        <span class="password-strength-fill"></span>
    </div>
    <p class="password-strength-label" aria-live="polite">Enter a password</p>
    <ul class="password-strength-rules" aria-label="Password requirements">
        <li data-rule="length">At least 8 characters</li>
        <li data-rule="letters">At least one letter</li>
        <li data-rule="numbers">At least one number</li>
        <li data-rule="symbols">At least one special character</li>
    </ul>
</div>
<style>
    .password-strength { margin:.55rem 0 .2rem; }
    .password-strength-track { height:7px; overflow:hidden; border-radius:999px; background:#e5e7eb; }
    .password-strength-fill { display:block; width:0; height:100%; border-radius:inherit; background:#dc2626; transition:width .2s ease, background-color .2s ease; }
    .password-strength-label { margin:.35rem 0; color:#64748b; font-size:.76rem; font-weight:700; }
    .password-strength[data-strength="weak"] .password-strength-fill { background:#dc2626; }
    .password-strength[data-strength="fair"] .password-strength-fill { background:#f59e0b; }
    .password-strength[data-strength="strong"] .password-strength-fill { background:#16a34a; }
    .password-strength[data-strength="weak"] .password-strength-label { color:#b42318; }
    .password-strength[data-strength="fair"] .password-strength-label { color:#b45309; }
    .password-strength[data-strength="strong"] .password-strength-label { color:#15803d; }
    .password-strength-rules { display:grid; grid-template-columns:1fr 1fr; gap:.22rem .65rem; margin:.4rem 0 0; padding:0; list-style:none; color:#64748b; font-size:.72rem; }
    .password-strength-rules li::before { content:'○'; display:inline-block; width:1rem; color:#94a3b8; }
    .password-strength-rules li.met { color:#15803d; }
    .password-strength-rules li.met::before { content:'✓'; color:#16a34a; font-weight:800; }
    @media(max-width:480px) { .password-strength-rules { grid-template-columns:1fr; } }
</style>
<script>
    (() => {
        const input = document.getElementById(@json($strengthInputId));
        const meter = document.querySelector(`[data-password-strength-for="${@js($strengthInputId)}"]`);
        if (!input || !meter) return;

        const track = meter.querySelector('[role="progressbar"]');
        const fill = meter.querySelector('.password-strength-fill');
        const label = meter.querySelector('.password-strength-label');
        const rules = [...meter.querySelectorAll('[data-rule]')];
        const update = () => {
            const password = input.value;
            const checks = {
                length: Array.from(password).length >= 8,
                letters: /\p{L}/u.test(password),
                numbers: /\p{N}/u.test(password),
                symbols: /[\p{Z}\p{S}\p{P}]/u.test(password),
            };
            const score = Object.values(checks).filter(Boolean).length;
            rules.forEach(rule => rule.classList.toggle('met', checks[rule.dataset.rule]));
            track.setAttribute('aria-valuenow', String(score));

            if (!password) {
                meter.removeAttribute('data-strength');
                fill.style.width = '0%';
                label.textContent = 'Enter a password';
                return;
            }

            const strength = score === 4 ? 'strong' : score >= 2 ? 'fair' : 'weak';
            meter.dataset.strength = strength;
            fill.style.width = `${score * 25}%`;
            label.textContent = `${strength[0].toUpperCase()}${strength.slice(1)} password`;
        };

        input.addEventListener('input', update);
        update();
    })();
</script>
