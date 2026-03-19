<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Module — Connection Name
    |--------------------------------------------------------------------------
    | All Platform models extend PlatformModel which uses this connection.
    | Maps to the 'platform' search_path in PostgreSQL.
    */
    'connection' => 'platform',

    /*
    |--------------------------------------------------------------------------
    | Migration Path
    |--------------------------------------------------------------------------
    */
    'migration_path' => __DIR__ . '/../Database/Migrations',

    /*
    |--------------------------------------------------------------------------
    | Seeder
    |--------------------------------------------------------------------------
    */
    'seeder' => 'Modules\\Platform\\Database\\Seeders\\PlatformSeeder',

];
