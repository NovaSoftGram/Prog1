<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="Estilos/stylesB.css">
  <title>Bienvenido</title>
</head>
<body class="bienvenidaBody">
  <header class="Hbienvenida">
    <h1 class="nombreCoop">CoviJoven</h1>
    <nav class="navBienvenida">
      <a href="nosotros.html" class="OpHeader">Nosotros</a>
      <a href="noticias.html" class="OpHeader">Noticias</a>
      <a href="logout.php" class="OpHeader">Cerrar sesión</a>
    </nav>
  </header>

  <main>
    <h1 class="MensajeB">Bienvenido, <?= htmlspecialchars($_SESSION["user_name"]) ?></h1>
    <p class="tuidref">Tu ID es <?= $_SESSION["user_id"] ?></p>

    <!-- Estado actual -->
    <div class=contenidoBienvenida>
    <section class="seccionHoras">
      <h2>Horas faltantes</h2>
      <p id="horas">Cargando…</p>
    </section>
    <section class="seccionPlata">
      <h2 class="seccionPlata">Plata faltante</h2>
      <p id="plata">Cargando…</p>
    </section>

    <!-- Registrar horas trabajadas -->
     <img src="Imagenes/hammer.png" class="martillo">
    <section class="registrarHoras">
      <h3>Registrar horas trabajadas</h3>
      <input id="horasTrabajadas" type="number" min="1" placeholder="Horas" />
      <button id="btnHoras">Enviar horas</button>
      <p id="msgHoras" style="color:red"></p>
    </section>

    <!-- Solicitar acreditación de pago (comprobante) -->
     <img src="Imagenes/money.png" class="moneda">
    <section class="acreditarPago">
      <h3>Solicitar acreditación de pago</h3>
      <form id="pagoForm" enctype="multipart/form-data">
        <input type="hidden" name="action" value="solicitar_pago">
        <label>Monto pagado:
          <input id="IntroducirPlata" name="monto" type="number" min="1" required>
        </label><br>
        <label>Comprobante
          <input name="recibo" type="file" accept="image/*" required class=inputRecibo>
        </label><br>
        <button type="submit">Enviar comprobante</button>
      </form>
      <p id="msgPago" style="color:green"></p>
    </section>

    <!-- Intercambiar plata por horas -->
    <section class="seccionTrade">
      <h3>Intercambiar plata por horas</h3>
      <label for="plataIntercambiar">Monto a pagar</label>
      <input id="plataIntercambiar" type="number" min="100" step="100" />
      <button id="btnIntercambiar">Intercambiar</button>
      <p id="msgIntercambio" style="color:red"></p>
    </section>
  </main>
</div>
  <script>
    const api = "bienvenidaControlador.php";

    // Carga inicial de horas y plata
    function cargarDatos() {
      fetch(api, { credentials: "same-origin" })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            document.getElementById("horas").innerText = d.horas;
            document.getElementById("plata").innerText = d.plata;
          }
        })
        .catch(e => console.error("Error GET:", e));
    }

    // Enviar horas trabajadas
    document.getElementById("btnHoras").addEventListener("click", () => {
      const hrs = parseInt(document.getElementById("horasTrabajadas").value, 10);
      const msg = document.getElementById("msgHoras");
      msg.innerText = "";
      if (isNaN(hrs) || hrs < 1) {
        msg.innerText = "Ingresa un número válido de horas.";
        return;
      }

      fetch(api, {
        method: "POST",
        credentials: "same-origin",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ action: "restar_horas", valor: hrs })
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          document.getElementById("horas").innerText = d.horas;
          document.getElementById("horasTrabajadas").value = "";
        } else {
          msg.innerText = d.error;
        }
      })
      .catch(() => msg.innerText = "Error de comunicación.");
    });

    // Enviar comprobante de pago
    document.getElementById("pagoForm").addEventListener("submit", e => {
      e.preventDefault();
      const msg = document.getElementById("msgPago");
      msg.innerText = "";
      const form = new FormData(e.target);

      fetch(api, {
        method: "POST",
        credentials: "same-origin",
        body: form
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          msg.style.color = "green";
          msg.innerText = "Comprobante enviado. Espera aprobación del admin.";
          e.target.reset();
        } else {
          msg.style.color = "red";
          msg.innerText = d.error;
        }
      })
      .catch(() => msg.innerText = "Error de comunicación.");
    });

    // Intercambiar plata por horas
    document.getElementById("btnIntercambiar").addEventListener("click", () => {
      const monto = parseInt(document.getElementById("plataIntercambiar").value, 10);
      const msg = document.getElementById("msgIntercambio");
      msg.innerText = "";
      if (isNaN(monto) || monto < 100 || monto % 100 !== 0) {
        msg.innerText = "Ingresa un monto válido (múltiplo de 100).";
        return;
      }

      fetch(api, {
        method: "POST",
        credentials: "same-origin",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({ action: "intercambiar", valor: monto })
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          const horasRestadas = monto / 100;
          document.getElementById("horas").innerText = d.horas;
          document.getElementById("plata").innerText = d.plata;
          document.getElementById("plataIntercambiar").value = "";
          msg.style.color = "green";
          msg.innerText = `Añadiste $${monto} a pagar, se restaron ${horasRestadas} horas de trabajo.`;
        } else {
          msg.style.color = "red";
          msg.innerText = d.error;
        }
      })
      .catch(() => msg.innerText = "Error de comunicación.");
    });

    cargarDatos();
  </script>
</body>
</html>
