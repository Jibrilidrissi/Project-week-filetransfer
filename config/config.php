<?php

// Upload instellingen
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB

// Toegestane bestandstypes
$allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg', 'zip', 'txt','webp','avif'];