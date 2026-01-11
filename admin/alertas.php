<?php
/**
 * admin/alertas.php
 * Gestión de mensajes de emergencia o novedades globales.
 */

require_once 'auth.php';

// 1. Cargar configuración de alertas
$archivo_datos = __DIR__ . '/../datos/alertas.json';
$alerta = [
    'activa' => false,
    'mensaje' => '',
    'tipo' => 'info', // info, warning, danger, success
    'url' => ''
];

if (file_exists($archivo_datos)) {
    $json_content = file_get_contents($archivo_datos);
    $datos_guardados = json_decode($json_content, true);
    if ($datos_guardados) {
        $alerta = array_merge($alerta, $datos_guardados);
    }
}

include 'includes/header.php';
?>

<div class="admin-content-wrapper">
    
    <div class="admin-page-header">
        <div class="header-titles">
            <h2>📢 Alertas y Novedades</h2>
            <p class="subtitle">Configura un mensaje global visible en el inicio del sitio.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 offset-md-3">
            
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success text-center">
                    <?= htmlspecialchars($_GET['msg']) ?>
                </div>
            <?php endif; ?>

            <form action="procesar.php" method="POST">
                <input type="hidden" name="accion" value="guardar_alerta">

                <div class="card">
                    <div class="card-header-simple">
                        <h3>Configuración del Mensaje</h3>
                    </div>
                    
                    <div class="form-group p-3" style="background: #f8f9fa; border-bottom: 1px solid #eee;">
                        <label class="d-flex align-items-center justify-content-between cursor-pointer">
                            <span class="font-weight-bold">Estado de la Alerta</span>
                            <div class="toggle-switch">
                                <input type="checkbox" name="activa" value="1" <?= $alerta['activa'] ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </div>
                        </label>
                        <small class="text-muted">Si está apagado, el mensaje no se mostrará a los usuarios.</small>
                    </div>

                    <div class="p-4">
                        <div class="form-group">
                            <label>Texto del Mensaje <span class="text-danger">*</span></label>
                            <textarea name="mensaje" class="form-control" rows="3" placeholder="Ej: Se levanta el Pico y Placa hoy por paro de transportadores..." required><?= htmlspecialchars($alerta['mensaje']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Tipo de Alerta (Color)</label>
                            <select name="tipo" class="form-control">
                                <option value="info" <?= $alerta['tipo'] == 'info' ? 'selected' : '' ?>>🔵 Información (Azul)</option>
                                <option value="warning" <?= $alerta['tipo'] == 'warning' ? 'selected' : '' ?>>🟡 Advertencia (Amarillo)</option>
                                <option value="danger" <?= $alerta['tipo'] == 'danger' ? 'selected' : '' ?>>🔴 Crítico / Urgente (Rojo)</option>
                                <option value="success" <?= $alerta['tipo'] == 'success' ? 'selected' : '' ?>>🟢 Positivo (Verde)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Enlace "Ver más" (Opcional)</label>
                            <input type="url" name="url" class="form-control" value="<?= htmlspecialchars($alerta['url']) ?>" placeholder="https://...">
                            <small class="form-text text-muted">Si pones un link, aparecerá un botón en la alerta.</small>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary btn-block btn-lg">💾 Guardar Configuración</button>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <h4>Vista Previa:</h4>
                    <div class="alert-preview alert-<?= $alerta['tipo'] ?>" style="opacity: <?= $alerta['activa'] ? '1' : '0.5' ?>;">
                        <strong><?= $alerta['tipo'] == 'danger' ? '¡ATENCIÓN!' : 'NOVEDAD:' ?></strong> 
                        <?= $alerta['mensaje'] ? htmlspecialchars($alerta['mensaje']) : 'Aquí aparecerá tu mensaje...' ?>
                        <?php if($alerta['url']): ?>
                            <a href="#" class="alert-link">Ver más →</a>
                        <?php endif; ?>
                    </div>
                    <?php if(!$alerta['activa']): ?>
                        <small class="text-muted">(Actualmente desactivada)</small>
                    <?php endif; ?>
                </div>

            </form>
        </div>
    </div>
</div>

<style>
/* Estilos para el Toggle Switch y Alertas */
.toggle-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
.slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
input:checked + .slider { background-color: #2ecc71; }
input:checked + .slider:before { transform: translateX(24px); }

.alert-preview { padding: 15px; border-radius: 8px; margin-top: 10px; text-align: left; border: 1px solid transparent; }
.alert-info { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; }
.alert-warning { background-color: #fff3cd; color: #856404; border-color: #ffeeba; }
.alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
.alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
.alert-link { font-weight: bold; color: inherit; text-decoration: underline; margin-left: 10px; }
</style>

<?php include 'includes/footer.php'; ?>
