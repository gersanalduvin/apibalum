<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Snappy PDF / Image Configuration
    |--------------------------------------------------------------------------
    |
    | This option contains settings for PDF generation.
    |
    | Enabled:
    |
    |    Whether to load PDF / Image generation.
    |
    | Binary:
    |
    |    The file path of the wkhtmltopdf / wkhtmltoimage executable.
    |
    | Timeout:
    |
    |    The amount of time to wait (in seconds) before PDF / Image generation is stopped.
    |    Setting this to false disables the timeout (unlimited processing time).
    |
    | Options:
    |
    |    The wkhtmltopdf command options. These are passed directly to wkhtmltopdf.
    |    See https://wkhtmltopdf.org/usage/wkhtmltopdf.txt for all options.
    |
    | Env:
    |
    |    The environment variables to set while running the wkhtmltopdf process.
    |
    */

    'pdf' => [
        'enabled' => true,
        'binary'  => env('WKHTML_PDF_BINARY', env('WKHTML_PDF_BINARY_CUSTOM', defined('PHP_WINDOWS_VERSION_BUILD') ? '"C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe"' : '/usr/local/bin/wkhtmltopdf')),
        'timeout' => 60, // 60 segundos de timeout
        'options' => [
            // Usar esquema file:/// y slashes Unix para wkhtmltopdf en Windows
            // 'user-style-sheet' => 'file:///'.str_replace('\\', '/', public_path('css/pdf-global-styles.css')),
            'enable-local-file-access' => true,
            'load-error-handling' => 'ignore',
        ],
        'env'     => [],
        'zoom'    => env('WKHTML_PDF_ZOOM', 1.3),
        'dpi'     => env('WKHTML_PDF_DPI', 300),
    ],

    'image' => [
        'enabled' => true,
        'binary'  => env('WKHTML_IMG_BINARY', '/usr/local/bin/wkhtmltoimage'),
        'timeout' => false,
        'options' => [],
        'env'     => [],
        'zoom'    => env('WKHTML_PDF_ZOOM', 1.3),
        'dpi'     => env('WKHTML_PDF_DPI', 300),
    ],

];
