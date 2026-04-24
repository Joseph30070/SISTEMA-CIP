<?php

require_once __DIR__ . '/../config/auth.php'; // o ajusta la ruta si tu auth.php está en otra carpeta
checkRole(['ADMINISTRADOR', 'ASESOR']); // Solo estos roles pueden entrar

$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/db.php';

$base = rtrim($config['base_url'], '/');
$title  = "Registro";
$active = "registro";

//se añadio esto---
$ROLE = strtoupper($_SESSION['role'] ?? '');

// ID del usuario logeado (users.id)
$userId = $_SESSION['user_id'];
$role = $_SESSION['role']; // Esto viene del login

$advisorId = null;

// Si el usuario es un ASESOR, obtener su advisor_id
if ($role === 'ASESOR') {
    $stmt = $pdo->prepare("SELECT id FROM asesores WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $advisor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$advisor) {
        die("No se encontró el asesor para este usuario.");
    }

    $advisorId = $advisor['id'];
}

// --------------------------------------------------------------------
// VENTAS DISPONIBLES SEGÚN ROL
// --------------------------------------------------------------------
if ($role === 'ASESOR') {
    // Solo las ventas hechas por el asesor logeado
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.teacher_id,
            c.nombre AS tipo_programa,
            p.nombre_programa  AS programa
        FROM ventas s
        LEFT JOIN tipo_certificacion c ON s.curso_id = c.id
        LEFT JOIN programas p ON s.programa_id = p.id
        WHERE s.advisor_id = :advisor_id
          AND s.id NOT IN (
              -- Ventas totalmente pagadas al contado
              SELECT p3.sale_id
              FROM pagos p3
              WHERE p3.tipo_pago = 'CONTADO'
              AND p3.deleted_at IS NULL

              UNION
              -- Ventas con las 4 cuotas completas
              SELECT p4.sale_id
              FROM pagos p4
              WHERE p4.tipo_pago LIKE 'CUOTA%'
                AND p4.deleted_at IS NULL
              GROUP BY p4.sale_id
              HAVING COUNT(DISTINCT p4.tipo_pago) >= 4
          )
        ORDER BY c.nombre, p.nombre_programa
    ");
    $stmt->execute(['advisor_id' => $advisorId]);
} else {
    // ADMINISTRADOR ve todas las ventas
    $stmt = $pdo->query("
        SELECT 
            s.id,
            s.teacher_id,
            c.nombre AS tipo_programa,
            p.nombre_programa  AS programa
        FROM ventas s
        LEFT JOIN tipo_certificacion c ON s.curso_id = c.id
        LEFT JOIN programas p ON s.programa_id = p.id
        WHERE s.id NOT IN (
            -- Ventas totalmente pagadas al contado
            SELECT p3.sale_id
            FROM pagos p3
            WHERE p3.tipo_pago = 'CONTADO'
            AND p3.deleted_at IS NULL
            UNION

            -- Ventas con las 4 cuotas completas
            SELECT p4.sale_id
            FROM pagos p4
            WHERE p4.tipo_pago LIKE 'CUOTA%'
            AND p4.deleted_at IS NULL
            GROUP BY p4.sale_id
            HAVING COUNT(DISTINCT p4.tipo_pago) >= 4
        )
        ORDER BY c.nombre, p.nombre_programa
    ");
}

$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

//de aqui creo que se borra
// --------------------------------------------------------------------
// PAGOS AL CONTADO / ENVÍOS
// --------------------------------------------------------------------
if ($role === 'ASESOR') {
    // Solo los pagos de ese asesor
    $pagosAsesor = $pdo->prepare("
        SELECT 
            p.id,
            p.monto_total,
            p.fecha_pago,
            p.forma_pago,
            p.banco,
            p.titular_pago,
            pr.nombre_programa AS programa,
            c.nombre AS tipo_programa,
            t.nombres,
            t.apellidos,
            t.dni
        FROM pagos p
        JOIN ventas s ON p.sale_id = s.id
        JOIN docentes t ON s.teacher_id = t.id
        LEFT JOIN tipo_certificacion c ON s.curso_id = c.id
        LEFT JOIN programas pr ON s.programa_id = pr.id
        WHERE p.tipo_pago IN ('CONTADO', 'ENVIO')
          AND p.deleted_at IS NULL
          AND s.deleted_at IS NULL
          AND s.advisor_id = :advisor_id
        ORDER BY p.fecha_pago DESC
    ");
    $pagosAsesor->execute(['advisor_id' => $advisorId]);
} else {
    // ADMINISTRADOR ve todos los pagos
    $pagosAsesor = $pdo->prepare("
        SELECT 
            p.id,
            p.monto_total,
            p.fecha_pago,
            p.forma_pago,
            p.banco,
            p.titular_pago,
            pr.nombre_programa AS programa,
            c.nombre AS tipo_programa,
            t.nombres,
            t.apellidos,
            t.dni
        FROM pagos p
        JOIN ventas s ON p.sale_id = s.id
        JOIN docentes t ON s.teacher_id = t.id
        LEFT JOIN tipo_certificacion c ON s.curso_id = c.id
        LEFT JOIN programas pr ON s.programa_id = pr.id
        WHERE p.tipo_pago IN ('CONTADO', 'ENVIO')
          AND p.deleted_at IS NULL
          AND s.deleted_at IS NULL
        ORDER BY p.fecha_pago DESC
    ");
    $pagosAsesor->execute();
}

$pagosAsesor = $pagosAsesor->fetchAll(PDO::FETCH_ASSOC);

// --------------------------------------------------------------------
// CUOTAS
// --------------------------------------------------------------------
if ($role === 'ASESOR') {
    $cuotasAsesor = $pdo->prepare("
        SELECT 
            p.id,
            p.monto_total AS monto,
            p.fecha_pago,
            p.forma_pago,
            p.banco,
            p.titular_pago,
            pr.nombre_programa AS programa,
            c.nombre AS tipo_programa,
            t.nombres,
            t.apellidos,
            t.dni,
            p.tipo_pago AS numero_cuota
        FROM pagos p
        JOIN ventas s ON p.sale_id = s.id
        JOIN docentes t ON s.teacher_id = t.id
        LEFT JOIN tipo_certificacion c ON s.curso_id = c.id
        LEFT JOIN programas pr ON s.programa_id = pr.id
        WHERE p.id IN (
            SELECT MAX(p2.id)
            FROM pagos p2
            JOIN ventas s2 ON p2.sale_id = s2.id
            WHERE p2.tipo_pago LIKE 'CUOTA%'
              AND p2.deleted_at IS NULL
              AND s2.advisor_id = :advisor_id
              AND s2.deleted_at IS NULL
            GROUP BY s2.id
        )
        ORDER BY p.fecha_pago DESC
    ");
    $cuotasAsesor->execute(['advisor_id' => $advisorId]);
} else {
    // ADMIN ve todas las cuotas
    $cuotasAsesor = $pdo->prepare("
        SELECT 
            p.id,
            p.monto_total AS monto,
            p.fecha_pago,
            p.forma_pago,
            p.banco,
            p.titular_pago,
            pr.nombre_programa AS programa,
            c.nombre AS tipo_programa,
            t.nombres,
            t.apellidos,
            t.dni,
            p.tipo_pago AS numero_cuota
        FROM pagos p
        JOIN ventas s ON p.sale_id = s.id
        JOIN docentes t ON s.teacher_id = t.id
        LEFT JOIN tipo_certificacion c ON s.curso_id = c.id
        LEFT JOIN programas pr ON s.programa_id = pr.id
        WHERE p.id IN (
            SELECT MAX(p2.id)
            FROM pagos p2
            JOIN ventas s2 ON p2.sale_id = s2.id
            WHERE p2.tipo_pago LIKE 'CUOTA%'
              AND p2.deleted_at IS NULL
              AND s2.deleted_at IS NULL
            GROUP BY s2.id
        )
        ORDER BY p.fecha_pago DESC
    ");
    $cuotasAsesor->execute();
}

$cuotasAsesor = $cuotasAsesor->fetchAll(PDO::FETCH_ASSOC);

//hasta aqui creo que se borra

if ($role === 'ASESOR') {
    // Solo mostrar el asesor logueado
    $stmt = $pdo->prepare("SELECT id, nombre_completo, team FROM asesores WHERE id = :id");
    $stmt->execute(['id' => $advisorId]);
    $asesores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Mostrar todos los asesores si es administrador
    $asesores = $pdo->query("SELECT id, nombre_completo, team FROM asesores ORDER BY nombre_completo")->fetchAll(PDO::FETCH_ASSOC);
}

// Todos ven todos los docentes tanto los asesores como los administradores
$todos_docentes = $pdo->query("
    SELECT 
        t.id,
        t.nombres,
        t.apellidos,
        t.dni,
        t.celular,
        t.email,
        t.nivel,
        t.departamento,
        t.provincia,
        t.distrito,
        t.copia_dni_path,
        CONCAT(t.nombres, ' ', t.apellidos) AS nombre,
        GROUP_CONCAT(ts.specialty_id) AS especialidades_ids
    FROM docentes t
    LEFT JOIN docente_especialidad ts ON t.id = ts.teacher_id
    GROUP BY t.id
    ORDER BY t.apellidos
")->fetchAll(PDO::FETCH_ASSOC);

// Mapa de docentes (para JavaScript)
$docentesById = [];
foreach ($todos_docentes as $d) {
    $docentesById[$d['id']] = $d['nombre'];
}

// === Función para obtener los valores ENUM ===
function getEnumValues($pdo, $table, $column)
{
    $query = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $query->execute([$column]);
    $row = $query->fetch(PDO::FETCH_ASSOC);
    if (!$row) return [];
    preg_match("/^enum\('(.*)'\)$/", $row['Type'], $matches);
    return isset($matches[1]) ? explode("','", $matches[1]) : [];
}

// === Cargar opciones dinámicas ===
$especialidades = $pdo->query("SELECT id, nombre FROM especialidades ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$cursos = $pdo->query("SELECT id, nombre FROM tipo_certificacion ORDER BY nombre ASC");
$tipoPrograma = $cursos->fetchAll(PDO::FETCH_ASSOC);
$programas = $pdo->query("SELECT id, nombre_programa FROM programas ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Enums de las tablas:
$niveles         = getEnumValues($pdo, 'docentes', 'nivel');
$tipoTransaccion = getEnumValues($pdo, 'ventas', 'tipo_transaccion');
$certificados = getEnumValues($pdo, 'ventas', 'certificado');
$modalidades     = getEnumValues($pdo, 'ventas', 'modalidad');
$tipoPago        = getEnumValues($pdo, 'pagos', 'tipo_pago');
$formaPago       = getEnumValues($pdo, 'pagos', 'forma_pago');
$bancos          = getEnumValues($pdo, 'pagos', 'banco');

// 👇 CONSULTAS PARA MOSTRAR DATOS REALES EN LAS TABLAS
// Traer pagos (ventas completas: CONTADO o ENVIO)
$pagos = $pdo->query("
    SELECT 
        p.id,
        p.monto_total,
        p.fecha_pago,
        p.forma_pago,
        p.banco,
        p.titular_pago,
        pr.nombre_programa AS programa,
        c.nombre AS tipo_programa, -- tipo de programa desde cursos
        t.nombres,
        t.apellidos,
        t.dni
    FROM pagos p
    JOIN ventas s ON p.sale_id = s.id
    JOIN docentes t ON s.teacher_id = t.id
    LEFT JOIN tipo_certificacion c ON s.curso_id = c.id
    LEFT JOIN programas pr ON s.programa_id = pr.id
    WHERE p.tipo_pago IN ('CONTADO', 'ENVIO')
    ORDER BY p.fecha_pago DESC
")->fetchAll(PDO::FETCH_ASSOC);


// Traer cuotas (CUOTA #1, CUOTA #2, CUOTA #3, CUOTA #4) actuales
$cuotas = $pdo->query("
    SELECT 
        p.id,
        p.monto_total AS monto,
        p.fecha_pago,
        p.forma_pago,
        p.banco,
        p.titular_pago,
        pr.nombre_programa AS programa,
        c.nombre AS tipo_programa,  -- traer nombre del curso
        t.nombres,
        t.apellidos,
        t.dni,
        p.tipo_pago AS numero_cuota
    FROM pagos p
    JOIN ventas s ON p.sale_id = s.id
    JOIN docentes t ON s.teacher_id = t.id
    LEFT JOIN tipo_certificacion c ON s.curso_id = c.id  -- traer tipo de programa
    LEFT JOIN programas pr ON s.programa_id = pr.id
    WHERE p.id IN (
        SELECT MAX(p2.id)
        FROM pagos p2
        JOIN ventas s2 ON p2.sale_id = s2.id
        WHERE p2.tipo_pago LIKE 'CUOTA%'
        GROUP BY s2.id
    )
    ORDER BY p.fecha_pago DESC
")->fetchAll(PDO::FETCH_ASSOC);

ob_start();

?>
<h2 class="text-3xl font-bold text-gray-800 mb-6">Registro Docente</h2>

<!-- Pestañas -->
<div class="flex border-b mb-6">
    <button id="tab-docente" class="tab-btn px-4 py-2 font-semibold bg-teal-600 text-white"
        onclick="mostrarTab('docente')">Registro Docente </button>

    <button id="tab-pago" class="tab-btn px-4 py-2 font-semibold text-gray-600 hover:bg-gray-100"
        onclick="mostrarTab('pago')">Registro de Cuotas</button>
</div>

<div id="contenido-docente" class="tab-content block">

    <!-- FORMULARIO DOCENTE -->
    <form id="form-docente" action="<?= $base ?>/process_runner_registro.php" method="post" enctype="multipart/form-data" class="space-y-8">
        <input type="hidden" name="action" id="form-action" value="docente">
        <!-- Datos del Docente -->
        <section class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Datos del Docente</h3>
            <label class="block text-sm font-medium mb-1"><b>Buscar Docente ( si ha sido registrado anteriormente )</b></label>

            <!-- Buscador docente -->
            <div class="mb-6">
                <div style="position: relative;">
                    <!-- Fantasma -->
                    <input id="docente_sugerido"
                        class="w-full border rounded px-3 py-2 absolute top-0 left-0 text-gray-400 pointer-events-none"
                        style="background: transparent;"
                        tabindex="-1">

                    <!-- Input real -->
                    <input id="docente_buscar"
                        class="w-full border rounded px-3 py-2 bg-transparent"
                        placeholder="Escribe el nombre completo del docente.."
                        autocomplete="off">

                    <p class="text-xs text-gray-500 mt-1">
                        Si el docente aún no está registrado en el sistema, por favor regístrelo.
                    </p>

                    <!-- LISTA DE COINCIDENCIAS DENTRO DEL RELATIVE -->
                    <ul id="lista_docentes" class="border bg-white hidden absolute left-0 right-0 mt-1 rounded shadow z-50 max-h-60 overflow-auto"></ul>
                </div>


            </div>

            <div class="grid md:grid-cols-3 gap-4">
                <div><label class="block text-sm font-medium mb-1">Nombres *</label><input name="nombres" required class="w-full border rounded px-3 py-2"></div>
                <div><label class="block text-sm font-medium mb-1">Apellidos *</label><input name="apellidos" required class="w-full border rounded px-3 py-2"></div>
                <div>
                    <label class="block text-sm font-medium mb-1">Numero de Documento (DNI) *</label>
                    <input
                        name="dni"
                        required
                        maxlength="8"
                        minlength="8"
                        class="w-full border rounded px-3 py-2"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                        inputmode="numeric">
                </div>
                <div><label class="block text-sm font-medium mb-1">Numero de Celular *</label><input name="celular" required class="w-full border rounded px-3 py-2"></div>
                <div><label class="block text-sm font-medium mb-1">Correo electrónico *</label><input type="email" name="email" required class="w-full border rounded px-3 py-2"></div>
                <div>
                    <label class="block text-sm font-medium mb-1">Grado de Enseñanza *</label>
                    <select name="nivel" required class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($niveles as $nivel): ?>
                            <option value="<?= htmlspecialchars($nivel) ?>"><?= htmlspecialchars($nivel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div><label class="block text-sm font-medium mb-1">Departamento *</label><input name="departamento" required class="w-full border rounded px-3 py-2"></div>
                <div><label class="block text-sm font-medium mb-1">Provincia *</label><input name="provincia" required class="w-full border rounded px-3 py-2"></div>
                <div><label class="block text-sm font-medium mb-1">Distrito *</label><input name="distrito" required class="w-full border rounded px-3 py-2"></div>

                <!-- ESPECIALIDADES: solo visibles para SECUNDARIA -->
                <div class="md:col-span-3 hidden" id="especialidades-wrapper">
                    <label class="block text-sm font-medium mb-1">Especialidades (multi)</label>
                    <select name="especialidades[]" multiple size="5" class="w-full border rounded px-3 py-2">
                        <?php foreach ($especialidades as $esp): ?>
                            <option value="<?= $esp['id'] ?>"><?= htmlspecialchars($esp['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Ctrl/Cmd + clic para seleccionar varias.</p>
                </div>
                <!-- FIN ESPECIALIDADES -->

                <!-- DNI -->
                <div class="mt-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Copia de DNI
                        <span class="text-xs font-normal text-gray-500">(PDF / JPG / PNG / WEBP)</span>
                    </label>

                    <div class="w-full rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 flex items-center justify-between gap-4">
                        <!-- Icono + texto -->
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                <!-- Si ya usas FontAwesome, este icono se verá bonito -->
                                <i class="fas fa-id-card text-emerald-600"></i>
                            </div>

                            <div class="flex flex-col">
                                <!-- Texto que cambia con el archivo -->
                                <span id="dni-file-label" class="text-sm font-medium text-gray-700">
                                    Ningún archivo seleccionado
                                </span>
                                <span class="text-xs text-gray-400">
                                    Tamaño máx. 3MB · Formatos: PDF, JPG, PNG, WEBP
                                </span>

                                <!-- Archivo existente (si lo usas al editar) -->
                                <div id="dni-actual" class="text-xs text-blue-600 mt-1 hidden"></div>
                            </div>
                        </div>

                        <!-- Botón para seleccionar archivo -->
                        <label
                            for="copia_dni"
                            class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium cursor-pointer hover:bg-emerald-700 transition">
                            Seleccionar archivo
                            <input
                                type="file"
                                id="copia_dni"
                                name="copia_dni"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                required
                                class="sr-only">
                        </label>
                    </div>
        </section>

        <!-- Programa Adquirido -->
        <section class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Programa Adquirido</h3>
            <div class="grid md:grid-cols-3 gap-4">

                <div>
                    <label class="block text-sm font-medium mb-1">Modalidad de compra (al contado o en cuotas)</label>
                    <select id="tipo-transaccion" name="tipo_transaccion" required class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($tipoTransaccion as $tt): ?>
                            <option value="<?= htmlspecialchars($tt) ?>"><?= htmlspecialchars($tt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Tipo de Certificacion*</label>
                    <select name="tipo_programa" required class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($tipoPrograma as $tp): ?>
                            <option value="<?= htmlspecialchars($tp['id']) ?>"><?= htmlspecialchars($tp['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Modalidad del curso *</label>
                    <select name="modalidad" required class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($modalidades as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Nombre de Programa *</label>
                    <!-- Contenedor relativo -->
                    <div class="relative w-full mb-1">
                        <!-- Fantasma -->
                        <input id="programa_sugerido"
                            class="w-full border rounded px-3 py-2 absolute top-0 left-0 text-gray-400 pointer-events-none"
                            style="background: transparent; box-sizing: border-box;"
                            tabindex="-1">

                        <!-- Input real -->
                        <input id="programa_buscar"
                            class="w-full border rounded px-3 py-2 bg-transparent"
                            placeholder="Escribe el programa..."
                            autocomplete="off"
                            required>
                        <!-- Input oculto para enviar el ID -->
                        <input type="hidden" name="programa" id="programa_id">
                        <p class="text-xs text-gray-500 mt-1">
                            Escribe y presiona TAB para autocompletar.
                        </p>
                        <!-- Lista de coincidencias: ahora dentro del relative -->
                        <ul id="lista_programas" class="border bg-white hidden absolute mt-1 rounded shadow" style="z-index:9999;"></ul>
                    </div>


                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Precio del Programa *</label>
                    <input type="number" step="0.01" min="0" name="precio_programa" required class="w-full border rounded px-3 py-2" placeholder="0.00">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Mención *</label>
                    <input name="mencion" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Inicio del Programa *</label>
                    <input type="date" name="inicio_programa" required class="w-full border rounded px-3 py-2">
                </div>

                <!-- NUEVO CAMPO: CERTIFICADO -->
                <div>
                    <label class="block text-sm font-medium mb-1">Certificado *</label>
                    <select name="certificado" required class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($certificados as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>"
                                <?= (isset($venta['certificado']) && $venta['certificado'] === $c) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- FIN NUEVO CAMPO -->
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium mb-1">Observaciones</label>
                    <textarea name="obs_programa" rows="3" class="w-full border rounded px-3 py-2"></textarea>
                </div>
            </div>
        </section>


        <!-- FORMULARIO DE PAGO (OCULTO AL INICIO) -->
        <section id="form-pago-docente" class="bg-white rounded-lg shadow p-6 hidden">
            <h3 class="text-lg font-semibold mb-4">Datos del Pago</h3>
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Monto Total Pagado</label>
                    <input type="number" step="0.01" name="monto_total" required class="w-full border rounded px-3 py-2" placeholder="0.00">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Tipo de Pago *</label>
                    <select name="tipo_pago" required class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($tipoPago as $tp): ?>
                            <option value="<?= htmlspecialchars($tp) ?>"><?= htmlspecialchars($tp) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Forma de Pago *</label>
                    <select name="forma_pago" required class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($formaPago as $fp): ?>
                            <option value="<?= htmlspecialchars($fp) ?>"><?= htmlspecialchars($fp) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Fecha del Pago *</label>
                    <input type="date" name="fecha_pago" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Banco *</label>
                    <select name="banco" required class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($bancos as $b): ?>
                            <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Código de Operación *</label>
                    <input name="codigo_operacion" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Titular que Pagó *</label>
                    <input name="titular_pago" required class="w-full border rounded px-3 py-2">
                </div>

                <!--añadido-->
                <div class="md:col-span-2 mt-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Voucher del pago
                        <span class="text-xs font-normal text-gray-500">(PDF / JPG / PNG / WEBP)</span>
                    </label>

                    <div class="w-full rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 flex items-center justify-between gap-4">
                        <!-- Icono + texto -->
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                <i class="fas fa-file-invoice-dollar text-emerald-600"></i>
                            </div>

                            <div class="flex flex-col">
                                <!-- Texto que cambia con el archivo -->
                                <span id="voucher-docente-label" class="text-sm font-medium text-gray-700">
                                    Ningún archivo seleccionado
                                </span>
                                <span class="text-xs text-gray-400">
                                    Tamaño máx. 3MB · Formatos: PDF, JPG, PNG, WEBP
                                </span>
                            </div>
                        </div>

                        <!-- Botón para seleccionar archivo -->
                        <label
                            for="voucher_docente"
                            class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium cursor-pointer hover:bg-emerald-700 transition">
                            Seleccionar archivo
                            <input
                                type="file"
                                id="voucher_docente"
                                name="voucher"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                required
                                class="sr-only">
                        </label>
                    </div>
                </div>

            </div>
        </section>

        <!-- Asesor -->
        <section class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Nombre del Asesor</h3>
            <div class="grid md:grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Seleccionar Asesor *</label>
                    <select name="asesor_id" required class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($asesores as $a): ?>
                            <option value="<?= $a['id'] ?>">
                                <?= htmlspecialchars($a['nombre_completo']) ?> (<?= htmlspecialchars($a['team']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </section>


        <!-- BOTONES -->
        <div class="flex justify-end gap-3 mt-6">
            <a href="<?= $base ?>/home.php" class="px-4 py-2 border rounded">Cancelar</a>
            <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-700">Guardar</button>
        </div>
    </form>
</div>

<div id="contenido-pago" class="tab-content hidden">
    <!-- FORMULARIO PAGO (pestaña separada) -->
    <form id="form-pago" action="<?= $base ?>/process_runner_registro.php" method="post" enctype="multipart/form-data" class="space-y-8">
        <input type="hidden" name="action" value="pago">

        <section id="seccion-pago" class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-2">Datos del Pago</h3>

            <div>
                <label class="block text-sm font-medium mb-1 mt-2">
                    Venta (Docente - Tipo Programa - Nombre Programa) *
                </label>

                <div class="relative w-full mb-1">
                    <!-- Fantasma (idéntico estilo que el real pero sin border para no bloquear) -->
                    <input id="venta_sugerida"
                        class="w-full absolute top-0 left-0 px-3 py-2 text-gray-400 pointer-events-none"
                        style="background: transparent; border: 1px solid #d1d5db; box-sizing: border-box;"
                        tabindex="-1">

                    <!-- Input real -->
                    <input id="venta_buscar"
                        class="w-full border rounded px-3 py-2 bg-transparent"
                        placeholder="Escribe parte de la venta..."
                        autocomplete="off"
                        required>

                    <!-- Input oculto para enviar el ID real -->
                    <input type="hidden" name="sale_id" id="sale_id">

                    <p class="text-xs text-gray-500 mt-1">
                        Escribe y presiona TAB para autocompletar.
                    </p>
                    <!-- Lista de coincidencias: DENTRO del relative y con tamaño completo -->
                    <ul id="lista_ventas"
                        class="border bg-white hidden absolute left-0 right-0 mt-1 rounded shadow z-50 max-h-60 overflow-auto">
                    </ul>
                </div>
            </div>


            <!-- Tipo De Certificación y Programa adquirido (automático) -->
            <div>
                <label class="block text-sm font-medium mb-1 mt-2">Programa Adquirido *</label>
                <input type="text" id="tipo_programa"
                    class="w-full border rounded px-3 py-2 bg-gray-100"
                    readonly placeholder="Tipo de programa...">

                <input type="text" id="programa_nombre"
                    class="w-full border rounded px-3 py-2 bg-gray-100 mt-2"
                    readonly placeholder="Nombre de Programa...">
            </div>

            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1 mt-2">Monto Total Pagado</label>
                    <input type="number" step="0.01" name="monto_total" class="w-full border rounded px-3 py-2" placeholder="0.00">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1 mt-2">Tipo de Pago *</label>
                    <select name="tipo_pago" id="tipo_pago" required class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccione una venta primero --</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1 mt-2">Forma de Pago *</label>
                    <select name="forma_pago" required class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($formaPago as $fp): ?>
                            <option value="<?= htmlspecialchars($fp) ?>"><?= htmlspecialchars($fp) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Fecha del Pago *</label>
                    <input type="date" name="fecha_pago" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Banco *</label>
                    <select name="banco" required class="w-full border rounded px-3 py-2">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($bancos as $b): ?>
                            <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Código de Operación *</label>
                    <input name="codigo_operacion" required class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Titular que Pagó *</label>
                    <input name="titular_pago" required class="w-full border rounded px-3 py-2">
                </div>
                <!--añadido nuevo-->
                <div class="md:col-span-2 mt-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Voucher del pago
                        <span class="text-xs font-normal text-gray-500">(PDF / JPG / PNG / WEBP)</span>
                    </label>

                    <div class="w-full rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 flex items-center justify-between gap-4">
                        <!-- Icono + texto -->
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                                <i class="fas fa-file-invoice-dollar text-emerald-600"></i>
                            </div>

                            <div class="flex flex-col">
                                <!-- Texto que cambia con el archivo -->
                                <span id="voucher-pago-label" class="text-sm font-medium text-gray-700">
                                    Ningún archivo seleccionado
                                </span>
                                <span class="text-xs text-gray-400">
                                    Tamaño máx. 3MB · Formatos: PDF, JPG, PNG, WEBP
                                </span>
                            </div>
                        </div>

                        <!-- Botón para seleccionar archivo -->
                        <label
                            for="voucher_pago"
                            class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium cursor-pointer hover:bg-emerald-700 transition">
                            Seleccionar archivo
                            <input
                                type="file"
                                id="voucher_pago"
                                name="voucher"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                required
                                class="sr-only">
                        </label>
                    </div>
                </div>

            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="<?= $base ?>/home.php" class="px-4 py-2 border rounded">Cancelar</a>
            <button class="px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-700">Guardar</button>
        </div>

        <!-- NUEVAS SECCIONES -->
        <?php if ($role !== 'ASESOR'): ?>
            <!-- Registros de Pagos al Contado -->
            <section class="bg-white rounded-lg shadow p-8 mt-8">
                <h3 class="text-lg font-semibold mb-6">Registros de Pagos al Contado</h3>

                <!-- Buscador General -->
                <div class="mb-8 flex gap-4">
                    <input type="text" id="search-pagos" placeholder="Buscar por nombre, fecha o programa..."
                        class="border rounded px-4 py-3 w-full">
                    <button type="button" id="btn-buscar-pagos"
                        class="px-5 py-3 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Buscar
                    </button>
                    <button type="button" id="btn-retroceder-pagos"
                        class="px-5 py-3 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Retroceder
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table id="tabla-pagos" class="min-w-full divide-y divide-gray-200 mb-8">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left">Docente</th>
                                <th class="px-6 py-3 text-left">Tipo De Certificacion</th>
                                <th class="px-6 py-3 text-left">Programa</th>
                                <th class="px-6 py-3 text-right">Monto</th>
                                <th class="px-6 py-3 text-left">Forma de Pago</th>
                                <th class="px-6 py-3 text-left">Banco</th>
                                <th class="px-6 py-3 text-left">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($pagosAsesor) > 0): ?>
                                <?php foreach ($pagosAsesor as $pago): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3"><?= htmlspecialchars($pago['nombres'] . ' ' . $pago['apellidos']) ?></td>
                                        <td class="px-6 py-3"><?= htmlspecialchars($pago['tipo_programa'] ?? 'Sin curso') ?></td>
                                        <td class="px-6 py-3"><?= htmlspecialchars($pago['programa']) ?></td>
                                        <td class="px-6 py-3 text-right">S/ <?= number_format($pago['monto_total'], 2) ?></td>
                                        <td class="px-6 py-3"><?= htmlspecialchars($pago['forma_pago']) ?></td>
                                        <td class="px-6 py-3"><?= htmlspecialchars($pago['banco']) ?></td>
                                        <td class="px-6 py-3"><?= htmlspecialchars($pago['fecha_pago']) ?></td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-6">No hay pagos al contado registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Registros de Cuotas -->
            <section class="bg-white rounded-lg shadow p-8 mt-8">
                <h3 class="text-lg font-semibold mb-6">Registros de Cuotas</h3>

                <!-- Buscador General -->
                <div class="mb-8 flex gap-4">
                    <input type="text" id="search-cuotas" placeholder="Buscar por nombre, fecha o programa..."
                        class="border rounded px-4 py-3 w-full">
                    <button type="button" id="btn-buscar-cuotas"
                        class="px-5 py-3 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Buscar
                    </button>
                    <button type="button" id="btn-retroceder-cuotas"
                        class="px-5 py-3 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Retroceder
                    </button>
                </div>

                <!-- Tabla de cuotas -->
                <div class="overflow-x-auto">
                    <table id="tabla-cuotas" class="min-w-full divide-y divide-gray-200 mb-8">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left">Docente</th>
                                <th class="px-6 py-3 text-left">Tipo De Certificacion</th>
                                <th class="px-6 py-3 text-left">Programa</th>
                                <th class="px-6 py-3 text-center">Cuota#</th>
                                <th class="px-6 py-3 text-right">Monto</th>
                                <th class="px-6 py-3 text-left">Forma de Pago</th>
                                <th class="px-6 py-3 text-left">Fecha</th>

                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($cuotasAsesor) > 0): ?>
                                <?php foreach ($cuotasAsesor as $cuota): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 break-words whitespace-normal"><?= htmlspecialchars($cuota['nombres'] . ' ' . $cuota['apellidos']) ?></td>
                                        <td class="px-6 py-3 break-words whitespace-normal"><?= htmlspecialchars($cuota['tipo_programa'] ?? 'Sin curso') ?></td>
                                        <td class="px-6 py-3 break-words whitespace-normal"><?= htmlspecialchars($cuota['programa']) ?></td>
                                        <td class="px-6 py-3 text-center"><?= htmlspecialchars($cuota['numero_cuota']) ?></td>
                                        <td class="px-6 py-3 text-right">S/ <?= number_format($cuota['monto'], 2) ?></td>
                                        <td class="px-6 py-3 break-words whitespace-normal"><?= htmlspecialchars($cuota['forma_pago']) ?></td>
                                        <td class="px-6 py-3"><?= htmlspecialchars($cuota['fecha_pago']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-6">No hay cuotas registradas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
    </form>
</div>
<?php endif; ?>
<script>
    function mostrarTab(tab) {
        // Oculta todos los contenidos
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));

        // Resetear clases en todos los botones
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('bg-teal-600', 'text-white');
            b.classList.add('text-gray-600', 'hover:bg-gray-100');
        });

        // Botón activo
        const btnActivo = document.getElementById(`tab-${tab}`);
        btnActivo.classList.remove('text-gray-600', 'hover:bg-gray-100');
        btnActivo.classList.add('bg-teal-600', 'text-white');

        // Mostrar contenido seleccionado
        document.getElementById(`contenido-${tab}`).classList.remove('hidden');

        // Guardar pestaña activa
        localStorage.setItem('tabActiva', tab);
    }

    // Restaurar pestaña al recargar
    document.addEventListener('DOMContentLoaded', () => {
        const tabGuardada = localStorage.getItem('tabActiva') || 'docente';
        mostrarTab(tabGuardada);
    });

    // Resto de tu script (no se toca)
    document.addEventListener('DOMContentLoaded', () => {

        function crearBuscador(idInput, idTabla, idBuscar, idRetroceder) {
            const input = document.getElementById(idInput);
            const tabla = document.getElementById(idTabla);
            const btnBuscar = document.getElementById(idBuscar);
            const btnRetroceder = document.getElementById(idRetroceder);

            if (!input || !tabla || !btnBuscar || !btnRetroceder) return;

            btnBuscar.addEventListener('click', () => {
                filtrarTabla(tabla, input.value.trim().toLowerCase());
            });

            input.addEventListener('keyup', (e) => {
                if (e.key === 'Enter') filtrarTabla(tabla, input.value.trim().toLowerCase());
            });

            btnRetroceder.addEventListener('click', () => {
                input.value = '';
                filtrarTabla(tabla, '');
            });
        }

        function filtrarTabla(tabla, query) {
            const filas = tabla.querySelectorAll('tbody tr');
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                const esFilaVacia = fila.querySelector('td[colspan]');
                if (!esFilaVacia) {
                    fila.style.display = texto.includes(query) ? '' : 'none';
                }
            });
        }

        crearBuscador('search-pagos', 'tabla-pagos', 'btn-buscar-pagos', 'btn-retroceder-pagos');
        crearBuscador('search-cuotas', 'tabla-cuotas', 'btn-buscar-cuotas', 'btn-retroceder-cuotas');
    });
</script>

<script>
    const docentes = <?php echo json_encode($todos_docentes); ?>;
    // Inputs
    const inputBuscar = document.getElementById('docente_buscar');
    const inputSugerido = document.getElementById('docente_sugerido');
    const inputFile = document.querySelector('input[name="copia_dni"]');
    const dniActual = document.getElementById('dni-actual');
    const listaDocentes = document.getElementById('lista_docentes');

    // AUTOCOMPLETADO FANTASMA + LISTA DE COINCIDENCIAS
    inputBuscar.addEventListener('input', function() {
        const texto = this.value;
        const textoLower = texto.toLowerCase();

        if (texto === "") {
            inputSugerido.value = "";
            listaDocentes.classList.add('hidden');
            listaDocentes.innerHTML = '';
            limpiarCampos();
            mostrarInputFile();
            return;
        }
        // Filtrar todas las coincidencias
        const coincidencias = docentes.filter(d => d.nombre.toLowerCase().startsWith(textoLower));
        if (coincidencias.length > 0) {
            // Fantasma: mostrar primera coincidencia
            const restante = coincidencias[0].nombre.substring(texto.length);
            inputSugerido.value = texto + restante;
            inputSugerido.style.color = "rgba(0,0,0,0.3)";

            // Mostrar lista de opciones
            listaDocentes.innerHTML = coincidencias.map(d =>
                `<li class="px-3 py-2 cursor-pointer hover:bg-gray-100" data-nombre="${d.nombre}">
                ${d.nombre}
            </li>`
            ).join('');
            listaDocentes.classList.remove('hidden');
        } else {
            inputSugerido.value = '';
            listaDocentes.innerHTML = '';
            listaDocentes.classList.add('hidden');
        }
    });
    // Selección con click en la lista
    listaDocentes.addEventListener('click', function(e) {
        if (e.target.tagName === 'LI') {
            inputBuscar.value = e.target.dataset.nombre;
            inputSugerido.value = '';
            listaDocentes.classList.add('hidden');
            cargarDocente(e.target.dataset.nombre);
        }
    });

    // Autocompletar con TAB
    inputBuscar.addEventListener('keydown', function(e) {
        if (e.key === "Tab" && inputSugerido.value !== "") {
            e.preventDefault();
            inputBuscar.value = inputSugerido.value.trim();
            inputSugerido.value = '';
            listaDocentes.classList.add('hidden');
            cargarDocente(inputBuscar.value);
        }
    });
    let selectedIndex = -1; // índice actual en la lista

    inputBuscar.addEventListener('keydown', function(e) {
        const items = listaDocentes.querySelectorAll('li');
        if (items.length === 0) return;

        if (e.key === "ArrowDown") {
            e.preventDefault();
            selectedIndex = (selectedIndex + 1) % items.length;
            actualizarSeleccion(items);
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            selectedIndex = (selectedIndex - 1 + items.length) % items.length;
            actualizarSeleccion(items);
        } else if (e.key === "Enter") {
            if (selectedIndex >= 0) {
                e.preventDefault();
                const nombre = items[selectedIndex].dataset.nombre;
                inputBuscar.value = nombre;
                inputSugerido.value = '';
                listaDocentes.classList.add('hidden');
                cargarDocente(nombre);
                selectedIndex = -1;
            }
        }
    });

    function actualizarSeleccion(items) {
        items.forEach((li, i) => {
            if (i === selectedIndex) {
                li.classList.add('bg-gray-200'); // resaltado
            } else {
                li.classList.remove('bg-gray-200');
            }
        });
    }
    // Ocultar lista si se clic fuera
    document.addEventListener('click', function(e) {
        if (!listaDocentes.contains(e.target) && e.target !== inputBuscar) {
            listaDocentes.classList.add('hidden');
        }
    });
    // CARGAR DOCENTE
    function cargarDocente(nombreCompleto) {
        const nombre = nombreCompleto.toLowerCase();
        const docente = docentes.find(d => d.nombre.toLowerCase() === nombre);
        if (!docente) return;

        document.querySelector('[name="nombres"]').value = docente.nombres;
        document.querySelector('[name="apellidos"]').value = docente.apellidos;
        document.querySelector('[name="dni"]').value = docente.dni;
        document.querySelector('[name="celular"]').value = docente.celular;
        document.querySelector('[name="email"]').value = docente.email;
        document.querySelector('[name="nivel"]').value = docente.nivel;
        document.querySelector('[name="departamento"]').value = docente.departamento;
        document.querySelector('[name="provincia"]').value = docente.provincia;
        document.querySelector('[name="distrito"]').value = docente.distrito;

        const espSelect = document.querySelector('select[name="especialidades[]"]');
        for (const opt of espSelect.options) opt.selected = false;

        if (docente.especialidades_ids) {
            const ids = docente.especialidades_ids.split(',');
            for (const opt of espSelect.options) {
                opt.selected = ids.includes(opt.value);
            }
        }

        if (docente.copia_dni_path) {
            const nombreArchivo = docente.copia_dni_path.split('/').pop();
            dniActual.classList.remove("hidden");
            dniActual.innerHTML = `
            <div class="border rounded px-3 py-2 bg-gray-50">
                <span class="text-gray-700 font-medium">DNI registrado:</span><br>
                <a href="${docente.copia_dni_path}" target="_blank" class="text-blue-600 underline">
                    ${nombreArchivo}
                </a>
            </div>
        `;
            ocultarInputFile();
        } else {
            dniActual.classList.add("hidden");
            dniActual.innerHTML = "";
            mostrarInputFile();
        }

        // Actualizar visibilidad de especialidades según el nivel cargado
        const nivelSelect = document.querySelector('select[name="nivel"]');
        if (nivelSelect) {
            nivelSelect.dispatchEvent(new Event('change'));
        }
    }
    // LIMPIAR CAMPOS
    function limpiarCampos() {
        document.querySelectorAll('#form-docente input, #form-docente select').forEach(el => {
            if (
                el.type !== 'file' &&
                el.id !== 'docente_buscar' &&
                el.id !== 'docente_sugerido'
            ) {
                el.value = "";
            }
        });
        const espSelect = document.querySelector('select[name="especialidades[]"]');
        for (const opt of espSelect.options) opt.selected = false;
        dniActual.innerHTML = "";
        dniActual.classList.add("hidden");
        mostrarInputFile();

        // También actualizar visibilidad de especialidades al limpiar
        const nivelSelect = document.querySelector('select[name="nivel"]');
        if (nivelSelect) {
            nivelSelect.dispatchEvent(new Event('change'));
        }
    }
    // MOSTRAR / OCULTAR INPUT FILE
    function ocultarInputFile() {
        inputFile.classList.add("hidden");
        inputFile.removeAttribute("required");
    }

    function mostrarInputFile() {
        inputFile.classList.remove("hidden");
        inputFile.setAttribute("required", "required");
    }
</script>

<!-- ESPECIALIDADES SOLO PARA SECUNDARIA -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const nivelSelect = document.querySelector('select[name="nivel"]');
        const espWrapper = document.getElementById('especialidades-wrapper');
        const espSelect = document.querySelector('select[name="especialidades[]"]');

        if (!nivelSelect || !espWrapper || !espSelect) return;

        function actualizarEspecialidades() {
            const nivel = (nivelSelect.value || '').toUpperCase();

            if (nivel === 'SECUNDARIA') {
                espWrapper.classList.remove('hidden');
                espSelect.removeAttribute('disabled');
                espSelect.setAttribute('required', 'required');
            } else {
                espWrapper.classList.add('hidden');
                espSelect.setAttribute('disabled', 'disabled');
                espSelect.removeAttribute('required');
                Array.from(espSelect.options).forEach(opt => opt.selected = false);
            }
        }

        // Estado inicial
        actualizarEspecialidades();
        nivelSelect.addEventListener('change', actualizarEspecialidades);
    });
</script>
<!-- FIN ESPECIALIDADES SOLO PARA SECUNDARIA -->

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const selectTransaccion = document.getElementById("tipo-transaccion");
        const formPago = document.getElementById("form-pago-docente");
        const selectTipoPago = formPago.querySelector("select[name='tipo_pago']");
        const actionInput = document.getElementById("form-action");

        formPago.classList.add("hidden");

        selectTransaccion.addEventListener("change", function() {
            const valor = this.value.trim().toUpperCase();

            // Mostrar el bloque de pago solo para VENTA o CUOTAS
            if (valor === "VENTA" || valor === "CUOTAS") {
                formPago.classList.remove("hidden");
                actionInput.value = "pago"; // importante IMPORTANTISIMO

                // Mostrar todas las opciones
                Array.from(selectTipoPago.options).forEach(opt => opt.classList.remove("hidden"));

                if (valor === "VENTA") {
                    Array.from(selectTipoPago.options).forEach(opt => {
                        if (!opt.value.toUpperCase().includes("CONTADO")) {
                            opt.classList.add("hidden");
                        }
                    });
                    const contado = Array.from(selectTipoPago.options).find(opt => opt.value.toUpperCase().includes("CONTADO"));
                    if (contado) selectTipoPago.value = contado.value;
                }

                if (valor === "CUOTAS") {
                    Array.from(selectTipoPago.options).forEach(opt => {
                        if (!opt.value.toUpperCase().includes("CUOTA #1")) {
                            opt.classList.add("hidden");
                        }
                    });
                    const cuota1 = Array.from(selectTipoPago.options).find(opt => opt.value.toUpperCase().includes("CUOTA #1"));
                    if (cuota1) selectTipoPago.value = cuota1.value;
                }
            } else {
                // Si no es venta ni cuotas, ocultar sección y volver a acción docente
                formPago.classList.add("hidden");
                actionInput.value = "docente";
                selectTipoPago.value = "";
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const programas = <?php echo json_encode($programas); ?>;
        const inputBuscar = document.getElementById('programa_buscar');
        const inputFantasma = document.getElementById('programa_sugerido');
        const lista = document.getElementById('lista_programas');
        const inputId = document.getElementById('programa_id');
        let selectedIndex = -1;

        // Filtrado y fantasma
        inputBuscar.addEventListener('input', function() {
            const texto = this.value.toLowerCase();
            if (!texto) {
                inputFantasma.value = '';
                lista.classList.add('hidden');
                lista.innerHTML = '';
                inputId.value = '';
                return;
            }

            const coincidencias = programas.filter(p => p.nombre_programa.toLowerCase().startsWith(texto));
            if (coincidencias.length > 0) {
                const restante = coincidencias[0].nombre_programa.substring(this.value.length);
                inputFantasma.value = this.value + restante;
                inputFantasma.style.color = "rgba(0,0,0,0.3)";

                lista.innerHTML = coincidencias.map(p =>
                    `<li class="px-3 py-2 cursor-pointer hover:bg-gray-100" data-id="${p.id}" data-nombre="${p.nombre_programa}">
                    ${p.nombre_programa}
                </li>`
                ).join('');
                lista.classList.remove('hidden');
                selectedIndex = -1;
            } else {
                inputFantasma.value = '';
                lista.innerHTML = '';
                lista.classList.add('hidden');
                inputId.value = '';
            }
        });

        // Selección con click
        lista.addEventListener('click', e => {
            if (e.target.tagName === 'LI') {
                inputBuscar.value = e.target.dataset.nombre;
                inputId.value = e.target.dataset.id;
                inputFantasma.value = '';
                lista.classList.add('hidden');
            }
        });

        // Navegación con teclado
        inputBuscar.addEventListener('keydown', e => {
            const items = lista.querySelectorAll('li');

            if (e.key === "Tab") {
                if (inputFantasma.value && inputFantasma.value !== inputBuscar.value) {
                    e.preventDefault();
                    inputBuscar.value = inputFantasma.value;
                    const coincidencias = programas.filter(p => p.nombre_programa.toLowerCase().startsWith(inputBuscar.value.toLowerCase()));
                    if (coincidencias.length > 0) inputId.value = coincidencias[0].id;
                    lista.classList.add('hidden');
                }
                return;
            }

            if (!items.length) return;

            if (e.key === "ArrowDown") {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                items.forEach((li, i) => li.classList.toggle('bg-gray-200', i === selectedIndex));
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                items.forEach((li, i) => li.classList.toggle('bg-gray-200', i === selectedIndex));
            } else if (e.key === "Enter" && selectedIndex >= 0) {
                e.preventDefault();
                const li = items[selectedIndex];
                inputBuscar.value = li.dataset.nombre;
                inputId.value = li.dataset.id;
                inputFantasma.value = '';
                lista.classList.add('hidden');
                selectedIndex = -1;
            }
        });

        // Ocultar lista si clic fuera
        document.addEventListener('click', function(e) {
            if (!lista.contains(e.target) && e.target !== inputBuscar) {
                lista.classList.add('hidden');
            }
        });
    });
</script>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ventas = <?php echo json_encode($ventas); ?> || [];
        const docentesById = <?php echo json_encode($docentesById); ?> || {};

        const inputBuscarVenta = document.getElementById('venta_buscar');
        const inputSugeridaVenta = document.getElementById('venta_sugerida');
        const listaVentas = document.getElementById('lista_ventas');
        const hiddenVentaId = document.getElementById('sale_id');

        const inputTipoPrograma = document.getElementById('tipo_programa');
        const inputProgramaNombre = document.getElementById('programa_nombre');
        const tipoPagoSelect = document.getElementById('tipo_pago');

        if (!inputBuscarVenta || !inputSugeridaVenta || !listaVentas) return;

        function formatearVenta(v) {
            const docente = docentesById[v.teacher_id] ?? 'Desconocido';
            return `${docente} - ${v.tipo_programa ?? ''} - ${v.programa ?? ''}`.trim();
        }

        function escapeHtml(text) {
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        }

        async function llenarCamposVenta(venta) {
            if (!venta) return;
            inputTipoPrograma.value = venta.tipo_programa ?? '';
            inputProgramaNombre.value = venta.programa ?? '';

            try {
                const response = await fetch(`process_runner_get_next_cuota.php?sale_id=${venta.id}`);
                const data = await response.json();
                tipoPagoSelect.innerHTML = data.cuota ?
                    `<option value="${data.cuota}" selected>${data.cuota}</option>` :
                    `<option value="">-- No disponible --</option>`;
            } catch (error) {
                console.error('Error al obtener cuota:', error);
                tipoPagoSelect.innerHTML = `<option value="">-- Error --</option>`;
            }
        }

        function renderLista(coincidencias) {
            listaVentas.innerHTML = coincidencias.map(v =>
                `<li class="px-3 py-2 cursor-pointer hover:bg-gray-100" data-id="${v.id}">
         ${escapeHtml(formatearVenta(v))}
       </li>`).join('');
            listaVentas.classList.toggle('hidden', coincidencias.length === 0);
        }

        inputBuscarVenta.addEventListener('input', () => {
            const typedRaw = inputBuscarVenta.value || '';
            const typed = typedRaw.trim().toLowerCase();

            if (!typed) {
                inputSugeridaVenta.value = "";
                listaVentas.innerHTML = "";
                listaVentas.classList.add('hidden');
                hiddenVentaId.value = '';
                inputTipoPrograma.value = '';
                inputProgramaNombre.value = '';
                tipoPagoSelect.innerHTML = `<option value="">-- Seleccione una venta --</option>`;
                return;
            }

            // FILTRADO: startsWith para que coincida solo al inicio
            const coincidencias = ventas.filter(v => formatearVenta(v).toLowerCase().startsWith(typed));
            renderLista(coincidencias);

            // Fantasma: solo la parte faltante en gris
            if (coincidencias.length) {
                const fullText = formatearVenta(coincidencias[0]);
                const restante = fullText.substring(typedRaw.length);
                inputSugeridaVenta.value = typedRaw + restante;
                inputSugeridaVenta.style.color = "rgba(0,0,0,0.3)"; // gris la parte sugerida
            } else {
                inputSugeridaVenta.value = '';
            }
        });

        listaVentas.addEventListener('click', async (e) => {
            const li = e.target.closest('li');
            if (!li) return;

            const venta = ventas.find(v => v.id == li.dataset.id);
            if (!venta) return;

            inputBuscarVenta.value = formatearVenta(venta);
            inputSugeridaVenta.value = '';
            hiddenVentaId.value = venta.id;
            listaVentas.classList.add('hidden');

            await llenarCamposVenta(venta);
            inputBuscarVenta.focus();
        });

        inputBuscarVenta.addEventListener('keydown', async (e) => {
            const items = Array.from(listaVentas.querySelectorAll('li'));
            let idx = items.findIndex(i => i.classList.contains('bg-gray-200'));

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                idx = (idx + 1) % items.length;
                items.forEach(it => it.classList.remove('bg-gray-200'));
                items[idx]?.classList.add('bg-gray-200');
                items[idx]?.scrollIntoView({
                    block: 'nearest'
                });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                idx = (idx - 1 + items.length) % items.length;
                items.forEach(it => it.classList.remove('bg-gray-200'));
                items[idx]?.classList.add('bg-gray-200');
                items[idx]?.scrollIntoView({
                    block: 'nearest'
                });
            } else if (e.key === 'Enter') {
                const sel = listaVentas.querySelector('li.bg-gray-200');
                if (sel) {
                    sel.click();
                    e.preventDefault();
                }
            } else if (e.key === 'Tab') {
                if (inputSugeridaVenta.value && inputSugeridaVenta.value !== inputBuscarVenta.value) {
                    e.preventDefault();
                    const venta = ventas.find(v => formatearVenta(v) === inputSugeridaVenta.value);
                    if (venta) {
                        inputBuscarVenta.value = formatearVenta(venta);
                        inputSugeridaVenta.value = '';
                        hiddenVentaId.value = venta.id;
                        listaVentas.classList.add('hidden');
                        await llenarCamposVenta(venta);
                    }
                }
            }
        });

        // Ocultar lista si clic afuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.relative')) listaVentas.classList.add('hidden');
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function setupFileLabel(inputId, labelId) {
            const input = document.getElementById(inputId);
            const label = document.getElementById(labelId);
            if (!input || !label) return;

            input.addEventListener('change', function() {
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
        }

        // DNI
        setupFileLabel('copia_dni', 'dni-file-label');
        // Voucher en registro docente (primer pago / venta)
        setupFileLabel('voucher_docente', 'voucher-docente-label');
        // Voucher en registro de cuotas / pagos posteriores
        setupFileLabel('voucher_pago', 'voucher-pago-label');
    });
</script>


<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
