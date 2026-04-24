<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
checkRole(['ADMINISTRADOR']); // Solo admin puede eliminar docentes

// Validar ID por GET
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: home.php?error=ID+inválido");
    exit;
}

$id = (int) $_GET['id'];

// Consultar datos del docente (tabla correcta: teachers)
$stmt = $pdo->prepare("SELECT nombres, apellidos FROM docentes WHERE id = ?");
$stmt->execute([$id]);
$docente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$docente) {
    header("Location: home.php?error=Docente+no+encontrado");
    exit;
}

$title = 'Eliminar Docente';
$active = 'home';
ob_start();
?>

<div class="flex justify-center items-center min-h-[85vh]">
  <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md text-center">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Eliminar Docente</h2>

    <p class="text-gray-700 mb-6">
      ¿Seguro que deseas eliminar al docente
      <strong><?= htmlspecialchars($docente['nombres'] . ' ' . $docente['apellidos']) ?></strong>?
    </p>

    <form action="../process/process_eliminar_docente.php" method="POST">
      <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

      <div class="flex justify-between">
        <a href="home.php"
           class="bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 transition">
          Cancelar
        </a>

        <button type="submit"
          class="bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition">
          Eliminar Docente
        </button>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
