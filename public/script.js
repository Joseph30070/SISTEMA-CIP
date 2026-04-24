let current = 0;
function showStep(idx) {
    const steps = document.querySelectorAll('.step');
    steps.forEach((s, i) => s.classList.toggle('active', i === idx));
    const pr = document.querySelector('.progress > div');
    pr.style.width = (((idx + 1) / steps.length) * 100) + '%';
    current = idx;
}
function nextStep() { if (validateStep(current)) showStep(current + 1); }
function prevStep() { showStep(Math.max(0, current - 1)); }
function validateStep(idx) {
    const step = document.querySelectorAll('.step')[idx];
    let ok = true;
    step.querySelectorAll('[data-required]')?.forEach(el => {
        const err = el.nextElementSibling && el.nextElementSibling.classList.contains('err') ? el.nextElementSibling : null;
        if ((el.type === 'file' && el.files.length === 0) || (el.value || '').trim() === '') {
            ok = false; if (err) err.textContent = 'Este campo es obligatorio';
        } else { if (err) err.textContent = ''; }
    });
    return ok;
}
document.addEventListener('DOMContentLoaded', () => showStep(0));