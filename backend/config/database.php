<?php
$host = 'localhost';
$db = 'faultreporter';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// $host = 'sql18.cpt4.host-h.net';
// $db = 'pkouv_jobcard';
// $user = 'jgdwb_lvvli_jobcard';
// $pass = 'c8078j441JD52Y';
// $charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
