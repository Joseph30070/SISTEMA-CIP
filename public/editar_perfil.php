<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$title  = "Editar Perfil";
$active = "perfil";

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT id, fullname, email, password, profile_image FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Mensajes del process (NO escaparlos)
$mensaje_perfil    = $_GET['perfil_msg'] ?? '';
$mensaje_password  = $_GET['password_msg'] ?? '';

// Mostrar foto actual (sí escapa porque es ruta)
$avatarUrl = !empty($usuario['profile_image']) ? htmlspecialchars($usuario['profile_image']) : null;

ob_start();
?>

<div class="max-w-3xl mx-auto mt-10">
  <div class="bg-white shadow-xl rounded-xl p-8 relative">

    <!-- Botón de volver -->
    <a href="perfil.php"
       class="absolute -top-4 -left-4 bg-teal-600 hover:bg-teal-700 text-white rounded-full p-3 shadow-md transition transform hover:-translate-y-0.5">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
      </svg>
    </a>

    <h2 class="text-2xl font-semibold text-gray-800 mb-6 text-center">Editar Perfil</h2>

    <!-- Mensaje del perfil -->
    <?php if ($mensaje_perfil): ?>
      <div class="mb-4"><?= $mensaje_perfil ?></div>
    <?php endif; ?>

    <!-- FORMULARIO: EDITAR PERFIL -->
    <form method="POST" enctype="multipart/form-data"
          action="../process/process_update_profile.php"
          class="space-y-6 border-b border-gray-200 pb-8 mb-8">

      <!-- Avatar -->
      <div class="flex flex-col items-center gap-4 mb-4">
        <?php if ($avatarUrl): ?>
          <img src="<?= $avatarUrl ?>" class="w-24 h-24 rounded-full object-cover border-4 border-teal-500 shadow-lg bg-white">
        <?php else: ?>
          <div class="w-24 h-24 bg-teal-100 text-teal-700 rounded-full flex items-center justify-center text-4xl font-bold shadow-lg">
            <?= strtoupper(substr($usuario['fullname'], 0, 1)) ?>
          </div>
        <?php endif; ?>

        <div class="text-center">
          <label class="block text-gray-600 font-medium mb-1">Cambiar foto de perfil</label>
          <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp"
            class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4
                   file:rounded-full file:border-0 file:text-sm file:font-semibold
                   file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
          <p class="text-xs text-gray-400 mt-1">Opcional. Formatos: JPG, PNG, WEBP.</p>
        </div>
      </div>

      <!-- Nombre -->
      <div>
        <label class="block text-gray-600 font-medium mb-1">Nombre completo</label>
        <input type="text" name="fullname" value="<?= htmlspecialchars($usuario['fullname']) ?>"
          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500" required>
      </div>

      <!-- Email -->
      <div>
        <label class="block text-gray-600 font-medium mb-1">Correo electrónico</label>
        <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>"
          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500" required>
      </div>

      <div class="flex justify-end space-x-4 pt-4">
        <a href="perfil.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">Cancelar</a>
        <button type="submit" name="update_profile"
                class="bg-teal-600 text-white px-5 py-2 rounded-md hover:bg-teal-700">Guardar Cambios</button>
      </div>
    </form>

    <!-- Mensaje exclusivo de contraseña -->
    <?php if ($mensaje_password): ?>
      <div class="mb-4"><?= $mensaje_password ?></div>
    <?php endif; ?>

    <!-- FORMULARIO: CAMBIO DE CONTRASEÑA -->
    <h3 class="text-xl font-semibold text-gray-800 mb-4">Cambiar Contraseña</h3>

    <form method="POST" action="../process/process_update_profile.php" class="space-y-6">

      <?php
      $campos = [
        ['id' => 'password_actual', 'label' => 'Contraseña actual'],
        ['id' => 'password_nueva', 'label' => 'Nueva contraseña'],
        ['id' => 'password_confirmar', 'label' => 'Confirmar nueva contraseña'],
      ];

      foreach ($campos as $c): ?>
        <div class="relative">
          <label class="block text-gray-600 font-medium mb-1"><?= $c['label'] ?></label>
          <input type="password" id="<?= $c['id'] ?>" name="<?= $c['id'] ?>"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-10 focus:ring-2 focus:ring-teal-500" required>

          <!-- Icono -->
          <button type="button" onclick="togglePassword('<?= $c['id'] ?>', this)"
            class="absolute right-3 top-8 text-gray-500 hover:text-teal-600">
            <svg xmlns="http://www.w3.org/2000/svg" id="eye_<?= $c['id'] ?>" fill="none"
                 viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.036 12.322a1 1 0 010-.639C3.423 7.51 7.26 4.5 12 4.5c4.74 0 8.577 3.01 9.964 7.183.07.2.07.44 0 .639C20.577 16.49 16.74 19.5 12 19.5c-4.74 0-8.577-3.01-9.964-7.178z" />
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </button>
        </div>
      <?php endforeach; ?>

      <div class="flex justify-end pt-4">
        <button type="submit" name="update_password"
                class="bg-amber-600 text-white px-5 py-2 rounded-md hover:bg-amber-700">Actualizar Contraseña</button>
      </div>
    </form>
  </div>
</div>

<script>
function togglePassword(id, btn) {
  const input = document.getElementById(id);
  const icon = btn.querySelector("svg");

  if (input.type === "password") {
    input.type = "text";
    icon.innerHTML = `
      <path stroke-linecap="round" stroke-linejoin="round"
            d="M3.98 8.223A10.477 10.477 0 001.934 12c1.292 2.927 4.498 6 10.066 6 5.568 0 8.774-3.073 10.066-6a10.478 10.478 0 00-2.046-3.777M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      <path stroke-linecap="round" stroke-linejoin="round"
            d="M4.5 4.5l15 15" />`;
  } else {
    input.type = "password";
    icon.innerHTML = `
      <path stroke-linecap="round" stroke-linejoin="round"
            d="M2.036 12.322a1 1 0 010-.639C3.423 7.51 7.26 4.5 12 4.5c4.74 0 8.577 3.01 9.964 7.183.07.2.07.44 0 .639C20.577 16.49 16.74 19.5 12 19.5c-4.74 0-8.577-3.01-9.964-7.178z" />
      <path stroke-linecap="round" stroke-linejoin="round"
            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />`;
  }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
