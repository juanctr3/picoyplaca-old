<?php
/**
 * admin/festivos.php
 * Gestión de calendario de días festivos con soporte para nombres de festividades.
 */

require_once 'auth.php';
require_once 'data_manager.php';

// 1. Cargar datos
$festivos = DataManager::getFestivos();

// 2. Procesar y agrupar por años
$festivos_por_anio = [];

if (!empty($festivos)) {
    foreach ($festivos as $f) {
        // Normalizar datos (Soporte para formato antiguo string y nuevo array)
        if (is_array($f)) {
            $fecha = $f['fecha'];
            $nombre = $f['nombre'] ?? 'Festivo';
        } else {
            $fecha = $f;
            $nombre = 'Festivo';
        }
        
        // Extraer año para agrupar
        $year = substr($fecha, 0, 4);
        
        // Guardar en estructura temporal
        $festivos_por_anio[$year][] = [
            'fecha' => $fecha,
            'nombre' => $nombre
        ];
    }
}

// Ordenar años de más reciente a más antiguo
krsort($festivos_por_anio);

// 3. Incluir Header
include 'includes/header.php';
?>

<div class="admin-content-wrapper">
    
    <div class="admin-page-header">
        <div class="header-titles">
            <h2>📅 Calendario de Festivos</h2>
            <p class="subtitle">Gestiona las fechas en las que NO aplica la medida (Festivos Nacionales).</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 20px;">
                <h3 class="card-title">➕ Agregar Festivo</h3>
                <form action="procesar.php" method="POST">
                    <input type="hidden" name="accion" value="agregar_festivo">
                    
                    <div class="form-group">
                        <label>Selecciona el día</label>
                        <input type="date" name="fecha" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Nombre de la Festividad</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Día de la Independencia" required>
                    </div>

                    <button type="submit" class="btn btn-success btn-block">Guardar Festivo</button>
                </form>

                <div class="info-box mt-4 p-3 bg-light rounded text-muted text-sm">
                    <small>
                        <strong>Nota:</strong> Los festivos se ordenan automáticamente. Si agregas una fecha que ya pasó, aparecerá atenuada en la lista.
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <?php if (empty($festivos_por_anio)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📆</div>
                    <h3>Sin festivos configurados</h3>
                    <p>Agrega las fechas importantes para el año en curso y siguiente.</p>
                </div>
            <?php else: ?>
                
                <?php foreach ($festivos_por_anio as $anio => $fechas): ?>
                    <div class="card mb-4">
                        <div class="card-header-simple d-flex justify-content-between align-items-center">
                            <h3>Año <?= $anio ?></h3>
                            <span class="badge badge-gray"><?= count($fechas) ?> fechas</span>
                        </div>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th width="25%">Fecha</th>
                                        <th width="60%">Celebración</th>
                                        <th width="15%" class="text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $meses = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
                                    
                                    foreach ($fechas as $item): 
                                        $timestamp = strtotime($item['fecha']);
                                        $dia = date('d', $timestamp);
                                        $mes_num = date('m', $timestamp);
                                        $mes = $meses[$mes_num];
                                        
                                        // Estilo visual si ya pasó la fecha
                                        $es_pasado = $timestamp < strtotime(date('Y-m-d'));
                                        $style_row = $es_pasado ? 'opacity: 0.5; background: #fafafa;' : '';
                                    ?>
                                        <tr style="<?= $style_row ?>">
                                            <td>
                                                <div class="date-badge">
                                                    <span style="font-size: 1.2rem; font-weight: 700; color: #2d3748;"><?= $dia ?></span>
                                                    <span style="font-size: 0.85rem; color: #718096; text-transform: uppercase;"><?= $mes ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="font-weight: 500; font-size: 1rem;"><?= htmlspecialchars($item['nombre']) ?></span>
                                                <?php if($es_pasado): ?><small class="d-block">(Pasado)</small><?php endif; ?>
                                            </td>
                                            <td class="text-right">
                                                <a href="procesar.php?accion=eliminar_festivo&fecha=<?= $item['fecha'] ?>" 
                                                   class="btn-icon btn-delete" 
                                                   title="Eliminar"
                                                   onclick="return confirm('¿Seguro que deseas eliminar el festivo: <?= htmlspecialchars($item['nombre']) ?>?')">
                                                    🗑️
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>
    </div>

</div>

<style>
    /* Estilos adicionales para esta vista */
    .d-block { display: block; }
    .bg-light { background-color: #f8f9fa !important; }
    .rounded { border-radius: 0.25rem !important; }
    .text-sm { font-size: 0.875rem; }
    
    .date-badge {
        display: flex;
        flex-direction: column;
        line-height: 1.1;
    }
</style>

<?php include 'includes/footer.php'; ?>
