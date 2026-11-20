<?php

use Framework\Session;

?>

<header class="bg-blue-900 text-white p-4">

  <!-- Main Navbar -->
  <div class="container mx-auto flex justify-between items-center">
    <h1 class="text-3xl font-semibold">
      <a href="/">Online Application - Traveling Assistant</a>
    </h1>

    <nav>
      <div class="flex justify-between items-center gap-4">
        <?php if (Session::has('user')) : ?>

          <div class="text-blue-500">
            Welcome <?= Session::get('user')['username'] ?>
          </div>

          <a href="/auth/profile" class="text-white hover:underline">
            Update Profile
          </a>

          <a href="/trips" class="text-white hover:underline">
            Trips
          </a>

          <form method="POST" action="/auth/logout">
            <button type="submit" class="text-white inline hover:underline">
              Logout
            </button>
          </form>




        <?php else : ?>

          <a href="/auth/login" class="text-white hover:underline">Login</a>
          <a href="/auth/register" class="text-white hover:underline">Register</a>

        <?php endif; ?>
      </div>
    </nav>
  </div>

  <!-- Admin Login Below Navbar (Right Side) -->
  <div class="container mx-auto flex justify-end items-center gap-4 mt-3">
    <?php if (Session::has('admin')) : ?>

      <div class="text-blue-500">
        Welcome <?= Session::get('admin')['name'] ?>
      </div>


      <a href="/trips" class="text-white hover:underline">
        Trips
      </a>

      <form method="POST" action="/admin/logout">
        <button type="submit" class="text-white hover:underline">
          Admin Logout
        </button>



      </form>

    <?php else : ?>

      <a href="/admin/login" class="text-white hover:underline">
        Admin Login
      </a>

      <a href="/admin/register" class="text-white hover:underline">
        Admin Register
      </a>

    <?php endif; ?>
  </div>

</header>