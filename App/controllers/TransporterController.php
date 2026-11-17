<?php

namespace App\Controllers;

use Framework\Database;
use Framework\TripDb;
use Framework\UserValidator;

class TransporterController
{
    protected TripDb $db;

    public function __construct()
    {
        $config = require basePath('config/db2.php');
        $this->db = new TripDb($config);
    }

    public function index()
    {
        $listings = $this->db->query("SELECT * FROM transporters")->fetchAll();
        loadView('transporters/home', [
            'listings' =>  $listings
        ]);
    }

    public function create()
    {
        loadView('transporters/create');
    }

    public function update()
    {
        loadView('transporters/update');
    }

    public function delete()
    {
        loadView('transporters/delete');
    }

    public function show()
    {
        $id = $_GET['id'] ?? '';
        $params = [
            'id' => $id
        ];

        $listing = $this->db->query('SELECT * FROM listings WHERE id = :id', $params)->fetch();

        loadView('listings/show', [
            'listing' => $listing
        ]);
    }


    public function search()
    {
        $errors = UserValidator::validate($_POST);

        if (!empty($errors)) {
            loadView('transporter/update', [
                'errors' => $errors
            ]);
            exit();
        }

        $transportId = $_POST['transporter_id'] ?? null;

        $params = [
            'id' => $transportId
        ];

        // Search transporter by ID
        $dbTransporter = $this->db->query(
            "SELECT * FROM transporters WHERE transport_id = :id",
            $params
        )->fetch();

        if (!$dbTransporter) {
            loadView('transporters/update', [
                'errors' => [
                    'transporter_id' => 'Transporter not found.'
                ],
                'transporter' => null
            ]);
            exit();
        }

        // Transporter found
        loadView('transporters/update', [
            'errors' => [],
            'transporter' => $dbTransporter
        ]);

        exit();
    }

    
public function change()
{
    $errors = UserValidator::validate($_POST);

    $transport = [
        'id' => $_POST['id'],
        //'transporter_id' => $_POST['transporter_id'],
        'transporter_name' => $_POST['transporter_name'],
        'transporter_picture' => $_POST['transporter_picture'],
    ];

    if (!empty($errors)) {
        loadView('transporters/update', [
            'errors' => $errors,
            'listing' => (object) $transport
        ]);
        exit();
    }

    $params = [
        'id' => $transport['id'],
        //'transporter_id' => $transport['transporter_id'],
        'name' => $transport['transporter_name'],
        'picture' => $transport['transporter_picture'],
    ];

    $sql = "UPDATE transporters
            SET transport_name = :name,
                transport_picture = :picture
            WHERE id = :id";

    $this->db->query($sql, $params);

    redirect("/transporters");
}


    public function store()
    {
        $errors = UserValidator::validate(($_POST));

        $transport = [
            'id' =>  $_POST['transporter_id'],
            'name' => $_POST['transporter_name'],
            'picture' => $_POST['transporter_picture'],
        ];

        if (!empty($errors)) {
            loadView('transporter/create', [
                'errors' => $errors,
                'user' => $transport
            ]);
            exit();
        }


        $params = [
            'id' =>  $transport['id'],
            'name' => $transport['name'],
            'picture' => $transport['picture'],
        ];

        $sql = "INSERT INTO transporters (transport_id, transport_name, transport_picture)
        VALUES (:id, :name, :picture)";

        $this->db->query($sql, $params);

        $userId = $this->db->getConn()->lastInsertId();

        redirect("/transporters");
    }

    public function searchDelete()
    {
        $errors = UserValidator::validate($_POST);

        if (!empty($errors)) {
            loadView('transporters/delete', [
                'errors' => $errors
            ]);
            exit();
        }

        $transportId = $_POST['transporter_id'] ?? null;

        $params = [
            'id' => $transportId
        ];

        $dbTransporter = $this->db->query(
            "SELECT * FROM transporters WHERE transport_id = :id",
            $params
        )->fetch();

        if (!$dbTransporter) {
            loadView('transporters/delete', [
                'errors' => [
                    'transporter_id' => 'Transporter not found.'
                ],
                'transporter' => null
            ]);
            exit();
        }

        loadView('transporters/delete', [
            'errors' => [],
            'transporter' => $dbTransporter
        ]);

        exit();
    }


    public function remove()
    {
        $errors = UserValidator::validate($_POST);

        $transport = [
            'id' => $_POST['id'],
        ];

        if (!empty($errors)) {
            loadView('transporters/delete', [
                'errors' => $errors,
                '$transporter' => (object) $transport
            ]);
            exit();
        }

        $params = [
            'id' => $transport['id']
        ];

        // Check if transporter exists
        $dbTransporter = $this->db->query(
            "SELECT * FROM transporters WHERE id = :id",
            $params
        )->fetch();

        if (!$dbTransporter) {
            redirect("/transporters");
            exit();
        }

        // Delete transporter
        $this->db->query(
            "DELETE FROM transporters WHERE id = :id",
            $params
        );

        redirect("/transporters");

        exit();
    }




}
