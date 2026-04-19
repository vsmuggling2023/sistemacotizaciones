<?php
session_start();
include("database.php");

// Variables de sesión para rol-cargos (simulando contexto si no se incluye directo, pero mejor incluirlo si no da conflicto)
// Como este archivo se carga via AJAX, necesitamos el contexto de sesión.
$usuario = $_SESSION['NOMBRE_USUARIO'] ?? '';
include("rol-cargos.php"); // Para obtener $puedeCrearOT, $soloLectura, etc.

if (!isset($_GET['id'])) {
    echo "ID no proporcionado";
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT c.*, 
                        cl.NOMBRE AS nombre_cliente, 
                        co.nombre AS comuna_origen, 
                        cd.nombre AS comuna_destino, 
                        s.Nombre AS servicio,
                        d.NOMBRE AS division_nombre,
                        t.nombre AS turno_nombre,
                        v.Tipo AS tipo_vehiculo_nombre,
                        tc.nombre AS tipo_camino_nombre
                        FROM cotizaciones c
                        LEFT JOIN clientes cl ON c.rut_CLIENTE = cl.RUT
                        LEFT JOIN comunas co ON c.id_comuna_origen = co.id
                        LEFT JOIN comunas cd ON c.id_comuna_destino = cd.id
                        LEFT JOIN servicios s ON c.id_servicio = s.id
                        LEFT JOIN division d ON c.id_division = d.id
                        LEFT JOIN turno t ON c.id_turno = t.id
                        LEFT JOIN vehiculos v ON c.id_tipo_vehiculo = v.id
                        LEFT JOIN tipo_camino tc ON tc.id = c.tipo_camino
                        WHERE c.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo "Cotización no encontrada";
    exit;
}
?>

<div class="modal fade" id="modalVerCotizacion-<?= $id ?>" tabindex="-1" aria-labelledby="modalVerLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalVerLabel">Detalle Cotización #
                    <?= $id ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- Cliente -->
                <div class="mb-3">
                    <label class="fw-bold text-muted small">CLIENTE</label>
                    <div class="fs-6">
                        <?= htmlspecialchars($row['nombre_cliente'] ?? 'N/A') ?>
                    </div>
                    <div class="text-muted small">
                        <?= htmlspecialchars($row['rut_CLIENTE'] ?? '-') ?>
                    </div>
                </div>

                <hr class="my-2 opacity-10">

                <!-- División y Servicio -->
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="fw-bold text-muted small">DIVISIÓN</label>
                        <div>
                            <?= htmlspecialchars($row['division_nombre'] ?? 'N/A') ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="fw-bold text-muted small">SERVICIO</label>
                        <div>
                            <?= htmlspecialchars($row['servicio'] ?? 'N/A') ?>
                        </div>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="mb-3">
                    <label class="fw-bold text-muted small">DESCRIPCIÓN</label>
                    <div class="bg-light p-2 rounded small">
                        <?= nl2br(htmlspecialchars($row['Descripcion'] ?? 'Sin descripción')) ?>
                    </div>
                </div>

                <!-- Panne -->
                <div class="mb-3">
                    <label class="fw-bold text-muted small">PANNE</label>
                    <div>
                        <?= htmlspecialchars($row['Panne'] ?? 'N/A') ?>
                    </div>
                </div>

                <!-- Tipo de Vehículo -->
                <?php if (!empty($row['tipo_vehiculo_nombre'])): ?>
                <div class="mb-3">
                    <label class="fw-bold text-muted small">TIPO DE VEHÍCULO</label>
                    <div>
                        <?= htmlspecialchars($row['tipo_vehiculo_nombre']) ?>
                        <?php if (!empty($row['id_sub_tipo'])): ?>
                            - <?= htmlspecialchars($row['id_sub_tipo']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <hr class="my-2 opacity-10">

                <!-- Comuna Origen y Dirección -->
                <div class="mb-3">
                    <label class="fw-bold text-muted small">COMUNA ORIGEN</label>
                    <div>
                        <?= htmlspecialchars($row['comuna_origen'] ?? 'N/A') ?>
                    </div>
                    <?php if (!empty($row['direccion_origen'])): ?>
                    <div class="text-muted small mt-1">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        <?= htmlspecialchars($row['direccion_origen']) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Comuna Destino y Dirección (solo si aplica) -->
                <?php if (!empty($row['comuna_destino']) || !empty($row['direccion_destino'])): ?>
                <div class="mb-3">
                    <label class="fw-bold text-muted small">COMUNA DESTINO</label>
                    <div>
                        <?= htmlspecialchars($row['comuna_destino'] ?? 'N/A') ?>
                    </div>
                    <?php if (!empty($row['direccion_destino'])): ?>
                    <div class="text-muted small mt-1">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        <?= htmlspecialchars($row['direccion_destino']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <hr class="my-2 opacity-10">

                <!-- Base de Salida y Turno -->
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="fw-bold text-muted small">BASE DE SALIDA</label>
                        <div>
                            <?= htmlspecialchars($row['base_salida'] ?? 'N/A') ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="fw-bold text-muted small">TURNO</label>
                        <div>
                            <?= htmlspecialchars($row['turno_nombre'] ?? 'N/A') ?>
                        </div>
                    </div>
                </div>

                <!-- Tipo Tarifa y Tipo Camino -->
                <div class="row mb-3">
                    <?php if (!empty($row['tipo_camino_nombre'])): ?>
                    <div class="col-6">
                        <label class="fw-bold text-muted small">TIPO TARIFA</label>
                        <div>
                            <?= htmlspecialchars($row['tipo_camino_nombre']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="<?= !empty($row['tipo_camino_nombre']) ? 'col-6' : 'col-12' ?>">
                        <label class="fw-bold text-muted small">TIPO MONEDA</label>
                        <div>
                            CLP (Peso Chileno)
                        </div>
                    </div>
                </div>

                <hr class="my-2 opacity-10">

                <!-- Monto Final y Estado -->
                <div class="row">
                    <div class="col-6">
                        <label class="fw-bold text-muted small">MONTO FINAL</label>
                        <div class="fs-5 fw-bold text-primary">$
                            <?= number_format($row['monto_final'] > 0 ? $row['monto_final'] : $row['monto_inicial'], 0, '', '.') ?>
                        </div>
                    </div>
                    <div class="col-6 text-end">
                        <label class="fw-bold text-muted small d-block">ESTADO</label>
                        <span class="badge bg-secondary">
                            <?= htmlspecialchars($row['estado'] ?? 'Desconocido') ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                
                <!-- Botón Rechazar Cotización -->
                <form method="POST" action="rechazar_cotizacion.php" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('¿Está seguro de rechazar esta cotización?');">
                        <i class="fas fa-times-circle me-2"></i>Rechazar Cotización
                    </button>
                </form>
                
                <?php
                // Se eliminó la restricción de estado 'aprobada' a petición del usuario.
                $condicion = ($puedeCrearOT && !$soloLectura && !$bloquearFueraDeOT);
                ?>
                <?php if ($condicion): ?>
                    <button type="button" class="btn btn-primary btn-enviar-ot" data-id="<?= $id ?>">
                        <i class="fas fa-paper-plane me-2"></i>Enviar a OT
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$stmt->close();
$conn->close();
?>