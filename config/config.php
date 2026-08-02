<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */

    'db' => [

        'host' => 'localhost',

        'database' => 'u929623538_cibil',

        'username' => 'u929623538_cibilrepair',

        'password' => 'YOUR_DATABASE_PASSWORD'

    ],

    /*
    |--------------------------------------------------------------------------
    | SMTP
    |--------------------------------------------------------------------------
    */

    'smtp' => [

        'host' => 'smtp.hostinger.com',

        'port' => 587,

        'username' => 'contact@cibilrepair.in',

        'password' => 'Kundanlaxmi@1995',

        'encryption' => 'tls',

        'from_email' => 'contact@cibilrepair.in',

        'from_name' => 'CIBIL Repair'

    ],

    /*
    |--------------------------------------------------------------------------
    | Website
    |--------------------------------------------------------------------------
    */

    'website' => [

        'name' => 'CIBIL Repair',

        'url' => 'https://cibilrepair.in',

        'support_email' => 'contact@cibilrepair.in',

        'uploads' => $_SERVER['DOCUMENT_ROOT'].'/uploads/partner_docs/'

    ]

];