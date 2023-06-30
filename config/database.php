<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],
        
        'cep' => [
            'driver' => 'pgsql',
            'host' => env('DB_CEP_HOST', '127.0.0.1'),
            'port' => env('DB_CEP_PORT', '5432'),
            'database' => env('DB_CEP_DATABASE', 'forge'),
            'username' => env('DB_CEP_USERNAME', 'forge'),
            'password' => env('DB_CEP_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'nasajon' => [
            'driver' => 'pgsql',
            'host' => env('DB_NASAJON_HOST', '127.0.0.1'),
            'port' => env('DB_NASAJON_PORT', '5432'),
            'database' => env('DB_NASAJON_DATABASE', 'forge'),
            'username' => env('DB_NASAJON_USERNAME', 'forge'),
            'password' => env('DB_NASAJON_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'srv_prologos' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST_srv_prologos', 'localhost'),
            'port' => env('DB_PORT_srv_prologos', '1433'),
            'database' => env('DB_DATABASE_srv_prologos', 'forge'),
            'username' => env('DB_USERNAME_srv_prologos', 'forge'),
            'password' => env('DB_PASSWORD_srv_prologos', ''),
            'charset' => 'iso_1',
            'collation' => 'latin1_unicode_ci',
            'prefix' => '',
        ],
        
        'srv_almirante' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST_srv_almirante', 'localhost'),
            'port' => env('DB_PORT_srv_almirante', '1433'),
            'database' => env('DB_DATABASE_srv_almirante', 'forge'),
            'username' => env('DB_USERNAME_srv_almirante', 'forge'),
            'password' => env('DB_PASSWORD_srv_almirante', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        
        'srv_botelho' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST_srv_botelho', 'localhost'),
            'port' => env('DB_PORT_srv_botelho', '1433'),
            'database' => env('DB_DATABASE_srv_botelho', 'forge'),
            'username' => env('DB_USERNAME_srv_botelho', 'forge'),
            'password' => env('DB_PASSWORD_srv_botelho', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        
        'srv_armazen' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST_srv_armazen', 'localhost'),
            'port' => env('DB_PORT_srv_armazen', '1433'),
            'database' => env('DB_DATABASE_srv_armazen', 'forge'),
            'username' => env('DB_USERNAME_srv_armazen', 'forge'),
            'password' => env('DB_PASSWORD_srv_armazen', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
                
        'srv_xavantes' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST_srv_xavantes', 'localhost'),
            'port' => env('DB_PORT_srv_xavantes', '1433'),
            'database' => env('DB_DATABASE_srv_xavantes', 'forge'),
            'username' => env('DB_USERNAME_srv_xavantes', 'forge'),
            'password' => env('DB_PASSWORD_srv_xavantes', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
                
        'srv_ww' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST_srv_ww', 'localhost'),
            'port' => env('DB_PORT_srv_ww', '1433'),
            'database' => env('DB_DATABASE_srv_ww', 'forge'),
            'username' => env('DB_USERNAME_srv_ww', 'forge'),
            'password' => env('DB_PASSWORD_srv_ww', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
                
        'srv_rondonia' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST_srv_rondonia', 'localhost'),
            'port' => env('DB_PORT_srv_rondonia', '1433'),
            'database' => env('DB_DATABASE_srv_rondonia', 'forge'),
            'username' => env('DB_USERNAME_srv_rondonia', 'forge'),
            'password' => env('DB_PASSWORD_srv_rondonia', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
                
        'srv_pedido' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST_srv_pedido', 'localhost'),
            'port' => env('DB_PORT_srv_pedido', '1433'),
            'database' => env('DB_DATABASE_srv_pedido', 'forge'),
            'username' => env('DB_USERNAME_srv_pedido', 'forge'),
            'password' => env('DB_PASSWORD_srv_pedido', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],
	
	    'srv_tocantins' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST_srv_tocantins', 'localhost'),
            'port' => env('DB_PORT_srv_tocantins', '1433'),
            'database' => env('DB_DATABASE_srv_tocantins', 'forge'),
            'username' => env('DB_USERNAME_srv_tocantins', 'forge'),
            'password' => env('DB_PASSWORD_srv_tocantins', ''),
            'charset' => 'utf8',
            'prefix' => '',
        ],

        ''

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => 'predis',

        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ],

    ],

];
