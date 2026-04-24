<?php
// public/home.php
require_once __DIR__ . '/../config/auth.php';
require __DIR__ . '/../config/db.php';

$title  = 'Inicio';
$active = 'home';
$ROLE   = strtoupper($_SESSION['role'] ?? '');

// BLOQUEO / REDIRECCIÓN POR ROL (ASESOR)
if ($ROLE === 'ASESOR') {
  header('Location: ventana.php');
  exit;
}

//  AÑO ACTUAL (puede cambiar por GET)
$anioActual = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

//  AÑO PARA EL GRÁFICO DE ASESOR (puede cambiar por GET)
$anioAsesor = isset($_GET['yearAsesor'])? (int)$_GET['yearAsesor']: (int)date('Y');
$yearAsesorParam  = "&yearAsesor=$anioAsesor";

//  MES SELECCIONADO (puede cambiar por GET)
$mesSeleccionado = isset($_GET['month']) && $_GET['month'] !== '' ? (int)$_GET['month'] : null;

//  MES PARA EL GRÁFICO DE ASESOR (puede cambiar por GET)
$mesAsesor = isset($_GET['monthAsesor']) && $_GET['monthAsesor'] !== ''? (int)$_GET['monthAsesor']: null;
$monthAsesorParam = $mesAsesor ? "&monthAsesor=$mesAsesor" : "";

//  NIVEL SELECCIONADO (puede cambiar por GET)
$nivelSeleccionado = $_GET['nivel'] ?? 'PRIMARIA';

//  FUNCIONES
function mes_corto_es($n) {
  static $nombres = [1=>'Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
  return $nombres[(int)$n] ?? '';
}

//  KPIs
// Total docentes registrados (filtrado por mes si aplica)
$paramsDoc = [$anioActual];

$sqlDocentes = "
    SELECT COUNT(*) 
    FROM docentes
    WHERE deleted_at IS NULL
";

$stmt = $pdo->query($sqlDocentes);
$docentesMes = (int)$stmt->fetchColumn();

// Total de pagos (filtrado por mes si aplica)
$params = [$anioActual];
$sqlPagos = "
    SELECT COUNT(*) 
    FROM pagos 
    WHERE deleted_at IS NULL
    AND YEAR(fecha_pago) = ?
";
if ($mesSeleccionado) {
    $sqlPagos .= " AND MONTH(fecha_pago) = ?";
    $params[] = $mesSeleccionado;
}
$stmt = $pdo->prepare($sqlPagos);
$stmt->execute($params);
$pagosMes = (int)$stmt->fetchColumn();

// Total recaudado
$params = [$anioActual];
$sqlRecaudado = "
    SELECT COALESCE(SUM(monto_total),0) 
    FROM pagos 
    WHERE deleted_at IS NULL
    AND YEAR(fecha_pago) = ?
";
if ($mesSeleccionado) {
    $sqlRecaudado .= " AND MONTH(fecha_pago) = ?";
    $params[] = $mesSeleccionado;
}
$stmt = $pdo->prepare($sqlRecaudado);
$stmt->execute($params);
$recaudadoMes = (float)$stmt->fetchColumn();

// Total ventas (filtrado por mes)
$params = [$anioActual];
$sqlVentas = "
SELECT COUNT(*) 
FROM ventas s
WHERE s.deleted_at IS NULL
AND YEAR(s.created_at) = ?
AND (s.tipo_transaccion = 'VENTA' OR s.tipo_transaccion = 'CUOTAS')
";

if ($mesSeleccionado) {
    $sqlVentas .= " AND MONTH(s.created_at) = ?";
    $params[] = $mesSeleccionado;
}

$stmt = $pdo->prepare($sqlVentas);
$stmt->execute($params);
$ventasMes = (int)$stmt->fetchColumn();

//  DATOS DEL GRÁFICO (Ventas + Pagos)
$labels = [];
for ($i = 1; $i <= 12; $i++) {
    $labels[] = mes_corto_es($i);
}

// ----- SERIES DE VENTAS -----
$serieVentasContado = array_fill(0, 12, 0);
$serieVentasCuotas  = array_fill(0, 12, 0);

$rowsSales = $pdo->prepare("
    SELECT 
        MONTH(s.created_at) AS mes,
        COUNT(DISTINCT CASE WHEN s.tipo_transaccion = 'VENTA' THEN s.id END) AS contado,
        COUNT(DISTINCT CASE WHEN s.tipo_transaccion = 'CUOTAS' THEN s.id END) AS cuotas
    FROM ventas s
    LEFT JOIN pagos p ON p.sale_id = s.id AND p.deleted_at IS NULL
    WHERE s.deleted_at IS NULL
    AND YEAR(s.created_at) = ?
    GROUP BY mes
    ORDER BY mes
");
$rowsSales->execute([$anioActual]);

foreach ($rowsSales->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $idx = (int)$r['mes'] - 1;
    $serieVentasContado[$idx] = (int)$r['contado'];
    $serieVentasCuotas[$idx]  = (int)$r['cuotas'];
}

// ----- SERIES DE PAGOS -----
// Montos recaudados (en dinero)
$seriePagosContado = array_fill(0, 12, 0);
$seriePagosCuotas  = array_fill(0, 12, 0);

// Cantidades de pagos (en número de registros)
$serieCantPagosContado = array_fill(0, 12, 0);
$serieCantPagosCuotas  = array_fill(0, 12, 0);

$rowsPagos = $pdo->prepare("
    SELECT 
        MONTH(p.created_at) AS mes,
        SUM(CASE WHEN tipo_pago = 'CONTADO' THEN monto_total ELSE 0 END) AS monto_contado,
        SUM(CASE WHEN tipo_pago LIKE 'CUOTA%' THEN monto_total ELSE 0 END) AS monto_cuotas,
        COUNT(CASE WHEN tipo_pago = 'CONTADO' THEN 1 END) AS cant_contado,
        COUNT(CASE WHEN tipo_pago LIKE 'CUOTA%' THEN 1 END) AS cant_cuotas
    FROM pagos p
    WHERE p.deleted_at IS NULL
    AND YEAR(p.created_at) = ?
    GROUP BY mes
    ORDER BY mes
");
$rowsPagos->execute([$anioActual]);

foreach ($rowsPagos->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $idx = (int)$r['mes'] - 1;

    // Monto total recaudado
    $seriePagosContado[$idx] = (float)$r['monto_contado'];
    $seriePagosCuotas[$idx]  = (float)$r['monto_cuotas'];

    // Cantidad de pagos
    $serieCantPagosContado[$idx] = (int)$r['cant_contado'];
    $serieCantPagosCuotas[$idx]  = (int)$r['cant_cuotas'];
}

$serieDocentes = array_fill(0, 12, 0);

$stmt = $pdo->prepare("
    SELECT MONTH(created_at) AS mes, COUNT(*) AS total
    FROM docentes
    WHERE deleted_at IS NULL
    AND YEAR(created_at) = :anio
    GROUP BY MONTH(created_at)
");
$stmt->execute(['anio' => $anioActual]);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($resultados as $row) {
    $indice = (int)$row['mes'] - 1; // Enero = 0
    $serieDocentes[$indice] = (int)$row['total'];
}

// ------------------------------------------------------------------
//  SE AÑADIÓ ESTO NUEVO: VENTAS POR ASESOR (CANTIDAD Y MONTO)
// ------------------------------------------------------------------
// ------------------------------------------------------------------
//  VENTAS POR ASESOR (CANTIDAD Y MONTO)
// ------------------------------------------------------------------
$paramsAsesor = [$anioAsesor];

$sqlAsesor = "
    SELECT 
        a.nombre_completo AS asesor,
        COUNT(DISTINCT s.id) AS num_ventas,
        COALESCE(SUM(p.monto_total),0) AS monto_vendido
    FROM asesores a
    LEFT JOIN ventas s 
        ON s.advisor_id = a.id 
        AND s.deleted_at IS NULL
    LEFT JOIN pagos p 
        ON p.sale_id = s.id
        AND p.deleted_at IS NULL
    WHERE s.id IS NOT NULL
      AND YEAR(s.created_at) = ?
";

if ($mesAsesor) {
    $sqlAsesor .= " AND MONTH(s.created_at) = ?";
    $paramsAsesor[] = $mesAsesor;
}

$sqlAsesor .= "
    GROUP BY a.id
    HAVING num_ventas > 0 OR monto_vendido > 0
    ORDER BY monto_vendido DESC
";

$stmt = $pdo->prepare($sqlAsesor);
$stmt->execute($paramsAsesor);
$asesorRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$asesoresLabels = [];
$asesoresVentas = [];
$asesoresMontos = [];

foreach ($asesorRows as $row) {
    $asesoresLabels[] = $row['asesor'];
    $asesoresVentas[] = (int)$row['num_ventas'];
    $asesoresMontos[] = (float)$row['monto_vendido'];
}

$asesoresLabelsJson = json_encode($asesoresLabels, JSON_UNESCAPED_UNICODE);
$asesoresVentasJson = json_encode($asesoresVentas);
$asesoresMontosJson = json_encode($asesoresMontos);

// ------------------------------------------------------------------
//  FIN BLOQUE NUEVO: VENTAS POR ASESOR
// ------------------------------------------------------------------

//  CURSOS MÁS ESCOGIDOS POR LOS PROFESORES
$stmt = $pdo->prepare("
    SELECT 
        c.nombre AS curso,
        COUNT(s.id) AS total
    FROM ventas s
    INNER JOIN tipo_certificacion c ON c.id = s.curso_id
    WHERE s.deleted_at IS NULL
    GROUP BY c.id
    ORDER BY total DESC
    LIMIT 5
");
$stmt->execute();
$cursosRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cursosLabels = [];
$cursosValues = [];

foreach ($cursosRows as $r) {
    $cursosLabels[] = $r['curso'];
    $cursosValues[] = $r['total'];
}

$cursosLabelsJson = json_encode($cursosLabels, JSON_UNESCAPED_UNICODE);
$cursosValuesJson = json_encode($cursosValues);

//  PROFESORES CON MÁS CURSOS REGISTRADOS
$stmt = $pdo->prepare("
    SELECT 
        CONCAT(t.nombres, ' ', t.apellidos) AS docente,
        COUNT(s.id) AS total_cursos
    FROM ventas s
    INNER JOIN docentes t ON t.id = s.teacher_id
    WHERE s.deleted_at IS NULL
    GROUP BY t.id
    ORDER BY total_cursos DESC
    LIMIT 5
");
$stmt->execute();
$profesoresRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$profesoresLabels = [];
$profesoresValues = [];

foreach ($profesoresRows as $r) {
    $profesoresLabels[] = $r['docente'];
    $profesoresValues[] = $r['total_cursos'];
}

$profesoresLabelsJson = json_encode($profesoresLabels, JSON_UNESCAPED_UNICODE);
$profesoresValuesJson = json_encode($profesoresValues);

// ------------------------------------------------------------------
//  SE AÑADIÓ ESTO NUEVO: PROGRAMAS MÁS VENDIDOS
// ------------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT 
        pr.nombre_programa AS programa,
        COUNT(s.id) AS total
    FROM ventas s
    INNER JOIN programas pr ON pr.id = s.programa_id
    WHERE s.deleted_at IS NULL
    GROUP BY pr.id
    ORDER BY total DESC
    LIMIT 5
");
$stmt->execute();
$programasRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$programasLabels = [];
$programasValues = [];

foreach ($programasRows as $r) {
    $programasLabels[] = $r['programa'];
    $programasValues[] = (int)$r['total'];
}

$programasLabelsJson = json_encode($programasLabels, JSON_UNESCAPED_UNICODE);
$programasValuesJson = json_encode($programasValues);

// ------------------------------------------------------------------
//  FIN BLOQUE NUEVO: PROGRAMAS MÁS VENDIDOS
// ------------------------------------------------------------------


//  DOCENTES (listado para ADMINISTRADOR)
$queryTeachers = "
  SELECT id, nombres, apellidos, dni, celular, email, departamento, provincia, distrito, nivel, copia_dni_path
  FROM docentes
  WHERE deleted_at IS NULL
  ORDER BY id ASC
";
$teachers = $pdo->query($queryTeachers);

// =============================
// PROFESORES POR NIVEL
// =============================
$stmt = $pdo->prepare("
    SELECT nivel, COUNT(*) AS total
    FROM docentes
    WHERE deleted_at IS NULL
    GROUP BY nivel
");
$stmt->execute();
$nivelesRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Siempre mostrar estos niveles
$nivelesLabels = ['INICIAL','PRIMARIA','SECUNDARIA'];
$nivelesValues = [];

foreach ($nivelesLabels as $nivel) {
    $found = false;
    foreach ($nivelesRows as $r) {
        if ($r['nivel'] === $nivel) {
            $nivelesValues[] = (int)$r['total'];
            $found = true;
            break;
        }
    }
    if (!$found) $nivelesValues[] = 0;
}

$nivelesLabelsJson = json_encode($nivelesLabels, JSON_UNESCAPED_UNICODE);
$nivelesValuesJson = json_encode($nivelesValues);

// =============================
// ESPECIALIDADES POR NIVEL
// =============================
$especialidadesPorNivel = [];
$profesoresPorEspecialidad = [];

foreach ($nivelesLabels as $nivel) {
    // Obtener especialidades y total de profesores por especialidad
    $stmt = $pdo->prepare("
        SELECT s.nombre AS especialidad, COUNT(t.id) AS total
        FROM especialidades s
        LEFT JOIN docente_especialidad ts ON ts.specialty_id = s.id
        LEFT JOIN docentes t ON t.id = ts.teacher_id 
            AND t.nivel = ?
            AND t.deleted_at IS NULL
        GROUP BY s.id
        ORDER BY s.nombre ASC
    ");
    $stmt->execute([$nivel]);
    $espRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $especialidadesPorNivel[$nivel] = $espRows;

    // Obtener lista de profesores por especialidad
    $stmt2 = $pdo->prepare("
    SELECT s.nombre AS especialidad, t.nombres, t.apellidos
    FROM especialidades s
    JOIN docente_especialidad ts ON ts.specialty_id = s.id
    JOIN docentes t ON t.id = ts.teacher_id
    WHERE t.nivel = ?
      AND t.deleted_at IS NULL
    ORDER BY s.nombre, t.apellidos, t.nombres
    ");
    $stmt2->execute([$nivel]);
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $profesoresPorEspecialidad[$nivel] = [];
    foreach ($rows as $r) {
        $esp = $r['especialidad'];
        if (!isset($profesoresPorEspecialidad[$nivel][$esp])) {
            $profesoresPorEspecialidad[$nivel][$esp] = [];
        }
        $profesoresPorEspecialidad[$nivel][$esp][] = $r['nombres'].' '.$r['apellidos'];
    }
}

$especialidadesPorNivelJson = json_encode($especialidadesPorNivel, JSON_UNESCAPED_UNICODE);
$profesoresPorEspecialidadJson = json_encode($profesoresPorEspecialidad, JSON_UNESCAPED_UNICODE);

//  VISTA
ob_start(); ?>

<!-- Encabezado -->
<div class="mb-6">
  <h2 class="text-3xl font-bold text-gray-800 mb-2">Bienvenido a CiipGestión</h2>
  <p class="text-sm text-gray-500">
    Resumen general <?= $mesSeleccionado ? 'del mes de '.mes_corto_es($mesSeleccionado).' del '.$anioActual : 'del año '.$anioActual ?>
  </p>
</div>

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

<!-- ================= ADMINISTRADOR: KPIs ================= -->
<?php if ($ROLE === 'ADMINISTRADOR'): ?>
   <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-2xl shadow p-5 border border-gray-100">
      <div class="text-xs uppercase tracking-wide text-gray-500">Docentes Registrados <?= $mesSeleccionado ? 'del mes' : 'del año' ?></div>
      <div class="mt-2 text-3xl font-semibold text-gray-900" id="kpiDocentes"><?= number_format($docentesMes) ?></div>
    </div>

    <div class="bg-white rounded-2xl shadow p-5 border border-gray-100">
      <div class="text-xs uppercase tracking-wide text-gray-500">Ventas <?= $mesSeleccionado ? 'del mes' : 'del año' ?></div>
      <div class="mt-2 text-3xl font-semibold text-indigo-600" id="kpiVentas"><?= number_format($ventasMes) ?></div>
    </div>

    <div class="bg-white rounded-2xl shadow p-5 border border-gray-100">
      <div class="text-xs uppercase tracking-wide text-gray-500">Pagos <?= $mesSeleccionado ? 'del mes' : 'del año' ?></div>
      <div class="mt-2 text-3xl font-semibold text-gray-900" id="kpiPagos"><?= number_format($pagosMes) ?></div>
    </div>

    <div class="bg-white rounded-2xl shadow p-5 border border-gray-100">
      <div class="text-xs uppercase tracking-wide text-gray-500">Recaudado <?= $mesSeleccionado ? 'del mes' : 'del año' ?></div>
      <div class="mt-2 text-3xl font-semibold text-green-600" id="kpiRecaudado">S/ <?= number_format($recaudadoMes,2) ?></div>
    </div>
  </div>


  <!-- ================= ADMINISTRADOR: Gráfico de barras: Ventas================= -->
<div class="w-full overflow-x-auto">
  <div class="bg-white min-w-[600px] rounded-2xl shadow p-6 border border-gray-100">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-4
            md:flex-nowrap md:items-center">
      <div>
        <h3 id="tituloGrafico" class="text-lg font-semibold text-gray-800">
          Ventas <?= $mesSeleccionado ? 'del mes de '.mes_corto_es($mesSeleccionado) : 'por mes' ?> - Año <?= $anioActual ?>
        </h3>
        <p class="text-xs text-gray-500">Contado · Cuotas</p>
      </div>
      <!-- BOTÓN PARA MOSTRAR/OCULTAR GRAFICO DE ASESOR -->
      <div class="flex flex-wrap gap-2 md:justify-end">

        <button id="btnToggleAsesor"
                class="px-3 py-1 bg-purple-500 hover:bg-purple-600 text-white rounded-lg text-sm font-medium">
          Ocultar ventas por asesor
        </button>

        <button id="toggleTipo" 
                class="px-3 py-1 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm font-medium">
          Ver por Pagos
        </button>

        <button id="btnVolver" style="display:none;" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm font-medium">
          Volver al año completo  
        </button>

        <?php if ($mesSeleccionado): ?>
          <a href="?year=<?= $anioActual ?><?= $yearAsesorParam ?><?= $monthAsesorParam ?>" 
            class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm font-medium">
            Volver al año completo
          </a>
        <?php else: ?>
            <a href="?year=<?= $anioActual - 1 ?><?= $yearAsesorParam ?><?= $monthAsesorParam ?>" 
              class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm font-medium">&larr; <?= $anioActual - 1 ?></a>
            <a href="?year=<?= $anioActual + 1 ?><?= $yearAsesorParam ?><?= $monthAsesorParam ?>" 
              class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm font-medium"><?= $anioActual + 1 ?> &rarr;</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="w-full h-[320px]">
        <canvas id="pagosChart"></canvas>
    </div>
  </div>
</div>

  <!-- ======================================================
       TARJETA "VENTAS POR ASESOR" (CON FECHA)
       ====================================================== -->
  <div id="cardVentasAsesor"
       class="bg-white rounded-2xl shadow p-6 border border-gray-100 mt-6">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-4
            md:flex-nowrap md:items-center">
      <div>
        <h3 class="text-lg font-semibold text-gray-800">
          Ventas por asesor
          <?= $mesAsesor
              ? '( '.mes_corto_es($mesAsesor).' '.$anioAsesor.' )'
              : '(Año '.$anioAsesor.')' ?>
        </h3>
        <p class="text-xs text-gray-500">
          Cantidad de ventas y monto vendido por asesor
        </p>
      </div>
      <!-- BOTONES PARA CAMBIAR MÉTRICA -->
      <div class="flex flex-wrap gap-2 md:justify-end">
        <button id="btnMetricVentas"
                class="px-3 py-1 text-xs rounded-lg bg-indigo-500 text-white font-medium">
          N° de ventas
        </button>
        <button id="btnMetricMonto"
                class="px-3 py-1 text-xs rounded-lg bg-gray-200 text-gray-700 font-medium">
          Monto vendido (S/)
        </button>
         <!-- FILTRO MES / AÑO -->
        <form method="get" class="flex items-center gap-2">
          <span class="text-xs text-gray-500">Mes:</span>
          <select name="monthAsesor" class="border rounded px-2 py-1 text-sm">
            <option value="">Todos</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= ($mesAsesor === $m) ? 'selected' : '' ?>>
                <?= mes_corto_es($m) ?>
              </option>
            <?php endfor; ?>
          </select>

          <span class="text-xs text-gray-500 ml-2">Año:</span>
          <select name="yearAsesor" class="border rounded px-2 py-1 text-sm">
            <?php for ($y = $anioAsesor - 3; $y <= $anioAsesor + 1; $y++): ?>
              <option value="<?= $y ?>" <?= ($anioAsesor === $y) ? 'selected' : '' ?>>
                <?= $y ?>
              </option>
            <?php endfor; ?>
          </select>

          <button type="submit"
                  class="ml-2 px-4 py-1 rounded-lg bg-indigo-500 text-white text-sm font-medium">
            Ver
          </button>
        </form>
      </div>
    </div>

    <?php if (!empty($asesorRows)): ?>
      <div class="w-full h-[320px]">
        <canvas id="chartVentasAsesor"></canvas>
      </div>
    <?php else: ?>
      <!-- AJUSTE: mensaje cuando no hay datos -->
      <div class="mt-6 text-sm text-gray-500">
        No hay ventas registradas para este período.
      </div>
    <?php endif; ?>
  </div>
  <!-- ======================= FIN TARJETA ASESOR ======================= -->

<?php endif; ?>

<!-- ============ ADMINISTRADOR y ADMISION: Gráficos circulares ============ -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
  <div class="bg-white rounded-2xl shadow p-6 border border-gray-100">
    <h3 class="text-lg font-semibold text-gray-800 mb-1">Tipos de Certificación más vendidos</h3>
    <p class="text-xs text-gray-500 mb-4">Basado en el total de ventas registradas</p>
    <div class="w-full h-[320px]">
      <canvas id="pieCursos"></canvas>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow p-6 border border-gray-100">
    <h3 class="text-lg font-semibold text-gray-800 mb-1">Maestros con más tipos de certificación vendidos</h3>
    <p class="text-xs text-gray-500 mb-4">Basado en el número de registros en ventas</p>
    <div class="w-full h-[320px]">
      <canvas id="pieProfes"></canvas>
    </div>
  </div>
</div>

<!-- TARJETA CON GRÁFICO DE PROGRAMAS   -->
<div class="grid grid-cols-1 gap-6 mt-6">
  <div class="bg-white rounded-2xl shadow p-6 border border-gray-100">
    <h3 class="text-lg font-semibold text-gray-800 mb-1">Programas más vendidos</h3>
    <p class="text-xs text-gray-500 mb-4">Basado en el total de ventas registradas</p>
    <div class="w-full h-[320px]">
      <canvas id="pieProgramas"></canvas>
    </div>
  </div>
</div>

<!-- ================= ADMINISTRADOR: Profesores por Nivel ================= -->
<div class="bg-white rounded-2xl shadow p-6 border border-gray-100 mt-6">
    <div class="flex justify-between items-center mb-2">
        <h3 id="chartTitle" class="text-lg font-semibold text-gray-800">Cantidad de Profesores por Nivel</h3>
        <button id="btnBack" class="hidden px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-md transition">
            Volver a niveles
        </button>
    </div>
    <div class="w-full h-[320px]">
        <canvas id="mainChart"></canvas>
    </div>
    <div id="profesoresList" class="hidden mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 text-sm"></div>
</div>

<?php if ($ROLE === 'ADMINISTRADOR'): ?>
  <!-- ================= ADMINISTRADOR: Lista de Docentes ================= -->
  <div class="bg-white rounded-2xl shadow p-6 border border-gray-100 mt-6 overflow-x-auto">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-lg font-semibold text-gray-800">Lista de Docentes Registrados</h3>
        <p class="text-xs text-gray-500">Visualiza, edita o elimina docentes del sistema</p>
      </div>
      <a href="agregar_docente.php" 
         class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
        + Agregar Docente
      </a>
    </div>

    <div class="rounded-xl overflow-hidden border border-gray-200 overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
          <tr>
            <th class="px-4 py-3 font-semibold">ID</th>
            <th class="px-4 py-3 font-semibold">Nombre Completo</th>
            <th class="px-4 py-3 font-semibold">DNI</th>
            <th class="px-4 py-3 font-semibold">Celular</th>
            <th class="px-4 py-3 font-semibold">Email</th>
            <th class="px-4 py-3 font-semibold">Ubicación</th>
            <th class="px-4 py-3 font-semibold">Nivel</th>
            <th class="px-4 py-3 font-semibold">Copia DNI</th>
            <th class="px-4 py-3 font-semibold text-center">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php if ($teachers && $teachers->rowCount() > 0): ?>
            <?php foreach ($teachers as $t): ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="px-4 py-2 font-medium text-gray-700"><?= (int)$t['id'] ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars(trim($t['nombres'].' '.$t['apellidos'])) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($t['dni']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($t['celular']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($t['email']) ?></td>
              <td class="px-4 py-2">
                <?= htmlspecialchars($t['departamento']) ?> /
                <?= htmlspecialchars($t['provincia']) ?> /
                <?= htmlspecialchars($t['distrito']) ?>
              </td>
              <td class="px-4 py-2">
                <span class="px-2 py-1 text-xs font-medium rounded-lg
                  <?= $t['nivel'] === 'INICIAL' ? 'bg-yellow-100 text-yellow-800' :
                     ($t['nivel'] === 'PRIMARIA' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                  <?= htmlspecialchars($t['nivel']) ?>
                </span>
              </td>
              <td class="px-4 py-2">
                <?php if (!empty($t['copia_dni_path'])): ?>
                  <a href="<?= htmlspecialchars($t['copia_dni_path']) ?>" target="_blank"
                     class="text-indigo-600 hover:underline text-xs">Ver archivo</a>
                <?php else: ?>
                  <span class="text-gray-400 text-xs">No disponible</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-2 text-center">
                <a href="editar_docente.php?id=<?= (int)$t['id'] ?>" 
                   class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs font-medium transition">
                  Editar
                </a>
                <a href="eliminar_docente.php?id=<?= (int)$t['id'] ?>"
                  class="inline-block bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs font-medium ml-1 transition">
                  Eliminar
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="9" class="text-center py-4 text-gray-500">No hay docentes registrados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php if ($ROLE === 'ADMINISTRADOR'): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
/* Gráfico de barras y lógica de KPIs: SOLO administrador */
const labels = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
const ventasData = { contado: <?= json_encode($serieVentasContado) ?>, cuotas: <?= json_encode($serieVentasCuotas) ?> };
const pagosData = { 
  contado: <?= json_encode($seriePagosContado) ?>, 
  cuotas: <?= json_encode($seriePagosCuotas) ?> 
};
const pagosCantidad = { 
  contado: <?= json_encode($serieCantPagosContado) ?>, 
  cuotas: <?= json_encode($serieCantPagosCuotas) ?> 
};
const docentesData = <?= json_encode($serieDocentes) ?>;

const datosOriginales = { 
  labels: [...labels], 
  ventas: { 
    contado: [...ventasData.contado], 
    cuotas: [...ventasData.cuotas],
    docentes: [...docentesData]
  }, 
  pagos: { 
    contado: [...pagosData.contado], 
    cuotas: [...pagosData.cuotas],
    docentes: [...docentesData] // docentes no cambia con pagos, pero lo mantenemos para consistencia
  }
};

let mostrandoPagos = false;
let mesSeleccionado = null;

const ctx = document.getElementById('pagosChart').getContext('2d');
const chart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: labels,
    datasets: [
      { label: 'Contado',  data: ventasData.contado,  backgroundColor: 'rgba(59,130,246,0.7)', maxBarThickness: 80 },
      { label: 'Cuotas',   data: ventasData.cuotas,   backgroundColor: 'rgba(99,102,241,0.7)', maxBarThickness: 80 },
      { label: 'Docentes Registrados', data: docentesData,        backgroundColor: 'rgba(16,185,129,0.7)', maxBarThickness: 80 }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'top' },
      tooltip: { mode: 'index', intersect: false }
    },
    scales: {
      x: { stacked: true },
      y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
    },
    onClick: (evt, elements) => {
      if (mesSeleccionado !== null) return;
      if (!elements.length) return;

      mesSeleccionado = elements[0].index;
      actualizarChart(mesSeleccionado);
      document.getElementById('btnVolver').style.display = 'inline-block';

      actualizarKPIs(mesSeleccionado);

      document.getElementById('tituloGrafico').textContent =
        (mostrandoPagos ? 'Pagos' : 'Ventas') + ' del mes de ' + labels[mesSeleccionado] + ' - Año <?= $anioActual ?>';
    }
  }
});

/* =========================
     ACTUALIZAR KPIs
========================= */
function actualizarKPIs(mes) {
  const totalVentas = ventasData.contado[mes] + ventasData.cuotas[mes];
  const totalPagos  = pagosCantidad.contado[mes] + pagosCantidad.cuotas[mes];
  const recaudado   = pagosData.contado[mes] + pagosData.cuotas[mes];

  document.getElementById('kpiVentas').textContent = totalVentas.toLocaleString();
  document.getElementById('kpiPagos').textContent  = totalPagos.toLocaleString();
  document.getElementById('kpiRecaudado').textContent = 'S/ ' + recaudado.toLocaleString();
  document.getElementById('kpiDocentes').textContent = docentesData[mes].toLocaleString();

  document.querySelector('#kpiVentas').previousElementSibling.textContent  = 'Ventas del mes de ' + labels[mes] + ' - <?= $anioActual ?>';
  document.querySelector('#kpiPagos').previousElementSibling.textContent   = 'Pagos del mes de ' + labels[mes] + ' - <?= $anioActual ?>';
  document.querySelector('#kpiRecaudado').previousElementSibling.textContent = 'Recaudado del mes de ' + labels[mes] + ' - <?= $anioActual ?>';
  document.querySelector('#kpiDocentes').previousElementSibling.textContent  = 'Docentes Registrados del mes de ' + labels[mes] + ' - <?= $anioActual ?>';
}

/* =========================
      ACTUALIZAR CHART
========================= */
function actualizarChart(mes = null) {
  if (mes !== null) {

    chart.data.labels = [labels[mes]];

    chart.data.datasets[0].data = [mostrandoPagos ? pagosData.contado[mes] : ventasData.contado[mes]];
    chart.data.datasets[1].data = [mostrandoPagos ? pagosData.cuotas[mes]  : ventasData.cuotas[mes]];
    chart.data.datasets[2].data = [docentesData[mes]];

  } else {

    chart.data.labels = [...labels];

    chart.data.datasets[0].data = mostrandoPagos ? [...datosOriginales.pagos.contado]   : [...datosOriginales.ventas.contado];
    chart.data.datasets[1].data = mostrandoPagos ? [...datosOriginales.pagos.cuotas]    : [...datosOriginales.ventas.cuotas];
    chart.data.datasets[2].data = [...datosOriginales.ventas.docentes];

    document.querySelector('#kpiVentas').previousElementSibling.textContent = 'Ventas del año';
    document.querySelector('#kpiPagos').previousElementSibling.textContent  = 'Pagos del año';
    document.querySelector('#kpiDocentes').previousElementSibling.textContent = 'Docentes Registrados del año';
    document.querySelector('#kpiRecaudado').previousElementSibling.textContent = 'Recaudado del año';
  }

  chart.update();
}

/* =========================
      TOGGLE VENTAS / PAGOS
========================= */
document.getElementById('toggleTipo').addEventListener('click', () => {
  mostrandoPagos = !mostrandoPagos;

  actualizarChart(mesSeleccionado);

  document.getElementById('toggleTipo').textContent =
    mostrandoPagos ? 'Ver por Ventas' : 'Ver por Pagos';

  if (mesSeleccionado !== null) {
    document.getElementById('tituloGrafico').textContent =
      (mostrandoPagos ? 'Pagos' : 'Ventas') + ' del mes de ' + labels[mesSeleccionado] + ' - Año <?= $anioActual ?>';
  }
});

/* =========================
         BOTÓN VOLVER
========================= */
document.getElementById('btnVolver').addEventListener('click', () => {
  mesSeleccionado = null;
  actualizarChart();
  document.getElementById('btnVolver').style.display = 'none';

  document.getElementById('kpiVentas').textContent = <?= $ventasMes ?>;
  document.getElementById('kpiPagos').textContent  = <?= $pagosMes ?>;
  document.getElementById('kpiRecaudado').textContent = 'S/ <?= number_format($recaudadoMes,2) ?>';
  document.getElementById('kpiDocentes').textContent = <?= $docentesMes ?>;

  document.getElementById('tituloGrafico').textContent =
    (mostrandoPagos ? 'Pagos' : 'Ventas') + ' por mes - Año <?= $anioActual ?>';
});

// ===================== GRÁFICO "VENTAS POR ASESOR" =====================
const asesorLabels = <?= $asesoresLabelsJson ?>;
const asesorVentas = <?= $asesoresVentasJson ?>;
const asesorMontos = <?= $asesoresMontosJson ?>;

const ctxAsesor = document.getElementById('chartVentasAsesor');
let chartAsesor = null;
let metricAsesor = 'ventas';

if (ctxAsesor && asesorLabels.length > 0) {
  chartAsesor = new Chart(ctxAsesor.getContext('2d'), {
    type: 'bar',
    data: {
      labels: asesorLabels,
      datasets: [{
        label: 'N° de ventas',
        data: asesorVentas,
        backgroundColor: 'rgba(59,130,246,0.8)',
        maxBarThickness: 60
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: true },
        tooltip: { mode: 'index', intersect: false }
      },
      scales: {
        x: { ticks: { autoSkip: false } },
        y: {
          beginAtZero: true,
          title: { display: true, text: 'Número de ventas' }
        }
      }
    }
  });
}

function setMetricAsesor(metric) {
  if (!chartAsesor) return;
  metricAsesor = metric;

  const btnVentas = document.getElementById('btnMetricVentas');
  const btnMonto  = document.getElementById('btnMetricMonto');

  if (metric === 'ventas') {
    chartAsesor.data.datasets[0].label = 'N° de ventas';
    chartAsesor.data.datasets[0].data  = asesorVentas;
    chartAsesor.options.scales.y.title.text = 'Número de ventas';

    btnVentas.classList.add('bg-indigo-500','text-white');
    btnVentas.classList.remove('bg-gray-200','text-gray-700');
    btnMonto.classList.add('bg-gray-200','text-gray-700');
    btnMonto.classList.remove('bg-indigo-500','text-white');
  } else {
    chartAsesor.data.datasets[0].label = 'Monto vendido (S/)';
    chartAsesor.data.datasets[0].data  = asesorMontos;
    chartAsesor.options.scales.y.title.text = 'Monto vendido (S/)';

    btnMonto.classList.add('bg-indigo-500','text-white');
    btnMonto.classList.remove('bg-gray-200','text-gray-700');
    btnVentas.classList.add('bg-gray-200','text-gray-700');
    btnVentas.classList.remove('bg-indigo-500','text-white');
  }

  chartAsesor.update();
}

document.getElementById('btnMetricVentas')
  ?.addEventListener('click', () => setMetricAsesor('ventas'));

document.getElementById('btnMetricMonto')
  ?.addEventListener('click', () => setMetricAsesor('monto'));

setMetricAsesor('ventas');

const cardVentasAsesor = document.getElementById('cardVentasAsesor');
const btnToggleAsesor  = document.getElementById('btnToggleAsesor');

if (cardVentasAsesor && btnToggleAsesor) {
  btnToggleAsesor.addEventListener('click', () => {
    const hidden = cardVentasAsesor.classList.toggle('hidden');
    btnToggleAsesor.textContent = hidden
      ? 'Ver ventas por asesor'
      : 'Ocultar ventas por asesor';
  });
}
</script>
<?php endif; ?>

<!-- Carga de Chart.js para los PIE (ADMISION y ADMIN) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// PIE charts para ADMISION y ADMIN
const cursosLabels     = <?= $cursosLabelsJson ?>;
const cursosValues     = <?= $cursosValuesJson ?>;
const profLabels       = <?= $profesoresLabelsJson ?>;
const profValues       = <?= $profesoresValuesJson ?>;
const programasLabels  = <?= $programasLabelsJson ?>;
const programasValues  = <?= $programasValuesJson ?>;

const pieColors = [
    '#60a5fa','#a78bfa','#34d399','#fbbf24','#f472b6','#f87171',
    '#22d3ee','#93c5fd','#86efac','#fca5a5','#c4b5fd','#fcd34d'
];

// PIE: Tipos de Certificación
const ctxCursos = document.getElementById('pieCursos');
if (ctxCursos) {
    new Chart(ctxCursos.getContext('2d'), {
        type: 'pie',
        data: {
            labels: cursosLabels,
            datasets: [{
                data: cursosValues,
                backgroundColor: cursosLabels.map((_, i) => pieColors[i % pieColors.length]),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const total = cursosValues.reduce((a,b)=>a+b,0) || 1;
                            const val = ctx.parsed ?? 0;
                            const pct = ((val / total) * 100).toFixed(1);
                            return `${ctx.label}: ${val} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
}

// PIE: Profesores con más ventas
const ctxProfesPie = document.getElementById('pieProfes');
if (ctxProfesPie) {
    new Chart(ctxProfesPie.getContext('2d'), {
        type: 'pie',
        data: {
            labels: profLabels,
            datasets: [{
                data: profValues,
                backgroundColor: profLabels.map((_, i) => pieColors[(i + 3) % pieColors.length]),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const total = profValues.reduce((a,b)=>a+b,0) || 1;
                            const val = ctx.parsed ?? 0;
                            const pct = ((val / total) * 100).toFixed(1);
                            return `${ctx.label}: ${val} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
}

// PIE: PROGRAMAS MÁS VENDIDOS
const ctxProgramas = document.getElementById('pieProgramas');
if (ctxProgramas) {
    new Chart(ctxProgramas.getContext('2d'), {
        type: 'pie',
        data: {
            labels: programasLabels,
            datasets: [{
                data: programasValues,
                backgroundColor: programasLabels.map((_, i) => pieColors[(i + 6) % pieColors.length]),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const total = (programasValues || []).reduce((a,b)=>a+b,0) || 1;
                            const val   = ctx.parsed ?? 0;
                            const pct   = ((val / total) * 100).toFixed(1);
                            return `${ctx.label}: ${val} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nivelesLabels = <?= $nivelesLabelsJson ?>;
    const nivelesValues = <?= $nivelesValuesJson ?>;
    const especialidadesPorNivel = <?= $especialidadesPorNivelJson ?>;
    const profesoresPorEspecialidad = <?= $profesoresPorEspecialidadJson ?>;

    const ctxProfes = document.getElementById('mainChart').getContext('2d');
    const profesoresList = document.getElementById('profesoresList');
    const btnBack = document.getElementById('btnBack');
    let currentViewProfes = 'niveles';
    let currentNivel = null;

    const coloresEspecialidades = [
        '#60a5fa', '#34d399', '#fbbf24', '#f87171', '#a78bfa', '#f472b6', '#22d3ee', '#fcd34d'
    ];

    function updateChartProfes(labels, data, bgColor, title) {
        chartProfes.data.labels = labels;
        chartProfes.data.datasets[0].data = data;
        chartProfes.data.datasets[0].backgroundColor = bgColor;
        document.getElementById('chartTitle').innerText = title;
        chartProfes.update();
    }

    const chartProfes = new Chart(ctxProfes, {
        type: 'bar',
        data: {
            labels: nivelesLabels,
            datasets: [{
                label: 'Cantidad de Profesores',
                data: nivelesValues,
                backgroundColor: ['#60a5fa','#34d399','#fbbf24'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } },
            // niveles se cambio estoo desde aquii
            onClick: (evt, elements) => {
    if (!elements.length) return;

    const index = elements[0].index;

    if (currentViewProfes === 'niveles') {
        currentNivel = nivelesLabels[index];

        if (currentNivel !== 'SECUNDARIA') {
            return;
        }

        const esp = especialidadesPorNivel[currentNivel] || [];
        const labels = esp.map(e => e.especialidad);
        const values = esp.map(e => parseInt(e.total) || 0);
        const colors = labels.map((_, i) => coloresEspecialidades[i % coloresEspecialidades.length]);

        currentViewProfes = 'especialidades';
        updateChartProfes(labels, values, colors, 'Especialidades del Nivel: ' + currentNivel);
        btnBack.classList.remove('hidden');
        profesoresList.classList.add('hidden');

    } else if (currentViewProfes === 'especialidades') {
        const espLabels = chartProfes.data.labels;
        const espName = espLabels[index];
        const profs = (profesoresPorEspecialidad[currentNivel] || {})[espName] || [];

        if (profs.length === 0) {
            profesoresList.innerHTML = '<p>No hay profesores registrados en esta especialidad.</p>';
        } else {
            profesoresList.innerHTML = `
                <p class="font-semibold mb-2">Profesores en ${espName}:</p>
                <ul class="list-disc list-inside">${profs.map(p => `<li>${p}</li>`).join('')}</ul>
            `;
        }
        profesoresList.classList.remove('hidden');
    }
}
// fin del nuevo cambioo

        }
    });

    btnBack.addEventListener('click', () => {
        currentViewProfes = 'niveles';
        currentNivel = null;
        updateChartProfes(nivelesLabels, nivelesValues, ['#60a5fa','#34d399','#fbbf24'], 'Cantidad de Profesores por Nivel');
        btnBack.classList.add('hidden');
        profesoresList.classList.add('hidden');
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';