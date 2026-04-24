<?php
require_once __DIR__ . '/../config/auth.php';  // Sesión y roles
require __DIR__ . '/../config/db.php';         // Conexión a la DB
checkRole(['ADMINISTRADOR']);                  // Solo administradores
// 🆕 1) ID del usuario que ha iniciado sesión
$currentUserId = $_SESSION['user_id'] ?? null;

// 🆕 2) Pasamos de query() a prepare() y excluimos al usuario actual
$stmt = $pdo->prepare("
    SELECT id, fullname, email, role
    FROM usuarios
    WHERE deleted_at IS NULL
      AND id != :current_id      -- <== no traer al usuario logueado
    ORDER BY id DESC
");

$stmt->execute(['current_id' => $currentUserId]);

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-white p-8 rounded-2xl shadow-xl">
  <h2 class="text-2xl font-bold mb-6 text-gray-800 text-center">Actualizar / Eliminar Usuarios</h2>

  <table class="w-full border border-gray-300 rounded-lg">
    <thead>
      <tr class="bg-gray-100 text-left">
        <th class="p-3">Nombre</th>
        <th class="p-3">Correo</th>
        <th class="p-3">Rol</th>
        <th class="p-3 text-center">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($usuarios as $u): ?>
        <tr class="border-t hover:bg-gray-50">
          <td class="p-3"><?= htmlspecialchars($u['fullname']) ?></td>
          <td class="p-3"><?= htmlspecialchars($u['email']) ?></td>
          <td class="p-3"><?= htmlspecialchars($u['role']) ?></td>
          <td class="p-3 text-center flex justify-center gap-2">
            <!-- Botón Editar -->
            <a href="../public/editar_usuario.php?id=<?= $u['id'] ?>"
               class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 text-sm">
               Editar
            </a>

            <!-- Botón Eliminar (directo al proceso sin abrir otra página) -->
            <form action="../public/eliminar_usuario.php" method="POST"
                  onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <a href="../public/eliminar_usuario.php?id=<?= $u['id'] ?>"
                class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">
                Eliminar
              </a>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
