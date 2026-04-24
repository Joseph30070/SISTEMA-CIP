<?php 
// Obtener roles desde ENUM en la tabla
$query = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'role'");
$row = $query->fetch(PDO::FETCH_ASSOC);
preg_match("/^enum\('(.*)'\)$/", $row['Type'], $matches);
$roles = explode("','", $matches[1]);
?>

<!-- Contenedor full-width, sin centrado -->
<div class="w-full">
  <!-- Encabezado -->
  <div class="mb-6">
    <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Registrar Nuevo Trabajador</h2>
    <p class="text-sm text-gray-500 mt-1">Completa los datos para crear una cuenta en el sistema.</p>
  </div>

  <!-- Alertas -->
  <?php if(isset($_GET['error'])): ?>
    <div class="bg-red-50 text-red-700 p-3 rounded-lg border border-red-200 text-sm mb-4">
      <?= htmlspecialchars($_GET['error']) ?>
    </div>
  <?php endif; ?>

  <!-- Panel de formulario a todo lo ancho -->
  <div class="bg-white rounded-xl shadow p-5 md:p-6">
    <form action="../process/process_user.php" method="POST" id="userForm" class="space-y-6">
      <!-- Grid responsivo: 1 columna en móvil, 2 en md+ -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- Nombre completo -->
        <div class="col-span-1">
          <label for="fullname" class="block text-gray-700 font-semibold mb-1">Nombre Completo</label>
          <input
            type="text"
            id="fullname"
            name="fullname"
            required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 outline-none"
            placeholder="Ej. Ana Pérez Gómez">
        </div>

        <!-- Correo -->
        <div class="col-span-1">
          <label for="email" class="block text-gray-700 font-semibold mb-1">Correo Electrónico</label>
          <input
            type="email"
            id="email"
            name="email"
            required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 outline-none"
            placeholder="correo@ejemplo.com">
        </div>

        <!-- Contraseña -->
        <div class="col-span-1">
          <label for="password" class="block text-gray-700 font-semibold mb-1">Contraseña</label>
          <div class="relative">
            <input
              type="password"
              id="password"
              name="password"
              required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-10 focus:ring-2 focus:ring-green-500 outline-none"
              placeholder="********">
            <button
              type="button"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 toggle-password"
              data-target="password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <!-- Confirmar contraseña -->
        <div class="col-span-1">
          <label for="confirm_password" class="block text-gray-700 font-semibold mb-1">Confirmar Contraseña</label>
          <div class="relative">
            <input
              type="password"
              id="confirm_password"
              name="confirm_password"
              required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-10 focus:ring-2 focus:ring-green-500 outline-none"
              placeholder="********">
            <button
              type="button"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 toggle-password"
              data-target="confirm_password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <!-- Rol -->
        <div class="col-span-1">
          <label for="role" class="block text-gray-700 font-semibold mb-1">Rol</label>
          <select
            id="role"
            name="role"
            required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 outline-none">
            <option value="">Seleccione un rol...</option>
            <?php foreach ($roles as $rol): ?>
              <option value="<?= htmlspecialchars($rol) ?>"><?= htmlspecialchars($rol) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Team (solo para ASESOR) -->
        <div class="col-span-1 hidden" id="team-field">
          <label for="team" class="block text-gray-700 font-semibold mb-1">Team</label>
          <input
            type="text"
            id="team"
            name="team"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 outline-none"
            placeholder="Nombre del equipo (solo asesores)">
        </div>
      </div>

      <!-- Botón -->
      <div class="pt-2">
        <button
          type="submit"
          class="inline-flex items-center justify-center gap-2 w-full md:w-auto bg-green-600 text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-green-700 focus:ring-2 focus:ring-green-500">
          <i class="fas fa-user-plus"></i>
          Registrar 
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const roleSelect = document.getElementById('role');
  const teamField  = document.getElementById('team-field');

  // Mostrar / ocultar campo Team según rol
  roleSelect.addEventListener('change', function () {
    const selectedRole = (this.value || '').toUpperCase();
    if (selectedRole === 'ASESOR' || selectedRole === 'ADVISOR') {
      teamField.classList.remove('hidden');
    } else {
      teamField.classList.add('hidden');
      const teamInput = document.getElementById('team');
      if (teamInput) teamInput.value = '';
    }
  });

  // Toggle mostrar/ocultar contraseñas
  const toggleButtons = document.querySelectorAll('.toggle-password');
  toggleButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target');
      const input    = document.getElementById(targetId);
      if (!input) return;

      const icon = this.querySelector('i');

      if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
          icon.classList.remove('fa-eye');
          icon.classList.add('fa-eye-slash');
        }
      } else {
        input.type = 'password';
        if (icon) {
          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');
        }
      }
    });
  });
});
</script>
