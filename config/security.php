<?php

function isSecureConnection()
{
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if ($host === 'localhost' || $host === '127.0.0.1') {
        return true;
    }

    return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
}

function requireSecureConnection()
{
    if (!isSecureConnection()) {
        die('Beveiligde verbinding is verplicht.');
    }
}
?>