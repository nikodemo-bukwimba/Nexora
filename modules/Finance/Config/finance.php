<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Finance Module — Connection Name
    |--------------------------------------------------------------------------
    | All Finance models use this connection which maps to the
    | 'finance' schema in PostgreSQL.
    */
    'connection' => 'finance',

    /*
    |--------------------------------------------------------------------------
    | Migration Path
    |--------------------------------------------------------------------------
    */
    'migration_path' => __DIR__ . '/../Database/Migrations',

    /*
    |--------------------------------------------------------------------------
    | Commission
    |--------------------------------------------------------------------------
    | Default platform commission rate as a decimal.
    | e.g. 0.05 = 5%. Overridable via platform admin.
    */
    'default_commission_rate' => env('FINANCE_DEFAULT_COMMISSION_RATE', 0.05),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */
    'default_currency' => env('FINANCE_DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Credit
    |--------------------------------------------------------------------------
    */
    'credit' => [
        'min_topup_amount' => env('FINANCE_CREDIT_MIN_TOPUP', 1.00),
        'max_topup_amount' => env('FINANCE_CREDIT_MAX_TOPUP', 10000.00),
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice
    |--------------------------------------------------------------------------
    */
    'invoice' => [
        'due_days' => env('FINANCE_INVOICE_DUE_DAYS', 30),
        'prefix'   => env('FINANCE_INVOICE_PREFIX', 'INV'),
    ],

];
