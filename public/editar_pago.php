<?php
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR']);

require __DIR__ . '/../config/db.php';

// Verificar ID del pago
if (!isset($_GET['id'])) {
    header("Location: editar_registros.php?tab=pagos");
    exit;
}

$id = (int) $_GET['id'];

// Obtener pago con información del docente y venta
$sql = "
    SELECT p.*, 
           s.teacher_id,
           t.nombres, t.apellidos
    FROM pagos p
    INNER JOIN ventas s ON p.sale_id = s.id
    INNER JOIN docentes t ON s.teacher_id = t.id
    WHERE p.id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$pago = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pago) {
    echo "<p>Pago no encontrado.</p>";
    exit;
}

// Docente relacionado
$docente = $pago['nombres'] . ' ' . $pago['apellidos'];

// --- Obtener los valores ENUM directamente desde la base de datos ---
function getEnumValues($pdo, $table, $column) {
    $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    preg_match_all("/'([^']+)'/", $row['Type'], $matches);
    return $matches[1];
}

$tipoPago = getEnumValues($pdo, 'pagos', 'tipo_pago');
$formaPago = getEnumValues($pdo, 'pagos', 'forma_pago');
$bancos = getEnumValues($pdo, 'pagos', 'banco');
?>

<div class="max-w-5xl mx-auto px-4 py-8">
  <nav class="text-sm text-gray-500 mb-4">
    <a href="home.php" class="hover:text-teal-700">Inicio</a>
    <span class="mx-2">/</span>
    <a href="editar_registros.php?tab=pagos&teacher_id=<?= intval($pago['teacher_id']) ?>" class="hover:text-teal-700">Editar Registros</a>
    <span class="mx-2">/</span>
    <span class="text-gray-900 font-medium">Editar Pago</span>
  </nav>

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900">Editar Pago</h1>
      <p class="text-gray-600">Ajusta la información del pago del docente.</p>
    </div>
    <a href="editar_registros.php?tab=pagos&teacher_id=<?= intval($pago['teacher_id']) ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-md border hover:bg-gray-50 text-gray-700">
      <i class="fas fa-arrow-left"></i> Volver
    </a>
  </div>

  <form action="../process/process_actualizar_pago.php" method="POST" enctype="multipart/form-data" class="space-y-6">
    <input type="hidden" name="id" value="<?= htmlspecialchars($pago['id']) ?>">

    <!-- Docente -->
    <section class="bg-white rounded-xl shadow border p-6">
      <h3 class="text-lg font-semibold mb-2">Docente</h3>
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
          <i class="fas fa-user"></i>
        </div>
        <p class="text-gray-800 font-medium"><?= htmlspecialchars($docente) ?></p>
      </div>
    </section>

    <!-- Datos del Pago -->
    <section class="bg-white rounded-xl shadow border p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Datos del Pago</h3>
        <div class="text-xs text-gray-500">ID Pago: <span class="font-semibold text-gray-700">#<?= intval($pago['id']) ?></span></div>
      </div>

      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Tipo de Pago *</label>
          <select name="tipo_pago" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
            <option value="">-- Seleccione --</option>
            <?php foreach ($tipoPago as $tp): ?>
              <option value="<?= htmlspecialchars($tp) ?>" <?= $pago['tipo_pago'] == $tp ? 'selected' : '' ?>><?= htmlspecialchars($tp) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Monto Total *</label>
          <input type="number" step="0.01" name="monto_total" value="<?= htmlspecialchars($pago['monto_total']) ?>" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Fecha de Pago *</label>
          <input type="date" name="fecha_pago" value="<?= htmlspecialchars($pago['fecha_pago']) ?>" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Forma de Pago *</label>
          <select name="forma_pago" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
            <option value="">-- Seleccione --</option>
            <?php foreach ($formaPago as $fp): ?>
            <option value="<?= htmlspecialchars($fp) ?>" <?= $pago['forma_pago'] == $fp ? 'selected' : '' ?>><?= htmlspecialchars($fp) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Banco *</label>
          <select name="banco" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
            <option value="">-- Seleccione --</option>
            <?php foreach ($bancos as $b): ?>
            <option value="<?= htmlspecialchars($b) ?>" <?= $pago['banco'] == $b ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Código de Operación</label>
          <input type="text" name="codigo_operacion" value="<?= htmlspecialchars($pago['codigo_operacion']) ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Titular que Pagó *</label>
          <input type="text" name="titular_pago" value="<?= htmlspecialchars($pago['titular_pago']) ?>" required class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500">
        </div>

        <div class="mt-4 md:col-span-3">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Voucher de Pago
                <span class="text-xs font-normal text-gray-500">(PDF / JPG / PNG / WEBP)</span>
            </label>

            <div class="w-full rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 
                        flex items-center justify-between gap-4">

                <!-- Icono + texto -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                        <i class="fas fa-file-invoice-dollar text-emerald-600"></i>
                    </div>

                    <div class="flex flex-col">
                        <span id="voucher-file-label" class="text-sm font-medium text-gray-700">
                            Ningún archivo seleccionado
                        </span>
                        <span class="text-xs text-gray-400">
                            Tamaño máx. 3MB · Formatos: PDF, JPG, PNG, WEBP
                        </span>

                        <div id="voucher-actual" class="text-xs text-blue-600 mt-1">
                            <?php if (!empty($pago['voucher_path'])): ?>
                                Archivo actual:
                                <a href="<?= htmlspecialchars($pago['voucher_path']) ?>"
                                  target="_blank"
                                  class="underline text-blue-700">
                                  <?= basename($pago['voucher_path']) ?>
                                </a>
                            <?php else: ?>
                                Ningún archivo cargado
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Botón seleccionar archivo -->
                <label for="voucher"
                      class="shrink-0 inline-flex items-center px-4 py-2 rounded-lg 
                            bg-emerald-600 text-white text-sm font-medium cursor-pointer 
                            hover:bg-emerald-700 transition">
                    Seleccionar archivo
                    <input type="file"
                          id="voucher"
                          name="voucher"
                          accept=".pdf,.jpg,.jpeg,.png,.webp"
                          class="sr-only">
                </label>
            </div>
        </div>
    </section>

    <div class="pt-2 flex gap-3">
      <button type="submit"
         class="bg-teal-600 text-white px-5 py-2.5 rounded-md hover:bg-teal-700 inline-flex items-center gap-2">
         <i class="fas fa-save"></i> Actualizar
    </button>

      <a href="editar_registros.php?tab=pagos&teacher_id=<?= intval($pago['teacher_id']) ?>" class="px-5 py-2.5 rounded-md border hover:bg-gray-50 inline-flex items-center gap-2">
        <i class="fas fa-times"></i> Cancelar
      </a>
    </div>
  </form>
</div>
<script>
document.getElementById('voucher').addEventListener('change', function () {
    const label = document.getElementById('voucher-file-label');

    if (this.files && this.files.length > 0) {
        label.textContent = this.files[0].name;
        label.classList.remove('text-gray-400');
        label.classList.add('text-gray-700');
    } else {
        label.textContent = 'Ningún archivo seleccionado';
        label.classList.remove('text-gray-700');
        label.classList.add('text-gray-400');
    }
});
</script>
