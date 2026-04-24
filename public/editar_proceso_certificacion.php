<?php
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR', 'ADMISION']);
require_once __DIR__ . '/../config/db.php';

$title  = "Editar Proceso de Certificación";
$active = "consulta";
ob_start();

$id = isset($_GET['id']) ? (int)trim($_GET['id']) : 0;
$vista = isset($_GET['vista']) ? trim($_GET['vista']) : 'resumido';
$filtro = isset($_GET['filtro']) ? trim($_GET['filtro']) : 'ventas';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

if (!$id) {
    echo "<p class='text-red-600'>ID de venta no válido.</p>";
    $content = ob_get_clean();
    require __DIR__ . '/layout.php';
    exit;
}

// Obtener venta
$stmt = $pdo->prepare("SELECT * FROM ventas WHERE id = ?");
$stmt->execute([$id]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    echo "<p class='text-red-600'>Venta no encontrada.</p>";
    $content = ob_get_clean();
    require __DIR__ . '/layout.php';
    exit;
}

// Selects
$docentes   = $pdo->query("SELECT id, CONCAT(nombres,' ',apellidos) AS nombre FROM docentes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$cursos     = $pdo->query("SELECT id, nombre FROM tipo_certificacion ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$programas  = $pdo->query("SELECT id, nombre_programa FROM programas ORDER BY nombre_programa")->fetchAll(PDO::FETCH_ASSOC);
$asesores   = $pdo->query("SELECT id, nombre_completo FROM asesores ORDER BY nombre_completo")->fetchAll(PDO::FETCH_ASSOC);

// Obtener ENUM
function obtenerEnumValores($pdo, $tabla, $columna) {
    $stmt = $pdo->query("SHOW COLUMNS FROM $tabla LIKE '$columna'");
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    if (preg_match("/^enum\('(.*)'\)$/", $fila['Type'], $matches)) {
        return explode("','", $matches[1]);
    }
    return [];
}
$procesosCertificacion = obtenerEnumValores($pdo, 'ventas', 'proceso_certificacion');
?>
<div class="mb-6">
  <h2 class="text-3xl font-bold text-gray-800 mb-4">Editar Proceso de Certificación</h2>
  <p class="text-gray-600">Solo puedes modificar el proceso de certificación.</p>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-10">

  <form action="../process/process_editar_proceso_certificacion.php" method="POST" class="space-y-5">

    <input type="hidden" name="id" value="<?= $venta['id'] ?>">
    <input type="hidden" name="vista" value="<?= htmlspecialchars($vista) ?>">
    <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
    <input type="hidden" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>">

    <!-- CAMPOS BLOQUEADOS -->
    <div>
      <label class="block font-medium mb-1">Docente</label>
      <select disabled class="border w-full px-3 py-2 rounded">
        <?php foreach ($docentes as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $d['id']==$venta['teacher_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <input type="hidden" name="teacher_id" value="<?= $venta['teacher_id'] ?>">
    </div>

    <!-- CAMPO EDITABLE -->
    <div>
      <label class="block font-medium mb-1 text-teal-700 font-semibold">Proceso de Certificación *</label>
      <select name="proceso_certificacion" required
              class="w-full border rounded-lg px-3 py-2 bg-white focus:ring-2 focus:ring-teal-500">
        <?php foreach ($procesosCertificacion as $pc): ?>
          <option value="<?= htmlspecialchars($pc) ?>" <?= $venta['proceso_certificacion'] == $pc ? 'selected' : '' ?>>
            <?= htmlspecialchars($pc) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- BOTONES -->
    <div class="flex gap-4 pt-4">
      <button class="bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700">
        Guardar Cambios
      </button>

      <a href="consulta.php?filtro=<?= urlencode($filtro) ?>&busqueda=<?= urlencode($busqueda) ?>&vista=<?= urlencode($vista) ?>"
         class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
        Cancelar
      </a>
    </div>

  </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
