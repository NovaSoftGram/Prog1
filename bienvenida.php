<?php
// bienvenida.php
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
      <a href="noticias.php" class="OpHeader">Noticias</a>
      <a href="logout.php" class="OpHeader">Cerrar sesión</a>
    </nav>
  </header>

  <main>
    <h1 class="MensajeB">Bienvenido, <?= htmlspecialchars($_SESSION["user_name"]) ?></h1>
    <p class="tuidref">Tu ID es <?= (int)$_SESSION["user_id"] ?></p>

    <!-- Estado actual -->
    <div class="contenidoBienvenida">
      <section class="seccionHoras">
        <h2>Horas faltantes</h2>
        <p id="horas">Cargando…</p>
      </section>

      <section class="seccionPlata">
        <h2>Plata faltante</h2>
        <p id="plata">Cargando…</p>
      </section>

      <!-- Registrar horas trabajadas -->
      <img src="Imagenes/hammer.png" class="martillo" alt="Martillo">
      <section class="registrarHoras">
        <h3>Registrar horas trabajadas</h3>
        <input id="horasTrabajadas" type="number" min="1" placeholder="Horas" />
        <button id="btnHoras">Enviar horas</button>
        <p id="msgHoras" style="color:red"></p>
      </section>

      <!-- Solicitar acreditación de pago (comprobante) -->
      <img src="Imagenes/money.png" class="moneda" alt="Moneda">
      <section class="acreditarPago">
        <h3>Solicitar acreditación de pago</h3>
        <form id="pagoForm" enctype="multipart/form-data">
          <input type="hidden" name="action" value="solicitar_pago">
          <label>Monto pagado:
            <input name="monto" type="number" min="1" required>
          </label><br>
          <label>Comprobante
            <input id="Recibo" name="recibo" type="file" accept="image/*" required class="inputRecibo">
          </label><br>
          <button type="submit">Enviar comprobante</button>
        </form>
        <p id="msgPago" style="color:green"></p>
      </section>

      <!-- Sección que muestra siempre el último comprobante y su estado -->
      <section class="ultimoComprobante">
        <h3>Último comprobante enviado</h3>
        <p id="estadoComprobante">Cargando…</p>
        <div id="imagenComprobanteCont" style="margin-top:8px;">
          <a id="linkComprobante" href="#" target="_blank" style="display:none;">
            <img id="imagenComprobante" src="" alt="Comprobante" style="max-width:240px; display:block;">
          </a>
        </div>
      </section>

      <!-- Sección: seleccionar unidad habitacional -->
      <section class="solicitarUnidad">
        <h3>Solicitar unidad habitacional</h3>
        <p id="msgSolicitudUnidad" style="color:green"></p>
        <div id="unidadesCont">
          <p>Cargando unidades disponibles…</p>
        </div>
      </section>

      <!-- Intercambiar plata por horas -->
      <section class="seccionTrade">
        <h3>Intercambiar plata por horas</h3>
        <label for="plataIntercambiar">Monto a pagar</label>
        <input id="plataIntercambiar" type="number" min="100" step="100" />
        <button id="btnIntercambiar">Intercambiar</button>
        <p id="msgIntercambio" style="color:red"></p>
      </section>
    </div>
  </main>

  <script>
    const api = "bienvenidaControlador.php";

    // Render del último comprobante
    function renderUltimoComprobante(ultimo) {
      const estadoEl = document.getElementById("estadoComprobante");
      const imgEl = document.getElementById("imagenComprobante");
      const linkEl = document.getElementById("linkComprobante");

      if (!ultimo) {
        estadoEl.innerText = "No has enviado ningún comprobante.";
        imgEl.style.display = "none";
        linkEl.style.display = "none";
        imgEl.src = "";
        linkEl.href = "#";
        return;
      }

      const estadoMap = {
        pendiente: "En espera de aprobación",
        aprobado: "Aprobado",
        rechazado: "Rechazado"
      };
      estadoEl.innerText = estadoMap[ultimo.estado] || ultimo.estado;

      if (ultimo.recibo) {
        imgEl.src = ultimo.recibo;
        linkEl.href = ultimo.recibo;
        imgEl.style.display = "block";
        linkEl.style.display = "inline-block";
      } else {
        imgEl.style.display = "none";
        linkEl.style.display = "none";
        imgEl.src = "";
        linkEl.href = "#";
      }
    }

    // Carga inicial de datos (horas, plata, ultimo comprobante)
    function cargarDatos() {
      fetch(api, { credentials: "same-origin" })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            document.getElementById("horas").innerText = d.horas !== null ? d.horas : "—";
            document.getElementById("plata").innerText = d.plata !== null ? d.plata : "—";
            renderUltimoComprobante(d.ultimo_pago ?? null);
          } else {
            console.error("Error en respuesta:", d);
          }
        })
        .catch(e => console.error("Error GET:", e));
    }

    // Cargar unidades disponibles (GET action)
    function cargarUnidades() {
      const cont = document.getElementById("unidadesCont");
      cont.innerHTML = "Cargando unidades disponibles…";

      // Llamada a: bienvenidaControlador.php?action=list_unidades_disponibles
      fetch(api + "?action=list_unidades_disponibles", { credentials: "same-origin" })
        .then(r => r.json())
        .then(list => {
          if (!Array.isArray(list) || list.length === 0) {
            cont.innerHTML = "<p>No hay unidades libres en este momento.</p>";
            return;
          }

          const ul = document.createElement("ul");
          list.forEach(u => {
            const li = document.createElement("li");
            // mostrar dirección y botón que solicita unidad mediante fetch JSON
            li.innerHTML = `
              <strong>${escapeHtml(u.direccion || u.nombre || ("Unidad " + u.id))}</strong>
              <button data-id="${u.id}" class="btnSolicitar" style="margin-left:8px">Solicitar</button>
            `;
            ul.appendChild(li);
          });
          cont.innerHTML = "";
          cont.appendChild(ul);

          // Añadir listeners a los botones
          document.querySelectorAll(".btnSolicitar").forEach(btn => {
            btn.addEventListener("click", () => {
              const unidadId = parseInt(btn.getAttribute("data-id"), 10);
              solicitarUnidad(unidadId);
            });
          });
        })
        .catch(e => {
          cont.innerHTML = "<p>Error al cargar unidades.</p>";
          console.error("Error cargarUnidades:", e);
        });
    }

    // Solicitar una unidad (POST JSON)
    function solicitarUnidad(unidadId) {
      if (!confirm("¿Querés solicitar esta unidad? Esta solicitud quedará en estado pendiente para el administrador.")) return;
      const msgEl = document.getElementById("msgSolicitudUnidad");
      msgEl.innerText = "";

      fetch(api, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "solicitar_unidad", unidad_id: unidadId })
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          msgEl.style.color = "green";
          msgEl.innerText = "Solicitud enviada. Esperá la confirmación del administrador.";
          // refrescar lista de unidades para quitar la que quedó reservada / pendiente
          cargarUnidades();
          // refrescar otros datos por si hace falta
          cargarDatos();
        } else {
          msgEl.style.color = "red";
          msgEl.innerText = res.error || "No se pudo enviar la solicitud.";
          // refrescar listado por si el estado cambió
          cargarUnidades();
        }
      })
      .catch(e => {
        msgEl.style.color = "red";
        msgEl.innerText = "Error de comunicación.";
        console.error("Error solicitarUnidad:", e);
      });
    }

    // Utilidad: escapar HTML simple para seguridad en inyección en innerHTML
    function escapeHtml(s) {
      if (!s && s !== 0) return "";
      return String(s)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
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
          msg.style.color = "green";
          msg.innerText = "Horas registradas.";
        } else {
          msg.style.color = "red";
          msg.innerText = d.error || "Error al registrar horas.";
        }
      })
      .catch(() => msg.innerText = "Error de comunicación.");
    });

    // Enviar comprobante de pago (multipart/form-data)
    document.getElementById("pagoForm").addEventListener("submit", function(e) {
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
          // Refrescar datos para mostrar el nuevo comprobante y estado
          cargarDatos();
        } else {
          msg.style.color = "red";
          msg.innerText = d.error || "Error al enviar comprobante.";
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
          cargarDatos();
        } else {
          msg.style.color = "red";
          msg.innerText = d.error || "Error al intercambiar.";
        }
      })
      .catch(() => msg.innerText = "Error de comunicación.");
    });

    // Inicializa carga de datos y unidades
    cargarDatos();
    cargarUnidades();
  </script>
</body>
</html>
