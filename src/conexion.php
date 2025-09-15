<?php
$host     = getenv('DB_HOST')     ?: 'db';
$dbname   = getenv('MYSQL_DATABASE');
$user     = getenv('MYSQL_USER');
$password = getenv('MYSQL_PASSWORD');

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>