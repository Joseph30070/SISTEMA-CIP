<?php
require_once __DIR__ . '/../config/auth.php';

$title  = 'Ventana';
$active = 'ventana';

// Normaliza el rol
$ROLE = strtoupper($_SESSION['role'] ?? '');

ob_start(); 
?>

<h2 class="text-3xl font-bold text-gray-800 mb-6">Bienvenido a CiipGestión</h2>
<p class="text-gray-600 mb-6">Usa el menú para registrar docentes o consultar ventas y pagos.</p>

<div class="grid md:grid-cols-3 gap-4">
    <?php if (in_array($ROLE, ['ADMINISTRADOR','ASESOR'], true)): ?>
        <a href="registro.php" class="block bg-white rounded shadow p-6 hover:shadow-md transition">
            <div class="text-teal-600 text-3xl mb-3"><i class="fas fa-user-plus"></i></div>
            <h3 class="font-semibold">Registrar Docente</h3>
            <p class="text-sm text-gray-600">Alta de docente, venta y pago con voucher.</p>
        </a>   
    <?php endif; ?>

    <?php if (in_array($ROLE, ['ADMINISTRADOR','ADMISION'], true)): ?>
        <a href="consulta.php" class="block bg-white rounded shadow p-6 hover:shadow-md transition">
            <div class="text-blue-600 text-3xl mb-3"><i class="fas fa-search"></i></div>
            <h3 class="font-semibold">Consultas</h3>
            <p class="text-sm text-gray-600">Revisa pagos, montos y comprobantes por docente.</p>
        </a>
    <?php endif; ?>

    <!-- Usuario: todos -->
    <?php if ($ROLE === 'ASESOR'): ?>
    <a href="usuario.php" class="block bg-white rounded shadow p-6 hover:shadow-md transition">
        <div class="text-gray-700 text-3xl mb-3"><i class="fas fa-user"></i></div>
        <h3 class="font-semibold">Ver Registros</h3>
        <p class="text-sm text-gray-600">Revisa tus registros de ventas y pagos.</p>
    </a>
    <?php endif; ?>
    
    <!-- NUEVA TARJETA -->
    <?php if ($ROLE === 'ADMINISTRADOR'): ?>
        <a href="registrar_usuario.php" class="block bg-white rounded shadow p-6 hover:shadow-md transition">
            <div class="text-green-600 text-3xl mb-3"><i class="fas fa-user-cog"></i></div>
            <h3 class="font-semibold">Registrar Nuevo Trabajador</h3>
            <p class="text-sm text-gray-600">Crear cuentas nuevas para el sistema.</p>
        </a>    
    <!-- NUEVA TARJETA: Gestión de Cursos -->
        <a href="tipo_certificacion_programas.php" class="block bg-white rounded shadow p-6 hover:shadow-md transition">
            <div class="text-purple-600 text-3xl mb-3"><i class="fas fa-book"></i></div>
            <h3 class="font-semibold">Administrar Tipos de Certificacion</h3>
            <p class="text-sm text-gray-600">Agregar nuevos tipos de certificacion o eliminar los existentes.</p>
        </a>
    <!-- NUEVA TARJETA: Editar Registros -->
        <a href="editar_registros.php" class="block bg-white rounded shadow p-6 hover:shadow-md transition">
            <div class="text-orange-600 text-3xl mb-3"><i class="fas fa-edit"></i></div>
            <h3 class="font-semibold">Editar Registros</h3>
            <p class="text-sm text-gray-600">Modificar o eliminar registros de ventas y pagos.</p>
        </a>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
