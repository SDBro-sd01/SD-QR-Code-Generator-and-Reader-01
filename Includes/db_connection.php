<?php
declare(strict_types=1);

/* =====================================================================
 |  QR Studio — Database Connection
 |  Loads credentials from `.env` via vlucas/phpdotenv and returns a
 |  configured PDO instance.
 * ===================================================================== */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;

/* ---------------------------------------------------------------------
 |  1. Load the .env file (one directory up from /includes)
 * ------------------------------------------------------------------- */
try {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
} catch (InvalidPathException $e) {
    http_response_code(500);
    die('Configuration error: .env file not found. Copy .env.example to .env and fill in your credentials.');
}

/* ---------------------------------------------------------------------
 |  2. Validate required environment variables
 * ------------------------------------------------------------------- */
try {
    $dotenv->required([
        'DB_HOST',
        'DB_PORT',
        'DB_NAME',
        'DB_USER',
        'DB_PASS',
        'DB_CHARSET',
        'APP_TIMEZONE',
    ])->notEmpty();
} catch (\Dotenv\Exception\ValidationException $e) {
    http_response_code(500);
    die('Configuration error: missing required environment variables. Check your .env file.');
}

/* ---------------------------------------------------------------------
 |  3. Read values
 * ------------------------------------------------------------------- */
$dbHost   = (string) $_ENV['DB_HOST'];
$dbPort   = (string) $_ENV['DB_PORT'];
$dbName   = (string) $_ENV['DB_NAME'];
$dbUser   = (string) $_ENV['DB_USER'];
$dbPass   = (string) $_ENV['DB_PASS'];
$dbCharset= (string) $_ENV['DB_CHARSET'];
$appTz    = (string) $_ENV['APP_TIMEZONE'];

/* ---------------------------------------------------------------------
 |  4. Apply application timezone (used by {YYYY}, {MM}, {DD} tokens)
 * ------------------------------------------------------------------- */
date_default_timezone_set($appTz);

/* ---------------------------------------------------------------------
 |  5. Connect via PDO
 * ------------------------------------------------------------------- */
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $dbHost,
    $dbPort,
    $dbName,
    $dbCharset
);

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
        PDO::ATTR_PERSISTENT         => false,
    ]);

    /* Sync MySQL session timezone with PHP so NOW() matches */
    $offset = (new DateTime('now', new DateTimeZone($appTz)))->format('P');
    $pdo->exec("SET time_zone = '{$offset}'");
    $pdo->exec("SET NAMES {$dbCharset} COLLATE {$dbCharset}_unicode_ci");

} catch (PDOException $e) {
    /* Log the real error for the developer, show a generic one to users */
    error_log('[QR Studio] DB connection failed: ' . $e->getMessage());

    http_response_code(500);
    die('Database connection failed. Please try again later.');
}