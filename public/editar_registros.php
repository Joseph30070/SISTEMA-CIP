<?php 
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR']); 
require __DIR__ . '/../config/db.php';

$title  = "Editar Registros";
$active = "editar_registros";
ob_start();

$tab = $_GET['tab'] ?? 'ventas';

// Obtener lista de docentes
$teachers = $pdo->query("SELECT id, CONCAT(nombres, ' ', apellidos) AS nombre FROM docentes ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// Recibir datos del formulario
$teacher_name = $_GET['teacher_name'] ?? '';
$teacher_id   = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : null;
$edit_id      = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;
$registros    = [];

// Si solo tenemos nombre pero no id, buscamos el id
if (!$teacher_id && $teacher_name) {
    foreach ($teachers as $t) {
        if (strtolower($t['nombre']) === strtolower(trim($teacher_name))) {
            $teacher_id = $t['id'];
            break;
        }
    }
}

// Si tenemos id de docente, traemos los registros
if ($teacher_id) {
    if ($tab === 'ventas') {
        $sql = "
            SELECT 
                s.id,
                c.nombre AS tipo_de_certifcacion,
                pgr.nombre_programa AS programa,
                s.tipo_transaccion,
                s.precio_programa,
                s.inicio_programa,
                a.nombre_completo AS asesor,
                s.created_at AS fecha_registro
            FROM ventas s
            LEFT JOIN tipo_certificacion c ON s.curso_id = c.id
            LEFT JOIN asesores a ON s.advisor_id = a.id
            LEFT JOIN programas pgr ON s.programa_id = pgr.id
            WHERE s.teacher_id = ?
            AND s.deleted_at IS NULL
            ORDER BY s.created_at DESC
        ";
    } else {
        $sql = "
          SELECT 
              p.id,
              c.nombre AS tipo_de_certifcacion,
              pgr.nombre_programa,
              p.tipo_pago,
              p.monto_total,
              p.fecha_pago,
              p.forma_pago
          FROM pagos p
          LEFT JOIN ventas s ON p.sale_id = s.id
          LEFT JOIN tipo_certificacion c ON s.curso_id = c.id
          LEFT JOIN programas pgr ON s.programa_id = pgr.id
          WHERE s.teacher_id = ?
          AND p.deleted_at IS NULL
          ORDER BY p.fecha_pago DESC
        ";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$teacher_id]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="mb-6">
  <h2 class="text-3xl font-bold text-gray-800 mb-6">Gestión de Registros</h2>
  <p class="text-gray-600">Edita o elimina ventas y pagos por docente.</p>
</div>

<div class="mb-6 border-b">
  <div class="flex space-x-4">
    <a href="?tab=ventas" class="px-4 py-2 font-medium <?= $tab === 'ventas' ? 'border-b-2 border-teal-600 text-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">Ventas</a>
    <a href="?tab=pagos" class="px-4 py-2 font-medium <?= $tab === 'pagos' ? 'border-b-2 border-teal-600 text-teal-600' : 'text-gray-500 hover:text-gray-700' ?>">Pagos</a>
  </div>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-6">
  <form method="GET" class="grid md:grid-cols-3 gap-4 items-center">
    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
    <input type="hidden" name="teacher_name" id="teacher_name_hidden" value="<?= htmlspecialchars($teacher_name) ?>">
    <input type="hidden" name="teacher_id" id="teacher_id_hidden" value="<?= htmlspecialchars($teacher_id) ?>">

    <div class="md:col-span-2 relative">
      <label class="block text-sm font-medium mb-1">Seleccionar Docente</label>

      <div class="relative">
        <!-- Fantasma -->
        <input id="docente_sugerido_tab"
               class="w-full absolute top-0 left-0 h-full px-3 py-2 flex items-center pointer-events-none text-gray-400 rounded border"
               style="background: transparent; box-sizing: border-box;"
               tabindex="-1">

        <!-- Input real -->
        <input id="docente_buscar_tab"
               class="w-full border rounded px-3 py-2 bg-transparent"
               placeholder="Escribe el nombre completo del docente..."
               autocomplete="off"
               value="<?= htmlspecialchars($teacher_name) ?>">
      </div>

      <p class="text-xs text-gray-500 mt-1">
        Escribe y presiona TAB para autocompletar o buscar.
      </p>

      <!-- Lista desplegable -->
      <ul id="lista_docentes_tab" class="border bg-white hidden absolute left-0 right-0 mt-1 rounded shadow z-50 max-h-60 overflow-auto"></ul>
    </div>

    <!-- Botón Buscar a la misma altura que el input -->
    <div class="flex items-end">
      <button type="submit" class="w-full h-full bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700">
        Buscar
      </button>
    </div>
  </form>
</div>


<div class="bg-white shadow rounded-lg p-6 overflow-x-auto">
  <?php if (!$teacher_id): ?>
    <p class="text-gray-500 text-center">Seleccione un docente para ver los registros.</p>
  <?php elseif (empty($registros)): ?>
    <p class="text-gray-500 text-center">No hay registros para este docente.</p>
  <?php else: ?>
    <table class="min-w-full border border-gray-200 rounded-lg mb-2">
      <thead class="bg-gray-100">
        <tr>
          <?php foreach (array_keys($registros[0]) as $col): ?>
            <th class="px-4 py-2 border text-sm font-semibold text-gray-700"><?= ucwords(str_replace('_', ' ', $col)) ?></th>
          <?php endforeach; ?>
          <th class="px-4 py-2 border text-sm font-semibold text-gray-700">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($registros as $fila): ?>
          <tr class="hover:bg-gray-50 text-center">
            <?php foreach ($fila as $valor): ?>
              <td class="px-4 py-2 border text-gray-800"><?= htmlspecialchars($valor) ?></td>
            <?php endforeach; ?>
            <td class="px-4 py-2 border">
              <?php if ($tab === 'ventas'): ?>
                <a href="?tab=ventas&teacher_id=<?= $teacher_id ?>&edit_id=<?= $fila['id'] ?>" 
                  class="inline-flex items-center justify-center px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700 mr-2">
                  Editar
                </a>
                <form action="../process/process_eliminar_venta.php" method="POST" class="inline" onsubmit="return confirm('¿Seguro que deseas eliminar esta venta?')">
                  <input type="hidden" name="id" value="<?= $fila['id'] ?>">
                  <button type="submit" class="inline-flex items-center justify-center px-3 py-1 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700">
                    Eliminar
                  </button>
                </form>
              <?php else: ?>
                <a href="?tab=pagos&teacher_id=<?= $teacher_id ?>&edit_id=<?= $fila['id'] ?>" 
                  class="inline-flex items-center justify-center px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700 mr-2">
                  Editar
                </a>
                <form action="../process/process_eliminar_pago.php" method="POST" class="inline" onsubmit="return confirm('¿Seguro que deseas eliminar este pago?')">
                  <input type="hidden" name="id" value="<?= $fila['id'] ?>">
                  <button type="submit" class="inline-flex items-center justify-center px-3 py-1 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700">
                    Eliminar
                  </button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p class="text-xs text-gray-500">Consejo: usa el botón “Editar” para abrir el formulario embebido a continuación.</p>
  <?php endif; ?>
</div>

<?php
if ($edit_id) {
    $_GET['id'] = $edit_id;
    echo '<div class="mt-6 bg-white shadow rounded-lg p-6">';
    echo '<div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">'.($tab==='ventas'?'Editar Venta':'Editar Pago').'</h3>
            <a href="?tab='.htmlspecialchars($tab).'&teacher_id='.intval($teacher_id).'" class="text-sm text-gray-600 hover:text-gray-800 underline">Cerrar editor</a>
          </div>';
    if ($tab === 'ventas') {
        include __DIR__ . '/editar_venta.php';
    } else {
        include __DIR__ . '/editar_pago.php';
    }
    echo '</div>';
}
?>

<script>
const docentes_tab = <?= json_encode($teachers) ?>;
const inputBuscarTab   = document.getElementById('docente_buscar_tab');
const inputSugeridoTab = document.getElementById('docente_sugerido_tab');
const listaDocentesTab = document.getElementById('lista_docentes_tab');
const inputHiddenId    = document.getElementById('teacher_id_hidden');
const inputHiddenName  = document.getElementById('teacher_name_hidden');
let selectedIndexTab = -1;

function actualizarLista() {
    const textoIngresado = inputBuscarTab.value;
    inputHiddenName.value = textoIngresado;

    if (!textoIngresado) {
        inputSugeridoTab.value = '';
        listaDocentesTab.classList.add('hidden');
        listaDocentesTab.innerHTML = '';
        inputHiddenId.value = '';
        return;
    }

    const coincidencias = docentes_tab.filter(d => d.nombre.toLowerCase().startsWith(textoIngresado.toLowerCase()));
    if (coincidencias.length > 0) {
        const restante = coincidencias[0].nombre.slice(textoIngresado.length);
        inputSugeridoTab.value = textoIngresado + restante;
        inputSugeridoTab.style.color = "rgba(0,0,0,0.3)";

        listaDocentesTab.innerHTML = coincidencias.map(d =>
            `<li class="px-3 py-2 cursor-pointer hover:bg-gray-100" data-id="${d.id}" data-nombre="${d.nombre}">${d.nombre}</li>`
        ).join('');
        listaDocentesTab.classList.remove('hidden');
        selectedIndexTab = -1;
    } else {
        inputSugeridoTab.value = '';
        listaDocentesTab.innerHTML = '';
        listaDocentesTab.classList.add('hidden');
        selectedIndexTab = -1;
    }
}

// Actualiza la lista mientras escribes
inputBuscarTab.addEventListener('input', actualizarLista);

// Selección por clic
listaDocentesTab.addEventListener('click', function(e) {
    if (e.target.tagName === 'LI') {
        seleccionarDocente(e.target.dataset.id, e.target.dataset.nombre, true);
    }
});

// Navegación con teclado
inputBuscarTab.addEventListener('keydown', function(e) {
    const items = listaDocentesTab.querySelectorAll('li');
    if (e.key === "ArrowDown") {
        e.preventDefault();
        if (items.length === 0) return;
        selectedIndexTab = (selectedIndexTab + 1) % items.length;
        actualizarSeleccion(items);
    } 
    else if (e.key === "ArrowUp") {
        e.preventDefault();
        if (items.length === 0) return;
        selectedIndexTab = (selectedIndexTab - 1 + items.length) % items.length;
        actualizarSeleccion(items);
    } 
    else if (e.key === "Enter") {
        e.preventDefault();
        if (selectedIndexTab >= 0) {
            const li = items[selectedIndexTab];
            seleccionarDocente(li.dataset.id, li.dataset.nombre, true);
        } else {
            const match = docentes_tab.find(d => d.nombre.toLowerCase() === inputBuscarTab.value.toLowerCase());
            if (match) seleccionarDocente(match.id, match.nombre, true);
        }
    } 
    else if (e.key === "Tab") {
    const matchExact = docentes_tab.find(d => d.nombre.toLowerCase() === inputBuscarTab.value.toLowerCase());
    const matchFirst = docentes_tab.find(d => d.nombre.toLowerCase().startsWith(inputBuscarTab.value.toLowerCase()));
    const match = matchExact || matchFirst;
    if (match) {
        inputBuscarTab.value = match.nombre;
        inputHiddenId.value = match.id;
        inputHiddenName.value = match.nombre;
        inputSugeridoTab.value = '';
        listaDocentesTab.classList.add('hidden');

        // Enviar automáticamente el formulario
        inputBuscarTab.closest('form').submit();
    }
    // NO preventDefault, Tab sigue al siguiente campo
}
});

function actualizarSeleccion(items) {
    items.forEach((li, i) => li.classList.toggle('bg-gray-200', i === selectedIndexTab));
}

function seleccionarDocente(id, nombre, enviar) {
    inputBuscarTab.value = nombre;
    inputSugeridoTab.value = '';
    inputHiddenId.value = id;
    inputHiddenName.value = nombre;
    listaDocentesTab.classList.add('hidden');
    if (enviar) inputBuscarTab.closest('form').submit();
}

</script>

<?php
$content = ob_get_clean();
require __DIR__. '/layout.php';
