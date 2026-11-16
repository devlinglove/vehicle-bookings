<?php

namespace App\Controllers;

use Framework\Database;
use Framework\Session;
use Framework\TripDb;
use Framework\UserValidator as FrameworkUserValidator;
use Framework\Validation;
use Framework\UserValidator;

class AuthController
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
        loadView('auth/create');
    }

    public function login()
    {
        loadView('auth/login');
    }

    public function authenticate(){
        $errors = UserValidator::validate(($_POST));
        $user = [
            'email' => $_POST['email'],
            'password' => $_POST['password'],
        ];
        if (!empty($errors)) {
            loadView('auth/login', [
                'errors' => $errors,
                'user' => $user
            ]);
            exit();
        }

        $params = [
            'email' => $user['email']
        ];

        $dbUser = $this->db->query("Select id, password, name, email, city, state from users where email =:email", $params)->fetch();
        
        if (!$dbUser) {
            $errors['incorrect'] = 'Incorrect email or password';
            loadView('auth/login', [
                'errors' => $errors,
                'user' => $user
            ]);
            exit();
        }

        if(password_verify($user['password'], $dbUser->password)){
            Session::set('user', [
                'userId' => $dbUser->id,
                'email' => $dbUser->email,
                'name' => $dbUser->name
            ]);
            redirect('/');
        }

        if(!password_verify($user['password'], $dbUser->password)){
            $errors['incorrect'] = 'Incorrect email or password';
            loadView('auth/login', [
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
            'city' => $_POST['city'],
            'state' => $_POST['state'],
        ];

        if (!empty($errors)) {
            loadView('auth/create', [
                'errors' => $errors,
                'user' => $user
            ]);
            exit();
        }

        $params = [
            'email' => $user['email']
        ];

        $dbUser = $this->db->query("Select email from users where email =:email", $params)->fetch();
        if($dbUser){
            $errors['email'] = 'User already exists';
            loadView('auth/create', [
                'errors' => $errors,
                'user' => $user
            ]);
            exit();
        }

        $params = [
            'name' => $user['name'],
            'email' => $user['email'],
            'password' => password_hash($user['password'], PASSWORD_DEFAULT),
            'city' => $user['city'],
            'state' => $user['state']
        ];

        $sql = "INSERT INTO users (name, email, password, city, state)
        VALUES (:name, :email, :password, :city, :state)";

        $this->db->query($sql, $params);

        $userId = $this->db->getConn()->lastInsertId();

        Session::set('user', [
            'userId' => $userId
        ]);

        redirect("/");
        
    }

    public function logout()
    {
        Session::clearAll();

        $params = session_get_cookie_params();
        setcookie('PHPSESSID', '', time() - 86400, $params['path'], $params['domain']);

        redirect('/');
    }



}
