<?php loadPartialView('header'); ?>
<?php loadPartialView('navbar'); ?>
<?php

/**
 * @var array  $errors
 * @var array  $user
 */
?>
<!-- Registration Form Box -->
<div class="flex justify-center items-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full md:w-[500px] mx-6">
        <h2 class="text-4xl text-center font-bold mb-4">Register</h2>

        <?php if (isset($errors)) : ?>
            <?php foreach ($errors as $error) : ?>
                <div class="message bg-red-100 my-3"><?= $error ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form action="/auth/register" method="POST">
            <div class="mb-4">
                <input
                    type="text"
                    name="name"
                    placeholder="User Name"
                    class="w-full px-4 py-2 border rounded focus:outline-none"
                    value="<?= $user['name'] ?? ''?>"
                 />
            </div>
            <div class="mb-4">
                <input
                    type="email"
                    name="email"
                    placeholder="Email Address"
                    class="w-full px-4 py-2 border rounded focus:outline-none" 
                    value="<?= $user['email'] ?? ''?>"
                    />
            </div>
            <div class="mb-4">
                <input
                    type="text"
                    name="contact"
                    placeholder="Contact"
                    class="w-full px-4 py-2 border rounded focus:outline-none"
                    value="<?= $user['contact'] ?? ''?>"
                    />
            </div>
            <!-- <div class="mb-4">
                <input
                    type="text"
                    name="city"
                    placeholder="City"
                    class="w-full px-4 py-2 border rounded focus:outline-none"
                    value="<?= $user['city'] ?? ''?>"
                    />
            </div> -->
            <!-- <div class="mb-4">
                <input
                    type="text"
                    name="state"
                    placeholder="State"
                    class="w-full px-4 py-2 border rounded focus:outline-none"
                    value="<?= $user['state'] ?? ''?>"
                    />
            </div> -->
            <div class="mb-4">
                <input
                    type="password"
                    name="password"
                    placeholder="Password"
                    class="w-full px-4 py-2 border rounded focus:outline-none"
                    value="<?= $user['password'] ?? ''?>"
                    
                    />
            </div>
            <div class="mb-4">
                <input
                    type="password"
                    name="password_confirmation"
                    placeholder="Confirm Password"
                    class="w-full px-4 py-2 border rounded focus:outline-none" 
                    value="<?= $user['password_confirmation'] ?? ''?>"
                    />
            </div>
            <button
                type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded focus:outline-none">
                Register
            </button>

            <p class="mt-4 text-gray-500">
                Already have an account?
                <a class="text-blue-900" href="/auth/login">Login</a>
            </p>
        </form>
    </div>
</div>

<?php loadPartialView('footer'); ?>