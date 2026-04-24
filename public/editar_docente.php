<?php
require_once __DIR__ . '/../config/auth.php';
require __DIR__ . '/../config/db.php';
checkRole(['ADMINISTRADOR']); // Solo los administradores pueden editar docentes

// Verificar que venga un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: home.php?error=ID+inválido");
    exit;
}

$id = (int) $_GET['id'];

// Obtener información del docente
$stmt = $pdo->prepare("SELECT * FROM docentes WHERE id = ?");
$stmt->execute([$id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    header("Location: home.php?error=Docente+no+encontrado");
    exit;
}

// Obtener especialidades y niveles
$especialidades = $pdo->query("SELECT id, nombre FROM especialidades")->fetchAll(PDO::FETCH_ASSOC);
$niveles = ['INICIAL','PRIMARIA','SECUNDARIA'];

// Especialidades del docente
$teacherEspStmt = $pdo->prepare("SELECT specialty_id FROM docente_especialidad WHERE teacher_id = ?");
$teacherEspStmt->execute([$id]);
$teacherEsp = $teacherEspStmt->fetchAll(PDO::FETCH_COLUMN);

$title = 'Editar Docente';
$active = 'home';
ob_start();
?>

<div class="flex justify-center items-center min-h-[85vh]">
  <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-4xl">
    <!-- Título principal -->
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Editar Docente</h1>

    <?php if (!empty($_SESSION['flash_message'])): ?>

      <?php
          $type = $_SESSION['flash_type'] ?? 'success';

          $classes = $type === 'error'
              ? 'bg-red-50 border-red-200 text-red-800'
              : 'bg-green-50 border-green-200 text-green-800';
      ?>

      <div class="mb-4 rounded-lg border px-4 py-2 text-sm <?= $classes ?>">
          <?= htmlspecialchars($_SESSION['flash_message']); ?>
      </div>

      <?php
          unset($_SESSION['flash_message']);
          unset($_SESSION['flash_type']);
      ?>

    <?php endif; ?>

    <form action="../process/process_editar_docente.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= htmlspecialchars($teacher['id']) ?>">

      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="block mb-1">Nombres *</label>
          <input name="nombres" value="<?= htmlspecialchars($teacher['nombres']) ?>" required class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block mb-1">Apellidos *</label>
          <input name="apellidos" value="<?= htmlspecialchars($teacher['apellidos']) ?>" required class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block mb-1">DNI *</label>
          <input name="dni" value="<?= htmlspecialchars($teacher['dni']) ?>" required class="w-full border rounded px-3 py-2">
        </div>

        <div>
          <label class="block mb-1">Celular *</label>
          <input name="celular" value="<?= htmlspecialchars($teacher['celular']) ?>" required class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block mb-1">Correo *</label>
          <input type="email" name="email" value="<?= htmlspecialchars($teacher['email']) ?>" required class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block mb-1">Nivel *</label>
          <select name="nivel" required class="w-full border rounded px-3 py-2">
            <option value="">-- Seleccione --</option>
            <?php foreach ($niveles as $nivel): ?>
              <option value="<?= htmlspecialchars($nivel) ?>" <?= $nivel==$teacher['nivel']?'selected':'' ?>><?= htmlspecialchars($nivel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block mb-1">Departamento *</label>
          <input name="departamento" value="<?= htmlspecialchars($teacher['departamento']) ?>" required class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block mb-1">Provincia *</label>
          <input name="provincia" value="<?= htmlspecialchars($teacher['provincia']) ?>" required class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block mb-1">Distrito *</label>
          <input name="distrito" value="<?= htmlspecialchars($teacher['distrito']) ?>" required class="w-full border rounded px-3 py-2">
        </div>

        <div id="especialidades-box" class="md:col-span-2 hidden">
                <label class="block mb-1">Especialidades (multi)</label>
                <select id="especialidades-select" name="especialidades[]" multiple size="5" class="w-full border rounded px-3 py-2">
                    <?php foreach ($especialidades as $esp): ?>
                        <option value="<?= $esp['id'] ?>" 
                            <?= in_array($esp['id'], $teacherEsp) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($esp['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        <div class="mt-4 md:col-span-3">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Copia de DNI
                <span class="text-xs font-normal text-gray-500">(PDF / JPG / PNG / WEBP)</span>
            </label>

            <div class="w-full rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 
                        flex items-center justify-between gap-4">

                <!-- Icono + texto -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                        <i class="fas fa-id-card text-emerald-600"></i>
                    </div>

                    <div class="flex flex-col">
                        <span id="dni-file-label" class="text-sm font-medium text-gray-700">
                            Ningún archivo seleccionado
                        </span>
                        <span class="text-xs text-gray-400">
                            Tamaño máx. 3MB · Formatos: PDF, JPG, PNG, WEBP
                        </span>

                        <div id="dni-actual" class="text-xs text-blue-600 mt-1">
                            <?php if (!empty($teacher['copia_dni_path'])): ?>
                                Archivo actual:
                                <a href="<?= htmlspecialchars($teacher['copia_dni_path']) ?>"
                                  target="_blank"
                                  class="underline text-blue-700">
                                  <?= basename($teacher['copia_dni_path']) ?>
                                </a>
                            <?php else: ?>
                                Ningún archivo cargado
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Botón para seleccionar archivo -->
                <label for="copia_dni"
                      class="shrink-0 inline-flex items-center px-4 py-2 rounded-lg 
                              bg-emerald-600 text-white text-sm font-medium cursor-pointer 
                              hover:bg-emerald-700 transition">

                    Seleccionar archivo

                    <input type="file"
                          id="copia_dni"
                          name="copia_dni"
                          accept=".pdf,.jpg,.jpeg,.png,.webp"
                          class="sr-only">
                </label>
            </div>
        </div>

      <div class="flex justify-between mt-6">
        <a href="home.php"
           class="bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 transition">
           Cancelar
        </a>
        <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition">
          Actualizar
        </button>
      </div>
    </form>
  </div>
</div>
<script>
document.getElementById('copia_dni').addEventListener('change', function () {
    const label = document.getElementById('dni-file-label');

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

<script>
document.getElementById('copia_dni').addEventListener('change', function () {
    const label = document.getElementById('dni-file-label');

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
<script>
const nivelSelect = document.querySelector('select[name="nivel"]');
const especialidadesBox = document.getElementById('especialidades-box');
const especialidadesSelect = document.getElementById('especialidades-select');

function actualizarEspecialidades() {
    if (nivelSelect.value === 'SECUNDARIA') {
        especialidadesBox.classList.remove('hidden');
        especialidadesSelect.setAttribute('required', 'required');
    } else {
        especialidadesBox.classList.add('hidden');
        especialidadesSelect.removeAttribute('required');
        especialidadesSelect.value = ""; // limpiar selección
    }
}

// Ejecutar al cargar por si acaso
actualizarEspecialidades();

// Ejecutar cada vez que cambie el nivel
nivelSelect.addEventListener('change', actualizarEspecialidades);
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
