<?php

return [
    'merchant_id' => 755593,
    'merchant_key' => 'zuYYuI_jnx7iol0azJSxF5',
    'secret' => 'oIY3ZPlvk/6yQTvsnDWvXeDArkswHvqGaLwYz6XFg+Y=',

    'sandbox' => true,

    'currency_code' => 'PKR',
    'proccode' => '00',

    'return_url' => ' https://1f62-39-46-10-36.ngrok-free.app/payment-return',
    'cancel_url' => ' https://1f62-39-46-10-36.ngrok-free.app/payment-cancel',
    'notify_url' => ' https://1f62-39-46-10-36.ngrok-free.app/payment-notify',


    'token_url' => 'https://ipguat.apps.net.pk/Ecommerce/api/Transaction/GetAccessToken',
    'checkout_url' => 'https://ipguat.apps.net.pk/Ecommerce/api/Transaction/PostTransaction',
];