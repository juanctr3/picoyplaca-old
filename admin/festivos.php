<?php
require_once 'auth.php';
require_once 'data_manager.php'; // Usamos DataManager para cargar

$festivos = DataManager::getFestivos();

// Agrupar por años
$festivos_por_anio = [];
foreach ($festivos as $f) {
    // Soporte para formato antiguo (string) y nuevo (array)
    $fecha = is_array($f) ? $f['fecha'] : $f;
    $nombre = is_array($f) ? ($f['nombre'] ?? 'Festivo') : 'Festivo';
    
    $year = substr($fecha, 0, 4);
    $festivos_por_anio[$year][] = ['fecha' => $fecha, 'nombre' => $nombre];
}
krsort($festivos_por_anio);

include 'includes/header.php';
?>

<div class="admin-content-wrapper">
    <div class="admin-page-header">
        <div class="header-titles">
            <h2>📅 Calendario de Festivos</h2>
            <p class="subtitle">Gestiona las fechas y nombres de los días festivos.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 20px;">
                <h3 class="card-title">➕ Agregar Festivo</h3>
                <form action="procesar.php" method="POST">
                    <input type="hidden" name="accion" value="agregar_festivo">
                    
                    <div class="form-group">
                        <label>Fecha</label>
                        <input type="date" name="fecha" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Nombre de la Festividad</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Reyes Magos" required>
                    </div>

                    <button type="submit" class="btn btn-success btn-block">Guardar</button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <?php foreach ($festivos_por_anio as $anio => $items): ?>
                <div class="card mb-4">
                    <div class="card-header-simple">
                        <h3>Año <?= $anio ?></h3>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead><tr><th>Fecha</th><th>Celebración</th><th></th></tr></thead>
                            <tbody>
                                <?php 
                                $meses = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
                                foreach ($items as $item): 
                                    $time = strtotime($item['fecha']);
                                    $dia = date('d', $time);
                                    $mes = $meses[date('m', $time)];
                                    $es_pasado = $time < time();
                                ?>
                                <tr style="<?= $es_pasado ? 'opacity:0.6' : '' ?>">
                                    <td width="30%">
                                        <strong style="font-size:1.2em;"><?= $dia ?></strong> <?= $mes ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($item['nombre']) ?>
                                    </td>
                                    <td class="text-right">
                                        <a href="procesar.php?accion=eliminar_festivo&fecha=<?= $item['fecha'] ?>" 
                                           class="btn-icon btn-delete" 
                                           onclick="return confirm('¿Borrar?')" title="Eliminar">🗑️</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
