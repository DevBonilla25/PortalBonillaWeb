<?php

return [

    /*
    |--------------------------------------------------------------------------
    |  Default Filesystem Disk
    |  Disco de Sistema de Archivos Predeterminado
    |--------------------------------------------------------------------------
    |
    | Aquí puedes especificar el disco de sistema de archivos predeterminado que debe ser utilizado
    | por el framework. El disco "local", así como una variedad de discos en la nube,
    | están disponibles para que tu aplicación almacene archivos.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    'logistics_media_disk' => env('LOGISTICS_MEDIA_DISK', env('FILESYSTEM_DISK', 'public')),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar tantos discos de sistema de archivos como necesites, y
    | incluso puedes configurar múltiples discos para el mismo controlador. Ejemplos
    | para los controladores de almacenamiento más comunes están configurados aquí
    | como referencia.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        'r2' => [
            'driver' => 's3',
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'region' => env('R2_REGION', 'auto'),
            'bucket' => env('R2_BUCKET'),
            'url' => env('R2_URL'),
            'endpoint' => env('R2_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar los enlaces simbólicos que se crearán cuando se ejecute el
    | comando Artisan `storage:link`. Las claves del array deben ser las ubicaciones de
    | los enlaces y los valores deben ser sus destinos.
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
