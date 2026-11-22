// Checkout page: hold countdown, "same number" checkbox, and double-submit guard.
// The server always re-checks the hold; this timer is only for the user.

const timerBox = document.getElementById('hold-timer');
const countdown = timerBox.querySelector('.hold-countdown');
const expiredBox = document.getElementById('hold-expired');
const form = document.getElementById('checkout-form');
const payButton = document.getElementById('pay-button');

// ---------- Countdown ----------
// The server sends seconds left, so a wrong clock on the user's device doesn't matter
const endsAt = Date.now() + Number(timerBox.dataset.secondsLeft) * 1000;

function tick() {
  const left = Math.max(0, Math.round((endsAt - Date.now()) / 1000));
  const min = String(Math.floor(left / 60)).padStart(2, '0');
  const sec = String(left % 60).padStart(2, '0');
  countdown.textContent = `${min}:${sec}`;

  if (left <= 60) {
    timerBox.classList.replace('bg-yellow-50', 'bg-red-50');
    timerBox.classList.replace('border-yellow-300', 'border-red-300');
    timerBox.classList.replace('text-yellow-800', 'text-red-700');
  }

  if (left === 0) {
    clearInterval(timer);
    timerBox.classList.add('hidden');
    expiredBox.classList.remove('hidden');
    payButton.disabled = true;
    payButton.textContent = 'Hold expired';
  }
}

const timer = setInterval(tick, 1000);
tick();

// ---------- Same as mobile number ----------
const phone = document.getElementById('phone');
const easypaisa = document.getElementById('easypaisa_number');
const sameAsPhone = document.getElementById('same-as-phone');

function syncNumber() {
  if (sameAsPhone.checked) easypaisa.value = phone.value;
  easypaisa.readOnly = sameAsPhone.checked;
}

sameAsPhone.addEventListener('change', syncNumber);
phone.addEventListener('input', syncNumber);

// ---------- Submit ----------
form.addEventListener('submit', (event) => {
  if (!form.checkValidity()) {
    event.preventDefault();
    form.reportValidity(); // shows the browser's message on the first invalid field
    return;
  }

  // Prevent a second payment request from a double click
  payButton.disabled = true;
  payButton.textContent = 'Sending request to Easypaisa…';
});