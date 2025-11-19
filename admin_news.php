<?php
session_start();
require_once __DIR__ . "/conexion.php";


if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo "Sin sesión";
    exit;
}

$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body'] ?? '');

    if ($title && $body) {
        $stmt = $conn->prepare("INSERT INTO news (title, body) VALUES (?, ?)");
        $stmt->bind_param("ss", $title, $body);
        if ($stmt->execute()) {
            $mensaje = "Noticia publicada correctamente.";
        } else {
            $mensaje = "Error al publicar.";
        }
        $stmt->close();
    } else {
        $mensaje = "Título y cuerpo son obligatorios.";
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Publicar noticia</title>
<link rel="stylesheet" href="Estilos/styles.css">
</head>
<body>
<header class="headerLog">Panel de noticias (Admin)</header>



<form class="notis" method="post" id="formNoticias">
<?php if ($mensaje): ?>
<p class="avisoNotis"><?= htmlspecialchars($mensaje) ?></p>
<?php endif; ?>
  <label class="tituloNotis">Título:<br>
  <input type="text" name="title" required></label>
  <br>
  <br>
  <label class="cuerpoNotis">Cuerpo:<br>
  <textarea name="body" rows="6" cols="60" required></textarea></label>
  <br>
  <br>
  <button class="publicar" type="submit">Publicar</button>
</form>
</body>
</html>
