<?php

return [
    'canonical_url' => env('FLOWERFLOW_CANONICAL_URL', 'https://app.flowerflow.com.mx'),
    'timezone' => env('FLOWERFLOW_TIMEZONE', 'America/Hermosillo'),
    'submissions_close_at' => env('FLOWERFLOW_SUBMISSIONS_CLOSE_AT', '2026-08-15 23:59:59'),
    'flags' => [
        'public' => env('FLOWERFLOW_PUBLIC_ENABLED', true),
        'registration' => env('FLOWERFLOW_REGISTRATION_ENABLED', false),
        'submissions' => env('FLOWERFLOW_SUBMISSIONS_ENABLED', false),
        'results' => env('FLOWERFLOW_RESULTS_ENABLED', false),
        'panel' => env('FLOWERFLOW_PANEL_ENABLED', true),
    ],
    'limits' => [
        'team_members' => (int) env('FLOWERFLOW_MAX_TEAM_MEMBERS', 5),
        'submissions_per_user' => (int) env('FLOWERFLOW_MAX_SUBMISSIONS_PER_USER', 3),
        'upload_kib' => (int) env('FLOWERFLOW_MAX_UPLOAD_KIB', 10240),
    ],
    'allowed_document_extensions' => [
        'pdf', 'doc', 'docx', 'odt', 'ppt', 'pptx', 'odp', 'xls', 'xlsx', 'ods',
    ],
    'allowed_editor_image_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    'external_links' => [
        'video_hosts' => ['youtube.com', 'www.youtube.com', 'youtu.be'],
        'folder_hosts' => ['drive.google.com', 'www.dropbox.com', 'dropbox.com', 'onedrive.live.com'],
    ],
    'mail' => [
        'from' => env('MAIL_FROM_ADDRESS', 'notificaciones@flowerflow.com.mx'),
        'reply_to' => env('MAIL_REPLY_TO_ADDRESS', 'convocatoria@flowerflow.com.mx'),
        'reply_to_name' => env('MAIL_REPLY_TO_NAME', 'FLOWER FLOW'),
        'privacy_reply_to' => env('PRIVACY_REPLY_TO_ADDRESS', 'privacidad@flowerflow.com.mx'),
        'queue_connection' => env('FLOWERFLOW_MAIL_QUEUE_CONNECTION', 'database'),
        'queue' => env('FLOWERFLOW_MAIL_QUEUE', 'default'),
        'tries' => (int) env('FLOWERFLOW_MAIL_TRIES', 4),
        'timeout' => (int) env('FLOWERFLOW_MAIL_JOB_TIMEOUT', 30),
        'backoff' => [60, 300, 900],
    ],
];
