<?php
/**
 * config/database.php
 * AdHub – Database connection via PDO
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'adhub');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO instance.
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production replace with a generic error page
            die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;">
                    <strong>Database connection failed.</strong><br>
                    Please check your XAMPP MySQL service and database credentials.
                 </div>');
        }
    }
    return $pdo;
}