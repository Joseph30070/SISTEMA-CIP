<?php
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR', 'ADMISION']);

require __DIR__ . '/../config/db.php';

$title  = "Consulta General";
$active = "consulta";

ob_start();

// ===============================
// CAPTURA DE PARÁMETROS
// ===============================
$filtro = $_GET['filtro'] ?? 'todo';
$busqueda = trim($_GET['busqueda'] ?? '');
$vista = $_GET['vista'] ?? 'resumido'; // resúmido por defecto

$paramsVentas = [];
$paramsCuotas = [];
$paramsResumen = [];

// ===============================
// CONSULTA DE VENTAS
// ===============================
$sqlVentas = "
SELECT 
    s.id AS id_venta,                              
    CONCAT(t.nombres, ' ', t.apellidos) AS docente,
    s.tipo_transaccion,
    c.nombre AS tipo_de_certificacion,
    pgr.nombre_programa AS programa,
    s.certificado,
    s.precio_programa,
    s.proceso_certificacion, 
    s.mencion,
    s.modalidad,
    s.inicio_programa,
    s.obs_programa,
    a.nombre_completo AS asesor,
    s.created_at AS fecha_registro_venta
FROM ventas s
INNER JOIN docentes t ON s.teacher_id = t.id
LEFT JOIN asesores a ON s.advisor_id = a.id
LEFT JOIN tipo_certificacion c ON s.curso_id = c.id
LEFT JOIN programas pgr ON s.programa_id = pgr.id
WHERE 1=1
AND s.deleted_at IS NULL
AND t.deleted_at IS NULL
AND c.deleted_at IS NULL
AND pgr.deleted_at IS NULL
";

if ($busqueda !== '') {
    $sqlVentas .= " AND (
        LOWER(t.nombres) LIKE LOWER(:busqueda) OR
        LOWER(t.apellidos) LIKE LOWER(:busqueda) OR
        LOWER(CONCAT(t.nombres,' ',t.apellidos)) LIKE LOWER(:busqueda) OR
        LOWER(pgr.nombre_programa) LIKE LOWER(:busqueda) OR
        LOWER(c.nombre) LIKE LOWER(:busqueda) OR
        LOWER(a.nombre_completo) LIKE LOWER(:busqueda) OR
        LOWER(s.proceso_certificacion) LIKE LOWER(:busqueda) OR
        LOWER(s.mencion) LIKE LOWER(:busqueda) OR
        LOWER(s.modalidad) LIKE LOWER(:busqueda) OR
        LOWER(s.certificado) LIKE LOWER(:busqueda) OR
        LOWER(s.obs_programa) LIKE LOWER(:busqueda) OR
        LOWER(s.tipo_transaccion) LIKE LOWER(:busqueda) OR
        CAST(s.precio_programa AS CHAR) LIKE :busqueda OR
        DATE_FORMAT(s.inicio_programa, '%Y-%m-%d') LIKE :busqueda OR
        DATE_FORMAT(s.created_at, '%Y-%m-%d %H:%i:%s') LIKE :busqueda
    )";

    $paramsVentas[':busqueda'] = "%$busqueda%";
}

$sqlVentas .= " ORDER BY s.created_at DESC";
$stmtVentas = $pdo->prepare($sqlVentas);
$stmtVentas->execute($paramsVentas);
$ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// CONSULTA DE CUOTAS / PAGOS
// ===============================
$sqlCuotas = "
SELECT 
    CONCAT(t.nombres, ' ', t.apellidos) AS docente,
    p.tipo_pago,
    p.monto_total AS monto_pagado,
    p.fecha_pago,
    p.codigo_operacion,
    p.titular_pago,
    p.voucher_path AS voucher,
    p.forma_pago,
    p.banco,
    p.created_at AS fecha_registro_pago
FROM pagos p
INNER JOIN ventas s ON p.sale_id = s.id
INNER JOIN docentes t ON s.teacher_id = t.id
WHERE 1=1
AND p.deleted_at IS NULL
AND s.deleted_at IS NULL
AND t.deleted_at IS NULL
";

if ($busqueda !== '') {
    $sqlCuotas .= " AND (
        LOWER(t.nombres) LIKE :busqueda_cuotas OR
        LOWER(t.apellidos) LIKE :busqueda_cuotas OR
        LOWER(CONCAT(t.nombres, ' ', t.apellidos)) LIKE :busqueda_cuotas OR
        LOWER(p.tipo_pago) LIKE :busqueda_cuotas OR
        LOWER(p.titular_pago) LIKE :busqueda_cuotas OR
        LOWER(p.codigo_operacion) LIKE :busqueda_cuotas OR
        LOWER(p.forma_pago) LIKE :busqueda_cuotas OR
        LOWER(p.banco) LIKE :busqueda_cuotas OR
        CAST(p.monto_total AS CHAR) LIKE :busqueda_cuotas OR
        DATE_FORMAT(p.fecha_pago, '%Y-%m-%d') LIKE :busqueda_cuotas OR
        DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s') LIKE :busqueda_cuotas OR
        LOWER(p.voucher_path) LIKE :busqueda_cuotas
    )";

    $paramsCuotas[':busqueda_cuotas'] = "%$busqueda%";
}


$sqlCuotas .= " ORDER BY p.fecha_pago DESC";
$stmtCuotas = $pdo->prepare($sqlCuotas);
$stmtCuotas->execute($paramsCuotas);
$cuotas = $stmtCuotas->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// CONSULTA RESUMEN DE CUOTAS POR DOCENTE
// ===============================
$condiciones = [
    "p.tipo_pago != 'CONTADO'",
    "p.deleted_at IS NULL",
    "s.deleted_at IS NULL",
    "t.deleted_at IS NULL",
    "c.deleted_at IS NULL",
    "pgr.deleted_at IS NULL"
];

if (!empty($busqueda)) {
    $condiciones[] = "(
        LOWER(CONCAT(t.nombres, ' ', t.apellidos)) LIKE LOWER(:busqueda)
        OR LOWER(pgr.nombre_programa) LIKE LOWER(:busqueda)
        OR LOWER(c.nombre) LIKE LOWER(:busqueda) 
        OR LOWER(p.tipo_pago) LIKE LOWER(:busqueda)
    )";
    $paramsResumen[':busqueda'] = "%$busqueda%";
}

// Detectar cuota específica
$columnaCuotaBuscada = null;
if (!empty($busqueda)) {
    $busq = strtolower($busqueda);
    if (strpos($busq, 'cuota #1') !== false || strpos($busq, 'cuota 1') !== false) $columnaCuotaBuscada = 'cuota1';
    elseif (strpos($busq, 'cuota #2') !== false || strpos($busq, 'cuota 2') !== false) $columnaCuotaBuscada = 'cuota2';
    elseif (strpos($busq, 'cuota #3') !== false || strpos($busq, 'cuota 3') !== false) $columnaCuotaBuscada = 'cuota3';
    elseif (strpos($busq, 'cuota #4') !== false || strpos($busq, 'cuota 4') !== false) $columnaCuotaBuscada = 'cuota4';
}

// Columnas dinámicas
$columnasResumen = "
      CONCAT(t.nombres, ' ', t.apellidos) AS docente, 
      c.nombre AS tipo_de_certificacion,
      pgr.nombre_programa AS programa
      ";

if ($columnaCuotaBuscada !== null) {
    $columnasResumen .= ", 
    SUM(CASE WHEN p.tipo_pago LIKE '%".substr($columnaCuotaBuscada,-1)."%' THEN p.monto_total ELSE 0 END) AS $columnaCuotaBuscada";
} else {
    $columnasResumen .= ",
        SUM(CASE WHEN p.tipo_pago LIKE '%1%' THEN p.monto_total ELSE 0 END) AS cuota1,
        SUM(CASE WHEN p.tipo_pago LIKE '%2%' THEN p.monto_total ELSE 0 END) AS cuota2,
        SUM(CASE WHEN p.tipo_pago LIKE '%3%' THEN p.monto_total ELSE 0 END) AS cuota3,
        SUM(CASE WHEN p.tipo_pago LIKE '%4%' THEN p.monto_total ELSE 0 END) AS cuota4,
        COALESCE(s.precio_programa, 0) AS total_a_pagar,
        SUM(p.monto_total) AS total_pagado,
        COALESCE(s.precio_programa, 0) - SUM(p.monto_total) AS resta
    ";
}

$sqlResumen = "
    SELECT $columnasResumen
    FROM pagos p
    INNER JOIN ventas s ON p.sale_id = s.id
    INNER JOIN docentes t ON s.teacher_id = t.id
    LEFT JOIN programas pgr ON s.programa_id = pgr.id
    LEFT JOIN tipo_certificacion c ON s.curso_id = c.id
    WHERE ".implode(' AND ', $condiciones)."
    GROUP BY s.id, t.id, t.nombres, t.apellidos, pgr.nombre_programa, s.precio_programa,c.nombre,s.precio_programa
    ORDER BY t.apellidos ASC, pgr.nombre_programa ASC
";

$stmtResumen = $pdo->prepare($sqlResumen);
$stmtResumen->execute($paramsResumen);
$resumen = $stmtResumen->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 class="text-3xl font-bold text-gray-800 mb-6">Consulta General</h2>

<div class="bg-white p-6 rounded-lg shadow mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end" action="consulta.php">
        
        <input type="hidden" name="vista" value="<?= $vista ?>">

        <div class="md:col-span-4 flex flex-col md:flex-row gap-4 items-end">
        
            <div class="w-full md:w-1/4">
                <label for="filtro" class="block text-sm font-medium mb-1">Filtrar:</label>
                <select name="filtro" onchange="this.form.submit()" class="border rounded px-3 py-2 w-full focus:ring-2 focus:ring-teal-500">
                    <option value="todo" <?= $filtro==='todo'?'selected':'' ?>>Todo</option>
                    <option value="ventas" <?= $filtro==='ventas'?'selected':'' ?>>Ventas</option>
                    <option value="cuotas" <?= $filtro==='cuotas'?'selected':'' ?>>Cuotas</option>
                </select>
            </div>

            <div class="w-full md:w-3/4">
                <label for="busqueda" class="block text-sm font-medium mb-1">Buscar</label>

                <div class="relative w-full">
                    <input id="busqueda_fantasma"
                        class="border rounded px-3 py-2 w-full absolute top-0 left-0 text-gray-400 pointer-events-none"
                        style="background: transparent; z-index:1"
                        tabindex="-1">

                    <input 
                        id="busqueda_real"
                        name="busqueda"
                        type="text"
                        value="<?= htmlspecialchars($busqueda) ?>"
                        placeholder="Docente, programa, banco..."
                        class="border rounded px-3 py-2 w-full bg-transparent"
                        autocomplete="off"
                        style="position: relative; z-index:2"
                    >

                    <ul id="lista_sugerencias" 
                        class="border bg-white hidden absolute mt-1 rounded shadow max-h-60 overflow-y-auto w-full z-50">
                    </ul>
                </div>
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700 transition">Buscar</button>
                <a href="consulta.php?filtro=<?= urlencode($filtro) ?>&vista=<?= urlencode($vista) ?>" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">Limpiar</a>
            </div>
        </div>
        <div class="md:col-span-2 flex flex-col md:flex-row md:items-end gap-2 justify-end">
            <?php $nuevaVista = $vista==='resumido'?'todo':'resumido'; ?>
            
            <a href="consulta.php?filtro=<?= $filtro ?>&busqueda=<?= urlencode($busqueda) ?>&vista=<?= $nuevaVista ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition text-center w-full md:w-auto">
                <?= $vista==='resumido'?'Ver Tabla Completa':'Ver Tabla Resumida' ?>
            </a>

            <a href="../process/exportar_pdf.php?filtro=<?= $filtro ?>&busqueda=<?= urlencode($busqueda) ?>&vista=<?= $vista ?>" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition text-center w-full md:w-auto">
                Exportar PDF
            </a>
            <a href="../process/exportar_bd.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition text-center w-full md:w-auto">
                Exportar Base de Datos
            </a>

        </div>
    </form>
</div>

<?php if ($filtro==='ventas' || $filtro==='todo'): ?>
<!-- TABLA VENTAS -->
<div class="bg-white shadow rounded-lg p-6 overflow-x-auto mb-10">
  <h2 class="text-xl font-semibold mb-4">Resultados de Ventas</h2>

  <?php if(empty($ventas)): ?>
    <p class="text-gray-500 text-center">No se encontraron ventas.</p>
  <?php else: ?>
    <table class="min-w-full border border-gray-200 rounded-lg">
      <thead class="bg-gray-100">
        <tr>
          <?php 
          $resumidoColsVentas = [
            'docente','tipo_transaccion','tipo_de_certificacion',
            'programa','certificado','precio_programa','proceso_certificacion'
          ];

          foreach(array_keys($ventas[0]) as $col):
              if ($col === 'id_venta') continue;

              if($vista==='todo' || in_array($col,$resumidoColsVentas)):
          ?>
            <th class="px-4 py-2 border"><?= htmlspecialchars(ucwords(str_replace('_',' ',$col))) ?></th>
          <?php 
              endif;
          endforeach;

          if ($vista === 'todo'): ?>
            <th class="px-4 py-2 border">Acciones</th>
          <?php endif; ?>
        </tr>
      </thead>

      <tbody>
        <?php foreach($ventas as $fila): ?>
          <tr class="hover:bg-gray-50 text-center">

            <?php foreach($fila as $col => $valor):
                if ($col === 'id_venta') continue;

                if($vista==='todo' || in_array($col,$resumidoColsVentas)):

                  // ----------- COLOR PARA PROCESO_CERTIFICACION -----------
                  if ($col === 'proceso_certificacion'):
                      $colorClass = 'bg-gray-200 text-gray-700';

                      if ($valor === 'Aprobado') {
                          $colorClass = 'bg-green-100 text-green-800';
                      } elseif ($valor === 'En Proceso') {
                          $colorClass = 'bg-yellow-100 text-yellow-700';
                      } elseif ($valor === 'Rechazado') {
                          $colorClass = 'bg-red-100 text-red-700';
                      }
            ?>
                <td class="px-4 py-2 border">
                  <span class="px-2 py-1 rounded-lg text-xs font-medium <?= $colorClass ?>">
                    <?= htmlspecialchars($valor) ?>
                  </span>
                </td>

            <?php 
                  // ----------- OTRAS COLUMNAS NORMALES -----------
                  else: 
            ?>
                <td class="px-4 py-2 border"><?= htmlspecialchars($valor) ?></td>
            <?php 
                  endif;
                endif;
              endforeach; 
            ?>

            <?php if ($vista === 'todo'): ?>
              <td class="px-4 py-2 border">
                <a href="editar_proceso_certificacion.php?id=<?= urlencode(trim($fila['id_venta'])) ?>&vista=<?= urlencode(trim($vista)) ?>&filtro=<?= urlencode(trim($filtro)) ?>&busqueda=<?= urlencode(trim($busqueda)) ?>" class="inline-block px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                  Editar
                </a>
              </td>
            <?php endif; ?>

          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php endif; ?>


<?php if ($filtro==='cuotas' || $filtro==='todo'): ?>
  <!-- TABLA CUOTAS/PAGOS -->
  <div class="bg-white shadow rounded-lg p-6 overflow-x-auto mb-10">
    <h2 class="text-xl font-semibold mb-4">Resultados de Cuotas y Pagos</h2>

    <?php if(empty($cuotas)): ?>
      <p class="text-gray-500 text-center">No se encontraron cuotas o pagos.</p>
    <?php else: ?>
      <table class="min-w-full border border-gray-200 rounded-lg">
        <thead class="bg-gray-100">
          <tr>
            <?php 
            $resumidoColsCuotas = ['docente','tipo_pago','monto_pagado','fecha_pago'];
            foreach(array_keys($cuotas[0]) as $col):
                if($vista==='todo'||in_array($col,$resumidoColsCuotas)):
            ?>
              <th class="px-4 py-2 border"><?= htmlspecialchars(ucwords(str_replace('_',' ',$col))) ?></th>
            <?php endif; endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach($cuotas as $fila): ?>
            <tr class="hover:bg-gray-50 text-center">
              <?php foreach($fila as $col=>$valor):
                  if($vista==='todo'||in_array($col,$resumidoColsCuotas)):
              ?>
                <td class="px-4 py-2 border">
                  <?php if(($col==='voucher_path'||$col==='voucher') && !empty($valor)):
                    $ext=strtolower(pathinfo($valor,PATHINFO_EXTENSION));
                    $texto=($ext==='pdf')?'Ver PDF':(in_array($ext,['jpg','jpeg','png'])?'Ver Imagen':'Ver Archivo');
                  ?>
                    <a href="<?= htmlspecialchars($valor) ?>" target="_blank" class="text-blue-600 hover:underline"><?= $texto ?></a>
                  <?php else: ?>
                    <?= htmlspecialchars($valor) ?>
                  <?php endif; ?>
                </td>
              <?php endif; endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- RESUMEN DE CUOTAS POR DOCENTE-->
  <div class="bg-white shadow rounded-lg p-6 overflow-x-auto mb-10">
    <h2 class="text-xl font-semibold mb-4">Resumen de Cuotas por Docente</h2>

    <?php if(empty($resumen)): ?>
      <p class="text-gray-500 text-center">No hay datos de cuotas registradas.</p>
    <?php else: ?>
      <table class="min-w-full border border-gray-200 rounded-lg">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-4 py-2 border">Docente</th>
            <th class="px-4 py-2 border">Tipo De Certificación</th>
            <th class="px-4 py-2 border">Programa</th>

            <?php if($columnaCuotaBuscada !== null): ?>
              <th class="px-4 py-2 border"><?= strtoupper($columnaCuotaBuscada) ?></th>
            <?php else: ?>
              <th class="px-4 py-2 border">Cuota #1</th>
              <th class="px-4 py-2 border">Cuota #2</th>
              <th class="px-4 py-2 border">Cuota #3</th>
              <th class="px-4 py-2 border">Cuota #4</th>
              <th class="px-4 py-2 border">Total a Pagar</th>
              <th class="px-4 py-2 border">Total Pagado</th>
              <th class="px-4 py-2 border">Resta</th>
            <?php endif; ?>
          </tr>
        </thead>

        <tbody>
          <?php foreach($resumen as $fila): ?>
            <tr class="hover:bg-gray-50 text-center">
              <td class="border px-4 py-2"><?= htmlspecialchars($fila['docente']) ?></td>
              <td class="border px-4 py-2"><?= htmlspecialchars($fila['tipo_de_certificacion']) ?></td>
              <td class="border px-4 py-2"><?= htmlspecialchars($fila['programa']) ?></td>

              <?php if($columnaCuotaBuscada !== null): ?>
                <td class="border px-4 py-2"><?= number_format($fila[$columnaCuotaBuscada],2) ?></td>

              <?php else: ?>
                <td class="border px-4 py-2"><?= number_format($fila['cuota1'],2) ?></td>
                <td class="border px-4 py-2"><?= number_format($fila['cuota2'],2) ?></td>
                <td class="border px-4 py-2"><?= number_format($fila['cuota3'],2) ?></td>
                <td class="border px-4 py-2"><?= number_format($fila['cuota4'],2) ?></td>
                <td class="border px-4 py-2"><?= number_format($fila['total_a_pagar'],2) ?></td>
                <td class="border px-4 py-2"><?= number_format($fila['total_pagado'],2) ?></td>
                <td class="border px-4 py-2"><?= number_format($fila['resta'],2) ?></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>

      </table>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- SCRIPT COMPLETO -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ============================================
    // 1. SUGERENCIAS DESDE PHP (CONDICIONAL)
    // ============================================
    const sugerencias = [
        <?php
            // Asegúrate de que la variable $filtro esté disponible aquí (asumo que sí lo está)
            $filtro_actual = $filtro ?? 'todo'; // Usamos 'todo' como valor por defecto
            $items = [];

            // --- LÓGICA CONDICIONAL DE SUGERENCIAS ---

            // Si el filtro es 'todo' o 'ventas', recolectamos sugerencias de ventas.
            if ($filtro_actual === 'todo' || $filtro_actual === 'ventas') {
                foreach ($ventas as $v) {
                    if (!empty($v['docente'])) $items[] = $v['docente'];
                    if (!empty($v['programa'])) $items[] = $v['programa'];
                    if (!empty($v['tipo_de_certificacion'])) $items[] = $v['tipo_de_certificacion'];
                    if (!empty($v['certificado'])) $items[] = $v['certificado'];
                    if (!empty($v['tipo_transaccion'])) $items[] = $v['tipo_transaccion'];
                    if (!empty($v['asesor'])) $items[] = $v['asesor'];
                    if (!empty($v['proceso_certificacion'])) $items[] = $v['proceso_certificacion'];
                    if (!empty($v['mencion'])) $items[] = $v['mencion'];
                    if (!empty($v['modalidad'])) $items[] = $v['modalidad'];
                    if (!empty($v['obs_programa'])) $items[] = $v['obs_programa'];
                    if (!empty($v['precio_programa'])) $items[] = $v['precio_programa'];
                    if (!empty($v['inicio_programa'])) $items[] = $v['inicio_programa'];
                    if (!empty($v['fecha_registro_venta'])) $items[] = $v['fecha_registro_venta'];
                }
            }

            // Si el filtro es 'todo' o 'cuotas', recolectamos sugerencias de cuotas.
            if ($filtro_actual === 'todo' || $filtro_actual === 'cuotas') {
                foreach ($cuotas as $c) {
                    if (!empty($c['docente'])) $items[] = $c['docente'];
                    if (!empty($c['banco'])) $items[] = $c['banco'];
                    if (!empty($c['forma_pago'])) $items[] = $c['forma_pago'];
                    if (!empty($c['tipo_pago'])) $items[] = $c['tipo_pago'];
                    if (!empty($c['codigo_operacion'])) $items[] = $c['codigo_operacion'];
                    if (!empty($c['titular_pago'])) $items[] = $c['titular_pago'];
                    if (!empty($c['monto_pagado'])) $items[] = $c['monto_pagado'];
                    if (!empty($c['fecha_pago'])) $items[] = $c['fecha_pago'];
                    if (!empty($c['fecha_registro_pago'])) $items[] = $c['fecha_registro_pago'];
                }
            }

            // 1. Eliminar duplicados
            // 2. Filtrar vacíos
            $items = array_filter(array_unique($items));

            // Imprimir para Javascript
            foreach ($items as $item) {
                echo '"' . addslashes($item) . '",';
            }
        ?>
    ];

    // ============================================
    // 2. ELEMENTOS DEL DOM
    // ============================================
    const inputBuscar = document.getElementById('busqueda_real');
    const inputFantasma = document.getElementById('busqueda_fantasma');
    const lista = document.getElementById('lista_sugerencias');

    let selectedIndex = -1;


    // ============================================
    // 3. FILTRADO + FANTASMA + AUTO-RESET
    // ============================================
    inputBuscar.addEventListener('input', function() {
        const texto = this.value.toLowerCase();

        //LÓGICA DE AUTO-RESET
        if (!texto) {
            inputFantasma.value = '';
            lista.classList.add('hidden');
            lista.innerHTML = '';
            selectedIndex = -1;
            
            // Envía el formulario para que PHP recargue la página SIN el parámetro 'busqueda',
            // mostrando así todos los resultados (estado inicial).
            this.form.submit(); 
            
            // Ya no se necesita 'return' porque el submit recarga la página.
            return; 
        }

        const coincidencias = sugerencias.filter(s =>
            s.toLowerCase().startsWith(texto)
        );

        // Fantasma
        if (coincidencias.length > 0) {
            const restante = coincidencias[0].substring(this.value.length);
            inputFantasma.value = this.value + restante;
            inputFantasma.style.color = "rgba(0,0,0,0.3)";
        } else {
            inputFantasma.value = '';
        }

        // Lista
        if (coincidencias.length > 0) {
            lista.innerHTML = coincidencias
                .map(s => `<li class="px-3 py-2 cursor-pointer hover:bg-gray-100">${s}</li>`)
                .join('');

            lista.classList.remove('hidden');
            selectedIndex = -1;
        } else {
            lista.innerHTML = '';
            lista.classList.add('hidden');
        }
    });


    // ============================================
    // 4. CLICK EN SUGERENCIA
    // ============================================
    lista.addEventListener('click', e => {
        if (e.target.tagName === 'LI') {
            inputBuscar.value = e.target.textContent;
            inputFantasma.value = '';
            lista.classList.add('hidden');

            // Enviar búsqueda automáticamente
            inputBuscar.form.submit();
        }
    });


    // ============================================
    // 5. TECLADO (TAB, flechas, Enter)
    // ============================================
    inputBuscar.addEventListener('keydown', e => {
        const items = lista.querySelectorAll('li');

        // TAB → autocompletar
        if (e.key === "Tab") {
            if (inputFantasma.value && inputFantasma.value !== inputBuscar.value) {
                e.preventDefault();
                inputBuscar.value = inputFantasma.value;
                lista.classList.add('hidden');

                // Enviar búsqueda automáticamente
                inputBuscar.form.submit();
            }
            return;
        }

        if (!items.length) return;

        // ↓
        if (e.key === "ArrowDown") {
            e.preventDefault();
            selectedIndex = (selectedIndex + 1) % items.length;
            items.forEach((li,i)=>li.classList.toggle('bg-gray-200', i===selectedIndex));
        }

        // ↑
        else if (e.key === "ArrowUp") {
            e.preventDefault();
            selectedIndex = (selectedIndex - 1 + items.length) % items.length;
            items.forEach((li,i)=>li.classList.toggle('bg-gray-200', i===selectedIndex));
        }

        // ENTER → seleccionar y enviar
        else if (e.key === "Enter" && selectedIndex >= 0) {
            e.preventDefault();
            const li = items[selectedIndex];
            inputBuscar.value = li.textContent;
            inputFantasma.value = '';
            lista.classList.add('hidden');
            selectedIndex = -1;

            // Enviar búsqueda automáticamente
            inputBuscar.form.submit();
        }
    });


    // ============================================
    // 6. OCULTAR LISTA AL CLIC FUERA
    // ============================================
    document.addEventListener('click', function(e) {
        if (!lista.contains(e.target) && e.target !== inputBuscar) {
            lista.classList.add('hidden');
        }
    });

});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>