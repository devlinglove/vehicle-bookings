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
        <h2 class="text-4xl text-center font-bold mb-4">Update Profile</h2>

        <?php if (isset($errors)) : ?>
            <?php foreach ($errors as $error) : ?>
                <div class="message bg-red-100 my-3"><?= $error ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form action="/auth/update-profile" method="POST">
            <div class="mb-4">
                <input
                    type="text"
                    name="name"
                    placeholder="User Name"
                    class="w-full px-4 py-2 border rounded focus:outline-none"
                    value="<?= $user->username ?? '' ?>" />
            </div>
            <div class="mb-4">
                <input
                    type="email"
                    name="email"
                    readonly
                    placeholder="Email Address"
                    class="w-full px-4 py-2 border rounded focus:outline-none"
                    value="<?= $user->email ?? '' ?>" />
                    
            </div>

            <div class="mb-4">
                <input
                    type="text"
                    name="contact"
                    placeholder="Contact"
                    class="w-full px-4 py-2 border rounded focus:outline-none"
                    value="<?= $user->contact ?? '' ?>" />
            </div>
            <button
                type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded focus:outline-none">
                Update
            </button>

        </form>
    </div>
</div>

<?php loadPartialView('footer'); ?>