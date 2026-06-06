<?php

// Copy this file to config.php on the server and adjust values if needed.

return [
    'company_name' => 'Karadag Celik',
    'from_email' => 'info@karadagcelik.com',
    'from_name' => 'Karadag Celik',
    'reply_to' => 'info@karadagcelik.com',
    'notification_emails' => [
        'info@karadagcelik.com',
    ],
    // Store uploads outside public_html when possible.
    // If this path is not writable, api/common.php falls back to a local private_uploads folder.
    'upload_dir' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'karadag_private_uploads',
    'max_file_bytes' => 25 * 1024 * 1024,
    'allowed_extensions' => [
        'dxf', 'dwg', 'pdf', 'step', 'stp', 'iges', 'igs', 'zip', 'rar', 'jpg', 'jpeg', 'png',
    ],
];
