<?php

namespace App\Controllers;

// use Framework\Database;
use Framework\TripDb;

class TripsController
{
    protected TripDb $db;

    public function __construct()
    {
        $config = require basePath('config/db2.php');
        $this->db = new TripDb($config);
    }

    public function index()
    {
        $sql = "SELECT 
            t.id,
            v.registration_number,  
            t.origin,  
            t.destination,  
            t.departure_time, 
            t.arrival_time 

        FROM trips t INNER JOIN vehicles v ON t.vehicle_id = v.id";

        $trips = $this->db->query($sql)->fetchAll();
       
        loadView('trips/home', [
            'trips' => $trips
        ]);
    }

    public function create()
    {
        loadView('listings/create');
    }

    public function show()
    {
        $id = $_GET['id'] ?? '';
        $params = [
            'id' => $id
        ];

        //$sql = "SELECT id, trip_id, seat_id, price, status, locked_until FROM trip_seats WHERE trip_id: id";

        $sql = "SELECT 
                    ts.id, trip_id, 
                    ts.seat_id, price, 
                    status, 
                    locked_until, 
                    s.seat_number, 
                    s.seat_class
                FROM trip_seats ts INNER JOIN seats s ON s.id = ts.seat_id  
                WHERE trip_id = :id";



        $seats = $this->db->query($sql, $params)->fetchAll();

        
        
        loadView('trips/show', [
            'seats' => $seats,
            'trip_id' => $id
        ]);
    }


}
