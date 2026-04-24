<?php
$config = require __DIR__ . '/config.php';

$host = $config['db']['host'] ?? '127.0.0.1';
$dbname = $config['db']['name'] ?? '2_colegio_db';
$user = $config['db']['user'] ?? 'root';
$pass = $config['db']['pass'] ?? '';
$charset = $config['db']['charset'] ?? 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

return $pdo;   // <-- IMPORTANTÍSIMO
