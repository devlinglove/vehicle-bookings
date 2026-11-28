<?php

/**
 * @var object $booking      booking_id, amount, status
 */
?>

<?php loadPartialView('header'); ?>
<?php loadPartialView('navbar'); ?>

<?php if (!$booking): ?>
  <main class="mx-auto max-w-xl p-8 text-white">
    <h1 class="text-2xl font-bold">Booking not found</h1>
    <p class="mt-2 text-slate-300">No booking exists for ID <?= htmlspecialchars((string)$booking->booking_id) ?>.</p>
  </main>
<?php else: ?>
  <div class="mx-auto max-w-7xl px-4 py-6 lg:px-8">
    <!-- Progress -->
    <div class="mb-8 flex items-center gap-3 overflow-x-auto whitespace-nowrap text-sm font-semibold">
      <span class="flex items-center gap-2 text-blue-500"><span class="grid h-7 w-7 place-items-center rounded-full bg-blue-600 text-white">✓</span> Voucher created</span>
      <span class="h-0.5 w-10 bg-slate-500"></span>
      <span class="flex items-center gap-2 text-blue-400"><span class="grid h-7 w-7 place-items-center rounded-full border-2 border-blue-500">2</span> Pay and upload</span>
      <span class="h-0.5 w-10 bg-slate-700"></span><span class="text-slate-500">◉ Verified</span>
      <span class="h-0.5 w-10 bg-slate-700"></span><span class="text-slate-500">◉ Ticket issued</span>
    </div>

    <div class="mb-6">
      <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-100">Your voucher is ready</h1><span class="rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-sm font-medium text-amber-800">Awaiting payment</span>
      </div>
      <p class="mt-2 text-slate-400">Download it, pay at your bank or in your banking app, then upload the receipt here.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_330px]">
      <div class="space-y-6">
        <!-- Voucher summary -->
        <section class="rounded-2xl bg-white p-6 shadow-lg">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
              <p class="text-sm text-slate-500">Voucher number</p>
              <p id="voucherNumber" class="mt-1 font-mono text-3xl font-bold tracking-wide"><?= htmlspecialchars($booking['voucher_number']) ?></p>
            </div>
            <button id="copyBtn" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold hover:bg-slate-50">▢ &nbsp; Copy number</button>
          </div>
          <div class="my-5 border-t border-slate-100"></div>
          <div class="grid gap-5 sm:grid-cols-3">
            <div>
              <p class="text-sm text-slate-500">Amount</p>
              <p class="mt-1 text-xl font-bold">Rs. <?= number_format($booking['amount']) ?></p>
            </div>
            <div>
              <p class="text-sm text-slate-500">Pay by</p>
              <p class="mt-1 text-xl font-bold"><?= htmlspecialchars($booking['pay_by']) ?></p>
            </div>
            <div>
              <p class="text-sm text-slate-500">Booking</p>
              <p class="mt-1 text-xl font-bold">#<?= htmlspecialchars((string)$booking['id']) ?></p>
            </div>
          </div>
          <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <button id="downloadVoucher" class="rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700">↓ &nbsp; Download voucher (PDF)</button>
            <button id="scrollUpload" class="rounded-xl border border-slate-300 px-5 py-3 font-semibold hover:bg-slate-50">↑ &nbsp; I've paid, upload receipt</button>
          </div>
        </section>

        <!-- Instructions -->
        <section class="rounded-2xl bg-white p-6 shadow-lg">
          <h2 class="text-xl font-bold">How to pay</h2>
          <div class="mt-5 grid gap-8 md:grid-cols-2">
            <div>
              <h3 class="font-semibold">In your banking app</h3>
              <ol class="mt-3 space-y-3 text-sm leading-6 text-slate-600">
                <li><span class="mr-2 inline-grid h-6 w-6 place-items-center rounded-full bg-blue-50 text-blue-700">1</span>Open bill or voucher payment.</li>
                <li><span class="mr-2 inline-grid h-6 w-6 place-items-center rounded-full bg-blue-50 text-blue-700">2</span>Enter voucher number <strong class="text-slate-900"><?= htmlspecialchars($booking['voucher_number']) ?></strong>.</li>
                <li><span class="mr-2 inline-grid h-6 w-6 place-items-center rounded-full bg-blue-50 text-blue-700">3</span>Pay <strong class="text-slate-900">Rs. <?= number_format($booking['amount']) ?></strong> and take a screenshot of the receipt.</li>
              </ol>
            </div>
            <div>
              <h3 class="font-semibold">At a bank branch</h3>
              <p class="mt-3 text-sm leading-6 text-slate-600">Take the printed voucher. The bank stamps it and gives your copy back.</p>
              <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                  <dt class="text-slate-500">Bank</dt>
                  <dd class="font-semibold"><?= htmlspecialchars($booking['bank']) ?></dd>
                </div>
                <div class="flex justify-between gap-4">
                  <dt class="text-slate-500">Account title</dt>
                  <dd class="font-semibold"><?= htmlspecialchars($booking['account_title']) ?></dd>
                </div>
                <div class="flex justify-between gap-4">
                  <dt class="text-slate-500">IBAN</dt>
                  <dd class="font-mono font-semibold"><?= htmlspecialchars($booking['iban']) ?></dd>
                </div>
              </dl>
            </div>
          </div>
          <div class="mt-6 rounded-xl bg-slate-100 p-4 text-sm text-slate-600">ⓘ &nbsp; Pay the exact amount. A different amount can delay your confirmation.</div>
        </section>

        <!-- Upload UI: demo, no server-side storage -->
        <section id="uploadSection" class="rounded-2xl bg-white p-6 shadow-lg">
          <h2 class="text-xl font-bold">Already paid? Upload your receipt</h2>
          <p class="mt-1 text-sm text-slate-500">A photo of the stamped voucher or a screenshot from your app is fine.</p>
          <form id="receiptForm" class="mt-5 space-y-5">
            <input type="hidden" name="booking_id" value="<?php echo $booking->booking_id; ?>">


            <label id="dropZone" for="receiptFile" class="flex min-h-40 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center hover:border-blue-400">
              <span class="grid h-12 w-12 place-items-center rounded-full bg-blue-50 text-xl text-blue-600">↑</span><span class="mt-3 font-semibold">Choose a file or drag it here</span><span class="mt-2 text-sm text-slate-500">JPG, PNG or PDF, up to 5 MB</span>
              <input
                id="receiptFile"
                name="receipt"
                type="file"
                accept=".jpg,.jpeg,.png,.pdf"
                class="hidden" />
            </label>

            <!-- Image Preview -->
            <div class="imagePreview hidden relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50">

              <img class="previewImg max-h-72 w-full object-contain p-2" src="" alt="Receipt preview" />

            </div>


            <div id="fileInfo" class="hidden flex items-center justify-between gap-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
              <span class="upload-file-info font-medium break-all"></span>

              <button
                type="button"
                class="remove-upload flex shrink-0 items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 font-medium text-red-600 transition hover:bg-red-50 hover:border-red-300 active:scale-95">
                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                Remove
              </button>

            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <div><label for="reference" class="mb-2 block text-sm font-medium">Transaction or reference ID</label><input id="reference" name="reference" type="text" placeholder="From your receipt" class="w-full rounded-xl border border-slate-300 px-3 py-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></div>
              <div><label for="paymentMethod" class="mb-2 block text-sm font-medium">Paid with</label><select id="paymentMethod" class="w-full rounded-xl border border-slate-300 px-3 py-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                  <option value="">Choose one</option>
                  <option>Bank transfer</option>
                  <option>Bank branch</option>
                  <option>Mobile banking</option>
                  <option>Other</option>
                </select></div>
            </div>
            <button id="submitBtn" disabled type="submit" class="w-full rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white disabled:cursor-not-allowed disabled:bg-slate-400">Submit for review</button>
            <p id="submitHint" class="text-center text-sm text-slate-500">Add your receipt to continue.</p>
    
            <div class="message"></div>
        
          </form>
        </section>
      </div>

      <!-- Sidebar -->
      <aside class="space-y-6">
        <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5">
          <p class="font-semibold text-amber-800">◷ &nbsp; Time left to pay and upload</p>
          <p class="mt-3 text-3xl font-extrabold text-amber-900">5h 42m</p>
          <p class="mt-2 text-sm leading-6 text-amber-800">If we don't have your receipt by 6:00 PM, the seats go back on sale.</p>
        </section>
        <section class="rounded-2xl bg-white p-6 shadow-lg">
          <div class="flex items-center justify-between gap-4">
            <div>
              <h2 class="text-xl font-bold"><?= htmlspecialchars($booking['route_from']) ?></h2>
              <p class="text-sm text-slate-500">Departure</p>
            </div>
            <div class="flex items-center gap-2 text-blue-600"><span>○</span><span class="w-12 border-t border-dashed border-blue-400"></span><span>●</span></div>
            <div class="text-right">
              <h2 class="text-xl font-bold"><?= htmlspecialchars($booking['route_to']) ?></h2>
              <p class="text-sm text-slate-500">Arrival</p>
            </div>
          </div>
          <div class="mt-6 grid grid-cols-2">
            <div>
              <p class="text-sm text-slate-500">Departs</p>
              <p class="mt-1 font-semibold"><?= htmlspecialchars($booking['departure']) ?></p>
              <p class="text-sm text-slate-600"><?= htmlspecialchars($booking['departure_time']) ?></p>
            </div>
            <div>
              <p class="text-sm text-slate-500">Seats</p>
              <div class="mt-1 flex gap-2"><?php foreach ($booking['seats'] as $seat): ?><span class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 font-semibold text-amber-800"><?= htmlspecialchars($seat) ?></span><?php endforeach; ?></div>
            </div>
          </div>
          <hr class="my-5 border-slate-100">
          <div class="flex justify-between text-sm"><span class="text-slate-500">Fare per seat</span><span>Rs. <?= number_format($booking['fare_per_seat']) ?></span></div>
          <div class="mt-2 flex justify-between text-sm"><span class="text-slate-500">Seats</span><span>× <?= count($booking['seats']) ?></span></div>
          <div class="mt-5 flex justify-between font-bold"><span>Total</span><span class="text-2xl">Rs. <?= number_format($booking['amount']) ?></span></div>
        </section>
        <p class="px-1 text-sm text-slate-500">Paid but can't upload? Call <strong><?= htmlspecialchars($booking['support_number']) ?></strong> with your voucher number.</p>
      </aside>
    </div>
  </div>

  <!-- Printable voucher content. Print stylesheet hides the rest of the UI. -->
  <div id="printVoucher" class="hidden">
    <div class="p-8 font-sans">
      <h1 class="text-2xl font-bold">Payment Voucher</h1>
      <p class="mt-2">Booking #<?= htmlspecialchars((string)$booking['id']) ?></p>
      <hr class="my-4">
      <p><strong>Voucher number:</strong> <?= htmlspecialchars($booking['voucher_number']) ?></p>
      <p><strong>Amount due:</strong> Rs. <?= number_format($booking['amount']) ?></p>
      <p><strong>Route:</strong> <?= htmlspecialchars($booking['route_from']) ?> → <?= htmlspecialchars($booking['route_to']) ?></p>
      <p><strong>Departure:</strong> <?= htmlspecialchars($booking['departure']) ?> <?= htmlspecialchars($booking['departure_time']) ?></p>
      <p><strong>Seats:</strong> <?= htmlspecialchars(implode(', ', $booking['seats'])) ?></p>
      <hr class="my-4">
      <h2 class="font-bold">Bank payment details</h2>
      <p>Bank: <?= htmlspecialchars($booking['bank']) ?></p>
      <p>Account title: <?= htmlspecialchars($booking['account_title']) ?></p>
      <p>IBAN: <?= htmlspecialchars($booking['iban']) ?></p>
      <p class="mt-6 text-sm">Please pay the exact amount and retain the receipt.</p>
    </div>
  </div>
  <style>
    @media print {
      body * {
        visibility: hidden !important;
      }

      #printVoucher,
      #printVoucher * {
        visibility: visible !important;
      }

      #printVoucher {
        display: block !important;
        position: absolute;
        inset: 0;
        width: 100%;
        background: white;
        color: black;
      }
    }
  </style>
  <script>
    const voucherNumber = <?= json_encode($booking['voucher_number']) ?>;
    const fileInput = document.getElementById('receiptFile');
    const submitBtn = document.getElementById('submitBtn');
    const fileInfo = document.getElementById('fileInfo');
    const submitHint = document.getElementById('submitHint');
    const dropZone = document.getElementById('dropZone');
    const imagePreview = document.querySelector('.imagePreview');
    const previewImg = document.querySelector('.previewImg');

    const uploadFileInfo = document.querySelector('#fileInfo .upload-file-info');
    const removeUpload = document.querySelector('#fileInfo .remove-upload');

    document.getElementById('copyBtn').addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(voucherNumber);
        alert('Voucher number copied!');
      } catch {
        prompt('Copy your voucher number:', voucherNumber);
      }
    });



    document.getElementById('downloadVoucher').addEventListener('click', () => window.print());
    document.getElementById('scrollUpload').addEventListener('click', () => document.getElementById('uploadSection').scrollIntoView({
      behavior: 'smooth'
    }));

    let previewUrl = null;

    function validateFile(file) {
      const allowed = ['image/jpeg', 'image/png', 'application/pdf'];
      if (!file) return false;
      if (!allowed.includes(file.type) || file.size > 5 * 1024 * 1024) {
        alert('Choose a JPG, PNG or PDF file no larger than 5 MB.');
        fileInput.value = '';
        fileInfo.classList.add('hidden');
        submitBtn.disabled = true;
        return false;
      }

      uploadFileInfo.textContent = `Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
      fileInfo.classList.remove('hidden');

      dropZone.classList.add('hidden');
      fileInfo.classList.remove('hidden');

      if (file.type.startsWith('image/')) {
        previewUrl = URL.createObjectURL(file);
        previewImg.src = previewUrl;
        imagePreview.classList.remove('hidden');
      } else {
        imagePreview.classList.add('hidden');
      }

      submitBtn.disabled = false;
      submitHint.textContent = 'Receipt selected. This demo does not send or store files.';
      return true;
    }

    function resetPreview() {
      if (previewUrl) {
        URL.revokeObjectURL(previewUrl);
        previewUrl = null;
      }

      previewImg.src = '';
      imagePreview.classList.add('hidden');
      fileInfo.classList.add('hidden');
      dropZone.classList.remove('hidden');
    }

    removeUpload.addEventListener('click', () => {
      fileInput.value = '';
      resetPreview();
      submitBtn.disabled = true;
      submitHint.textContent = 'No file selected.';
    });

    fileInput.addEventListener('change', () => validateFile(fileInput.files[0]));
    ['dragenter', 'dragover'].forEach(evt => dropZone.addEventListener(evt, e => {
      e.preventDefault();
      dropZone.classList.add('border-blue-500', 'bg-blue-50');
    }));
    ['dragleave', 'drop'].forEach(evt => dropZone.addEventListener(evt, e => {
      e.preventDefault();
      dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    }));
    dropZone.addEventListener('drop', e => {
      const f = e.dataTransfer.files[0];
      if (f) {
        fileInput.files = e.dataTransfer.files;
        validateFile(f);
      }
    });


    
    // document.getElementById('receiptForm').addEventListener('submit', e => {
    //   e.preventDefault();
    //   if (!fileInput.files[0]) return;
    //   alert('Demo UI only: connect this form to a PHP upload handler to store the receipt and mark it Pending Review.');
    // });



    document.getElementById('receiptForm').addEventListener('submit', async function(e) {

      e.preventDefault();

      const file = fileInput.files[0];

      if (!file) {
        alert('Please select a file');
        return;
      }

      const formData = new FormData();

      formData.append('file', file);
      formData.append('booking_id', 23)

      try {

        submitBtn.disabled = true;
        submitBtn.textContent = 'Uploading...';

        const response = await fetch('http://bookings.test/upload', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Upload failed');
        }

        console.log(result.data);

        document.querySelector('.message').textContent = 'Uploaded successfully!';

        // Use the uploaded image URL
        previewImg.src = result.data.url;

        // You can now save result.data.url and
        // result.data.public_id in your database.

      } catch (error) {

        document.querySelector('.message').textContent = error.message;

      } finally {

        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit for review';

      }

    });
  </script>
<?php endif; ?>



<?php loadPartialView('footer'); ?>