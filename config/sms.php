<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default SMS Driver
    |--------------------------------------------------------------------------
    |
    | The driver used when sending SMS via the Sms facade, Sms::send(), or the
    | "sms" notification channel. Falls back to "log" when unset so nothing
    | breaks in local/testing environments without gateway credentials.
    |
    | Supported out of the box: "log", "bulksmsbd", "mimsms", "sslwireless",
    | "alphasms", "greenweb", "http" (generic, config-only driver for any
    | other REST gateway).
    |
    */

    'default' => env('SMS_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Gateway Timeout
    |--------------------------------------------------------------------------
    |
    | Seconds to wait for a gateway before giving up, so a hanging gateway
    | cannot block a request or queue worker. Applies to every HTTP driver.
    |
    */

    'timeout' => (int) env('SMS_TIMEOUT', 10),

    'drivers' => [

        'bulksmsbd' => [
            'base_url' => env('BULKSMSBD_BASE_URL', 'https://bulksmsbd.net'),
            'api_key' => env('BULKSMSBD_API_KEY'),
            'sender_id' => env('BULKSMSBD_SENDER_ID'),
        ],

        'mimsms' => [
            'base_url' => env('MIMSMS_BASE_URL', 'https://api.mimsms.com'),
            'username' => env('MIMSMS_USERNAME'),
            'api_key' => env('MIMSMS_API_KEY'),
            'sender_id' => env('MIMSMS_SENDER_ID'),
        ],

        'sslwireless' => [
            'base_url' => env('SSLWIRELESS_BASE_URL', 'https://smsplus.sslwireless.com'),
            'api_token' => env('SSLWIRELESS_API_TOKEN'),
            'sid' => env('SSLWIRELESS_SID'),
        ],

        'alphasms' => [
            'base_url' => env('ALPHASMS_BASE_URL', 'https://api.sms.net.bd'),
            'api_key' => env('ALPHASMS_API_KEY'),
            'sender_id' => env('ALPHASMS_SENDER_ID'),
        ],

        'greenweb' => [
            'base_url' => env('GREENWEB_BASE_URL', 'http://api.greenweb.com.bd'),
            'token' => env('GREENWEB_TOKEN'),
        ],

        // Generic driver for any other gateway (e.g. Elitbuzz, REVE Systems).
        // Configure the endpoint and payload shape, using {to} and {message}
        // as placeholders — no code required.
        //
        // 'http' => [
        //     'url' => env('SMS_HTTP_URL'),
        //     'method' => 'post',
        //     'query' => [],
        //     'payload' => [
        //         'api_key' => env('SMS_HTTP_API_KEY'),
        //         'to' => '{to}',
        //         'message' => '{message}',
        //     ],
        //     'success_path' => 'status',
        //     'success_value' => 'success',
        //     'message_id_path' => 'data.id',
        // ],

    ],

];
