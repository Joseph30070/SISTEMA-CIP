<?php
require_once __DIR__ . '/../config/auth.php';
require __DIR__ . '/../config/db.php';

$title  = "Perfil del Usuario";
$active = "perfil";

// Obtener los datos del usuario
$stmt = $pdo->prepare("SELECT id, fullname, email, role, profile_image FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Avatar
$letraAvatar = strtoupper(substr($usuario['fullname'], 0, 1));
$rutaImagen  = !empty($usuario['profile_image']) ? $usuario['profile_image'] : null;

// Etiqueta de rol
$roleLabel = strtoupper($usuario['role'] ?? '');
$roleColor = 'bg-emerald-500';
if ($roleLabel === 'ASESOR')      $roleColor = 'bg-blue-500';
if ($roleLabel === 'ADMISION')    $roleColor = 'bg-amber-500';

ob_start();
?>

<div class="max-w-5xl mx-auto mt-10">
  <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">

    <!-- ENCABEZADO -->
    <div class="bg-gradient-to-r from-emerald-500 via-teal-500 to-green-400 px-10 py-8 text-white relative">
      <!-- Botón cerrar sesión (único) -->
      <div class="absolute top-4 right-4">
        <a href="logout.php"
           class="bg-white/20 hover:bg-white/30 text-white text-sm px-4 py-1.5 rounded-full transition">
          Cerrar Sesión
        </a>
      </div>

      <div class="flex items-center gap-6">
        <!-- Avatar -->
        <div class="w-24 h-24 rounded-full bg-white/10 flex items-center justify-center overflow-hidden shadow-lg border border-white/30">
          <?php if ($rutaImagen): ?>
            <img src="<?= htmlspecialchars($rutaImagen) ?>" alt="Foto de perfil"
                 class="w-full h-full object-cover">
          <?php else: ?>
            <span class="text-4xl font-bold">
              <?= $letraAvatar ?>
            </span>
          <?php endif; ?>
        </div>

        <div class="flex flex-col gap-2">
          <div class="text-xs tracking-[0.2em] uppercase text-white/70 font-semibold">
            Perfil de usuario
          </div>
          <h1 class="text-3xl font-bold leading-tight">
            <?= htmlspecialchars($usuario['fullname']) ?>
          </h1>
          <p class="text-sm text-emerald-50">
            <?= htmlspecialchars($usuario['email']) ?>
          </p>

          <div class="flex flex-wrap gap-3 mt-1">
            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full <?= $roleColor ?> text-white">
              <?= $roleLabel ?>
            </span>
            <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-white/15 text-white border border-white/30">
              ID usuario: <?= htmlspecialchars($usuario['id']) ?>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- CUERPO -->
    <div class="px-8 py-8 grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Columna izquierda: información personal -->
      <div class="md:col-span-2 bg-gray-50 rounded-xl border border-gray-200 p-6 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Información Personal</h2>
            <span class="text-xs text-gray-400">Datos básicos de tu cuenta</span>
          </div>

          <div class="space-y-4 text-sm">
            <div>
              <p class="text-gray-500 text-xs font-semibold uppercase tracking-wide">
                Nombre completo
              </p>
              <p class="text-gray-900 font-medium">
                <?= htmlspecialchars($usuario['fullname']) ?>
              </p>
            </div>

            <div>
              <p class="text-gray-500 text-xs font-semibold uppercase tracking-wide">
                Correo electrónico
              </p>
              <p class="text-gray-900 font-medium">
                <?= htmlspecialchars($usuario['email']) ?>
              </p>
            </div>
          </div>

          <!-- Texto informativo (reemplaza las pastillas anteriores) -->
          <p class="mt-6 text-xs text-gray-500 leading-relaxed">
            Estos datos se usan para iniciar sesión en el sistema. Si cambias tu correo desde
            <span class="font-semibold">Editar Perfil</span>, recuerda que será tu nuevo usuario de acceso.
          </p>
        </div>
      </div>

      <!-- Columna derecha: estado de la cuenta -->
      <div class="bg-white rounded-xl border border-gray-200 p-6 flex flex-col justify-between">
        <div class="space-y-4">
          <h2 class="text-lg font-semibold text-gray-800 mb-2">Estado de la cuenta</h2>

          <div>
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wide mb-1">
              Estado actual
            </p>
            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
              Activo
            </span>
          </div>

          <div>
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wide mb-1">
              Rol
            </p>
            <p class="text-gray-900 font-medium">
              <?= $roleLabel ?>
            </p>
          </div>

          <p class="text-xs text-gray-500 leading-relaxed">
            Cuida tu cuenta usando una contraseña segura 
          </p>
        </div>

        <!-- Solo botón Editar Perfil (ya no hay Cerrar Sesión aquí) -->
        <div class="mt-6 flex justify-end">
          <a href="editar_perfil.php"
             class="inline-flex items-center justify-center px-5 py-2.5 rounded-md bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium shadow-sm transition">
            Editar Perfil
          </a>
        </div>
      </div>
    </div>

  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
