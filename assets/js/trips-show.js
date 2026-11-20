
const socket = new WebSocket('ws://localhost:8080');

const tripId = window.tripId;
let selectedSeats = []
let socketSeats = []


socket.onopen = function () {
    console.log('Connected');
    socket.send(JSON.stringify({
        action: 'all_trips',
        trip_id: tripId
    }))
};

socket.onmessage = function (event) {
    try {
      const msg = JSON.parse(event.data);
      if (msg.type === 'all_trips') {
        let data = msg.data
        if(data && Array.isArray(data) && data.length > 0){
            renderTripsHtml(data)
        }
      }
    } catch (err) {
      console.error('Live update failed, keeping current data:', err);
    }
};

socket.onclose = function () {
    console.log('Disconnected');
};

const tripsList = document.querySelector('.trips-list');

function renderTripsHtml(trips){

    let buttonHtml = ""
    
    const html = trips.map(trip => {
    
    const isStandActive = trip.stand_status === 'active';
    const status = String(trip.stand_status || '');
    const vehicleLabel = trip.vehicle_name ?? 'Vehicle' + ' #' + trip.vehicle_number;

    const available_seats = isStandActive ? trip.available_seats : 0
    const canBook = isStandActive && available_seats > 0;

    
const badge = isStandActive
    ? `<span class="shrink-0 bg-green-700 text-white text-sm font-semibold px-2 py-0.5 rounded">Active</span>`
    : `<span class="shrink-0 bg-gray-500 text-white text-sm font-semibold px-2 py-0.5 rounded">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;


    if (canBook) {
        buttonHtml = `
            <a href="/trip?id=${trip.trip_id}"
                class="block text-center w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mt-4 transition">
                Book Seat Online
            </a>
        `;
    } else if (isStandActive) {
        buttonHtml = `
            <button disabled
                class="w-full bg-gray-400 text-white px-4 py-2 rounded mt-4 cursor-not-allowed">
                Fully Booked
            </button>
        `;
    } else {
        buttonHtml = `
            <button disabled
                class="w-full bg-gray-400 text-white px-4 py-2 rounded mt-4 cursor-not-allowed">
                Unavailable
            </button>
        `;
    }



    return `
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
            <div class="flex justify-between items-start gap-3">
                <h3 class="text-2xl font-bold leading-tight">
                    ${trip.stand_name}
                </h3>
                
                ${badge}
            </div>

                <p class="text-gray-600 mt-2">
                    Route: ${trip.route_name}
                </p>

                <hr class="my-4">

                <p class="font-bold">${vehicleLabel}</p>


            <p class="text-gray-600 mt-2">
                ${available_seats} / ${trip.total_seats} Seats Available | Fare: Rs. ${trip.fare}
            </p>

            <p class="text-gray-500 text-sm mt-1">
                Departs: ${trip.departure_time}
            </p>

            ${buttonHtml}

        </div>
    `;
    }).join("");

    tripsList.innerHTML = html
}



function renderTrips(trips) {
  // Build the HTML first: if anything throws, the page is left untouched
  const html = trips.length
    ? `<div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">${trips.map(tripCard).join('')}</div>`
    : `<p class="text-gray-500 mt-8">No upcoming trips right now. Check back later.</p>`;

  tripsList.innerHTML = html;
  //countLabel.textContent = `${trips.length} Upcoming ${trips.length === 1 ? 'Trip' : 'Trips'}`;
}


function tripCard(trip) {
  const active = trip.stand_status === 'active';
  const total = Number(trip.total_seats) || 0;
  const status = String(trip.stand_status || '');
  const available = Number(trip.available_seats) || 0;
  const bookedPercent = active && total ? Math.round(((total - available) / total) * 100) : 0;
  const leftPercent = total ? (available / total) * 100 : 0;


  
  const badge = active
    ? `<span class="shrink-0 bg-green-700 text-white text-sm font-semibold px-2 py-0.5 rounded">Active</span>`
    : `<span class="shrink-0 bg-gray-500 text-white text-sm font-semibold px-2 py-0.5 rounded">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;

 
  return `
     <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
      <div class="flex justify-between items-start gap-3">
        <h3 class="text-2xl font-bold leading-tight">${trip.stand_name}</h3>
        ${badge}
      </div>
    </div>`;
}


