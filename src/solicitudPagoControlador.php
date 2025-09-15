<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();
require "conexion.php";  // define $conn

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success"=>false, "error"=>"Sin sesión activa"]);
    exit;
}

// Validar monto
$monto = intval($_POST["monto"] ?? 0);
if ($monto <= 0) {
    echo json_encode(["success"=>false, "error"=>"Monto inválido"]);
    exit;
}

// Validar y mover archivo
if (!isset($_FILES["recibo"]) || $_FILES["recibo"]["error"] !== UPLOAD_ERR_OK) {
    echo json_encode(["success"=>false, "error"=>"Fallo al subir la imagen"]);
    exit;
}

$ext     = pathinfo($_FILES["recibo"]["name"], PATHINFO_EXTENSION);
$target  = "uploads/recibos/".uniqid("rbt_").".$ext";
if (!move_uploaded_file($_FILES["recibo"]["tmp_name"], $target)) {
    echo json_encode(["success"=>false, "error"=>"No se pudo guardar la imagen"]);
    exit;
}

// Insertar registro en pagos
$stmt = $conn->prepare(
  "INSERT INTO pagos (user_id, monto, recibo) 
   VALUES (?, ?, ?)"
);
$stmt->bind_param("iis", $_SESSION["user_id"], $monto, $target);
if (!$stmt->execute()) {
    echo json_encode(["success"=>false, "error"=>"Error al registrar solicitud"]);
    exit;
}

echo json_encode(["success"=>true]);
$stmt->close();
$conn->close();