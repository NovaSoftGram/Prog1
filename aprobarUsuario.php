<?php
header("Content-Type: text/plain");

$datos = json_decode(file_get_contents("php://input"), true);
$ci = $datos["CI"] ?? null;

if (!$ci || !is_numeric($ci)) {
    echo "CI inválido.";
    exit;
}

$conexion = new mysqli("localhost", "root", "", "cooperativa");

if ($conexion->connect_error) {
    echo "Error de conexión a la base de datos.";
    exit;
}

$stmt = $conexion->prepare("UPDATE personas SET confirmacion = 1 WHERE CI = ?");
$stmt->bind_param("i", $ci);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo "Usuario aprobado correctamente.";
} else {
    echo "Error: No se encontró el usuario o ya estaba aprobado.";
}

$stmt->close();
$conexion->close();
