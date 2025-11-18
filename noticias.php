<?php
session_start();
require_once __DIR__ . "/conexion.php";


$res = $conn->query("SELECT id, title, body, created_at FROM news ORDER BY created_at DESC");
$news = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Noticias</title>
<link rel="stylesheet" href="Estilos/styles.css">
</head>
<body>
<header class="headerLog">Noticias</header>

<?php if (empty($news)): ?>
  <p>No hay noticias publicadas.</p>
<?php else: ?>
  <?php foreach ($news as $n): ?>
    <article>
      <h2><?= htmlspecialchars($n['title']) ?></h2>
      <p><em>Publicado el <?= htmlspecialchars($n['created_at']) ?></em></p>
      <p><?= nl2br(htmlspecialchars($n['body'])) ?></p>
    </article>
    <hr>
  <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
