<?php
// public/layout.php

if (!isset($title))  $title  = 'CiipGestión';
if (!isset($active)) $active = 'home';

$config = require __DIR__ . '/../config/config.php';
$base   = rtrim($config['base_url'], '/'); // ej: http://localhost/Sistema-Ciip/public
// NORMALIZA ROL (clave: 'role', no 'rol')
$ROLE = strtoupper($_SESSION['role'] ?? '');

// EJECUTAR LIMPIEZA AUTOMÁTICA (FAKE CRON)
require_once __DIR__ . '/../cron_fake.php';   // ← AGREGAR ESTO

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($title) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>

    .sidebar{width:240px}
    @media (max-width:768px){
      .sidebar{position:fixed;z-index:50;transform:translateX(-100%)}
      .sidebar.active{transform:translateX(0)}
    }

    /* ===== Escritorio: sidebar fijo que NO se mueve ===== */
    @media (min-width:768px){
      .sidebar{
        position: fixed;      /* fijo */
        top: 0;
        left: 0;
        height: 100vh;        /* ocupa toda la altura */
        transform: none !important; /* evita cualquier “salto” del modo móvil */
        background: #ffffff;  /* asegura fondo */
      }
      .main-shift{
        margin-left: 240px;   /* mismo ancho del sidebar */
      }
    }
    .payment-status{position:relative;padding-left:16px}
    .payment-status:before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:10px;height:10px;border-radius:50%}
    .payment-status.paid:before{background:#10B981}
    .payment-status.pending:before{background:#F59E0B}
    .payment-status.overdue:before{background:#EF4444}
  </style>
</head>
<body class="bg-gray-50">
  <button class="md:hidden fixed top-4 left-4 z-50 bg-white p-2 rounded shadow sidebar-toggle">
    <i class="fas fa-bars"></i>
  </button>

  <!-- Contenedor general en modo flex -->
  <div class="flex min-h-screen">

    <!-- Menú lateral -->
    <aside class="sidebar bg-white border-r shadow-md flex flex-col">
      <div class="p-4 border-b flex items-center space-x-3">
  <!-- Logo -->
<img src="../img/isotipo1.png" class="w-8 h-8 object-contain" alt="Logo">

  <!-- Texto -->
  <div>
    <h1 class="text-xl font-bold text-gray-800">
      Ciip<span class="text-teal-500">Gestión</span>
    </h1>
    <p class="text-xs text-gray-500">Cuotas y Pagos</p>
  </div>
</div>

      <!-- El nav ocupa el alto y hace scroll interno si hace falta -->
      <nav class="p-3 space-y-1 flex-1 overflow-y-auto">

        <!-- Inicio: ADMISION o ADMINISTRADOR -->
        <?php if (in_array($ROLE, ['ADMISION','ADMINISTRADOR'], true)): ?>
        <a href="<?= $base ?>/home.php"
           class="flex items-center gap-3 px-3 py-2 rounded <?= $active==='home'?'bg-gray-200 text-gray-900':'text-gray-600 hover:bg-gray-100' ?>">
          <i class="fas fa-gauge"></i> <span>Panel Estadístico</span>
        </a>
        <?php endif; ?>
        
        <!-- Ventana (todos) -->
        <a href="<?= $base ?>/ventana.php"
           class="flex items-center gap-3 px-3 py-2 rounded <?= $active==='ventana'?'bg-gray-200 text-gray-900':'text-gray-600 hover:bg-gray-100' ?>">
          <i class="fas fa-window-restore"></i> <span>Centro de Control</span>
        </a>

        <!-- Registro Docente/Pagos: ASESOR o ADMINISTRADOR -->
        <?php if (in_array($ROLE, ['ASESOR','ADMINISTRADOR'], true)): ?>
          <a href="<?= $base ?>/registro.php"
            class="flex items-center gap-3 px-3 py-2 rounded <?= $active==='registro'?'bg-gray-200 text-gray-900':'text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-user-plus"></i> <span>Registro Docente</span>
          </a>
        <?php endif; ?>

        <!-- Registro Usuarios: solo ADMINISTRADOR -->
        <?php if ($ROLE === 'ADMINISTRADOR'): ?>
          <a href="<?= $base ?>/registrar_usuario.php"
            class="flex items-center gap-3 px-3 py-2 rounded <?= $active==='registrar_usuario'?'bg-gray-200 text-gray-900':'text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-user-cog"></i><span>Agregar nuevo trabajador</span>
          </a>
        <?php endif; ?>

        <!-- Consulta: ADMISION o ADMINISTRADOR -->
        <?php if (in_array($ROLE, ['ADMISION','ADMINISTRADOR'], true)): ?>
          <a href="<?= $base ?>/consulta.php"
            class="flex items-center gap-3 px-3 py-2 rounded <?= $active==='consulta'?'bg-gray-200 text-gray-900':'text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-search"></i> <span>Consulta</span>
          </a>
        <?php endif; ?>

        <!-- Usuario (todos) -->
        <?php if ($ROLE === 'ASESOR'): ?>
          <a href="<?= $base ?>/usuario.php"
            class="flex items-center gap-3 px-3 py-2 rounded <?= $active==='usuario'?'bg-gray-200 text-gray-900':'text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-user"></i> <span>Ver registros</span>
          </a>
        <?php endif; ?>
        <!-- Cursos: solo ADMINISTRADOR -->
        <?php if ($ROLE === 'ADMINISTRADOR'): ?>
          <a href="<?= $base ?>/tipo_certificacion_programas.php"
            class="flex items-center gap-3 px-3 py-2 rounded <?= $active==='cursos'?'bg-gray-200 text-gray-900':'text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-book"></i> <span>Tipos de Certificacion</span>
          </a>
        <?php endif; ?>
        
        <!-- Editar registros: solo ADMINISTRADOR -->
        <?php if ($ROLE === 'ADMINISTRADOR'): ?>
          <a href="<?= $base ?>/editar_registros.php"
            class="flex items-center gap-3 px-3 py-2 rounded <?= $active==='editar_registros'?'bg-gray-200 text-gray-900':'text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-pen-to-square"></i> <span>Editar registros</span>
          </a>
        <?php endif; ?>

        <!-- Perfil (todos) -->
        <a href="<?= $base ?>/perfil.php"
           class="flex items-center gap-3 px-3 py-2 rounded <?= $active==='perfil'?'bg-gray-200 text-gray-900':'text-gray-600 hover:bg-gray-100' ?>">
          <i class="fas fa-user-circle"></i> <span>Perfil</span>
        </a>
      </nav>
      
        <!-- Cerrar sesión -->
      <div class="p-3 mt-auto">
        <a href="<?= $base ?>/logout.php"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-red-600 hover:bg-red-100 transition-all cursor-pointer shadow-sm w-full justify-start">
          <i class="fas fa-right-from-bracket"></i>
          <span>Cerrar sesión</span>
        </a>
      </div>
    </aside>

    <!-- Contenido principal -->
    <main class="main-shift flex-1 bg-gray-50 min-h-screen overflow-x-hidden overflow-y-auto p-6">
      <div class="w-[95%] max-w-[1600px] mx-auto">
        <?= $content ?? '' ?>
      </div>
    </main> 
  </div>

  <script>
    document.querySelector('.sidebar-toggle')?.addEventListener('click', () => {
      document.querySelector('.sidebar')?.classList.toggle('active');
    });
  </script>
</body>
</html>
