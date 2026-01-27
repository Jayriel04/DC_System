<?php
// Database configuration
define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','root');
define('DB_NAME','dbclinic');
define('DB_CHARSET', 'utf8mb4');

// DSN (Data Source Name)
$dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Create a new PDO instance
    $dbh = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // In a real application, you would log this error and show a generic message
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
