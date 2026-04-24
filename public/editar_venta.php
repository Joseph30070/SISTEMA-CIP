<?php
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR']);

require __DIR__ . '/../config/db.php';

// Verificar ID
if (!isset($_GET['id'])) {
    header("Location: editar_registros.php?tab=ventas");
    exit;
}

$id = (int) $_GET['id'];

// Obtener la venta con datos relacionados
$sql = "SELECT s.*, 
               t.nombres, t.apellidos,
               c.nombre AS curso_nombre,
               a.nombre_completo AS asesor_nombre,
               pgr.nombre_programa          -- traer nombre_programa desde la tabla programas

        FROM ventas s
        JOIN docentes t ON s.teacher_id = t.id
        LEFT JOIN tipo_certificacion c ON s.curso_id = c.id
        LEFT JOIN asesores a ON s.advisor_id = a.id
        LEFT JOIN programas pgr ON s.programa_id = pgr.id  -- JOIN con programas
        WHERE s.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    echo "<p>Venta no encontrada.</p>";
    exit;
}
$docente = $venta['nombres'] . ' ' . $venta['apellidos'];

// Listas para selects
$cursos = $pdo->query("SELECT id, nombre FROM tipo_certificacion ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$asesores = $pdo->query("SELECT id, nombre_completo FROM asesores ORDER BY nombre_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
$programas = $pdo->query("SELECT id, nombre_programa FROM programas ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
// ---- OBTENER ENUMS DESDE LA BASE DE DATOS ----
function obtenerEnumValores($pdo, $tabla, $columna) {
    $stmt = $pdo->query("SHOW COLUMNS FROM $tabla LIKE '$columna'");
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    if (preg_match("/^enum\('(.*)'\)$/", $fila['Type'], $matches)) {
        return explode("','", $matches[1]);
    }
    return [];
}

$tiposTransaccion = obtenerEnumValores($pdo, 'ventas', 'tipo_transaccion');
$modalidades = obtenerEnumValores($pdo, 'ventas', 'modalidad');
$certificados = obtenerEnumValores($pdo, 'ventas', 'certificado');
$procesosCertificacion = obtenerEnumValores($pdo, 'ventas', 'proceso_certificacion');

?>

<div class="max-w-5xl mx-auto px-4 py-8">
  <!-- Breadcrumb -->
  <nav class="text-sm text-gray-500 mb-4">
    <a href="home.php" class="hover:text-teal-700">Inicio</a>
    <span class="mx-2">/</span>
    <a href="editar_registros.php?tab=ventas&teacher_id=<?= intval($venta['teacher_id']) ?>" class="hover:text-teal-700">Editar Registros</a>
    <span class="mx-2">/</span>
    <span class="text-gray-900 font-medium">Editar Venta</span>
  </nav>

  <!-- Header -->   
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Editar Venta</h1>
      <p class="text-gray-600">Modifica los datos de la venta sin cambiar el docente.</p>
    </div>
    <a href="editar_registros.php?tab=ventas&teacher_id=<?= intval($venta['teacher_id']) ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-md border hover:bg-gray-50 text-gray-700">
      <i class="fas fa-arrow-left"></i> Volver
    </a>
  </div>

  <form action="../process/process_actualizar_venta.php" method="POST" class="space-y-6">
      <input type="hidden" name="id" value="<?= htmlspecialchars($venta['id']) ?>">
      <input type="hidden" name="teacher_id" value="<?= htmlspecialchars($venta['teacher_id']) ?>">

      <!-- Docente -->
      <section class="bg-white rounded-xl shadow border p-6">
          <h3 class="text-lg font-semibold mb-2">Docente</h3>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center text-teal-700">
              <i class="fas fa-user"></i>
            </div>
            <p class="text-gray-800 font-medium"><?= htmlspecialchars($docente) ?></p>
          </div>
      </section>

      <!-- Programa Adquirido -->
      <section class="bg-white rounded-xl shadow border p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Programa Adquirido</h3>
            <div class="text-xs text-gray-500">ID Venta: <span class="font-semibold text-gray-700">#<?= intval($venta['id']) ?></span></div>
          </div>

          <div class="grid md:grid-cols-3 gap-4">
              <div>
                  <label class="block text-sm font-medium mb-1">Tipo de Transacción *</label>
                  <select name="tipo_transaccion" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
                      <?php foreach ($tiposTransaccion as $tipo): ?>
                          <option value="<?= $tipo ?>" <?= $venta['tipo_transaccion'] == $tipo ? 'selected' : '' ?>>
                              <?= htmlspecialchars($tipo) ?>
                          </option>
                      <?php endforeach; ?>
                  </select>
              </div>

              <div>
                  <label class="block text-sm font-medium mb-1">Tipo de Certificacion *</label>
                  <select name="curso_id" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
                      <?php foreach ($cursos as $c): ?>
                          <option value="<?= $c['id'] ?>" <?= $venta['curso_id'] == $c['id'] ? 'selected' : '' ?>>
                              <?= htmlspecialchars($c['nombre']) ?>
                          </option>
                      <?php endforeach; ?>
                  </select>
              </div>

              <div>
                  <label class="block text-sm font-medium mb-1">Modalidad *</label>
                  <select name="modalidad" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
                      <?php foreach ($modalidades as $m): ?>
                          <option value="<?= $m ?>" <?= $venta['modalidad'] == $m ? 'selected' : '' ?>>
                              <?= htmlspecialchars($m) ?>
                          </option>
                      <?php endforeach; ?>
                  </select>
              </div>

              <div>
                  <label class="block text-sm font-medium mb-1">Programa *</label>
                  <select name="programa_id" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
                      <?php foreach ($programas as $p): ?>
                          <option value="<?= $p['id'] ?>" <?= $venta['programa_id'] == $p['id'] ? 'selected' : '' ?>>
                              <?= htmlspecialchars($p['nombre_programa']) ?>
                          </option>
                      <?php endforeach; ?>
                  </select>
              </div>

              <div>
                  <label class="block text-sm font-medium mb-1">Precio del Programa *</label>
                  <input type="number" step="0.01" name="precio_programa" value="<?= htmlspecialchars($venta['precio_programa']) ?>" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500" placeholder="0.00">
              </div>

              <div>
                  <label class="block text-sm font-medium mb-1">Mención *</label>
                  <input type="text" name="mencion" value="<?= htmlspecialchars($venta['mencion']) ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
              </div>

              <div>
                  <label class="block text-sm font-medium mb-1">Inicio del Programa *</label>
                  <input type="date" name="inicio_programa" value="<?= htmlspecialchars($venta['inicio_programa']) ?>" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
              </div>
               <div>
                <label class="block text-sm font-medium mb-1">Certificado *</label>
                <select name="certificado" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
                    <?php foreach ($certificados as $c): ?>
                        <option value="<?= $c ?>" <?= $venta['certificado'] == $c ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
              </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Proceso de Certificación *</label>
                    <select name="proceso_certificacion" required 
                            class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">

                        <?php foreach ($procesosCertificacion as $pc): ?>
                            <option value="<?= htmlspecialchars($pc) ?>"
                                <?= $venta['proceso_certificacion'] == $pc ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pc) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>
              
              <div class="md:col-span-3">
                  <label class="block text-sm font-medium mb-1">Observaciones</label>
                  <textarea name="obs_programa" rows="3" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500"><?= htmlspecialchars($venta['obs_programa']) ?></textarea>
              </div>
              
          </div>
      </section>

      <!-- Asesor -->
      <section class="bg-white rounded-xl shadow border p-6">
          <h3 class="text-lg font-semibold mb-4">Asesor</h3>
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Seleccionar Asesor *</label>
              <select name="advisor_id" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
                  <?php foreach ($asesores as $a): ?>
                      <option value="<?= $a['id'] ?>" <?= $venta['advisor_id'] == $a['id'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($a['nombre_completo']) ?>
                      </option>
                  <?php endforeach; ?>
              </select>
            </div>
          </div>
      </section>

      <div class="pt-2 flex gap-3">

        <button type="submit"
            class="bg-teal-600 text-white px-5 py-2.5 rounded-md hover:bg-teal-700 inline-flex items-center gap-2">
            <i class="fas fa-save"></i> Actualizar
        </button>

          <a href="editar_registros.php?tab=ventas&teacher_id=<?= intval($venta['teacher_id']) ?>" class="px-5 py-2.5 rounded-md border hover:bg-gray-50 inline-flex items-center gap-2">
            <i class="fas fa-times"></i> Cancelar
          </a>
      </div>
  </form>
</div>
