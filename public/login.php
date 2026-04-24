<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login - Maestros CIIP</title>
  <link rel="stylesheet" href="style_general.css">

  <!-- TIPOGRAFÍA Y RESET -->
  <style>
    /* ================================
   RESET GENERAL
================================ */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Inter", "Segoe UI", sans-serif;
}

/* ================================
   LAYOUT PRINCIPAL
================================ */
body {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 1fr 1fr;
  background: #f5f5f5;
  overflow-x: hidden;
}

/* ================================
   PANEL IZQUIERDO (CARRUSEL)
================================ */
.left-panel {
  background: linear-gradient(135deg, #009D71 0%, #026842 100%);
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 60px 50px;
  text-align: center;
  position: relative;
  overflow: hidden;
  border-top-left-radius: 18px;
  border-bottom-left-radius: 18px;
}

/* Textura suave */
.left-panel::after {
  content: "";
  position: absolute;
  inset: 0;
  background: url('https://www.transparenttextures.com/patterns/cubes.png');
  opacity: 0.06;
  pointer-events: none;
}

/* Degradado de iluminación */
.left-panel::before {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 60%),
              radial-gradient(circle at bottom right, rgba(0,0,0,0.25), transparent 70%);
  pointer-events: none;
}


.carousel {
  width: 100%;
  max-width: 350px;
  position: relative;
  color: white;
  z-index: 2;
}

.carousel-item {
  opacity: 0;
  position: absolute;
  inset: 0;
  transition: opacity 0.8s ease;
}

.carousel-item.active {
  opacity: 1;
  position: relative;
}

.carousel-item img {
  width: 320px;
  margin-bottom: 15px;
  filter: drop-shadow(0 4px 10px rgba(0,0,0,0.25));
}

.carousel-item h1 {
  font-size: 34px;
  font-weight: 800;
  margin-bottom: 10px;
}

.carousel-item span {
  color: #C4D508;
}

.carousel-item p {
  color: #e6f5ec;
  font-size: 16px;
  max-width: 280px;
  margin: auto;
}

/* ================================
   PANEL DERECHO (LOGIN)
================================ */
.right-panel {
  background: #fff;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 50px 40px;
}

.login-box {
  width: 100%;
  max-width: 380px;
  animation: fadeUp .5s ease-out;
}

/* Animación de entrada */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Logo */
.logo {
  width: 100px;
  display: block;
  margin: 5px auto 15px auto;
}

.logo-container h2 {
  text-align: center;
  font-size: 26px;
  font-weight: 700;
  color: #0A5A32;
  margin-bottom: 25px;
}

/* Inputs */
.input-group {
  margin-bottom: 18px;
}

.input-group label {
  display: block;
  font-size: 14px;
  color: #1a1a1a;
  margin-bottom: 5px;
  font-weight: 600;
}

.input-group input {
  width: 100%;
  padding: 12px;
  border-radius: 7px;
  border: 1px solid #d2d8d3;
  background: #f9f9f9;
  transition: all 0.25s ease;
}

.input-group input:focus {
  border-color: #0C7A3C;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(12, 122, 60, 0.2);
}

/* Placeholder */
.input-group input::placeholder {
  color: #9b9b9b;
}

/* BOTÓN LOGIN */
.btn-login {
  width: 100%;
  padding: 13px;
  background: #0C7A3C;
  color: white;
  border: none;
  border-radius: 7px;
  font-size: 16px;
  cursor: pointer;
  font-weight: 600;
  transition: 0.25s ease;
}

.btn-login:hover {
  background: #0A5A32;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(12, 122, 60, 0.25);
}

/* ================================
   FOOTER MODERNO
================================ */
.footer-text {
  text-align: center;
  margin-top: 30px;
  font-size: 12px;
  color: #777;
  letter-spacing: 0.3px;
}

.footer-text span {
  font-weight: 600;
  color: #0A5A32;
}

/* ================================
   RESPONSIVE
================================ */
@media (max-width: 900px) {
  body {
    grid-template-columns: 1fr;
  }
  .left-panel {
    display: none;
  }
}

  </style>

  
</head>

<body>

  <!-- LADO IZQUIERDO -->
  <div class="left-panel">
    <div class="carousel">

  <div class="carousel-item active">
    <img src="/Sistema-Ciip/img/logociip.jfif" alt="Logo">
    <h1><span>CIIP</span> Maestros👋</h1>
    <p>Bienvenido al sistema de maestros. Organiza tu información y acelera tu trabajo.</p>
  </div>

  <div class="carousel-item">
    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png" alt="Docentes">
    <h1>Gestión <span>Fácil</span></h1>
    <p>Accede rápidamente al registro, pagos y documentos.</p>
  </div>

  <div class="carousel-item">
    <img src="https://cdn-icons-png.flaticon.com/512/1828/1828673.png" alt="Seguro">
    <h1><span>Seguro</span> y Rápido</h1>
    <p>Tu información siempre protegida y disponible.</p>
  </div>
  
  <div class="carousel-item">
    <img src="/Sistema-Ciip/img/hola.png" alt="Gráficas">
    <h1>Explora tus <span>Gráficos</span></h1>
    <p>Obtén reportes claros sobre pagos, asistencia y rendimiento.</p>
  </div>
</div>
  </div>

  <!-- LADO DERECHO -->
  <div class="right-panel">
    <div class="login-box">

      <div class="logo-container">
        <img src="https://yt3.googleusercontent.com/QdOu23hPslcyv7Z0GSnU7Ut1vHNiqeuwsyN8Y9Xly-xPmzMLyedTlBcvcaPYSykBI5zEc3QG=s900-c-k-c0x00ffffff-no-rj" alt="Logo CIIP" class="logo">
        <h2>Ciip Maestros</h2>
      </div>

      <!-- MENSAJE DE ERROR (SESSION) -->
      <?php if(isset($_SESSION['error'])): ?>
        <p class="error-msg"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
      <?php endif; ?>

      <!-- FORMULARIO LOGIN -->
      <form method="POST" action="../process/process.login.php">

        <?php if (isset($_GET['error'])): ?>
          <p class="error-msg"><?= htmlspecialchars($_GET['error']) ?></p>
        <?php endif; ?>

        <div class="input-group">
          <label for="correo">Correo electrónico</label>
          <input type="email" id="correo" name="correo" placeholder="Ingresa tu correo electrónico" required>
        </div>

        <div class="input-group">
          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
        </div>

        <button type="submit" class="btn-login">Iniciar sesión</button>
      </form>

      <p class="footer-text">
        © 2025 Maestros CIIP — Desarrollado por el Equipo de Desarrollo de Software.


      </p>

    </div>
  </div>

</body>
<script>
  let index = 0;
  const items = document.querySelectorAll('.carousel-item');

  function showNext() {
    items[index].classList.remove('active');
    index = (index + 1) % items.length;
    items[index].classList.add('active');
  }

  setInterval(showNext, 3500); // Cada 3.5s cambia
</script>

</html>
