<?php
require_once __DIR__ . '/../config/auth.php';
require __DIR__ . '/../config/db.php';
checkRole(['ADMINISTRADOR']); // Solo el administrador puede acceder

$title = 'Gestión de Usuarios';
$active = 'registrar_usuario';
ob_start();
?>


  <h2 class="text-3xl font-bold text-gray-800 mb-6">Gestión de Usuarios</h2>

  <!-- Mensaje de éxito o error -->
  <?php if(isset($_GET['msg'])): ?>
    <div class="bg-green-100 text-green-800 p-3 rounded mb-4 border border-green-300 text-sm text-center">
      <?= htmlspecialchars($_GET['msg']) ?>
    </div>
  <?php elseif(isset($_GET['error'])): ?>
    <div class="bg-red-100 text-red-800 p-3 rounded mb-4 border border-red-300 text-sm text-center">
      <?= htmlspecialchars($_GET['error']) ?>
    </div>
  <?php endif; ?>

  <!-- Pestañas -->
  <ul class="flex border-b mb-6" id="tabs">
    <li class="-mb-px mr-2">
      <button 
        class="tab-btn bg-white inline-block py-2 px-4 font-semibold border-l border-t border-r rounded-t text-green-600"
        data-tab="registrar">
        Registrar Nuevo Trabajador
      </button>
    </li>
    <li class="mr-2">
      <button 
        class="tab-btn bg-white inline-block py-2 px-4 text-gray-600 hover:text-green-600 font-semibold"
        data-tab="gestionar">
        Actualizar / Eliminar Trabajador
      </button>
    </li>
  </ul>

  <!-- Contenido de pestañas -->
  <div id="tab-registrar" class="tab-content">
    <?php include __DIR__ . '/registrar_nuevo_usuario.php'; ?>
  </div>

  <div id="tab-gestionar" class="tab-content hidden">
    <?php include __DIR__ . '/gestionar_usuarios.php'; ?>
  </div>


<script>
// Obtener la pestaña activa por GET o por defecto "registrar"
const activeTab = '<?= $_GET['tab'] ?? "registrar" ?>';

document.addEventListener('DOMContentLoaded', () => {
  // Mostrar la pestaña activa
  document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
  const activeContent = document.getElementById(`tab-${activeTab}`);
  if (activeContent) activeContent.classList.remove('hidden');

  // Ajustar estilos de botones
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.classList.remove('text-green-600','border-l','border-t','border-r','rounded-t');
    btn.classList.add('text-gray-600');
  });
  const activeBtn = document.querySelector(`.tab-btn[data-tab="${activeTab}"]`);
  if (activeBtn) activeBtn.classList.add('text-green-600','border-l','border-t','border-r','rounded-t');

  // Control de pestañas al hacer clic
  document.querySelectorAll('.tab-btn').forEach(button => {
    button.addEventListener('click', function() {
      const tab = this.getAttribute('data-tab');

      document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
      document.getElementById(`tab-${tab}`).classList.remove('hidden');

      document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('text-green-600','border-l','border-t','border-r','rounded-t');
        btn.classList.add('text-gray-600');
      });

      this.classList.add('text-green-600','border-l','border-t','border-r','rounded-t');
    });
  });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
