
const socket = new WebSocket('ws://localhost:8080');

const tripId = window.tripId;
let selectedSeats = []
let socketSeats = []


// document.querySelectorAll('.remove-seat').forEach(button => {
//     console.log('chip-clicked')
//     button.onclick = () => {
//         const seatId = button.dataset.seatId;

//         selectedSeats = selectedSeats.filter(
//             seat => seat.seat_id != seatId
//         );

//         render();
//     };
// });

const chipContainer = document.querySelector('.chip-container')

// Attach ONCE, outside render()
chipContainer.addEventListener('click', (e) => {
    const btn = e.target.closest('.remove-seat')
    if (!btn) return

    const seatId = btn.dataset.removeSeatId
    selectedSeats = selectedSeats.filter(s => s.seat_id != Number(seatId))

    removeFromHold(Number(seatId))
    
    renderSelectedSeats()
})


function removeFromHold(seatId) {
    let selected = socketSeats.find((item) => item.seat_id == seatId)
    socket.send(JSON.stringify({
        action: 'seat_status_change',
        trip_id: selected.trip_id,
        seat_id: selected.seat_id,
        status: 'available'
    }));


    renderSeats()


}

socket.onopen = function () {
    console.log('Connected');

    console.log('id', tripId)

    socket.send(JSON.stringify({
        action: 'join_trip',
        trip_id: tripId
    }));
};


function renderSelectedSeats() {
   
        chipContainer.innerHTML = selectedSeats.map((item) =>
            `<span class="inline-flex items-center gap-2 rounded-full bg-navy-50 py-1.5 pl-3 pr-1.5 text-sm font-bold text-navy-700">
                        Seat ${item.seat_number}
                        <button type="button" data-remove-seat-id="${item.seat_id}" class="remove-seat grid h-6 w-6 place-items-center rounded-full hover:bg-white" aria-label="Remove seat ${item.seat_number}">
                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                                <path d="m5.3 4.2 4.7 4.7 4.7-4.7 1.1 1.1-4.7 4.7 4.7 4.7-1.1 1.1-4.7-4.7-4.7 4.7-1.1-1.1 4.7-4.7-4.7-4.7z" />
                            </svg>
                        </button>
                    </span>`
        ).join('')

      
}

const seatClass = (status) =>{
   const base = 'seat relative w-[46px] min-w-[46px] h-[46px] min-h-[46px] not-mobile:w-[38px] not-mobile:min-w-[38px] not-mobile:h-[38px] not-mobile:min-h-[38px] flex items-center justify-center rounded-xl text-sm sm:text-base font-bold focus:outline-none focus-visible:ring-4';
    if (status == 'booked') {
        //return `${base} border-2 border-navy-600 bg-navy-50 text-navy-700 focus-visible:ring-navy-100`;
        return `${base} bg-navy-600 text-white cursor-not-allowed`;
    }
    if (status == 'hold') {
        return `${base} border-2 border-navy-600 bg-navy-50 text-navy-700 focus-visible:ring-navy-100`;
        //return `${base} bg-navy-50 text-white cursor-not-allowed`;
    }

    if (status == 'available') return `${base} border-2 border-slate-200 bg-white text-slate-700 hover:border-navy-600 hover:shadow-md focus-visible:ring-navy-100`;
}


function holdSeat(btn, element) {
    let selected = socketSeats.find((item) => item.seat_id == element.seat_id)
    selected.status = 'hold';
    selectedSeats.push(selected);
    renderSelectedSeats();
    socket.send(JSON.stringify({
        action: 'seat_status_change',
        trip_id: element.trip_id,
        seat_id: element.seat_id,
        status: 'hold'
    }));

    renderSeats()

}

function renderSeats() {
    const group = document.querySelector('.seat-grid');
     group.replaceChildren();

    socketSeats.forEach(element => {
         const btn = document.createElement('button');
    
        const seat = seatClass(element.status);

        if (element.status === 'booked') {
            btn.disabled = true;
        }

        btn.className = seat;
        btn.textContent = element.seat_number;
        btn.dataset.seatId = element.seat_id;

        if (element.status === 'hold') {
            btn.disabled = true;
            btn.innerHTML = check
        }

        btn.onclick = () => {
            holdSeat(btn, element);
        };

        group.appendChild(btn);
    });
}


const check = '<svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor"><path d="M7.6 13.2 4.4 10l-1.1 1.1 4.3 4.3 9-9-1.1-1.1z"/></svg>';

socket.onmessage = function (event) {
    const data = JSON.parse(event.data);
    if(data && Array.isArray(data) && data.length > 0){
        socketSeats = data;
        const availableSeats = socketSeats.filter((item) => item.status != 'booked')
        const total = document.querySelector('.total-seats')
        const available = document.querySelector('.available-seats')
        total.textContent = socketSeats.length
        available.textContent = availableSeats.length

    }
    
    renderSeats()

    console.log('sockets-seats', socketSeats);
    console.log('Seat update:', data);
};

socket.onclose = function () {
    console.log('Disconnected');
};

