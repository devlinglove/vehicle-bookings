<?php

namespace Framework\Middleware;

use Framework\Session;

class Authorize
{
  public function isAuthenticated()
  {
    return Session::has('user');
  }

  public function handle(string $role)
  {
    if ($role === 'guest' && $this->isAuthenticated()) {
      return redirect('/');
    } elseif ($role === 'auth' && !$this->isAuthenticated()) {
      return redirect('/auth/login');
    }
  }
}