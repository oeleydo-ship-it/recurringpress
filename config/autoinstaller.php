<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Manifest Filename
    |--------------------------------------------------------------------------
    |
    | The filename that triggers autoinstall detection when present in the
    | application root directory.
    |
    */
    'manifest' => 'install.json',

    /*
    |--------------------------------------------------------------------------
    | Installed Marker Filename
    |--------------------------------------------------------------------------
    |
    | After successful installation, this file is created to prevent
    | re-triggering the autoinstaller.
    |
    */
    'installed_marker' => 'installed',

    /*
    |--------------------------------------------------------------------------
    | Scan Directories
    |--------------------------------------------------------------------------
    |
    | Directories to scan for pending installations. By default, only the
    | storage path is scanned for uploaded applications.
    |
    */
    'scan_directories' => [
        // storage_path('app/uploads'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum PHP Version
    |--------------------------------------------------------------------------
    */
    'php_version' => '8.1.0',

    /*
    |--------------------------------------------------------------------------
    | Required PHP Extensions
    |--------------------------------------------------------------------------
    */
    'extensions' => [
        'pdo',
        'pdo_mysql',
        'mbstring',
        'tokenizer',
        'xml',
        'ctype',
        'json',
        'bcmath',
        'openssl',
        'fileinfo',
        'zip',
    ],

    /*
    |--------------------------------------------------------------------------
    | Required Folder Permissions
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'storage/framework/' => '0775',
        'storage/logs/' => '0775',
        'storage/app/' => '0775',
        'bootstrap/cache/' => '0775',
        '.env' => '0664',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Post-Install Commands
    |--------------------------------------------------------------------------
    */
    'post_install_commands' => [
        'config:clear',
        'cache:clear',
        'view:clear',
        'route:clear',
    ],

    /*
    |--------------------------------------------------------------------------
    | Enable Autoinstaller
    |--------------------------------------------------------------------------
    */
    'enabled' => env('AUTOINSTALLER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Installation Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => 300,
];
