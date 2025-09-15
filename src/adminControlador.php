<?php
header("Content-Type: application/json; charset=UTF-8");
require "conexion.php";
session_start();

$method = $_SERVER["REQUEST_METHOD"];
$action = $_GET["action"] 
         ?? json_decode(file_get_contents("php://input"), true)["action"] 
         ?? "";

//  ─── LISTADOS (GET) ───
if ($method === "GET") {
    switch ($action) {
        // 1) Pendientes de registro
        case "list_registros":
            $sql = "SELECT id, nombre, CI, cuota 
                    FROM personas 
                    WHERE confirmacion = 0";
            $res = $conn->query($sql);
            $out = $res->fetch_all(MYSQLI_ASSOC);
            echo json_encode($out);
            exit;

        // 2) Pendientes de pago
        case "list_pagos":
            $sql = "SELECT p.id AS pago_id, u.nombre, p.monto, p.recibo 
                    FROM pagos p
                    JOIN personas u ON u.id = p.user_id
                    WHERE p.estado = 'pendiente'";
            $res = $conn->query($sql);
            $out = $res->fetch_all(MYSQLI_ASSOC);
            echo json_encode($out);
            exit;

        default:
            echo json_encode(["error" => "Acción GET no válida"]);
            exit;
    }
}

//  ─── ACCIONES (POST) ───
if ($method === "POST") {
    $in = json_decode(file_get_contents("php://input"), true);

    switch ($in["action"] ?? "") {
        case "aprobar_usuario":
            $id = intval($in["id"]);
            $stmt = $conn->prepare("UPDATE personas SET confirmacion = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(["success" => true]);
            exit;

        case "aprobar_pago":
        $pagoId = intval($in["pago_id"]);

        // 1) Marco el pago como aprobado
        $stmt = $conn->prepare(
          "UPDATE pagos 
             SET estado = 'aprobado' 
          WHERE id = ?"
         );
        $stmt->bind_param("i", $pagoId);
        $stmt->execute();
        $stmt->close();

        // 2) Descubro el user_id y el monto de ese pago
        $stmt = $conn->prepare(
        "SELECT user_id, monto 
            FROM pagos 
            WHERE id = ?"
        );
        $stmt->bind_param("i", $pagoId);
        $stmt->execute();
        $stmt->bind_result($userIdPago, $montoPago);
        $stmt->fetch();
        $stmt->close();

        // 3) Le resto esa plata a la persona (sin que baje de cero)
        $upd = $conn->prepare(
          "UPDATE personas p
            JOIN pagos g ON g.user_id = p.id 
           SET p.plata = GREATEST(p.plata - g.monto, 0)
         WHERE g.id = ?"
        );
        $upd->bind_param("i", $pagoId);
        $upd->execute();
        $upd->close();

        echo json_encode(["success" => true]);
        exit;

        case "rechazar_pago":
            // define nuevo estado
            $nuevo = $in["action"] === "aprobar_pago" 
                     ? "aprobado" 
                     : "rechazado";
            $pagoId = intval($in["pago_id"]);
            $stmt = $conn->prepare(
              "UPDATE pagos 
               SET estado = ? 
               WHERE id = ?"
            );
            $stmt->bind_param("si", $nuevo, $pagoId);
            $stmt->execute();
            echo json_encode(["success" => true]);
            exit;

            case "reset_all":
            // Reinicia horas y plata de TODOS los usuarios (o quienes confirmen)
            $sql = "
            UPDATE personas
             SET horas = 40,
             plata = 5000
            ";
            $conn->query($sql);
            echo json_encode(["success" => true]);
            exit;


        default:
            echo json_encode(["error" => "Acción POST no válida"]);
            exit;
    }
}
