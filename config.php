<?php
// config.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* LOAD COMPOSER AUTOLOADER */
require_once __DIR__ . '/vendor/autoload.php';

/* LOAD credentials.env */
$envPath = __DIR__ . '/credentials.env';

if (!file_exists($envPath)) {
    die('credentials.env file not found');
}

foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#') continue;
    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value);
}

/* GOOGLE OAUTH */
$client = new Google\Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope('email');
$client->addScope('profile');
$client->setPrompt('select_account');

/* =========================
   DATABASE CONNECTION
   ========================= */
$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "QuizLance"
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
