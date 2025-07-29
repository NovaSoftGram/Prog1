<?php
require "conexion.php";

$json = file_get_contents("php://input");
$datos = json_decode($json, true);

$ci = $datos["ci"] ?? null;
$clave = $datos["clave"] ?? null;

$respuesta = ["success" => false, "mensaje" => "CI o contraseña incorrectos."];

if ($ci && $clave) {
    $stmt = $conn->prepare("SELECT password FROM personas WHERE CI = ?");
    $stmt->bind_param("i", $ci);
    $stmt->execute();
    $stmt->bind_result($hash);

    if ($stmt->fetch()) {
        if (password_verify($clave, $hash)) {
            $respuesta = ["success" => true];
        }
    }

    $stmt->close();
}

echo json_encode($respuesta);
$conn->close();
?>