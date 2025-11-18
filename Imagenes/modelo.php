<?php
require "conexion.php";

$json = file_get_contents("php://input");
$datos = json_decode($json, true);

$nombre = $datos["nombre"];
$mail = $datos["mail"];
$tel = $datos["tel"];
$ci = $datos["CI"];
$dep = $datos["dep"];
$cuota = $datos["cuota"];

$stmt = $conn->prepare("SELECT id FROM personas WHERE CI = ?");
$stmt->bind_param("i", $ci);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "Ya existe una persona con esa CI.";
    exit;
}
$stmt->close();

$stmt = $conn->prepare("INSERT INTO personas (nombre, mail, tel, CI, dep, cuota) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssiiss", $nombre, $mail, $tel, $ci, $dep, $cuota);

if ($stmt->execute()) {
    echo "Persona guardada correctamente en MySQL.";
} else {
    echo "Error al guardar: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>