<?php
require_once __DIR__ . '/../config/auth.php';
require __DIR__ . '/../config/db.php';
checkRole(['ADMINISTRADOR']); // Solo los administradores pueden eliminar usuarios

// Validar que se reciba el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: registrar_usuario.php?tab=gestionar&error=" . urlencode("ID inválido"));
    exit;
}

$id = (int) $_GET['id'];

// Evitar que un administrador se elimine a sí mismo
if ($_SESSION['user_id'] == $id) {
    header("Location: registrar_usuario.php?tab=gestionar&error=" . urlencode("No puedes eliminar tu propia cuenta"));
    exit;
}

// Verificar que el usuario exista
$stmt = $pdo->prepare("SELECT fullname FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: registrar_usuario.php?tab=gestionar&error=" . urlencode("Usuario no encontrado"));
    exit;
}

$title = 'Eliminar Usuario';
$active = 'gestionar_usuarios';
ob_start();
?>

<div class="flex justify-center items-center min-h-[85vh]">
  <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md text-center">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Eliminar Usuario</h2>
    <p class="text-gray-700 mb-6">
      ¿Seguro que deseas eliminar al usuario <strong><?= htmlspecialchars($user['fullname']) ?></strong>?
    </p>

    <form action="../process/process_eliminar_usuario.php" method="POST">
      <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

      <div class="flex justify-between">
        <a href="registrar_usuario.php?tab=gestionar"
           class="bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 transition">
           Cancelar
        </a>
        <button type="submit"
          class="bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition">
          Eliminar Usuario
        </button>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
