    <?php
    require_once __DIR__ . '/../config/auth.php';
    checkRole(['ADMINISTRADOR']); // Solo administradores pueden editar ventas

    require __DIR__ . '/../config/db.php';

    // Verificar que se haya enviado el ID
    if (!isset($_POST['id'])) {
        header("Location: ../public/editar_registros.php?tab=ventas");
        exit;
    }

    $id = (int) $_POST['id'];

    // Recoger los datos del formulario
    $teacher_id        = $_POST['teacher_id'];
    $tipo_transaccion  = $_POST['tipo_transaccion'];
    $curso_id          = $_POST['curso_id'];
    $programa_id       = $_POST['programa_id'];
    $modalidad         = $_POST['modalidad'];
    $nombre_programa   = $_POST['nombre_programa'];
    $precio_programa   = $_POST['precio_programa'];
    $mencion           = $_POST['mencion'] ?? null;
    $inicio_programa   = $_POST['inicio_programa'];
    $obs_programa      = $_POST['obs_programa'] ?? null;
    $advisor_id        = $_POST['advisor_id'];
    $certificado = $_POST['certificado'] ?? 'NO'; // valor seguro por defecto
    $proceso_certificacion = $_POST['proceso_certificacion'] ?? 'En Proceso';


    // Actualizar la venta en la base de datos
    $sql = "UPDATE ventas 
            SET teacher_id = ?, 
                tipo_transaccion = ?, 
                curso_id = ?, 
                modalidad = ?, 
                programa_id = ?, 
                precio_programa = ?, 
                mencion = ?, 
                inicio_programa = ?, 
                obs_programa = ?, 
                advisor_id = ?,
                certificado = ?,     -- <-- agregado aquí
                proceso_certificacion = ?
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);

    $success = $stmt->execute([
        $teacher_id,
        $tipo_transaccion,
        $curso_id,
        $modalidad,
        $programa_id,
        $precio_programa,
        $mencion,
        $inicio_programa,
        $obs_programa,
        $advisor_id,
        $certificado,   // <-- nuevo parámetro
        $proceso_certificacion,
        $id
    ]);

    if ($success) {
        header("Location: ../public/editar_registros.php?tab=ventas&msg=Venta+actualizada+correctamente");
    } else {
        header("Location: ../public/editar_registros.php?tab=ventas&error=No+se+pudo+actualizar+la+venta");
    }
    exit;
