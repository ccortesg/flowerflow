<?php

return [
    'show_warnings' => false,
    'public_path' => null,
    'convert_entities' => true,
    'options' => [
        'font_dir' => storage_path('framework/cache/dompdf'),
        'font_cache' => storage_path('framework/cache/dompdf'),
        'temp_dir' => storage_path('framework/cache'),
        'chroot' => realpath(public_path('assets/flowerflow')),
        'allowed_protocols' => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []],
            'https://' => ['rules' => []],
        ],
        'artifactPathValidation' => null,
        'log_output_file' => null,
        'enable_font_subsetting' => true,
        'pdf_backend' => 'CPDF',
        'default_media_type' => 'print',
        'default_paper_size' => 'a4',
        'default_paper_orientation' => 'portrait',
        'default_font' => 'DejaVu Sans',
        'dpi' => 96,
        'enable_php' => false,
        'enable_javascript' => false,
        'enable_remote' => false,
        'allowed_remote_hosts' => [],
        'font_height_ratio' => 1.1,
        'enable_html5_parser' => true,
    ],
];
