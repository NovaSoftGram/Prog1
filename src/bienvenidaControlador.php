<?php
// Desactivar advertencias y limpiar buffer
ini_set('display_errors', 0);
error_reporting(0);
if (ob_get_level()) ob_end_clean();

header("Content-Type: application/json; charset=UTF-8");
session_start();
require "conexion.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Sin sesión"]);
    exit;
}

$userId = $_SESSION["user_id"];
$method = $_SERVER["REQUEST_METHOD"];

// POST: manejo de acciones
if ($method === "POST") {
    // 1) Solicitar acreditación de pago (multipart/form-data)
    if (isset($_POST["action"]) && $_POST["action"] === "solicitar_pago") {
        $monto = intval($_POST["monto"] ?? 0);
        if ($monto < 1) {
            echo json_encode(["success" => false, "error" => "Monto inválido"]);
            exit;
        }
        if (!isset($_FILES["recibo"]) || $_FILES["recibo"]["error"] !== UPLOAD_ERR_OK) {
            echo json_encode(["success" => false, "error" => "Error al subir imagen"]);
            exit;
        }
        $dir = __DIR__ . "/uploads/recibos/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $ext  = pathinfo($_FILES["recibo"]["name"], PATHINFO_EXTENSION);
        $name = uniqid("rbt_") . ".$ext";
        $path = "uploads/recibos/" . $name;
        if (!move_uploaded_file($_FILES["recibo"]["tmp_name"], __DIR__ . "/" . $path)) {
            echo json_encode(["success" => false, "error" => "No se guardó la imagen"]);
            exit;
        }
        $stmt = $conn->prepare(
            "INSERT INTO pagos (user_id, monto, recibo) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iis", $userId, $monto, $path);
        if (!$stmt->execute()) {
            echo json_encode(["success" => false, "error" => "Fallo al registrar pago"]);
            exit;
        }
        echo json_encode(["success" => true]);
        exit;
    }

    // 2) JSON: restar horas o intercambiar
    $json = json_decode(file_get_contents("php://input"), true) ?: [];
    $action = $json["action"] ?? "";

    if ($action === "restar_horas") {
        $hrs = max(0, intval($json["valor"] ?? 0));
        $stmt = $conn->prepare(
            "UPDATE personas SET horas = GREATEST(horas - ?, 0) WHERE id = ?"
        );
        $stmt->bind_param("ii", $hrs, $userId);
        $stmt->execute();
        $stmt->close();
    }

    if ($action === "intercambiar") {
        $monto = intval($json["valor"] ?? 0);
        if ($monto < 100 || $monto % 100 !== 0) {
            echo json_encode(["success" => false, "error" => "Monto debe ser múltiplo de 100"]);
            exit;
        }
        $hrs    = $monto / 100;
        $stmt   = $conn->prepare(
            "UPDATE personas
               SET horas = GREATEST(horas - ?, 0),
                   plata = plata + ?
             WHERE id = ?"
        );
        $stmt->bind_param("iii", $hrs, $monto, $userId);
        $stmt->execute();
        $stmt->close();
    }
}

// GET o post final: siempre devolver horas y plata actualizadas
$stmt = $conn->prepare("SELECT horas, plata FROM personas WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res  = $stmt->get_result();
$data = $res->fetch_assoc() ?: ["horas" => null, "plata" => null];
$stmt->close();
$conn->close();

echo json_encode(array_merge(["success" => true], $data));
