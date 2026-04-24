<?php
require_once __DIR__ . '/../config/auth.php';
require __DIR__ . '/../config/db.php';
checkRole(['ADMINISTRADOR']); // Solo los administradores pueden editar usuarios

// Verificar que venga un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: gestionar_usuarios.php?error=ID+inválido");
    exit;
}

$id = (int) $_GET['id'];

// Obtener información del usuario
$stmt = $pdo->prepare("
    SELECT u.*, a.team 
    FROM usuarios u
    LEFT JOIN asesores a ON a.user_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: gestionar_usuarios.php?error=Usuario+no+encontrado");
    exit;
}

// Obtener roles posibles desde ENUM
$query = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'role'");
$row = $query->fetch(PDO::FETCH_ASSOC);
preg_match("/^enum\('(.*)'\)$/", $row['Type'], $matches);
$roles = explode("','", $matches[1]);

$title = 'Editar Usuario';
$active = 'gestionar_usuarios';
ob_start();
?>

<div class="flex justify-center items-center min-h-[85vh]">
  <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Editar Usuario</h2>

    <?php if(isset($_GET['error'])): ?>
      <div class="bg-red-100 text-red-800 p-3 rounded mb-4 border border-red-300 text-sm">
        <?= htmlspecialchars($_GET['error']) ?>
      </div>
    <?php endif; ?>

    <form action="../process/process_editar_usuario.php" method="POST" id="editUserForm">
      <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">

      <!-- Nombre completo -->
      <div class="mb-5">
        <label for="fullname" class="block text-gray-700 font-semibold mb-1">Nombre Completo</label>
        <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required
          class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-green-500 outline-none">
      </div>

      <!-- Correo -->
      <div class="mb-5">
        <label for="email" class="block text-gray-700 font-semibold mb-1">Correo Electrónico</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
          class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-green-500 outline-none">
      </div>

      <!-- Rol -->
      <div class="mb-5">
        <label for="role" class="block text-gray-700 font-semibold mb-1">Rol</label>
        <select id="role" name="role" required
          class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-green-500 outline-none">
          <?php foreach ($roles as $rol): ?>
            <option value="<?= htmlspecialchars($rol) ?>"
              <?= $user['role'] === $rol ? 'selected' : '' ?>>
              <?= htmlspecialchars($rol) ?>
            </option>
          <?php endforeach; ?>
        </select> 
      </div>

      <!-- Campo 'Team' (solo asesores o advisors) -->
      <div class="mb-5 <?= in_array(strtoupper($user['role']), ['ASESOR', 'ADVISOR']) ? '' : 'hidden' ?>" id="team-field">
        <label for="team" class="block text-gray-700 font-semibold mb-1">Team</label>
        <input type="text" id="team" name="team"
          value="<?= htmlspecialchars($user['team'] ?? '') ?>"
          class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-green-500 outline-none"
          placeholder="Nombre del equipo">
      </div>

      <!-- Nueva contraseña (opcional) -->
      <div class="mb-5">
        <label for="password" class="block text-gray-700 font-semibold mb-1">
          Nueva Contraseña <span class="text-gray-500 text-sm">(deja en blanco para no cambiar)</span>
        </label>
        <input type="password" id="password" name="password"
          class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-green-500 outline-none">
      </div>

      <!-- Botones -->
      <div class="flex justify-between mt-6">
        <a href="registrar_usuario.php?tab=gestionar"
           class="bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 transition">
           Cancelar
        </a>
        <button type="submit"
          class="bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition">
          Guardar Cambios
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Mostrar campo 'Team' solo si el rol es ASESOR o ADVISOR
const roleSelect = document.getElementById('role');
const teamField = document.getElementById('team-field');
roleSelect.addEventListener('change', function() {
  const value = this.value.toUpperCase();
  if (value === 'ASESOR' || value === 'ADVISOR') {
    teamField.classList.remove('hidden');
  } else {
    teamField.classList.add('hidden');
  }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
