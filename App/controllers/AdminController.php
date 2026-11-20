<?php

namespace App\Controllers;

use Framework\Database;
use Framework\Session;
use Framework\TripDb;
use Framework\UserValidator as FrameworkUserValidator;
use Framework\Validation;
use Framework\UserValidator;

class AdminController
{
    protected TripDb $db;

    public function __construct()
    {
        $config = require basePath('config/db2.php');
        $this->db = new TripDb($config);
    }

    public function index()
    {
        $listings = $this->db->query("SELECT * FROM listings")->fetchAll();
        loadView('listings/home', [
            'listings' =>  $listings
        ]);
    }

    public function create()
    {
        loadView('admin/create');
    }

    public function login()
    {
        loadView('admin/login');
    }

    public function authenticate(){
        $errors = UserValidator::validate(($_POST));
        $user = [
            'email' => $_POST['email'],
            'password' => $_POST['password'],
        ];
        if (!empty($errors)) {
            loadView('admin/login', [
                'errors' => $errors,
                'user' => $user
            ]);
            exit();
        }

        $params = [
            'email' => $user['email']
        ];

        $dbUser = $this->db->query("Select id, password, name, email, contact from admins where email =:email", $params)->fetch();
        
        if (!$dbUser) {
            $errors['incorrect'] = 'Incorrect email or password';
            loadView('admin/login', [
                'errors' => $errors,
                'user' => $user
            ]);
            exit();
        }

        if(password_verify($user['password'], $dbUser->password)){
            Session::set('admin', [
                'userId' => $dbUser->id,
                'email' => $dbUser->email,
                'name' => $dbUser->name
            ]);
            redirect('/trips');
        }

        if(!password_verify($user['password'], $dbUser->password)){
            $errors['incorrect'] = 'Incorrect email or password';
            loadView('admin/login', [
                'errors' => $errors,
                'user' => $user
            ]);
            exit();
        }
  
    }

    public function store()
    {
        $errors = UserValidator::validate(($_POST));

        $user = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'password_confirmation' => $_POST['password_confirmation'],
            //'city' => $_POST['city'],
            //'state' => $_POST['state'],
            'contact' => $_POST['contact']
        ];

        if (!empty($errors)) {
            loadView('admin/create', [
                'errors' => $errors,
                'user' => $user
            ]);
            exit();
        }

        $params = [
            'email' => $user['email']
        ];

        $dbUser = $this->db->query("Select email from admins where email =:email", $params)->fetch();
        if($dbUser){
            $errors['email'] = 'Admin already exists';
            loadView('admin/create', [
                'errors' => $errors,
                'user' => $user
            ]);
            exit();
        }

        $params = [
            'name' => $user['name'],
            'email' => $user['email'],
            'password' => password_hash($user['password'], PASSWORD_DEFAULT),
            'contact' => $user['contact']
            //'state' => $user['state']
        ];

        $sql = "INSERT INTO admins (name, email, password, contact)
        VALUES (:name, :email, :password, :contact)";

        $this->db->query($sql, $params);

        $userId = $this->db->getConn()->lastInsertId();

        // Session::set('admin', [
        //     'userId' => $userId
        // ]);

        Session::set('admin', [
            'userId' => $userId,
            'email' => $user['email'],
            'name' => $user['name']
        ]);


        redirect("/trips");
        
    }


    

    public function logout()
    {
        Session::clearAll();

        $params = session_get_cookie_params();
        setcookie('PHPSESSID', '', time() - 86400, $params['path'], $params['domain']);

        redirect('/');
    }



}
