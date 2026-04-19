<?php
session_start();
include("database.php");

$usuario = $_SESSION['NOMBRE_USUARIO'];
include("rol-cargos.php");

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
  header("Location: index.php");
  exit();
}
?><!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Vehículos | Premium Panel</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="css/global-premium.css" rel="stylesheet" />
  <link href="css/gestion-wide.css" rel="stylesheet" />
</head>

<body>
  <!-- Barra de navegación -->
  <nav class="navbar navbar-expand-lg navbar-light bg-light px-3 shadow-sm mb-4">
    <div class="container-fluid">
      <!-- Navegación izquierda -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="ot.php">Órdenes de Trabajo</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="cotizaciones.php">Cotizaciones</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="reporte.php">Reporte</a>
        </li>
        <?php if ($opcionesAdicionales2)
          echo $opcionesAdicionales2; ?>
      </ul>

      <!-- Usuario + cerrar sesión -->
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item dropdown me-3">
          <a class="nav-link" href="#" id="notifBell" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-bell"></i>
            <span class="badge bg-danger rounded-pill" id="notifCount">0</span>
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-dark fw-bold" href="#" id="navbarDropdown" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($usuario); ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="navbarDropdown">
            <?php if ($opcionesAdicionales)
              echo $opcionesAdicionales; ?>
            <li><a class="dropdown-item" href="clientes.php"></i>Clientes</a>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item text-danger" href="cerrar-sesion.php"><i
                  class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>

  <div class="main-container-wide">
    <!-- Header Section -->
    <div class="header-section">
      <div class="header-title">
        <h2><i class="fas fa-truck me-2"></i>Gestión de Vehículos</h2>
        <p class="text-muted mb-0">Administre la flota de móviles y patentes.</p>
      </div>
      <?php if ($accesoAdministrador && !$soloLectura && !$bloquearFueraDeOT): ?>
        <button type="button" class="btn btn-premium" data-bs-toggle="modal" data-bs-target="#modalCrearVehiculo">
          <i class="fas fa-plus me-2"></i>Nuevo Vehículo
        </button>
      <?php endif; ?>
    </div>

    <div id="alertVehiculos"></div>

    <div class="table-card">
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Patente</th>
              <th>Marca</th>
              <th>Modelo</th>
              <th>Código Móvil</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody id="flota-tbody">
            <tr>
              <td colspan="5" class="text-center py-5">Cargando flota...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php if ($accesoAdministrador && !$soloLectura && !$bloquearFueraDeOT): ?>
    <div class="modal fade" id="modalCrearVehiculo" tabindex="-1" aria-labelledby="modalCrearVehiculoLabel"
      aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalCrearVehiculoLabel">Crear vehículo</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <form id="formCrearVehiculo">
              <div class="mb-3">
                <label class="form-label">Patente</label>
                <input type="text" name="patente" class="form-control" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Marca</label>
                <input type="text" name="marca" class="form-control" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Modelo</label>
                <input type="text" name="modelo" class="form-control" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Código Móvil</label>
                <input type="text" name="codigo_movil" class="form-control" />
              </div>
            </form>
            <div id="alertCrearVehiculo"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnGuardarVehiculo">Guardar</button>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="modal fade" id="modalEditarVehiculo" tabindex="-1" aria-labelledby="modalEditarVehiculoLabel"
    aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEditarVehiculoLabel">Editar vehículo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <form id="formEditarVehiculo">
            <input type="hidden" name="id" />
            <div class="mb-3">
              <label class="form-label">Patente</label>
              <input type="text" name="patente" class="form-control" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Marca</label>
              <input type="text" name="marca" class="form-control" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Modelo</label>
              <input type="text" name="modelo" class="form-control" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Código Móvil</label>
              <input type="text" name="codigo_movil" class="form-control" />
            </div>
          </form>
          <div id="alertEditarVehiculo"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="btnActualizarVehiculo">Actualizar</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const accesoAdministrador = <?= $accesoAdministrador ? 'true' : 'false' ?>;
    const soloLectura = <?= $soloLectura ? 'true' : 'false' ?>;
    const bloquearFueraDeOT = <?= $bloquearFueraDeOT ? 'true' : 'false' ?>;
    async function loadFlota() {
      const tbody = document.getElementById('flota-tbody');
      tbody.innerHTML = '<tr><td colspan="6" class="text-center">Cargando...</td></tr>';
      try {
        const resp = await fetch('ajax/listar-flota.php');
        const data = await resp.json();
        const rows = data.data || [];
        if (rows.length === 0) { tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay vehículos registrados.</td></tr>'; return; }
        const html = rows.map(r => `
      <tr>
        <td>${r.patente || ''}</td>
        <td>${r.marca || ''}</td>
        <td>${r.modelo || ''}</td>
        <td>${r.codigo_movil || ''}</td>
        <td class="text-end">
          ${(accesoAdministrador && !soloLectura && !bloquearFueraDeOT) ? `<button class=\"btn btn-sm btn-warning me-1 btn-edit\" data-id=\"${r.id}\"><i class=\"fas fa-edit\"></i></button>
          <button class=\"btn btn-sm btn-danger btn-delete\" data-id=\"${r.id}\"><i class=\"fas fa-trash-alt\"></i></button>` : ''}
        </td>
      </tr>
    `).join('');
        tbody.innerHTML = html;
        document.querySelectorAll('.btn-edit').forEach(btn => { btn.addEventListener('click', () => openEditVehiculo(btn.getAttribute('data-id'))); });
        document.querySelectorAll('.btn-delete').forEach(btn => { btn.addEventListener('click', () => deleteVehiculo(btn.getAttribute('data-id'))); });
      } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">' + (e.message || 'Error') + '</td></tr>';
      }
    }

    document.addEventListener('DOMContentLoaded', () => { loadFlota(); });

    document.getElementById('btnGuardarVehiculo').addEventListener('click', async function () {
      if (!accesoAdministrador || soloLectura || bloquearFueraDeOT) { document.getElementById('alertCrearVehiculo').innerHTML = '<div class="alert alert-danger">Sin permiso para crear</div>'; return; }
      const form = document.getElementById('formCrearVehiculo');
      const fd = new FormData(form);
      const req = new URLSearchParams();
      ['patente', 'marca', 'modelo', 'codigo_movil'].forEach(k => req.append(k, (fd.get(k) || '').toString().trim()));
      if (!(fd.get('patente') || '').toString().trim() || !(fd.get('marca') || '').toString().trim() || !(fd.get('modelo') || '').toString().trim()) { document.getElementById('alertCrearVehiculo').innerHTML = '<div class="alert alert-danger">Complete Patente, Marca y Modelo</div>'; return; }
      try {
        const resp = await fetch('ajax/guardar-vehiculo.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: req.toString() });
        const ct = resp.headers.get('content-type') || '';
        const data = ct.includes('application/json') ? await resp.json() : {};
        if (resp.ok && data.success) {
          document.getElementById('alertVehiculos').innerHTML = '<div class="alert alert-success">Vehículo creado</div>';
          const modalEl = document.getElementById('modalCrearVehiculo');
          const modal = bootstrap.Modal.getInstance(modalEl);
          if (modal) modal.hide();
          form.reset();
          loadFlota();
        } else {
          const msg = (data && data.error) ? data.error : 'No se pudo crear el vehículo';
          document.getElementById('alertCrearVehiculo').innerHTML = '<div class="alert alert-danger">' + msg + '</div>';
        }
      } catch (e) {
        document.getElementById('alertCrearVehiculo').innerHTML = '<div class="alert alert-danger">' + (e.message || 'Error de red') + '</div>';
      }
    });

    async function openEditVehiculo(id) {
      try {
        const resp = await fetch('ajax/obtener-vehiculo.php?id=' + encodeURIComponent(id));
        const data = await resp.json();
        if (!data || !data.success) { document.getElementById('alertVehiculos').innerHTML = '<div class="alert alert-danger">No se pudo cargar el vehículo</div>'; return; }
        const v = data.vehiculo;
        const form = document.getElementById('formEditarVehiculo');
        form.querySelector('[name="id"]').value = v.id;
        form.querySelector('[name="patente"]').value = v.patente || '';
        form.querySelector('[name="marca"]').value = v.marca || '';
        form.querySelector('[name="modelo"]').value = v.modelo || '';
        form.querySelector('[name="codigo_movil"]').value = v.codigo_movil || '';
        const modal = new bootstrap.Modal(document.getElementById('modalEditarVehiculo'));
        modal.show();
      } catch (e) {
        document.getElementById('alertVehiculos').innerHTML = '<div class="alert alert-danger">' + (e.message || 'Error de red') + '</div>';
      }
    }

    document.getElementById('btnActualizarVehiculo').addEventListener('click', async function () {
      if (!accesoAdministrador || soloLectura || bloquearFueraDeOT) { document.getElementById('alertEditarVehiculo').innerHTML = '<div class="alert alert-danger">Sin permiso para actualizar</div>'; return; }
      const form = document.getElementById('formEditarVehiculo');
      const fd = new FormData(form);
      const req = new URLSearchParams();
      ['id', 'patente', 'marca', 'modelo', 'codigo_movil'].forEach(k => req.append(k, (fd.get(k) || '').toString().trim()));
      if (!(fd.get('id') || '').toString().trim()) { document.getElementById('alertEditarVehiculo').innerHTML = '<div class="alert alert-danger">ID inválido</div>'; return; }
      if (!(fd.get('patente') || '').toString().trim() || !(fd.get('marca') || '').toString().trim() || !(fd.get('modelo') || '').toString().trim()) { document.getElementById('alertEditarVehiculo').innerHTML = '<div class="alert alert-danger">Complete Patente, Marca y Modelo</div>'; return; }
      try {
        const resp = await fetch('ajax/actualizar-vehiculo.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: req.toString() });
        const ct = resp.headers.get('content-type') || '';
        const data = ct.includes('application/json') ? await resp.json() : {};
        if (resp.ok && data.success) {
          document.getElementById('alertVehiculos').innerHTML = '<div class="alert alert-success">Vehículo actualizado</div>';
          const modalEl = document.getElementById('modalEditarVehiculo');
          const modal = bootstrap.Modal.getInstance(modalEl);
          if (modal) modal.hide();
          loadFlota();
        } else {
          const msg = (data && data.error) ? data.error : 'No se pudo actualizar';
          document.getElementById('alertEditarVehiculo').innerHTML = '<div class="alert alert-danger">' + msg + '</div>';
        }
      } catch (e) {
        document.getElementById('alertEditarVehiculo').innerHTML = '<div class="alert alert-danger">' + (e.message || 'Error de red') + '</div>';
      }
    });

    async function deleteVehiculo(id) {
      if (!accesoAdministrador || soloLectura || bloquearFueraDeOT) { document.getElementById('alertVehiculos').innerHTML = '<div class="alert alert-danger">Sin permiso para eliminar</div>'; return; }
      if (!confirm('¿Eliminar vehículo ' + id + '?')) return;
      try {
        const resp = await fetch('ajax/eliminar-vehiculo.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'id=' + encodeURIComponent(id) });
        const data = await resp.json();
        if (resp.ok && data.success) {
          document.getElementById('alertVehiculos').innerHTML = '<div class="alert alert-success">Vehículo eliminado</div>';
          loadFlota();
        } else {
          document.getElementById('alertVehiculos').innerHTML = '<div class="alert alert-danger">' + (data.error || 'No se pudo eliminar') + '</div>';
        }
      } catch (e) {
        document.getElementById('alertVehiculos').innerHTML = '<div class="alert alert-danger">' + (e.message || 'Error de red') + '</div>';
      }
    }
  </script>
</body>

</html>