<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | DomPDF default configurations.
    |
    */

    'show_warnings' => false,

    'public_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    |
    | Dompdf options configuration.
    |
    */

    'options' => [
        /**
         * The location of the DOMPDF bootstrap file.
         */
        'defaul_paper_size' => 'a4',

        /**
         * Default font family.
         */
        'default_font' => 'sans-serif',

        /**
         * Image DPI setting.
         */
        'dpi' => 96,

        /**
         * Font height ratio.
         */
        'font_height_ratio' => 1.1,

        /**
         * Enable inline PHP execution.
         *
         * @var bool
         */
        'enable_php' => false,

        /**
         * Enable inline JavaScript.
         *
         * @var bool
         */
        'enable_javascript' => false,

        /**
         * Enable remote file access.
         *
         * ==== IMPORTANT ====
         * FIXED: Set to true to allow DomPDF to fetch the scannable clinical
         * validation QR code images dynamically from our generator servers.
         *
         * @var bool
         */
        'enable_remote' => true, // FIXED: Set to true for live QR rendering

        /**
         * For backward-compatibility with some DomPDF wrappers.
         */
        'isRemoteEnabled' => true, // FIXED: Set to true for live QR rendering

        /**
         * List of allowed remote hosts.
         * Each value of the array must be a valid hostname.
         *
         * @var array|null
         */
        'allowed_remote_hosts' => null,

        /**
         * Enable font subsetting.
         */
        'enable_font_subsetting' => false,

        /**
         * Help DomPDF parse HTML5 elements correctly.
         */
        'enable_html5_parser' => true,

        /**
         * Debug layouts.
         */
        'debug_layout' => false,
        'debug_layout_lines' => true,
        'debug_layout_blocks' => true,
        'debug_layout_inline' => true,
        'debug_layout_padding_box' => true,
    ],
];