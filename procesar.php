<?php
// Lee los datos del cuerpo JSON
$json = file_get_contents("php://input");
$datos = json_decode($json, true);

// Validación de datos básicos
if (
    isset($datos["nombre"]) && !empty($datos["nombre"]) &&
    isset($datos["mail"]) && !empty($datos["mail"]) &&
    isset($datos["tel"]) && is_numeric($datos["tel"]) &&
    isset($datos["CI"]) && is_numeric($datos["CI"]) &&
    isset($datos["dep"]) && !empty($datos["dep"]) &&
    isset($datos["cuota"])
) {
    // Reenvía los datos validados al modelo
    // Usamos cURL para enviar los datos a modelo.php como JSON
    $ch = curl_init("http://localhost/Prog1/modelo.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));

    $respuesta = curl_exec($ch);

if ($respuesta === false) {
    echo "Error cURL: " . curl_error($ch);
} else {
    echo $respuesta;
}
    
    curl_close($ch);

} else {
    echo "Datos inválidos.";
}
