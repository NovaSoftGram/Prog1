<?php
session_start();         
require "conexion.php";

$json      = file_get_contents("php://input");
$datos     = json_decode($json, true);
$ci        = $datos["ci"]    ?? null;
$clave     = $datos["clave"] ?? null;
$respuesta = ["success" => false, "mensaje" => "CI o contraseña incorrectos."];

if ($ci && $clave) {
    $stmt = $conn->prepare(
      "SELECT id, nombre, password
       FROM personas
       WHERE CI = ?"
    );
    $stmt->bind_param("i", $ci);
    $stmt->execute();
    $stmt->bind_result($id, $nombre, $hash);

    if ($stmt->fetch() && password_verify($clave, $hash)) {
        $_SESSION["user_id"]   = $id;
        $_SESSION["user_name"] = $nombre;
        $respuesta["success"]  = true;
    }

    $stmt->close();
}

echo json_encode($respuesta);
$conn->close();
