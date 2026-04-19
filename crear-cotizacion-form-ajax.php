<?php
include("crear-cotizacion.php");

?>
<head>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<form method="post" action="" novalidate>
    <input type="hidden" name="pestana_activa" id="pestana_activa" value="<?= htmlspecialchars($_POST['pestana_activa'] ?? 'tab1') ?>">
    <ul class="nav nav-tabs" id="tabsCotizacion" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($pestana_activa === 'tab1') ? 'active' : '' ?>" id="tab1-tab" data-bs-toggle="tab" data-bs-target="#tab1" type="button" role="tab" onclick="document.getElementById('pestana_activa').value='tab1';">Datos Generales</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($pestana_activa === 'tab2') ? 'active' : '' ?>" id="tab2-tab" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab" onclick="document.getElementById('pestana_activa').value='tab2';">Ubicación</button>
        </li>
        <?php if ($mostrar_tab_vehiculo): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($pestana_activa === 'tab3') ? 'active' : '' ?>" id="tab3-tab" data-bs-toggle="tab" data-bs-target="#tab3" type="button" role="tab" onclick="document.getElementById('pestana_activa').value='tab3';">Vehículos</button>
        </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content border border-top-0 p-3" id="tabsCotizacionContent">
        <div class="tab-pane fade <?= ($pestana_activa === 'tab1') ? 'show active' : '' ?>" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">

            <div class="mb-3">
                <label for="id_division" class="form-label">División</label>
                <select class="form-select" name="id_division" id="id_division" required onchange="this.form.submit()">
                    <option value="">Seleccione división</option>
                    <?php foreach ($divisiones as $div): ?>
                        <option value="<?= $div['id'] ?>" <?= ($sel_division == $div['id']) ? 'selected' : '' ?>><?= htmlspecialchars($div['NOMBRE']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="id_servicio" class="form-label">Servicio</label>
                <select class="form-select" name="id_servicio" id="id_servicio" required onchange="this.form.submit()">
                    <option value="">Seleccione servicio</option>
                    <?php foreach ($servicios as $serv): ?>
                        <option value="<?= $serv['id'] ?>" <?= ($sel_servicio == $serv['id']) ? 'selected' : '' ?>><?= htmlspecialchars($serv['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="id_turno" class="form-label">Turno</label>
                <select class="form-select" name="id_turno" id="id_turno" required>
                    <option value="">Seleccione turno</option>
                    <?php foreach ($turnos_con_monto as $turno): ?>
                        <option value="<?= $turno['id'] ?>" <?= ($sel_turno == $turno['id']) ? 'selected' : '' ?>><?= htmlspecialchars($turno['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="rut_cliente" class="form-label">Cliente (RUT)</label>
                <select class="form-select select2" name="rut_cliente" id="rut_cliente" required>
                    <option value="">Seleccione cliente</option>
                    <?php foreach ($clientes as $cli): ?>
                        <option value="<?= htmlspecialchars($cli['rut']) ?>" <?= ($sel_cliente == $cli['rut']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cli['nombre']) ?> - <?= htmlspecialchars($cli['rut']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($sel_cliente) && $credito_cliente !== null): ?>
                    <small class="mt-1 d-block <?= ($credito_cliente > 0) ? 'text-success' : 'text-danger' ?>">
                        El cliente tiene $<?= number_format($credito_cliente, 0, ',', '.') ?> de crédito.
                    </small>
                <?php endif; ?>
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
                <input type="datetime-local" name="Fecha_servicio" id="Fecha_servicio" required class="form-control" value="<?= htmlspecialchars($_POST['Fecha_servicio'] ?? '') ?>" />
            </div>

            <div class="mb-3">
                <label for="TELEFONO" class="form-label">Teléfono</label>
                <input type="text" name="TELEFONO" id="TELEFONO" class="form-control" required value="<?= htmlspecialchars($_POST['TELEFONO'] ?? '') ?>" />
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea name="descripcion" id="descripcion" class="form-control"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label for="panne" class="form-label">Panne</label>
                <textarea name="panne" id="panne" class="form-control"><?= htmlspecialchars($_POST['panne'] ?? '') ?></textarea>
            </div>

        </div>

        <div class="tab-pane fade <?= ($pestana_activa === 'tab2') ? 'show active' : '' ?>" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">

            <div class="mb-3">
                <label for="id_comuna_origen" class="form-label">Comuna Origen</label>
                <select class="form-select select2" id="id_comuna_origen" name="id_comuna_origen" required>
                    <option value="">Seleccione comuna origen</option>
                    <?php foreach ($comunas as $com): ?>
                        <option value="<?= $com['id'] ?>" <?= ($sel_comuna_origen == $com['id']) ? 'selected' : '' ?>><?= htmlspecialchars($com['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="id_comuna_destino" class="form-label">Comuna Destino</label>
                <select class="form-select select2" id="id_comuna_destino" name="id_comuna_destino" required>
                    <option value="">Seleccione comuna destino</option>
                    <?php foreach ($comunas as $com): ?>
                        <option value="<?= $com['id'] ?>" <?= ($sel_comuna_destino == $com['id']) ? 'selected' : '' ?>><?= htmlspecialchars($com['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
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

            <div class="mb-3">
                <label for="tipo_camino" class="form-label">Tipo de Camino</label>
                 <select class="form-select" name="tipo_camino" id="tipo_camino" required>
                    <option value="">Seleccione tipo camino</option>
                    <?php foreach ($tipos_camino_con_monto as $tipocamino): ?>
                        <option value="<?= $tipocamino['id'] ?>" <?= ($sel_tipocamino == $tipocamino['id']) ? 'selected' : '' ?>><?= htmlspecialchars($tipocamino['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="total_km" class="form-label">Total KM:</label>
                <input 
                    type="number" 
                    name="total_km" 
                    id="total_km" 
                    class="form-control" 
                    value="<?= htmlspecialchars($es_rm ? 0 : ($_POST['total_km'] ?? 0)) ?>" 
                    <?= $es_rm ? 'readonly' : '' ?> 
                    step="0.01" 
                    min="0" 
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
            </div>

            <?php if (!empty($mensaje2)): ?>
                <div class="alert alert-info mt-3"><?= htmlspecialchars($mensaje2) ?></div>
            <?php endif; ?>

        </div>

        <?php if ($mostrar_tab_vehiculo): ?>
        <div class="tab-pane fade <?= ($pestana_activa === 'tab3') ? 'show active' : '' ?>" id="tab3" role="tabpanel" aria-labelledby="tab3-tab">
            <div class="mb-3">
                <label for="id_tipo_vehiculo" class="form-label">Tipo de Vehículo</label>
                <select class="form-select" name="id_tipo_vehiculo" id="id_tipo_vehiculo" required onchange="this.form.submit()">
                    <option value="">Seleccione tipo vehículo</option>
                    <?php foreach ($vehiculos as $veh): ?>
                        <option value="<?= $veh['id'] ?>" <?= (isset($_POST['id_tipo_vehiculo']) && $_POST['id_tipo_vehiculo'] == $veh['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($veh['Tipo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (!empty($opciones_sub_tipo)): ?>
            <div class="mb-3">
                <label for="id_sub_tipo" class="form-label">Subtipo</label>
                <select class="form-select" name="id_sub_tipo" id="id_sub_tipo" required>
                    <option value="">Seleccione subtipo</option>
                    <?php foreach ($opciones_sub_tipo as $sub_tipo): ?>
                        <option value="<?= htmlspecialchars($sub_tipo) ?>" <?= (isset($_POST['id_sub_tipo']) && $_POST['id_sub_tipo'] == $sub_tipo) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sub_tipo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ($mostrar_camion_extra): ?>
            <div class="mb-3">
                <label for="tipo_direccion" class="form-label">Tipo de Dirección</label>
                <select class="form-select" name="tipo_direccion" id="tipo_direccion">
                    <option value="">Seleccione tipo de dirección</option>
                    <?php foreach ($opciones_direccion as $direccion): ?>
                        <option value="<?= htmlspecialchars($direccion) ?>" <?= (isset($_POST['tipo_direccion']) && $_POST['tipo_direccion'] == $direccion) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($direccion) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="carga" class="form-label">Tipo de Carga</label>
                <select class="form-select" name="carga" id="carga">
                    <option value="">Seleccione tipo de carga</option>
                    <?php foreach ($opciones_carga as $carga): ?>
                        <option value="<?= htmlspecialchars($carga) ?>" <?= (isset($_POST['carga']) && $_POST['carga'] == $carga) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($carga) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ($mostrar_tracto_extra): ?>
            <div class="mb-3">
                <label for="carga" class="form-label">Tipo de Carga</label>
                <select class="form-select" name="carga" id="carga">
                    <option value="">Seleccione tipo de carga</option>
                    <?php foreach ($opciones_carga as $carga): ?>
                        <option value="<?= htmlspecialchars($carga) ?>" <?= (isset($_POST['carga']) && $_POST['carga'] == $carga) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($carga) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="mt-3">
        <button type="submit" name="enviar_confirmacion" class="btn btn-primary">Crear cotización</button>
    </div>
</form>

<?php if ($mostrar_modal_confirm): ?>
<!-- Modal Confirmación -->
<div class="modal show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.5);" aria-modal="true" role="dialog" id="modalConfirm">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="">
        <div class="modal-header">
          <h5 class="modal-title">Confirme la cotización</h5>
          <button type="button" class="btn-close" onclick="cerrarModal()" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
            <?php foreach ($datos_confirmacion as $campo => $valor): ?>
                <?php if (strtolower($campo) !== 'monto inicial'): ?>
                    <p><strong><?= htmlspecialchars($campo) ?>:</strong> <?= htmlspecialchars($valor) ?></p>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Mostrar el monto_inicial real -->
            <p><strong>Monto Inicial (real):</strong> <?= number_format($monto_inicial, 2, ',', '.') ?></p>
            <input type="hidden" name="monto_inicial" value="<?= htmlspecialchars($monto_inicial) ?>" />

            <!-- Reenviar todos los campos del formulario -->
            <?php foreach ($_POST as $key => $val): ?>
                <?php if (!in_array($key, ['enviar_confirmacion', 'confirmar', 'monto_inicial'])): ?>
                    <?php if (is_array($val)): ?>
                        <?php foreach ($val as $subval): ?>
                            <input type="hidden" name="<?= htmlspecialchars($key) ?>[]" value="<?= htmlspecialchars($subval) ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($val) ?>">
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="modal-footer">
          <div class="mt-3">
                <button type="submit" name="confirmar" class="btn btn-success">Confirmar</button>
            </div>

          <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="js/crear-cotizacion.js"></script>
<script>
function cerrarModal() {
    const modal = document.getElementById('modalConfirm');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        modal.removeAttribute('aria-modal');
        modal.setAttribute('aria-hidden', 'true');
    }
}

$(document).ready(function () {
    function initSelect2() {
        // Select2 para cliente
        if (!$('#rut_cliente').hasClass("select2-hidden-accessible")) {
            $('#rut_cliente').select2({
                placeholder: "Seleccione cliente",
                allowClear: true,
                dropdownParent: $('#tab1'),
                width: '100%',
                language: { noResults: () => "No hay resultados" }
            });
        }

        // Comuna Origen
        if (!$('#id_comuna_origen').hasClass("select2-hidden-accessible")) {
            $('#id_comuna_origen').select2({
                placeholder: "Seleccione comuna origen",
                allowClear: true,
                dropdownParent: $('#tab2'),
                width: '100%',
                language: { noResults: () => "No hay resultados" }
            });
        }

        // Comuna Destino
        if (!$('#id_comuna_destino').hasClass("select2-hidden-accessible")) {
            $('#id_comuna_destino').select2({
                placeholder: "Seleccione comuna destino",
                allowClear: true,
                dropdownParent: $('#tab2'),
                width: '100%',
                language: { noResults: () => "No hay resultados" }
            });
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

    $('#rut_cliente').on('change', function () {
        recalcularMonto();
    });

    // AJAX cálculo de monto
    function recalcularMonto() {
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
                    $('#monto_manual').val(Math.round(parseFloat(response.monto)));
                    let hint = $('#monto_formula_hint');
                    if (!hint.length) {
                        $('#monto_manual').after('<small id="monto_formula_hint" class="form-text text-info"></small>');
                        hint = $('#monto_formula_hint');
                    }
                    if (response.tarifa_especial) {
                        hint.text('Tarifa especial aplicada: Base + Precio * Km');
                    } else {
                        hint.text('');
                    }
                } else {
                    console.error('Error cálculo monto:', response.message);
                }
            },
            error: function() {
                console.error('Error al conectar con calcular_monto.php');
            }
        });
    }

    // Escuchar cambios para recalcular monto
    $('#id_comuna_origen, #id_comuna_destino, #total_km, #id_turno, #tipo_camino, #id_tipo_vehiculo, #rut_cliente').on('change keyup', function () {
        recalcularMonto();
    });

    // Control checkbox "usar monto manual"
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

    // Calcular monto al iniciar
    recalcularMonto();

    // --- NUEVAS FUNCIONES AJAX para carga dinámica ---

    // Cargar servicios al cambiar división
    $('#id_division').on('change', function () {
        const idDivision = $(this).val();
        if (!idDivision) {
            // Limpia servicios, turnos y vehículos si division está vacía
            $('#id_servicio').empty().append('<option value="">Seleccione servicio</option>');
            $('#id_turno').empty().append('<option value="">Seleccione turno</option>');
            $('#id_tipo_vehiculo').empty().append('<option value="">Seleccione tipo vehículo</option>');
            return;
        }

        $.ajax({
            url: 'ajax/obtener-servicios.php',
            method: 'POST',
            dataType: 'json',
            data: { id_division: idDivision },
            success: function(res) {
                if (res.success) {
                    const $servicios = $('#id_servicio').empty();
                    $servicios.append('<option value="">Seleccione servicio</option>');
                    res.servicios.forEach(serv => {
                        $servicios.append(`<option value="${serv.id}">${serv.Nombre}</option>`);
                    });
                    // Limpiar turnos y vehículos cuando cambia servicio
                    $('#id_turno').empty().append('<option value="">Seleccione turno</option>');
                    $('#id_tipo_vehiculo').empty().append('<option value="">Seleccione tipo vehículo</option>');
                } else {
                    console.error('Error al cargar servicios:', res.message);
                }
            },
            error: function() {
                console.error('Error en AJAX obtener-servicios.php');
            }
        });
    });

    // Cargar turnos al cambiar servicio
    $('#id_servicio').on('change', function () {
        const idServicio = $(this).val();
        if (!idServicio) {
            $('#id_turno').empty().append('<option value="">Seleccione turno</option>');
            return;
        }

        $.ajax({
            url: 'ajax/cargar-turnos.php',
            method: 'POST',
            dataType: 'json',
            data: { id_servicio: idServicio },
            success: function(res) {
                if (res.success) {
                    const $turnos = $('#id_turno').empty();
                    $turnos.append('<option value="">Seleccione turno</option>');
                    res.turnos.forEach(turno => {
                        $turnos.append(`<option value="${turno.id}">${turno.nombre}</option>`);
                    });
                } else {
                    console.error('Error al cargar turnos:', res.message);
                }
            },
            error: function() {
                console.error('Error en AJAX cargar-turnos.php');
            }
        });
    });

    // Cargar caminos al cambiar comuna origen o destino (si aplica)
    $('#id_comuna_origen, #id_comuna_destino').on('change', function () {
        const idOrigen = $('#id_comuna_origen').val();
        const idDestino = $('#id_comuna_destino').val();

        if (!idOrigen || !idDestino) return;

        $.ajax({
            url: 'ajax/cargar-caminos.php',
            method: 'POST',
            dataType: 'json',
            data: { id_origen: idOrigen, id_destino: idDestino },
            success: function(res) {
                if (res.success) {
                    const $tipoCamino = $('#tipo_camino').empty();
                    $tipoCamino.append('<option value="">Seleccione tipo camino</option>');
                    res.caminos.forEach(camino => {
                        $tipoCamino.append(`<option value="${camino.id}">${camino.nombre}</option>`);
                    });
                } else {
                    console.error('Error al cargar caminos:', res.message);
                }
            },
            error: function() {
                console.error('Error en AJAX cargar-caminos.php');
            }
        });
    });

    // Cargar subtipos al cambiar tipo vehículo
    $('#id_tipo_vehiculo').on('change', function () {
        const idVehiculo = $(this).val();
        if (!idVehiculo) {
            $('#id_sub_tipo').empty().append('<option value="">Seleccione subtipo</option>');
            return;
        }

        $.ajax({
            url: 'ajax/obtener-subtipos.php',
            method: 'POST',
            dataType: 'json',
            data: { id_tipo_vehiculo: idVehiculo },
            success: function(res) {
                if (res.success) {
                    const $subtipos = $('#id_sub_tipo').empty();
                    $subtipos.append('<option value="">Seleccione subtipo</option>');
                    res.subtipos.forEach(sub => {
                        $subtipos.append(`<option value="${sub}">${sub}</option>`);
                    });
                } else {
                    console.error('Error al cargar subtipos:', res.message);
                }
            },
            error: function() {
                console.error('Error en AJAX obtener-subtipos.php');
            }
        });
    });

    // Puedes agregar aquí otras llamadas AJAX similares para cargar clientes, vehículos, etc.

});
</script>
