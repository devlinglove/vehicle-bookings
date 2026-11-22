// Live updates for the trips page.
// PHP renders the page first; this only replaces the cards when socket data arrives.
// If the socket fails, the controller's data simply stays on screen.

const tripsList = document.getElementById('trips-list');
const countLabel = document.getElementById('trip-count-label');
const liveStatus = document.getElementById('live-status');
//const terminalId = Number(tripsList.dataset.terminalId) || null;

const SOCKET_URL = `ws://localhost:8080`;
let gotLiveData = false;

function connect() {
  //const ws = new WebSocket(SOCKET_URL);
  const ws = new WebSocket(" https://fe87-39-46-10-36.ngrok-free.app")

  ws.onopen = () => {
    ws.send(JSON.stringify({ action: 'all_trips' }));
  };

  ws.onmessage = (event) => {
    try {
      const msg = JSON.parse(event.data);
      if (msg.type === 'all_trips') {
        //renderTrips(msg.data);
        console.log('-------------', msg.data)
        gotLiveData = true;
        //liveStatus.hidden = true;
      }
    } catch (err) {
      console.error('Live update failed, keeping current data:', err);
    }
  };

  ws.onclose = () => {
    if (gotLiveData) {
      //liveStatus.textContent = 'Reconnecting… seat counts may be out of date.';
      //liveStatus.hidden = false;
    }
    setTimeout(connect, 3000); // try again in 3 seconds
  };
}

function renderTrips(trips) {
  // Build the HTML first: if anything throws, the page is left untouched
  const html = trips.length
    ? `<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 items-start">${trips.map(tripCard).join('')}</div>`
    : `<p class="text-gray-500 mt-8">No upcoming trips right now. Check back later.</p>`;

  tripsList.innerHTML = html;
  countLabel.textContent = `${trips.length} Upcoming ${trips.length === 1 ? 'Trip' : 'Trips'}`;
}

function tripCard(trip) {
  const active = trip.stand_status === 'active';
  const total = Number(trip.total_seats) || 0;
  //const available = active ? Number(trip.available_seats) || 0 : 0;
  const available = Number(trip.available_seats) || 0;
  const bookedPercent = active && total ? Math.round(((total - available) / total) * 100) : 0;
  const leftPercent = total ? (available / total) * 100 : 0;

  let barColor = 'bg-yellow-400';
  if (!active) barColor = 'bg-gray-300';
  else if (leftPercent <= 25) barColor = 'bg-red-500';
  else if (leftPercent <= 50) barColor = 'bg-blue-600';

  let vehicle = `${trip.vehicle_name || 'Vehicle'} #${trip.vehicle_number}`;
  if (trip.vehicle_type) vehicle += ` (${trip.vehicle_type})`;

  const status = String(trip.stand_status || '');
  const badge = active
    ? `<span class="shrink-0 bg-green-700 text-white text-sm font-semibold px-2 py-0.5 rounded">Active</span>`
    : `<span class="shrink-0 bg-gray-500 text-white text-sm font-semibold px-2 py-0.5 rounded">${esc(status.charAt(0).toUpperCase() + status.slice(1))}</span>`;

  let button;
  if (active && available > 0) {
    button = `<a href="/trip?id=${Number(trip.trip_id)}" class="block text-center w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mt-4 transition">Book Seat Online</a>`;
  } else {
    button = `<button disabled class="w-full bg-gray-400 text-white px-4 py-2 rounded mt-4 cursor-not-allowed">${active ? 'Fully Booked' : 'Unavailable'}</button>`;
  }

  return `
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
      <div class="flex justify-between items-start gap-3">
        <h3 class="text-2xl font-bold leading-tight">${trip.stand_name}</h3>
        ${badge}
      </div>
    </div>`;
}

// Same as PHP htmlspecialchars()
function esc(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

// Same as PHP date('d M, h:i A') for "2026-11-21 09:30:00"
function formatDate(value) {
  const [date, time = '00:00'] = String(value).split(' ');
  const [, month, day] = date.split('-');
  const [h, m] = time.split(':').map(Number);
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const hour12 = String(h % 12 || 12).padStart(2, '0');
  return `${day} ${months[Number(month) - 1]}, ${hour12}:${String(m).padStart(2, '0')} ${h < 12 ? 'AM' : 'PM'}`;
}

connect();