<?php
/**
 * admin/festivos.php
 * Gestión de calendario de días festivos.
 */

require_once 'auth.php';

// 1. Cargar datos
$archivo_datos = __DIR__ . '/../datos/festivos.json';
$festivos = [];

if (file_exists($archivo_datos)) {
    $json_content = file_get_contents($archivo_datos);
    $festivos = json_decode($json_content, true) ?? [];
}

// Ordenar fechas cronológicamente
sort($festivos);

// Agrupar por años para mejor visualización
$festivos_por_anio = [];
foreach ($festivos as $f) {
    $year = substr($f, 0, 4);
    $festivos_por_anio[$year][] = $f;
}

// Ordenar años descendente (más reciente primero)
krsort($festivos_por_anio);

include 'includes/header.php';
?>

<div class="admin-content-wrapper">
    
    <div class="admin-page-header">
        <div class="header-titles">
            <h2>📅 Calendario de Festivos</h2>
            <p class="subtitle">En estos días NO aplica la medida de Pico y Placa (regla general).</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 20px;">
                <h3 class="card-title">➕ Agregar Fecha</h3>
                <form action="procesar.php" method="POST">
                    <input type="hidden" name="accion" value="agregar_festivo">
                    
                    <div class="form-group">
                        <label>Selecciona el día</label>
                        <input type="date" name="fecha" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-success btn-block">Agregar Festivo</button>
                </form>

                <div class="info-box mt-4">
                    <small class="text-muted">
                        <strong>Nota:</strong> Al agregar un festivo, el sistema recalculará automáticamente las reglas que dependen de días hábiles.
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <?php if (empty($festivos)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📆</div>
                    <h3>Sin festivos configurados</h3>
                    <p>Agrega las fechas importantes para el año en curso.</p>
                </div>
            <?php else: ?>
                
                <?php foreach ($festivos_por_anio as $anio => $fechas): ?>
                    <div class="card mb-4">
                        <div class="card-header-simple">
                            <h3>Año <?= $anio ?> <span class="badge badge-gray"><?= count($fechas) ?> fechas</span></h3>
                        </div>
                        <div class="fechas-grid">
                            <?php 
                            $meses = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
                            
                            foreach ($fechas as $fecha): 
                                $timestamp = strtotime($fecha);
                                $dia = date('d', $timestamp);
                                $mes = $meses[date('m', $timestamp)];
                                
                                // Marcar si ya pasó (estilo visual opaco)
                                $es_pasado = $timestamp < strtotime(date('Y-m-d'));
                                $clase = $es_pasado ? 'fecha-item pasado' : 'fecha-item';
                            ?>
                                <div class="<?= $clase ?>">
                                    <div class="fecha-content">
                                        <span class="f-dia"><?= $dia ?></span>
                                        <span class="f-mes"><?= $mes ?></span>
                                    </div>
                                    <a href="procesar.php?accion=eliminar_festivo&fecha=<?= $fecha ?>" 
                                       class="btn-remove" 
                                       title="Eliminar"
                                       onclick="return confirm('¿Eliminar el festivo <?= $fecha ?>?')">×</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>
    </div>

</div>

<style>
/* Estilos específicos para esta vista (pueden ir al CSS global luego) */
.fechas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
    padding: 10px 0;
}
.fecha-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 10px;
    text-align: center;
    position: relative;
    transition: all 0.2s;
}
.fecha-item:hover {
    background: #fff;
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}
.fecha-item.pasado {
    opacity: 0.6;
    background: #f1f1f1;
}
.fecha-content {
    display: flex;
    flex-direction: column;
}
.f-dia {
    font-size: 1.5rem;
    font-weight: 800;
    color: #2d3748;
    line-height: 1;
}
.f-mes {
    font-size: 0.85rem;
    text-transform: uppercase;
    color: #718096;
    font-weight: 600;
}
.btn-remove {
    position: absolute;
    top: -5px;
    right: -5px;
    width: 24px;
    height: 24px;
    background: #ff6b6b;
    color: white;
    border-radius: 50%;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 16px;
    opacity: 0;
    transition: opacity 0.2s;
}
.fecha-item:hover .btn-remove {
    opacity: 1;
}
</style>

<?php include 'includes/footer.php'; ?>
