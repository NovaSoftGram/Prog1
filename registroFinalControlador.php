<?php
require "conexion.php";

$json = file_get_contents("php://input");
$datos = json_decode($json, true);

$ci = $datos["ci"] ?? null;
$password = $datos["password"] ?? "";

if (!isset($ci) || !is_numeric($ci) || empty($password)) {
    echo "Datos inválidos.";
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("SELECT id FROM personas WHERE CI = ? AND confirmacion = 1");
$stmt->bind_param("i", $ci);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    echo "CI no confirmado o no existe.";
    exit;
}
$stmt->close();

$stmt = $conn->prepare("UPDATE personas SET password = ? WHERE CI = ?");
$stmt->bind_param("si", $hash, $ci);

if ($stmt->execute()) {
    echo "Registro finalizado correctamente.";
} else {
    echo "Error al actualizar.";
}

$stmt->close();
$conn->close();
?>