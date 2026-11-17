<?php

namespace Framework\Middleware;

use Framework\Session;

class Authorize
{
  public function isAuthenticated()
  {
    return Session::has('user');
  }

  public function isAdminAuthenticated()
  {
    return Session::has('admin');
  }

  public function handle(string $role)
    {
        switch ($role) {

            case 'guest':
                if ($this->isAuthenticated()) {
                    return redirect('/');
                }
                break;

            case 'auth':
                if (!$this->isAuthenticated()) {
                    return redirect('/auth/login');
                }
                break;

            case 'admin':
                if (!$this->isAdminAuthenticated()) {
                    return redirect('/transporters');
                }
                break;
        }

        return null;
    }
  
}