<?php
require_once __DIR__ . '/../config/auth.php';
require __DIR__ . '/../config/db.php';
checkRole(['ADMINISTRADOR']); // Solo administradores pueden agregar docentes

// Traer especialidades y niveles
$especialidades = $pdo->query("SELECT id, nombre FROM especialidades")->fetchAll(PDO::FETCH_ASSOC);
$niveles = ['INICIAL','PRIMARIA','SECUNDARIA'];

$title = 'Agregar Docente';
ob_start();
?>

<div class="flex justify-center mt-6">
  <div class="w-full max-w-4xl">
    <!-- Título principal -->
    <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Agregar Docente</h1>

    <form action="../process/process_agregar_docente.php" method="post" enctype="multipart/form-data"
          class="bg-white rounded-2xl shadow-lg p-6">
        <h3 class="text-xl font-semibold mb-6 text-gray-700">Datos del Docente</h3>

        <div class="grid md:grid-cols-3 gap-4">
            <div><label class="block mb-1">Nombres *</label><input name="nombres" required class="w-full border rounded px-3 py-2"></div>
            <div><label class="block mb-1">Apellidos *</label><input name="apellidos" required class="w-full border rounded px-3 py-2"></div>
            <div><label class="block mb-1">DNI *</label><input name="dni" required class="w-full border rounded px-3 py-2"></div>

            <div><label class="block mb-1">Celular *</label><input name="celular" required class="w-full border rounded px-3 py-2"></div>
            <div><label class="block mb-1">Correo *</label><input type="email" name="email" required class="w-full border rounded px-3 py-2"></div>
            <div>
                <label class="block mb-1">Nivel *</label>
                <select name="nivel" required class="w-full border rounded px-3 py-2">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($niveles as $nivel): ?>
                        <option value="<?= htmlspecialchars($nivel) ?>"><?= htmlspecialchars($nivel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div><label class="block mb-1">Departamento *</label><input name="departamento" required class="w-full border rounded px-3 py-2"></div>
            <div><label class="block mb-1">Provincia *</label><input name="provincia" required class="w-full border rounded px-3 py-2"></div>
            <div><label class="block mb-1">Distrito *</label><input name="distrito" required class="w-full border rounded px-3 py-2"></div>

            <div id="especialidades-box" class="md:col-span-2 hidden">
                <label class="block mb-1">Especialidades (multi)</label>
                <select id="especialidades-select" name="especialidades[]" multiple size="5" class="w-full border rounded px-3 py-2">
                    <?php foreach ($especialidades as $esp): ?>
                        <option value="<?= $esp['id'] ?>"><?= htmlspecialchars($esp['nombre']) ?></option>
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
                                
        <div class="mt-6 flex justify-between">
    
            <!-- Botón Cancelar -->
            <a href="home.php"
            class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition">
            Cancelar
            </a>

            <!-- Botón Guardar -->
            <button type="submit"
                    class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
                Guardar
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
