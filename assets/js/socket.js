
var conn = new WebSocket('ws://localhost:8080');
conn.onopen = function (e) {
    console.log("Connection established!");
    
};

conn.onmessage = function (e) {
    const data = JSON.parse(e.data)
    const listElement = document.querySelector('.data-list');

    console.log('data-ul', data, listElement);

    data.forEach(element => {
        const li = document.createElement('li');
        li.textContent = element.registration_number
        listElement.appendChild(li)
    });



    
};