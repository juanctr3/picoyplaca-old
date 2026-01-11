<?php
/**
 * admin/alertas.php
 * Gestión de Alertas informativas (Globales o por Ciudad).
 * Permite comunicar emergencias, cambios de vías o avisos importantes.
 */

require_once 'auth.php';
require_once 'data_manager.php';

// 1. Cargar datos
$ciudades = DataManager::getCiudades();
$alertas = DataManager::getAlertas();

// 2. Mapeo de colores para la interfaz
$tipos_alerta = [
    'info'    => ['label' => 'Información', 'class' => 'badge-blue',  'color' => '#3182ce'],
    'warning' => ['label' => 'Advertencia', 'class' => 'badge-warning', 'color' => '#dd6b20'],
    'danger'  => ['label' => 'Peligro',     'class' => 'badge-danger',  'color' => '#e53e3e']
];

include 'includes/header.php';
?>

<div class="admin-content-wrapper">
    
    <div class="admin-page-header">
        <div class="header-titles">
            <h2>📢 Gestión de Alertas</h2>
            <p class="subtitle">Publica avisos importantes en la parte superior del sitio web.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header-simple">
                    <h3 class="card-title">➕ Nueva Alerta</h3>
                </div>
                <div class="card-body" style="padding: 20px;">
                    <form action="procesar.php" method="POST">
                        <input type="hidden" name="accion" value="guardar_alerta">
                        
                        <div class="form-group">
                            <label>Alcance (Ciudad)</label>
                            <select name="ciudad_id" class="form-control" required>
                                <option value="global">🌍 Global (Todas las ciudades)</option>
                                <option disabled>----------------</option>
                                <?php foreach ($ciudades as $slug => $data): ?>
                                    <option value="<?php echo $slug; ?>"><?php echo $data['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tipo de Mensaje</label>
                            <select name="tipo" class="form-control">
                                <option value="info">ℹ️ Información (Azul)</option>
                                <option value="warning">⚠️ Advertencia (Naranja)</option>
                                <option value="danger">🚨 Urgente / Peligro (Rojo)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Mensaje</label>
                            <textarea name="mensaje" class="form-control" rows="4" placeholder="Ej: Se levanta el Pico y Placa por emergencia ambiental..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Enlace "Ver más" (Opcional)</label>
                            <input type="url" name="url" class="form-control" placeholder="https://...">
                        </div>
                        
                        <div class="form-group">
                            <label style="display:flex; align-items:center; cursor:pointer;">
                                <input type="checkbox" name="activa" value="1" checked style="width:auto; margin-right:10px;">
                                Mostrar alerta inmediatamente
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Publicar Alerta</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <?php if (empty($alertas)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🔕</div>
                    <h3>Sin alertas activas</h3>
                    <p>El sitio web se muestra sin avisos de emergencia.</p>
                </div>
            <?php else: ?>
                
                <div class="card">
                    <div class="card-header-simple">
                        <h3>Alertas Publicadas</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Alcance</th>
                                    <th>Mensaje</th>
                                    <th>Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alertas as $index => $item): 
                                    // Generar ID único temporal si no existe (compatibilidad)
                                    $id_alerta = $item['id'] ?? $index;
                                    $tipo_config = $tipos_alerta[$item['tipo']] ?? $tipos_alerta['info'];
                                    
                                    // Etiqueta Ciudad
                                    $ciudad_nombre = ($item['ciudad_id'] === 'global') 
                                        ? '🌍 Global' 
                                        : ($ciudades[$item['ciudad_id']]['nombre'] ?? $item['ciudad_id']);
                                ?>
                                <tr>
                                    <td width="25%">
                                        <div style="font-weight:700; color:#2d3748;"><?php echo $ciudad_nombre; ?></div>
                                        <span class="badge" style="background:<?php echo $tipo_config['color']; ?>20; color:<?php echo $tipo_config['color']; ?>;">
                                            <?php echo $tipo_config['label']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-size:0.95rem; margin-bottom:5px;">
                                            <?php echo htmlspecialchars($item['mensaje']); ?>
                                        </div>
                                        <?php if(!empty($item['url'])): ?>
                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" target="_blank" style="font-size:0.8rem; color:#667eea;">🔗 Ver enlace</a>
                                        <?php endif; ?>
                                    </td>
                                    <td width="15%">
                                        <?php if($item['activa']): ?>
                                            <span class="badge badge-blue">Activa</span>
                                        <?php else: ?>
                                            <span class="badge badge-gray">Oculta</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        <a href="procesar.php?accion=eliminar_alerta&id=<?php echo $id_alerta; ?>" 
                                           class="btn-icon btn-delete" 
                                           onclick="return confirm('¿Eliminar esta alerta?')" title="Borrar">
                                            🗑️
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .badge-warning { background: #fffaf0; color: #dd6b20; }
    .badge-danger { background: #fff5f5; color: #e53e3e; }
</style>

<?php include 'includes/footer.php'; ?>
