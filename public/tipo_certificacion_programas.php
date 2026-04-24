<?php
require_once __DIR__ . '/../config/auth.php'; // Maneja session_start() y login
checkRole(['ADMINISTRADOR']); // Solo los administradores pueden gestionar cursos/programas

$title = "Gestión de Tipos de Certificación y Programas";
$active = "cursos";

require_once __DIR__ . '/../config/db.php'; // Conexión a la base de datos

// ================== CURSOS ==================
// Obtener todos los cursos
$stmt = $pdo->query("
    SELECT *
    FROM tipo_certificacion
    WHERE deleted_at IS NULL
    ORDER BY id ASC
");
$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== PROGRAMAS ==================
// Obtener todos los programas
$stmt = $pdo->query("
    SELECT *
    FROM programas
    WHERE deleted_at IS NULL
    ORDER BY id ASC
");
$programas = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Gestión de Tipos de Certificación y Programas</h1>

<!-- Mensajes de éxito / error -->
<?php if (isset($_GET['msg'])): ?>
  <div class="bg-green-100 text-green-800 p-3 rounded mb-4 border border-green-300 text-sm text-center">
    <?= htmlspecialchars($_GET['msg']) ?>
  </div>
<?php elseif (isset($_GET['error'])): ?>
  <div class="bg-red-100 text-red-800 p-3 rounded mb-4 border border-red-300 text-sm text-center">
    <?= htmlspecialchars($_GET['error']) ?>
  </div>
<?php endif; ?>


<!-- Formulario para agregar nuevo curso -->
<form method="POST" action="../process/process_tipo_certificacion.php" class="flex gap-2 mb-6">
    <input type="hidden" name="action" value="add">
    <input type="text" name="nombre" placeholder="Nombre del tipo de certificación" required
           class="border rounded px-3 py-2 flex-1">
    <button type="submit" class="bg-teal-500 text-white px-4 py-2 rounded hover:bg-teal-600">
        Agregar
    </button>
</form>

<!-- Lista de cursos -->
<table class="w-full border-collapse border border-gray-300 mb-10">
    <thead>
        <tr class="bg-gray-100">
            
            <th class="border px-3 py-2">Nombre</th>
            <th class="border px-3 py-2">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($cursos as $curso): ?>
        <tr>
            <td class="border px-3 py-2"><?= htmlspecialchars($curso['nombre']) ?></td>
            <td class="border px-3 py-2 flex gap-2">
                <!-- Editar curso -->
                <form method="POST" action="../process/process_tipo_certificacion.php">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $curso['id'] ?>">
                    <input type="text" name="nombre" value="<?= htmlspecialchars($curso['nombre']) ?>" required
                           class="border px-2 py-1 rounded">
                    <button type="submit" class="bg-yellow-400 px-2 py-1 rounded hover:bg-yellow-500">Editar</button>
                </form>
                <!-- Eliminar curso -->
                <form method="POST" action="../process/process_tipo_certificacion.php" onsubmit="return confirm('¿Eliminar este curso?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $curso['id'] ?>">
                    <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">
                        Eliminar
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- ================== PROGRAMAS ================== -->


<!-- Formulario para agregar nuevo programa -->
<form method="POST" action="../process/process_programas.php" class="flex gap-2 mb-6">
    <input type="hidden" name="action" value="add">
    <input type="text" name="nombre_programa" placeholder="Nombre del programa" required
           class="border rounded px-3 py-2 flex-1">
    <button type="submit" class="bg-teal-500 text-white px-4 py-2 rounded hover:bg-teal-600">
        Agregar
    </button>
</form>

<!-- Lista de programas -->
<table class="w-full border-collapse border border-gray-300">
    <thead>
        <tr class="bg-gray-100">
            
            <th class="border px-3 py-2">Nombre del programa</th>
            <th class="border px-3 py-2">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($programas as $programa): ?>
        <tr>
            <td class="border px-3 py-2"><?= htmlspecialchars($programa['nombre_programa'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="border px-3 py-2 flex gap-2">
                <!-- Editar programa -->
                <form method="POST" action="../process/process_programas.php">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $programa['id'] ?>">
                    <input type="text" name="nombre_programa" value="<?= htmlspecialchars($programa['nombre_programa'], ENT_QUOTES, 'UTF-8') ?>" required
                           class="border px-2 py-1 rounded">
                    <button type="submit" class="bg-yellow-400 px-2 py-1 rounded hover:bg-yellow-500">Editar</button>
                </form>
                <!-- Eliminar programa -->
                <form method="POST" action="../process/process_programas.php" onsubmit="return confirm('¿Eliminar este programa?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $programa['id'] ?>">
                    <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">
                        Eliminar
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
