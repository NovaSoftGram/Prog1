<?php
header("Content-Type: application/json");

$conexion = new mysqli("localhost", "root", "", "cooperativa");

if ($conexion->connect_error) {
    echo json_encode(["error" => "Error de conexión a la base de datos."]);
    exit;
}

$sql = "SELECT id, nombre, mail, tel, CI, dep, cuota, confirmacion FROM personas WHERE confirmacion = 0";
$resultado = $conexion->query($sql);

$personas = [];

if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $personas[] = $fila;
    }
}

echo json_encode($personas);
$conexion->close();
