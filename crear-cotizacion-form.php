<?php
include("crear-cotizacion.php");
$fechaMin = date('Y-m-d\T00:00');
?>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-success" role="alert"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form id="formCrearCotizacion" method="post" action="" novalidate>
    <input type="hidden" name="pestana_activa" id="pestana_activa" value="<?= htmlspecialchars($_POST['pestana_activa'] ?? 'tab1') ?>">
    <ul class="nav nav-tabs" id="tabsCotizacion" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($pestana_activa === 'tab1') ? 'active' : '' ?>" id="tab1-tab" data-bs-toggle="tab" data-bs-target="#tab1" type="button" role="tab" onclick="document.getElementById('pestana_activa').value='tab1';">Datos Generales</button>
        </li>
        <li class="nav-item" role="presentation" id="vehiculo-tab-item" style="display:none;">
            <button class="nav-link <?= ($pestana_activa === 'tab3') ? 'active' : '' ?>" id="tab3-tab" data-bs-toggle="tab" data-bs-target="#tab3" type="button" role="tab" onclick="document.getElementById('pestana_activa').value='tab3';">Vehículos</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($pestana_activa === 'tab2') ? 'active' : '' ?>" id="tab2-tab" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab" onclick="document.getElementById('pestana_activa').value='tab2';">Ubicación</button>
        </li>
    </ul>

    <div class="tab-content border border-top-0 p-3" id="tabsCotizacionContent">
        <div class="tab-pane fade <?= ($pestana_activa === 'tab1') ? 'show active' : '' ?>" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">

            <div class="mb-3">
                <label for="id_division" class="form-label">División</label>
                <select class="form-select" name="id_division" id="id_division" required>
                    <option value="">Seleccione división</option>
                    <?php foreach ($divisiones as $div): ?>
                        <?php 
                            $divisionId = $div['id'] ?? '';
                            $divisionNombre = $div['nombre'] ?? ($div['NOMBRE'] ?? '');
                        ?>
                        <option value="<?= htmlspecialchars($divisionId) ?>" <?= ($sel_division == $divisionId) ? 'selected' : '' ?>><?= htmlspecialchars($divisionNombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="id_servicio" class="form-label">Servicio</label>
                <select class="form-select" name="id_servicio" id="id_servicio" required disabled>
                    <option value="">Primero seleccione una división</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="id_turno" class="form-label">Turno</label>
                <select class="form-select" name="id_turno" id="id_turno" required>
                    <option value="">Seleccione turno</option>
                    <?php foreach ($turnos_con_monto as $turno): ?>
                        <?php 
                            $turnoId = $turno['id'] ?? '';
                            $turnoNombre = $turno['nombre'] ?? ($turno['Nombre'] ?? '');
                        ?>
                        <option value="<?= htmlspecialchars($turnoId) ?>" <?= ($sel_turno == $turnoId) ? 'selected' : '' ?>><?= htmlspecialchars($turnoNombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="rut_cliente" class="form-label">Cliente (RUT)</label>
                <select class="form-select select2" name="rut_cliente" id="rut_cliente" required>
                    <option value="">Seleccione cliente</option>
                    <?php foreach ($clientes as $cli): ?>
                        <?php 
                            $clienteRut = $cli['id'] ?? ($cli['rut'] ?? '');
                            $clienteNombre = $cli['nombre'] ?? ($cli['Nombre'] ?? ($cli['NOMBRE'] ?? ''));
                        ?>
                        <option value="<?= htmlspecialchars($clienteRut) ?>" <?= ($sel_cliente == $clienteRut) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($clienteNombre) ?> - <?= htmlspecialchars($clienteRut) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="credito_cliente_info" class="mt-1"></div>
            </div>

            <div class="mb-3">
                <label for="nombre_solicitante" class="form-label">Solicitante</label>
                <input type="text" name="nombre_solicitante" id="nombre_solicitante" class="form-control" required value="<?= htmlspecialchars($_POST['nombre_solicitante'] ?? '') ?>" />
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            </div>

            <div class="mb-3">
                <label for="Fecha_servicio" class="form-label">Fecha y Hora Servicio</label>
                <input type="datetime-local" 
                    name="Fecha_servicio" 
                    id="Fecha_servicio" 
                    required 
                    class="form-control" 
                    value="<?= htmlspecialchars($_POST['Fecha_servicio'] ?? '') ?>" 
                    min="<?= $fechaMin ?>" />
            </div>

            <div class="mb-3">
                <label for="TELEFONO" class="form-label">Teléfono</label>
                <input type="text" name="TELEFONO" id="TELEFONO" class="form-control" required value="<?= htmlspecialchars($_POST['TELEFONO'] ?? '') ?>" />
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea name="descripcion" id="descripcion" class="form-control"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="mb-3" id="seccion_panne" style="display:none;">
                <label for="panne" class="form-label">Panne</label>
                <textarea name="panne" id="panne" class="form-control"><?= htmlspecialchars($_POST['panne'] ?? '') ?></textarea>
            </div>

            <div id="seccion_moneda" style="display:none;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tipo_moneda" class="form-label">Tipo Moneda</label>
                        <select class="form-select" name="tipo_moneda" id="tipo_moneda">
                            <option value="">Seleccione</option>
                            <option value="CLP" <?= (($_POST['tipo_moneda'] ?? '') === 'CLP') ? 'selected' : '' ?>>CLP</option>
                            <option value="UF" <?= (($_POST['tipo_moneda'] ?? '') === 'UF') ? 'selected' : '' ?>>UF</option>
                            <option value="USD" <?= (($_POST['tipo_moneda'] ?? '') === 'USD') ? 'selected' : '' ?>>USD</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="tipo_cambio" class="form-label">Tipo Cambio</label>
                        <input type="number" step="0.0001" min="0" class="form-control" name="tipo_cambio" id="tipo_cambio" value="<?= htmlspecialchars($_POST['tipo_cambio'] ?? '') ?>" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tarifa_unitaria" class="form-label">Tarifa Unitaria</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="tarifa_unitaria" id="tarifa_unitaria" value="<?= htmlspecialchars($_POST['tarifa_unitaria'] ?? '') ?>" />
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="cantidad" class="form-label">Cantidad</label>
                        <input type="number" step="1" min="0" class="form-control" name="cantidad" id="cantidad" value="<?= htmlspecialchars($_POST['cantidad'] ?? '') ?>" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3" id="seccion_tipo_tarifa">
                        <label for="tipo_tarifa" class="form-label">Tipo de Tarifa</label>
                        <select class="form-select" name="tipo_tarifa" id="tipo_tarifa">
                            <option value="">Seleccione</option>
                            <option value="Precio por Hora" <?= (($_POST['tipo_tarifa'] ?? '') === 'Precio por Hora') ? 'selected' : '' ?>>Precio por Hora</option>
                            <option value="Precio por Día" <?= (($_POST['tipo_tarifa'] ?? '') === 'Precio por Día') ? 'selected' : '' ?>>Precio por Día</option>
                            <option value="Precio por Evento" <?= (($_POST['tipo_tarifa'] ?? '') === 'Precio por Evento') ? 'selected' : '' ?>>Precio por Evento</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>

        <div class="tab-pane fade <?= ($pestana_activa === 'tab2') ? 'show active' : '' ?>" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">

            <div class="mb-3" id="seccion_link" style="display:none;">
                <label for="link" class="form-label">Link</label>
                <input type="url" name="link" id="link" class="form-control" placeholder="https://ejemplo.com" value="<?= htmlspecialchars($_POST['link'] ?? '') ?>">
            </div>

            <div class="mb-3" id="seccion_region_origen">
                <label for="region_origen" class="form-label">Región Origen</label>
                <select class="form-select" id="region_origen" name="region_origen">
                    <option value="">Todas las regiones</option>
                    <?php foreach ($regiones as $reg): ?>
                        <option value="<?= htmlspecialchars($reg) ?>" <?= ($sel_region_origen === $reg) ? 'selected' : '' ?>><?= htmlspecialchars($reg) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3" id="seccion_comuna_origen">
                <label for="id_comuna_origen" class="form-label">Comuna Origen</label>
                <select class="form-select select2" id="id_comuna_origen" name="id_comuna_origen">
                    <option value="">Seleccione comuna origen</option>
                    <?php foreach ($comunas_detalle as $com): ?>
                        <?php $comId = $com['id'] ?? ''; $comNombre = $com['nombre'] ?? ($com['Nombre'] ?? ''); $comRegion = $com['region'] ?? ''; ?>
                        <option value="<?= htmlspecialchars($comId) ?>" data-region="<?= htmlspecialchars($comRegion) ?>" <?= ($sel_comuna_origen == $comId) ? 'selected' : '' ?>><?= htmlspecialchars($comNombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3" id="seccion_direccion_origen">
                <label for="direccion_origen" class="form-label">Dirección Origen</label>
                <input type="text" class="form-control" id="direccion_origen" name="direccion_origen" placeholder="Ingrese dirección origen" value="<?= htmlspecialchars($_POST['direccion_origen'] ?? '') ?>">
            </div>

            <div class="mb-3" id="seccion_region_destino">
                <label for="region_destino" class="form-label">Región Destino</label>
                <select class="form-select" id="region_destino" name="region_destino">
                    <option value="">Todas las regiones</option>
                    <?php foreach ($regiones as $reg): ?>
                        <option value="<?= htmlspecialchars($reg) ?>" <?= ($sel_region_destino === $reg) ? 'selected' : '' ?>><?= htmlspecialchars($reg) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3" id="seccion_comuna_destino">
                <label for="id_comuna_destino" class="form-label">Comuna Destino</label>
                <select class="form-select select2" id="id_comuna_destino" name="id_comuna_destino">
                    <option value="">Seleccione comuna destino</option>
                    <?php foreach ($comunas_detalle as $com): ?>
                        <?php $comId = $com['id'] ?? ''; $comNombre = $com['nombre'] ?? ($com['Nombre'] ?? ''); $comRegion = $com['region'] ?? ''; ?>
                        <option value="<?= htmlspecialchars($comId) ?>" data-region="<?= htmlspecialchars($comRegion) ?>" <?= ($sel_comuna_destino == $comId) ? 'selected' : '' ?>><?= htmlspecialchars($comNombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3" id="seccion_direccion_destino">
                <label for="direccion_destino" class="form-label">Dirección Destino</label>
                <input type="text" class="form-control" id="direccion_destino" name="direccion_destino" placeholder="Ingrese dirección destino" value="<?= htmlspecialchars($_POST['direccion_destino'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="base_salida" class="form-label">Base de Salida</label>
                <select name="base_salida" id="base_salida" class="form-select" required>
                    <option value="" disabled <?= !isset($_POST['base_salida']) ? 'selected' : '' ?>>Seleccione una base</option>
                    <option value="Bulnes" <?= ($_POST['base_salida'] ?? '') === 'Bulnes' ? 'selected' : '' ?>>Bulnes</option>
                    <option value="Chillán" <?= ($_POST['base_salida'] ?? '') === 'Chillán' ? 'selected' : '' ?>>Chillán</option>
                    <option value="Maipú" <?= ($_POST['base_salida'] ?? '') === 'Maipú' ? 'selected' : '' ?>>Maipú</option>
                </select>
            </div>

            <div class="mb-3" id="seccion_tipo_camino" style="display:none;">
                <label for="tipo_camino" class="form-label">Tipo de Camino</label>
                 <select class="form-select" name="tipo_camino" id="tipo_camino" required>
                    <option value="">Seleccione tipo camino</option>
                    <?php foreach ($tipos_camino_con_monto as $tipocamino): ?>
                        <?php 
                            $tipoCaminoId = $tipocamino['id'] ?? '';
                            $tipoCaminoNombre = $tipocamino['nombre'] ?? ($tipocamino['Nombre'] ?? '');
                        ?>
                        <option value="<?= htmlspecialchars($tipoCaminoId) ?>" <?= ($sel_tipo_camino == $tipoCaminoId) ? 'selected' : '' ?>><?= htmlspecialchars($tipoCaminoNombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3" id="seccion_total_km" style="display:none;">
                <label for="total_km" class="form-label">Total KM:</label>
                <input 
                    type="number" 
                    name="total_km" 
                    id="total_km" 
                    class="form-control" 
                    value="<?= htmlspecialchars($es_rm ? '' : ($_POST['total_km'] ?? '')) ?>"
                    <?= $es_rm ? 'readonly' : '' ?> 
                    step="0.01" 
                    min="0" placeholder="0"
                />
                <?php if ($es_rm): ?>
                    <div class="form-text text-info">
                        Ambas comunas pertenecen a la RM. Se aplicará el mayor precio base automáticamente.
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="usar_monto_manual" name="usar_monto_manual" value="1"
                    <?= isset($_POST['usar_monto_manual']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="usar_monto_manual">
                    Modificar Monto
                </label>
            </div>

            <div class="mb-3">
                <label for="monto_manual" class="form-label">Monto a Pagar: $</label>
                <input type="number" id="monto_manual" name="monto_manual" class="form-control"
                    value="<?= isset($_POST['monto_manual']) ? htmlspecialchars($_POST['monto_manual']) : '' ?>"
                    <?= empty($_POST['usar_monto_manual']) ? 'readonly' : '' ?>>
                <input type="hidden" name="monto_final" id="monto_final_input" value="<?= isset($_POST['monto_final']) ? htmlspecialchars($_POST['monto_final']) : '' ?>">
                <small id="monto_iva_info" class="form-text text-dark"></small>
            </div>

            <?php if (!empty($mensaje2)): ?>
                <div class="alert alert-info mt-3"><?= htmlspecialchars($mensaje2) ?></div>
            <?php endif; ?>

        </div>
        <div class="tab-pane fade <?= ($pestana_activa === 'tab3') ? 'show active' : '' ?>" id="tab3" role="tabpanel" aria-labelledby="tab3-tab">
            <div class="mb-3">
                <label for="id_tipo_vehiculo" class="form-label">Tipo de Vehículo</label>
                <select class="form-select" name="id_tipo_vehiculo" id="id_tipo_vehiculo" required>
                    <option value="">Seleccione tipo vehículo</option>
                    <!-- Opciones se cargarán dinámicamente vía AJAX -->
                </select>
            </div>
            <div id="seccion_sub_tipo" class="mb-3" style="display:none;">
                <label for="id_sub_tipo" class="form-label">Subtipo</label>
                <select class="form-select" name="id_sub_tipo" id="id_sub_tipo" required>
                    <option value="">Seleccione subtipo</option>
                    <!-- Opciones subtipo aquí, se llenan dinámicamente -->
                </select>
            </div>

            <div id="seccion_tipo_direccion" class="mb-3" style="display:none;">
                <label for="tipo_direccion" class="form-label">Tipo de Dirección</label>
                <select class="form-select" name="tipo_direccion" id="tipo_direccion">
                    <option value="">Seleccione tipo de dirección</option>
                    <!-- Opciones tipo de dirección aquí, se llenan dinámicamente -->
                </select>
            </div>

            <div id="seccion_carga" class="mb-3" style="display:none;">
                <label for="carga" class="form-label">Tipo de Carga</label>
                <select class="form-select" name="carga" id="carga">
                    <option value="">Seleccione tipo de carga</option>
                    <!-- Opciones carga aquí, se llenan dinámicamente -->
                </select>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <button type="submit" name="enviar_confirmacion" class="btn btn-primary">Crear cotización</button>
    </div>
</form>

<?php if ($mostrar_modal_confirm): ?>
<!-- Modal Confirmación -->
<div class="modal fade" id="modalConfirm" tabindex="-1" aria-labelledby="modalConfirmLabel" aria-hidden="true" style="z-index:1060;">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="">
        <div class="modal-header">
          <h5 class="modal-title" id="modalConfirmLabel">Confirme la cotización</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
            <?php 
                $monto_mostrar = 0;
                if (isset($_POST['monto_inicial']) && is_numeric($_POST['monto_inicial'])) {
                    $monto_mostrar = (float)$_POST['monto_inicial'];
                } elseif (isset($_POST['monto_final']) && is_numeric($_POST['monto_final'])) {
                    $monto_mostrar = (float)$_POST['monto_final'];
                }
            ?>
            <?php foreach ($datos_confirmacion as $campo => $valor): ?>
                <?php 
                $campo_lower = strtolower($campo);
                if ($campo_lower !== 'monto inicial' && $campo_lower !== 'monto_a_pagar'): 
                    $label_limpio = str_replace('_', ' ', $campo);
                ?>
                    <p><strong><?= htmlspecialchars(ucfirst($label_limpio)) ?>:</strong> <?= htmlspecialchars($valor) ?></p>
                <?php endif; ?>
            <?php endforeach; ?>

            
            <?php 
                $iva_mostrar = isset($_POST['monto_iva']) && is_numeric($_POST['monto_iva']) ? (float)$_POST['monto_iva'] : round($monto_mostrar * 0.19, 2);
            ?>
            <p><strong>IVA (19%):</strong> <?= number_format($iva_mostrar, 2, ',', '.') ?></p>
            <!-- Mostrar el monto_inicial real -->
            <p><strong>Monto Total:</strong> <?= number_format($monto_mostrar, 2, ',', '.') ?></p>
            <input type="hidden" name="monto_inicial" value="<?= htmlspecialchars($monto_mostrar) ?>" />

            <!-- Reenviar todos los campos del formulario -->
            <?php foreach ($_POST as $key => $val): ?>
                <?php if (!in_array($key, ['enviar_confirmacion', 'confirmar', 'monto_inicial', 'direccion_origen', 'direccion_destino'])): ?>
                    <?php if (is_array($val)): ?>
                        <?php foreach ($val as $subval): ?>
                            <input type="hidden" name="<?= htmlspecialchars($key) ?>[]" value="<?= htmlspecialchars($subval) ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($val) ?>">
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Asegurar envío de direcciones -->
            <input type="hidden" name="direccion_origen" value="<?= htmlspecialchars($_POST['direccion_origen'] ?? '') ?>">
            <input type="hidden" name="direccion_destino" value="<?= htmlspecialchars($_POST['direccion_destino'] ?? '') ?>">
        </div>
        <div class="modal-footer">
          <div class="mt-3">
                <button type="submit" name="confirmar" class="btn btn-success">Confirmar</button>
            </div>

          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="js/crear-cotizacion.js"></script>
<script>
$(document).ready(function () {
    var returnToCreate = false;
    ['id_division','id_servicio','id_turno','rut_cliente','Fecha_servicio','id_comuna_origen','id_comuna_destino','tipo_camino','base_salida'].forEach(function(name){
        var el = document.querySelector('#formCrearCotizacion [name="'+name+'"]');
        if (el) {
            el.addEventListener('change', function(){ if (this.value) this.classList.remove('is-invalid'); });
            el.addEventListener('input', function(){ if (this.value) this.classList.remove('is-invalid'); });
        }
    });
    $(document).on('click', "#formCrearCotizacion button[name='enviar_confirmacion']", function (e) {
        e.preventDefault();
        var $form = $('#formCrearCotizacion');
        var data = $form.serialize() + '&enviar_confirmacion=1';
        $.ajax({
            url: '',
            type: 'POST',
            data: data,
            success: function (html) {
                var temp = document.createElement('div');
                temp.innerHTML = html;
                var modalConfirmEl = temp.querySelector('#modalConfirm');
                if (modalConfirmEl) {
                    var existingAlert = document.getElementById('ajaxError');
                    if (existingAlert) existingAlert.remove();
                    var existing = document.getElementById('modalConfirm');
                    if (existing) existing.remove();
                    document.body.appendChild(modalConfirmEl);
                    var bsModal = new bootstrap.Modal(modalConfirmEl);
                    var crearEl = document.getElementById('modalCrearCotizacion');
                    if (crearEl) {
                        try { (bootstrap.Modal.getInstance(crearEl) || new bootstrap.Modal(crearEl)).hide(); } catch(e){}
                    }
                    bsModal.show();
                    modalConfirmEl.addEventListener('hidden.bs.modal', function(){
                        setTimeout(function(){ if (modalConfirmEl && modalConfirmEl.parentNode) modalConfirmEl.parentNode.removeChild(modalConfirmEl); }, 150);
                    });
                } else {
                    var errorDiv = temp.querySelector('.alert.alert-danger');
                    var serverText = errorDiv ? (errorDiv.textContent || '').trim() : '';
                    var faltantes = [];
                    var formEl = document.getElementById('formCrearCotizacion');
                    function v(name) { var el = formEl.querySelector('[name="'+name+'"]'); return el ? (el.value || '') : ''; }
                    var campos = [
                        {n:'id_division',l:'División'},
                        {n:'id_servicio',l:'Servicio'},
                        {n:'id_turno',l:'Turno'},
                        {n:'rut_cliente',l:'Cliente (RUT)'},
                        {n:'Fecha_servicio',l:'Fecha y Hora Servicio'},
                        {n:'base_salida',l:'Base de Salida'},
                        {n:'id_comuna_origen',l:'Comuna Origen'} // Siempre requerido ahora
                    ];
                    if (debeMostrarCamposKmPanneCamino()) {
                        campos.push({n:'id_comuna_destino',l:'Comuna Destino'});
                        campos.push({n:'tipo_camino',l:'Tipo de Camino'});
                    }
                    campos.forEach(function(c){ var val = v(c.n); if (!val) { faltantes.push(c.l); var el = formEl.querySelector('[name="'+c.n+'"]'); if (el) { el.classList.add('is-invalid'); } } });
                    var msg = faltantes.length ? faltantes.join(', ') : (serverText || 'Complete los campos requeridos');
                    var existingAlert = document.getElementById('ajaxError');
                    if (!existingAlert) {
                        existingAlert = document.createElement('div');
                        existingAlert.id = 'ajaxError';
                        existingAlert.className = 'alert alert-danger';
                        var container = formEl.parentNode;
                        container.insertBefore(existingAlert, container.firstChild);
                    }
                    existingAlert.textContent = msg;
                    if (faltantes.length) {
                        var firstInvalid = formEl.querySelector('.is-invalid');
                        if (firstInvalid && typeof firstInvalid.scrollIntoView === 'function') firstInvalid.scrollIntoView({behavior:'smooth',block:'center'});
                    }
                }
            },
            error: function () {
                alert('Error al procesar la confirmación.');
            }
        });
    });

    $(document).on('click', '#modalConfirm button[name="confirmar"]', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creando cotización...');

        var $modalForm = $btn.closest('form');
        var payload = $modalForm.serialize() + '&confirmar=1';
        $.ajax({
            url: '',
            type: 'POST',
            data: payload,
            success: function (html) {
                var temp = document.createElement('div');
                temp.innerHTML = html;
                var ok = temp.querySelector('.alert.alert-success');
                var err = temp.querySelector('.alert.alert-danger');
                if (ok) {
                    var confirmEl = document.getElementById('modalConfirm');
                    if (confirmEl) { try { bootstrap.Modal.getInstance(confirmEl)?.hide(); } catch(e){} }
                    var crearEl = document.getElementById('modalCrearCotizacion');
                    if (crearEl) { try { bootstrap.Modal.getInstance(crearEl)?.hide(); } catch(e){} }
                    var globalAlert = document.getElementById('globalAlert');
                    if (!globalAlert) {
                        globalAlert = document.createElement('div');
                        globalAlert.id = 'globalAlert';
                        globalAlert.className = 'mb-3';
                        var listContainer = document.querySelector('.container.py-5');
                        if (listContainer) listContainer.insertBefore(globalAlert, listContainer.children[1]);
                    }
                    globalAlert.innerHTML = '<div class="alert alert-success">Cotizacion creada correctamente</div>';
                    document.getElementById('formCrearCotizacion').reset();
                    // Limpiar Select2
                    $('#formCrearCotizacion select.select2').val(null).trigger('change');
                    // Ocultar secciones dinámicas
                    $('#vehiculo-tab-item').hide();
                    $('#seccion_panne, #seccion_moneda, #seccion_link, #seccion_region_origen, #seccion_comuna_origen, #seccion_direccion_origen, #seccion_tipo_camino, #seccion_total_km, #seccion_sub_tipo, #seccion_tipo_direccion, #seccion_carga').hide();
                    // Limpiar info crédito
                    $('#credito_cliente_info').html('');
                    // Volver a tab 1
                    var tab1 = document.getElementById('tab1-tab');
                    if (tab1) { 
                        var bsTab = new bootstrap.Tab(tab1); 
                        bsTab.show(); 
                    }
                    try {
                        if (typeof cargarCotizaciones === 'function') {
                            if (typeof currentBusqueda !== 'undefined') { currentBusqueda = ''; }
                            var buscarInput = document.getElementById('buscarInput');
                            if (buscarInput) buscarInput.value = '';
                            setTimeout(function(){ cargarCotizaciones(window.currentPagina || 1, (typeof limite !== 'undefined' ? limite : 10)); }, 200);
                        }
                    } catch (e) {}
                } else {
                    $btn.prop('disabled', false).text(originalText);
                    var msg = err ? (err.textContent || '').trim() : 'Ocurrió un error al guardar.';
                    var existingError = document.getElementById('ajaxError');
                    if (!existingError) {
                        existingError = document.createElement('div');
                        existingError.id = 'ajaxError';
                        existingError.className = 'alert alert-danger';
                        var container = document.getElementById('formCrearCotizacion').parentNode;
                        container.insertBefore(existingError, container.firstChild);
                    }
                    existingError.textContent = msg;
                }
            },
            error: function () {
                $btn.prop('disabled', false).text(originalText);
                alert('Error al confirmar la cotización.');
            }
        });
    });
    $(document).on('click', '#modalConfirm .btn-secondary[data-bs-dismiss="modal"]', function (e) {
        e.preventDefault();
        returnToCreate = true;
        var el = document.getElementById('modalConfirm');
        if (el) {
            var inst = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
            inst.hide();
        }
    });

    document.addEventListener('hidden.bs.modal', function (event) {
        if (event && event.target && event.target.id === 'modalConfirm' && returnToCreate) {
            returnToCreate = false;
            var crearEl = document.getElementById('modalCrearCotizacion');
            if (crearEl) {
                try { (bootstrap.Modal.getInstance(crearEl) || new bootstrap.Modal(crearEl)).show(); } catch(e){}
            }
            return;
        }
        setTimeout(function(){
            var opened = document.querySelectorAll('.modal.show');
            if (opened.length > 0) return;
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            document.querySelectorAll('.modal-backdrop').forEach(function(el){
                el.classList.remove('show');
                setTimeout(function(){ el.remove(); }, 150);
            });
        }, 200);
    });

    function initSelect2() {
        if (!$('#rut_cliente').hasClass("select2-hidden-accessible")) {
            $('#rut_cliente').select2({
                placeholder: "Seleccione cliente",
                allowClear: true,
                dropdownParent: $('#tab1'),
                width: '100%',
                language: { noResults: () => "No hay resultados" }
            });
        }
        if (!$('#id_comuna_origen').hasClass("select2-hidden-accessible")) {
            $('#id_comuna_origen').select2({
                placeholder: "Seleccione comuna origen",
                allowClear: true,
                dropdownParent: $('#tab2'),
                width: '100%',
                language: { noResults: () => "No hay resultados" }
            });
        }
        if (!$('#id_comuna_destino').hasClass("select2-hidden-accessible")) {
            $('#id_comuna_destino').select2({
                placeholder: "Seleccione comuna destino",
                allowClear: true,
                dropdownParent: $('#tab2'),
                width: '100%',
                language: { noResults: () => "No hay resultados" }
            });
        }
        if (!$('#region_origen').hasClass("select2-hidden-accessible")) {
            $('#region_origen').select2({ dropdownParent: $('#tab2'), width: '100%' });
        }
        if (!$('#region_destino').hasClass("select2-hidden-accessible")) {
            $('#region_destino').select2({ dropdownParent: $('#tab2'), width: '100%' });
        }
    }

    if ($('#tab1').hasClass('show active')) {
        initSelect2();
    }

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('data-bs-target') === '#tab1') {
            initSelect2();
        }
    });

    var todasOrigen = Array.from(document.querySelectorAll('#id_comuna_origen option')).map(function(o){ return {v:o.value, t:o.textContent, r:(o.getAttribute('data-region')||'')}; });
    var todasDestino = Array.from(document.querySelectorAll('#id_comuna_destino option')).map(function(o){ return {v:o.value, t:o.textContent, r:(o.getAttribute('data-region')||'')}; });

    function filtrar(selectRegion, selectComuna, todas) {
        var region = $(selectRegion).val() || '';
        var $sel = $(selectComuna);
        var opts = '<option value="">Seleccione comuna</option>';
        todas.forEach(function(o){ if (!region || (o.r && o.r === region)) { opts += '<option value="'+o.v+'" data-region="'+(o.r||'')+'">'+o.t+'</option>'; } });
        $sel.html(opts).val('').trigger('change');
    }

    $('#region_origen').on('change', function(){ filtrar('#region_origen', '#id_comuna_origen', todasOrigen); });
    $('#region_destino').on('change', function(){ filtrar('#region_destino', '#id_comuna_destino', todasDestino); });

    if ($('#region_origen').val()) { filtrar('#region_origen', '#id_comuna_origen', todasOrigen); }
    if ($('#region_destino').val()) { filtrar('#region_destino', '#id_comuna_destino', todasDestino); }

    // Función para calcular y mostrar crédito disponible del cliente
    function mostrarCreditoCliente(rut) {
        if (!rut) {
            $('#credito_cliente_info').html('');
            actualizarBotonesSegunCredito(null);
            return;
        }
        $.ajax({
            url: 'obtener_credito_disponible.php',
            type: 'GET',
            data: { rut: rut },
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    const disponible = parseFloat((response.credito_disponible ?? response.credito) || 0);
                    const colorClass = disponible > 0 ? 'text-success' : 'text-danger';
                    const texto = 'Crédito disponible: $' + disponible.toLocaleString('es-CL');
                    $('#credito_cliente_info').html(`<small class="${colorClass}">${texto}</small>`);
                    actualizarBotonesSegunCredito(disponible);
                } else {
                    $('#credito_cliente_info').html('');
                    actualizarBotonesSegunCredito(null);
                }
            },
            error: function() {
                $('#credito_cliente_info').html('');
                actualizarBotonesSegunCredito(null);
                console.error('Error al calcular/obtener crédito disponible del cliente');
            }
        });
    }

    function actualizarBotonesSegunCredito(credito) {
        $("button[name='enviar_confirmacion']").prop('disabled', false);
        $("#solicitar_codigo").hide();
        $("#codigo_section").hide();
        $("#loader-codigo").hide();
    }

    // Mostrar crédito inicial si cliente ya seleccionado
    var clienteInicial = $('#rut_cliente').val();
    if (clienteInicial) {
        mostrarCreditoCliente(clienteInicial);
    }

    // Al cambiar cliente, mostrar crédito dinámico
    $('#rut_cliente').on('change', function () {
        var rut = $(this).val();
        mostrarCreditoCliente(rut);
        recalcularMonto();
    });

    // Código y solicitud de código deshabilitados por requerimiento

    // Función para mostrar/ocultar la pestaña Vehículos según el servicio seleccionado
    function mostrarTabVehiculoSiCorresponde(id_servicio) {
        if (!id_servicio) {
            console.log("No hay id_servicio para verificar pestaña");
            $('#vehiculo-tab-item').hide();
            return;
        }

        // Obtener el nombre del servicio
        var opt = document.querySelector('#id_servicio option[value="'+id_servicio+'"]');
        var nombre = (opt && (opt.getAttribute('data-nombre')||opt.textContent||'')) || '';
        var div = String($('#id_division').val()||'');
        var n = normalizarNombre(nombre);
        
        // Mostrar pestaña solo para Rescate, Remolque (div 1 o 6), o Despeje de vía
        var mostrar = false;
        if (n === 'rescate') { mostrar = true; }
        if (n === 'remolque' && (div === '1' || div === '6')) { mostrar = true; }
        if (n === 'despeje de via' || n === 'despeje de vía') { mostrar = true; }
        
        if (mostrar) {
            $('#vehiculo-tab-item').show();
            if ($('#pestana_activa').val() === 'tab3' || $('#tab3').hasClass('show active')) {
                $('#tab3').addClass('show active');
                $('#tab3-tab').addClass('active');
                $('#tab1-tab, #tab2-tab').removeClass('active');
                $('#tab1, #tab2').removeClass('show active');
            }
        } else {
            $('#vehiculo-tab-item').hide();
            if ($('#tab3').hasClass('show active') || $('#pestana_activa').val() === 'tab3') {
                $('#tab3').removeClass('show active');
                $('#tab3-tab').removeClass('active');
                $('#tab1-tab').addClass('active');
                $('#tab1').addClass('show active');
                $('#pestana_activa').val('tab1');
            }
        }
    }

    var vehiculosDatos = [];
    var subtiposDatos = {};
    var direccionesDatos = {};
    var cargasDatos = {};

    function normalizarNombre(nombre) {
        if (!nombre) return '';
        try {
            return nombre.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
        } catch (e) {
            return (nombre || '').toLowerCase().trim();
        }
    }

    function debeMostrarCamposKmPanneCamino() {
        var opt = document.querySelector('#id_servicio option:checked');
        var nombre = (opt && (opt.getAttribute('data-nombre') || opt.textContent || '')) || '';
        var n = normalizarNombre(nombre);
        var div = String($('#id_division').val() || '');
        if (n === 'remolque' && (div === '1' || div === '6')) return true;
        if (n === 'despeje de via' || n === 'despeje de vía') return true;
        if (n === 'rescate') return true;
        return false;
    }

    function toggleCamposOrigen() {
        var mostrar = debeMostrarCamposKmPanneCamino();
        var $secReg = $('#seccion_region_origen');
        var $secCom = $('#seccion_comuna_origen');
        var $secDir = $('#seccion_direccion_origen');
        
        // Siempre mostrar y requerir origen
        $secReg.show();
        $secCom.show();
        $secDir.show();
        $('#id_comuna_origen').prop('required', true);

        // Destino es opcional si no es servicio de ruta/grúa
        // CORRECCION: Debe Ocultarse si no es remolque/rescate/despeje
        var $secRegDest = $('#seccion_region_destino');
        var $secComDest = $('#seccion_comuna_destino');
        var $secDirDest = $('#seccion_direccion_destino');

        if (mostrar) {
             $secRegDest.show();
             $secComDest.show();
             $secDirDest.show();
             $('#id_comuna_destino').prop('required', true);
        } else {
             $secRegDest.hide();
             $secComDest.hide();
             $secDirDest.hide();
             $('#id_comuna_destino').prop('required', false);
             // Limpiar valores al ocultar para evitar envíos basura
             $('#id_comuna_destino').val('').trigger('change');
             $('#region_destino').val('').trigger('change');
             $('#direccion_destino').val('');
        }
    }

    function toggleCamposKmPanneCamino() {
        var mostrar = debeMostrarCamposKmPanneCamino();
        var $secKm = $('#seccion_total_km');
        var $secPanne = $('#seccion_panne');
        var $secLink = $('#seccion_link');
        var $secCamino = $('#seccion_tipo_camino');
        if (mostrar) {
            $secKm.show();
            $secPanne.show();
            $secLink.show();
            $secCamino.show();
            $('#tipo_camino').prop('required', true);
        } else {
            $secKm.hide();
            $secPanne.hide();
            $secLink.hide();
            $secCamino.hide();
            $('#tipo_camino').prop('required', false).val('').trigger('change');
            $('#total_km').val('');
            $('#panne').val('');
            $('#link').val('');
        }
    }

    function ocultarSeccionesExtras() {
        $('#seccion_sub_tipo, #seccion_tipo_direccion, #seccion_carga').hide();
        $('#id_sub_tipo, #tipo_direccion, #carga').html('');
    }

    var servicio_inicial = $('#id_servicio').val();
    var division_inicial = $('#id_division').val();
    if (servicio_inicial && division_inicial) {
        mostrarTabVehiculoSiCorresponde(servicio_inicial);
        toggleCamposKmPanneCamino();
        toggleCamposOrigen();

        $.post('vehiculos_por_servicios.php', { id_division: division_inicial, id_servicio: servicio_inicial }, function(data) {
            vehiculosDatos = data.vehiculos || [];
            subtiposDatos = data.subtipos || {};
            direccionesDatos = data.direcciones || {};
            cargasDatos = data.cargas || {};

            let options = '<option value="">Seleccione tipo vehículo</option>';
            $.each(vehiculosDatos, function(i, veh) {
                options += `<option value="${veh.id}" data-tipo="${veh.Tipo}">${veh.Tipo}</option>`;
            });
            $('#id_tipo_vehiculo').html(options);

            $('#id_tipo_vehiculo').trigger('change');
        }, 'json');

        // Toggle sección moneda (Tipo Moneda, Cambio, Tarifa U., Cantidad)
        (function(){
            var opt = document.querySelector('#id_servicio option[value="'+servicio_inicial+'"]');
            var nombre = (opt && (opt.getAttribute('data-nombre')||opt.textContent||'')) || '';
            var n = normalizarNombre(nombre);
            var ocultar = false;
            if (n === 'rescate') { ocultar = true; }
            if (n === 'remolque') { ocultar = true; } 
            if (n === 'despeje de via' || n === 'despeje de vía') { ocultar = true; }

            if (ocultar) {
                $('#seccion_moneda').hide();
                $('#tipo_moneda, #tipo_cambio, #tarifa_unitaria, #cantidad, #tipo_tarifa').val('');
            } else {
                $('#seccion_moneda').show();
            }
        })();
    } else {
        $('#vehiculo-tab-item').hide();
        ocultarSeccionesExtras();
        toggleCamposKmPanneCamino();
        toggleCamposOrigen();
    }

    $('#id_division').change(function() {
        let id_div = $(this).val();
        $.post('servicios_por_division.php', { id_division: id_div }, function(data) {
            let options = '<option value="">Seleccione servicio</option>';
            $.each(data, function(i, serv) {
                options += `<option value="${serv.id}" data-nombre="${serv.Nombre}">${serv.Nombre}</option>`;
            });
            $('#id_servicio').html(options);
            $('#id_tipo_vehiculo').html('<option value="">Seleccione tipo vehículo</option>');
            $('#vehiculo-tab-item').hide();
            ocultarSeccionesExtras();
            toggleCamposKmPanneCamino();
            toggleCamposOrigen();

            if (data.length > 0) {
                $('#id_servicio').val(data[0].id).trigger('change');
            }
        }, 'json');
    });

    $('#id_servicio').change(function() {
        let id_div = $('#id_division').val();
        let id_serv = $(this).val();

        if (!id_div || !id_serv) {
            $('#id_tipo_vehiculo').html('<option value="">Seleccione tipo vehículo</option>');
            ocultarSeccionesExtras();
            $('#vehiculo-tab-item').hide();
            toggleCamposKmPanneCamino();
            toggleCamposOrigen();
            return;
        }

        $.post('vehiculos_por_servicios.php', { id_division: id_div, id_servicio: id_serv }, function(data) {
            vehiculosDatos = data.vehiculos || [];
            subtiposDatos = data.subtipos || {};
            direccionesDatos = data.direcciones || {};
            cargasDatos = data.cargas || {};

            let options = '<option value="">Seleccione tipo vehículo</option>';
            $.each(vehiculosDatos, function(i, veh) {
                options += `<option value="${veh.id}" data-tipo="${veh.Tipo}">${veh.Tipo}</option>`;
            });
            $('#id_tipo_vehiculo').html(options);

            $('#id_tipo_vehiculo').trigger('change');
        }, 'json').fail(function() {
            console.error("Error al obtener vehículos");
            $('#id_tipo_vehiculo').html('<option value="">Seleccione tipo vehículo</option>');
            ocultarSeccionesExtras();
        });

        mostrarTabVehiculoSiCorresponde(id_serv);
        toggleCamposKmPanneCamino();
        toggleCamposOrigen();

        // Toggle sección moneda (Tipo Moneda, Cambio, Tarifa U., Cantidad, Tipo Tarifa)
        (function(){
            var opt = document.querySelector('#id_servicio option[value="'+id_serv+'"]');
            var nombre = (opt && (opt.getAttribute('data-nombre')||opt.textContent||'')) || '';
            var n = normalizarNombre(nombre);
            var ocultar = false;
            // "NO aparece en servicios de 'Rescate', 'Despeje de via' ni 'Remolque'"
            if (n === 'rescate') { ocultar = true; }
            if (n === 'remolque') { ocultar = true; } // Sin restricción de división según solicitud
            if (n === 'despeje de via' || n === 'despeje de vía') { ocultar = true; }

            if (ocultar) {
                $('#seccion_moneda').hide();
                // Limpiar valores
                $('#tipo_moneda, #tipo_cambio, #tarifa_unitaria, #cantidad, #tipo_tarifa').val('');
            } else {
                $('#seccion_moneda').show();
            }
        })();
    });

    $('#id_tipo_vehiculo').change(function() {
        let idVehiculo = $(this).val();
        let vehiculo = vehiculosDatos.find(v => v.id == idVehiculo);

        if (!vehiculo) {
            ocultarSeccionesExtras();
            return;
        }

        let tipoSeleccionado = vehiculo.Tipo;

        if (subtiposDatos[tipoSeleccionado]) {
            let opcionesSubtipo = '<option value="">Seleccione subtipo</option>';
            subtiposDatos[tipoSeleccionado].forEach(function(s) {
                opcionesSubtipo += `<option value="${s}">${s}</option>`;
            });
            $('#id_sub_tipo').html(opcionesSubtipo);
            $('#seccion_sub_tipo').show();
        } else {
            $('#seccion_sub_tipo').hide();
            $('#id_sub_tipo').html('');
        }

        if (direccionesDatos[tipoSeleccionado]) {
            let opcionesDireccion = '<option value="">Seleccione tipo de dirección</option>';
            direccionesDatos[tipoSeleccionado].forEach(function(d) {
                opcionesDireccion += `<option value="${d}">${d}</option>`;
            });
            $('#tipo_direccion').html(opcionesDireccion);
            $('#seccion_tipo_direccion').show();
        } else {
            $('#seccion_tipo_direccion').hide();
            $('#tipo_direccion').html('');
        }

        if (cargasDatos[tipoSeleccionado]) {
            let opcionesCarga = '<option value="">Seleccione tipo de carga</option>';
            cargasDatos[tipoSeleccionado].forEach(function(c) {
                opcionesCarga += `<option value="${c}">${c}</option>`;
            });
            $('#carga').html(opcionesCarga);
            $('#seccion_carga').show();
        } else {
            $('#seccion_carga').hide();
            $('#carga').html('');
        }
        
        // Obtener tarifa del vehículo cuando cambia el tipo
        obtenerTarifaVehiculo(function() {
            recalcularMonto();
        });
    });

    // Variable global para almacenar la tarifa del vehículo
    var tarifaVehiculo = 0;

    // Función para obtener la tarifa del vehículo desde la base de datos
    function obtenerTarifaVehiculo(callback) {
        var idVehiculo = $('#id_tipo_vehiculo').val();
        if (!idVehiculo) {
            tarifaVehiculo = 0;
            if (callback) callback();
            return;
        }

        // Buscar el tipo de vehículo seleccionado
        var vehiculo = vehiculosDatos.find(v => v.id == idVehiculo);
        if (!vehiculo) {
            tarifaVehiculo = 0;
            if (callback) callback();
            return;
        }

        var data = {
            tipo: vehiculo.Tipo,
            sub_tipo: $('#id_sub_tipo').val() || '',
            tipo_direccion: $('#tipo_direccion').val() || '',
            carga: $('#carga').val() || ''
        };

        $.ajax({
            url: 'ajax/obtener-tarifa-vehiculo.php',
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    tarifaVehiculo = parseFloat(response.monto_inicial) || 0;
                    console.log('Tarifa vehículo obtenida:', tarifaVehiculo);
                } else {
                    tarifaVehiculo = 0;
                    console.error('Error al obtener tarifa:', response.error);
                }
                if (callback) callback();
            },
            error: function() {
                tarifaVehiculo = 0;
                console.error('Error al conectar con obtener-tarifa-vehiculo.php');
                if (callback) callback();
            }
        });
    }

    function recalcularMonto() {
        /* SE ELIMINA EL BLOQUEO: Se debe calcular SIEMPRE el base y SUMAR lo de moneda
        if ($('#seccion_moneda').is(':visible')) {
            var aplicado = calcularMontoMoneda();
            if (aplicado) { return; }
        }
        */

        const data = {
            id_comuna_origen: $('#id_comuna_origen').val(),
            id_comuna_destino: $('#id_comuna_destino').val(),
            total_km: $('#total_km').val() || 0,
            id_turno: $('#id_turno').val(),
            tipo_camino: $('#tipo_camino').val(),
            id_tipo_vehiculo: $('#id_tipo_vehiculo').val(),
            rut_cliente: $('#rut_cliente').val(),
            accion: 'calcular'
        };

        if (!data.rut_cliente && (!data.id_comuna_origen || !data.id_comuna_destino)) return;

        $.ajax({
            url: 'calcular_monto.php',
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var subtotal = 0;
                    if (typeof response.monto !== 'undefined') { subtotal = parseFloat(response.monto)||0; }
                    else if (typeof response.monto_inicial !== 'undefined') { subtotal = parseFloat(response.monto_inicial)||0; }
                    
                    // SUMAR la tarifa del vehículo al subtotal
                    subtotal += tarifaVehiculo;

                    // SUMAR cálculo de moneda si aplica
                    var montoMoneda = obtenerMontoMoneda();
                    subtotal += montoMoneda;
                    
                    var iva = Math.round(subtotal * 0.19);
                    var total = Math.round(subtotal + iva);
                    $('#monto_manual').val(total);
                    $('#monto_final_input').val(total);
                    var fmt = function(n){ return Number(n).toLocaleString('es-CL'); };
                    $('#monto_iva_info').text('Subtotal: $'+fmt(subtotal)+' | IVA 19%: $'+fmt(iva)+' | Total: $'+fmt(total));
                    let hint = $('#monto_formula_hint');
                    if (!hint.length) {
                        $('#monto_manual').after('<small id="monto_formula_hint" class="form-text text-dark"></small>');
                        hint = $('#monto_formula_hint');
                    }
                    hint.removeClass('text-info').addClass('text-dark');
                    var mensaje = (response.tarifa_especial ? 'Tarifa especial aplicada' : 'Cálculo estándar');
                    if (tarifaVehiculo > 0) {
                        mensaje += ' + Tarifa vehículo: $' + fmt(tarifaVehiculo);
                    }
                    if (montoMoneda > 0) {
                        mensaje += ' + Adicional Moneda: $' + fmt(montoMoneda);
                    }
                    mensaje += ' + IVA 19%';
                    hint.text(mensaje);
                } else {
                    console.error('Error cálculo monto:', response.message);
                }
            },
            error: function() {
                console.error('Error al conectar con calcular_monto.php');
            }
        });
    }

    $('#id_comuna_origen, #id_comuna_destino, #total_km, #id_turno, #tipo_camino, #id_tipo_vehiculo').on('change keyup', function () {
        recalcularMonto();
    });

    // Event listeners para campos de vehículo que afectan la tarifa
    $('#id_sub_tipo, #tipo_direccion, #carga').on('change', function() {
        // Primero obtener la tarifa del vehículo, luego recalcular
        obtenerTarifaVehiculo(function() {
            recalcularMonto();
        });
    });

    const checkbox = $('#usar_monto_manual');
    const montoInput = $('#monto_manual');

    function toggleReadonly() {
        montoInput.prop('readonly', !checkbox.is(':checked'));
        if (!checkbox.is(':checked')) {
            recalcularMonto();
        }
    }

    checkbox.on('change', toggleReadonly);
    toggleReadonly();

    recalcularMonto();

    // Cálculo por moneda (Tipo Moneda / Tipo Cambio / Tarifa Unitaria / Cantidad)
    // Nueva función que SOLO retorna el cálculo de moneda, sin setear UI
    function obtenerMontoMoneda() {
        // CORRECCION: No usar is(':visible') porque falla cuando estamos en otra pestaña (ej: Ubicación)
        // en su lugar, confiamos en que si hay valores, se debe calcular.
        
        var moneda = ($('#tipo_moneda').val() || '').toUpperCase();
        var cambioVal = parseFloat($('#tipo_cambio').val() || '0');
        var tarifa = parseFloat($('#tarifa_unitaria').val() || '0');
        var cantidad = parseFloat($('#cantidad').val() || '0');
        
        console.log("Calculando Moneda:", { moneda, cambioVal, tarifa, cantidad });

        if (!tarifa || !cantidad) {
            console.log("Faltan tarifa o cantidad -> retorna 0");
            return 0;
        }
        
        var cambio = cambioVal;
        if (moneda === 'CLP' || moneda === '') {
            if (!cambio || cambio <= 0) cambio = 1;
        } else {
            if (!cambio || cambio <= 0) {
                console.log("Falta cambio para moneda extranjera -> retorna 0");
                return 0;
            }
        }
        
        var subtotal = tarifa * cantidad * (cambio || 1);
        console.log("Subtotal Moneda:", subtotal);
        return subtotal;
    }
    
    // Mantener la función antigua solo por compatibilidad, pero redirigiendo a recalcular
    function calcularMontoMoneda() {
        // Ahora simplemente triggereamos el recalculo general
        recalcularMonto();
        return true; 
    }

    $('#tipo_moneda, #tipo_cambio, #tarifa_unitaria, #cantidad').on('input change', function(){
        recalcularMonto();
    });

    $('#tipo_moneda, #tipo_cambio, #tarifa_unitaria, #cantidad').on('input change', function(){
        if ($('#seccion_moneda').is(':visible')) { calcularMontoMoneda(); }
    });
});

// Cargar servicios cuando se selecciona una división
document.getElementById('id_division').addEventListener('change', function() {
    const idDivision = this.value;
    const selectServicio = document.getElementById('id_servicio');
    
    if (idDivision) {
        // Habilitar el select y mostrar loading
        selectServicio.disabled = false;
        selectServicio.innerHTML = '<option value="">Cargando servicios...</option>';
        
        fetch(`ajax/obtener-servicios.php?id_division=${idDivision}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    selectServicio.innerHTML = '<option value="">Seleccione servicio</option>';
                    data.servicios.forEach(servicio => {
                        const option = document.createElement('option');
                        option.value = servicio.id;
                        option.textContent = servicio.nombre;
                        option.dataset.nombre = servicio.nombre;
                        selectServicio.appendChild(option);
                    });
                } else {
                    selectServicio.innerHTML = '<option value="">Error al cargar servicios</option>';
                    console.error('Error:', data.error);
                }
                try {
                    // SE ELIMINA LA RESTRICCION DE OCULTAR MONEDA
                    var seccion = document.getElementById('seccion_moneda');
                    if (seccion) {
                        seccion.style.display = '';
                    }
                } catch(e){}
            })
            .catch(error => {
                selectServicio.innerHTML = '<option value="">Error de conexión</option>';
                console.error('Error:', error);
            });
    } else {
        // Deshabilitar el select y limpiar opciones
        selectServicio.disabled = true;
        selectServicio.innerHTML = '<option value="">Primero seleccione una división</option>';
        // No ocultar moneda si no hay servicio/div, por default dejar visible o segun layout
        // Pero el requerimiento es que esté disponible, asi que lo dejamos visible.
        var seccion = document.getElementById('seccion_moneda');
        if (seccion) seccion.style.display = '';
    }
});
</script>
