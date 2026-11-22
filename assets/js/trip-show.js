
//const socket = new WebSocket('ws://localhost:8080');
const socket = new WebSocket(" https://fe87-39-46-10-36.ngrok-free.app")

const tripId = window.tripId;
const token = window.holdToken
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
const totalContainer = document.querySelector('.total-container')
const totalPrice = document.querySelector('.total-price');
const paymentButton = document.querySelector('.payment-button');

paymentButton.addEventListener('click', async (event) => {
 
  const seatIds = selectedSeats.map((item) => item.trip_seat_id);
  if (!seatIds.length) return;

    if (!seatIds.length) {
        event.preventDefault();
        alert('Please select at least one seat.');
        return;
    }

    const box = document.getElementById('seat-inputs');
    box.innerHTML = '';
    for (const id of seatIds) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'seat_ids[]'; 
        input.value = id;
        box.append(input);
    }
    
    //event.submitter.disabled = true;  

});




chipContainer.addEventListener('click', (e) => {
    const btn = e.target.closest('.remove-seat')
    if (!btn) return

    const seatId = btn.dataset.removeSeatId
    selectedSeats = selectedSeats.filter(s => s.trip_seat_id != Number(seatId))

    removeFromHold(Number(seatId))
    
    renderSelectedSeats()
})


function removeFromHold(seatId) {
    let selected = socketSeats.find((item) => item.trip_seat_id == seatId)
    socket.send(JSON.stringify({
        action: 'seat_status_change',
        trip_id: selected.trip_id,
        seat_id: selected.trip_seat_id,
        status: 'available',
        hold_token: holdToken
    }));

    renderSelectedSeats()
    renderSeats()


}

socket.onopen = function () {
    console.log('Connected');
    socket.send(JSON.stringify({
        action: 'join_trip',
        trip_id: tripId
    }));
};

function getSum(total, num) {
  return total + num;
}

function renderSelectedSeats() {
   
    chipContainer.innerHTML = selectedSeats.map((item) =>
        `<span class="inline-flex items-center gap-2 rounded-full bg-navy-50 py-1.5 pl-3 pr-1.5 text-sm font-bold text-navy-700">
                    Seat ${item.seat_number}
                    <button type="button" data-remove-seat-id="${item.trip_seat_id}" class="remove-seat grid h-6 w-6 place-items-center rounded-full hover:bg-white" aria-label="Remove seat ${item.seat_number}">
                        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="currentColor">
                            <path d="m5.3 4.2 4.7 4.7 4.7-4.7 1.1 1.1-4.7 4.7 4.7 4.7-1.1 1.1-4.7-4.7-4.7 4.7-1.1-1.1 4.7-4.7-4.7-4.7z" />
                        </svg>
                    </button>
                </span>`
    ).join('')


    if(selectedSeats.length > 0){
        totalContainer.classList.remove('hidden')
        totalContainer.classList.add('block')
    }

    if(selectedSeats.length == 0){
        totalContainer.classList.add('hidden')
    }

    //const price = selectedSeats.reduce((accumulator, currentValue) => accumulator + currentValue, 0);
    const price = selectedSeats.reduce((sum, seat) => { return sum + Number(seat.fare)}, 0);
    totalPrice.innerHTML = `${price} PKR`;

    paymentButton.disabled = selectedSeats.length == 0 ? true : false

    

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
    if(selectedSeats.length == 4){
        return;
    }

    let selected = socketSeats.find((item) => item.trip_seat_id == element.trip_seat_id)
    selected.status = 'hold';
    selectedSeats.push(selected);
    renderSelectedSeats();
    socket.send(JSON.stringify({
        action: 'seat_status_change',
        trip_id: element.trip_id,
        seat_id: element.trip_seat_id,
        status: 'hold',
        hold_token: holdToken
    }));

    renderSeats()

}

// document.querySelectorAll('.seat-grid button').forEach(btn => {
//     btn.addEventListener('click', function () {
//         const seatId = this.dataset.seatId;
//         console.log('Selected seat:', seatId);
//         holdSeat(this, seatId);
//     });
// });



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
        btn.dataset.seatId = element.trip_seat_id;

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
   
    let data = null
    const msg = JSON.parse(event.data);

    if (msg.type == 'join_trip' || msg.type == 'seat_status_change') {
        data = msg.data;
        if (data && Array.isArray(data) && data.length > 0) {
            socketSeats = data;
            const availableSeats = socketSeats.filter((item) => item.status != 'booked')
            const total = document.querySelector('.total-seats')
            const available = document.querySelector('.available-seats')
            total.textContent = socketSeats.length
            available.textContent = availableSeats.length
        }
    }

    
    
    renderSeats()
    renderSelectedSeats()

    console.log('sockets-seats', socketSeats);
    console.log('Seat update:', event);
};

socket.onclose = function () {
    console.log('Disconnected');
};

