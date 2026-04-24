<?php
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR', 'ASESOR']); // Solo admin y asesores
$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/db.php';

$base   = rtrim($config['base_url'], '/');
$title  = 'Registros de pagos';
$active = 'usuario';

$role    = $_SESSION['role'] ?? '';
$userId  = $_SESSION['user_id'] ?? null;
$advisorId = null;

// Obtener advisor_id si es ASESOR
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

ob_start();
?>

<h2 class="text-3xl font-bold text-gray-800 mb-6">Registros de pagos</h2>

<!-- ================== PAGOS AL CONTADO ================== -->
<section class="bg-white rounded-lg shadow p-8 mb-8">
    <h3 class="text-lg font-semibold mb-6">Registros de Pagos al Contado</h3>

    <!-- Buscador -->
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
        <table id="tabla-pagos" class="min-w-full divide-y divide-gray-200 mb-4">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left">Docente</th>
                    <th class="px-6 py-3 text-left">Tipo De Certificación</th>
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
                        <td colspan="7" class="text-center py-6">No hay pagos al contado registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- ================== CUOTAS ================== -->
<section class="bg-white rounded-lg shadow p-8 mb-8">
    <h3 class="text-lg font-semibold mb-6">Registros de Cuotas</h3>

    <!-- Buscador -->
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

    <div class="overflow-x-auto">
        <table id="tabla-cuotas" class="min-w-full divide-y divide-gray-200 mb-4">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left">Docente</th>
                    <th class="px-6 py-3 text-left">Tipo De Certificación</th>
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
                            <td class="px-6 py-3 break-words whitespace-normal">
                                <?= htmlspecialchars($cuota['nombres'] . ' ' . $cuota['apellidos']) ?>
                            </td>
                            <td class="px-6 py-3 break-words whitespace-normal">
                                <?= htmlspecialchars($cuota['tipo_programa'] ?? 'Sin curso') ?>
                            </td>
                            <td class="px-6 py-3 break-words whitespace-normal">
                                <?= htmlspecialchars($cuota['programa']) ?>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <?= htmlspecialchars($cuota['numero_cuota']) ?>
                            </td>
                            <td class="px-6 py-3 text-right">
                                S/ <?= number_format($cuota['monto'], 2) ?>
                            </td>
                            <td class="px-6 py-3 break-words whitespace-normal">
                                <?= htmlspecialchars($cuota['forma_pago']) ?>
                            </td>
                            <td class="px-6 py-3">
                                <?= htmlspecialchars($cuota['fecha_pago']) ?>
                            </td>
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

<script>
// Buscadores de las tablas (mismo código que en registro.php)
document.addEventListener('DOMContentLoaded', () => {

    function crearBuscador(idInput, idTabla, idBuscar, idRetroceder) {
        const input = document.getElementById(idInput);
        const tabla = document.getElementById(idTabla);
        const btnBuscar = document.getElementById(idBuscar);
        const btnRetroceder = document.getElementById(idRetroceder);

        if (!input || !tabla || !btnBuscar || !btnRetroceder) return;

        function filtrarTabla(query) {
            const filas = tabla.querySelectorAll('tbody tr');
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                const esFilaVacia = fila.querySelector('td[colspan]');
                if (!esFilaVacia) {
                    fila.style.display = texto.includes(query) ? '' : 'none';
                }
            });
        }

        btnBuscar.addEventListener('click', () => {
            filtrarTabla(input.value.trim().toLowerCase());
        });

        input.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                filtrarTabla(input.value.trim().toLowerCase());
            }
        });

        btnRetroceder.addEventListener('click', () => {
            input.value = '';
            filtrarTabla('');
        });
    }

    crearBuscador('search-pagos',  'tabla-pagos',  'btn-buscar-pagos',  'btn-retroceder-pagos');
    crearBuscador('search-cuotas', 'tabla-cuotas', 'btn-buscar-cuotas', 'btn-retroceder-cuotas');
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
