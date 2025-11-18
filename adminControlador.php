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

        // 3) Pendientes de solicitudes de unidades
        case "list_solicitudes_unidades":
            $sql = "SELECT s.id AS solicitud_id, s.unidad_id, u.direccion, s.user_id, p.nombre
                    FROM solicitudes_unidades s
                    JOIN unidades u ON u.id = s.unidad_id
                    JOIN personas p ON p.id = s.user_id
                    WHERE s.estado = 'pendiente'";
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
            $pagoId = intval($in["pago_id"]);
            $stmt = $conn->prepare(
              "UPDATE pagos 
               SET estado = 'rechazado' 
               WHERE id = ?"
            );
            $stmt->bind_param("i", $pagoId);
            $stmt->execute();
            echo json_encode(["success" => true]);
            exit;

        case "aprobar_solicitud_unidad":
    $solId = intval($in["solicitud_id"]);

    // obtener unidad_id y user_id de la solicitud pendiente
    $stmt = $conn->prepare("SELECT unidad_id, user_id FROM solicitudes_unidades WHERE id = ? AND estado = 'pendiente'");
    $stmt->bind_param("i", $solId);
    $stmt->execute();
    $stmt->bind_result($unidadId, $userIdAsign);
    if (!$stmt->fetch()) {
        $stmt->close();
        echo json_encode(["success"=>false, "error"=>"Solicitud no encontrada o ya procesada"]);
        exit;
    }
    $stmt->close();

    // iniciar transacción para evitar race conditions
    $conn->begin_transaction();

    // verificar que la unidad solicitada sigue disponible (bloqueo FOR UPDATE)
    $stmt = $conn->prepare("SELECT disponible FROM unidades WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $unidadId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if (!$row || !$row["disponible"]) {
        $conn->rollback();
        echo json_encode(["success"=>false, "error"=>"Unidad ya no está disponible"]);
        exit;
    }

    // buscar si el usuario ya tiene asignada otra unidad (también bloqueamos esa fila si existe)
    $stmt = $conn->prepare("SELECT id FROM unidades WHERE usuario_asignado = ? FOR UPDATE");
    $stmt->bind_param("i", $userIdAsign);
    $stmt->execute();
    $res2 = $stmt->get_result();
    $prev = $res2->fetch_assoc();
    $stmt->close();

    // marcar solicitud aprobada
    $stmt = $conn->prepare("UPDATE solicitudes_unidades SET estado = 'aprobada' WHERE id = ?");
    $stmt->bind_param("i", $solId);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->rollback();
        echo json_encode(["success"=>false, "error"=>"No se pudo actualizar la solicitud"]);
        exit;
    }
    $stmt->close();

    // si tenía una unidad previa, liberarla (hacerla disponible y quitar usuario_asignado)
    if ($prev && isset($prev["id"])) {
        $prevId = intval($prev["id"]);
        $stmt = $conn->prepare("UPDATE unidades SET disponible = 1, usuario_asignado = NULL WHERE id = ?");
        $stmt->bind_param("i", $prevId);
        if (!$stmt->execute()) {
            $stmt->close();
            $conn->rollback();
            echo json_encode(["success"=>false, "error"=>"No se pudo liberar la unidad previa"]);
            exit;
        }
        $stmt->close();
    }

    // asignar unidad solicitada al usuario y marcar no disponible
    $stmt = $conn->prepare("UPDATE unidades SET disponible = 0, usuario_asignado = ? WHERE id = ?");
    $stmt->bind_param("ii", $userIdAsign, $unidadId);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->rollback();
        echo json_encode(["success"=>false, "error"=>"No se pudo asignar la unidad"]);
        exit;
    }
    $stmt->close();

    $conn->commit();
    echo json_encode(["success"=>true]);
    exit;


        case "rechazar_solicitud_unidad":
            $solId = intval($in["solicitud_id"]);
            $stmt = $conn->prepare("UPDATE solicitudes_unidades SET estado = 'rechazada' WHERE id = ?");
            $stmt->bind_param("i", $solId);
            $stmt->execute();
            $stmt->close();
            echo json_encode(["success"=>true]);
            exit;

        case "reset_all":
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
