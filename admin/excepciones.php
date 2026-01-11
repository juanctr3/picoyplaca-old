<?php
/**
 * admin/excepciones.php
 * Gestión de excepciones (Días sin medida / Levantamiento de Pico y Placa).
 */

require_once 'auth.php';
require_once 'data_manager.php';

// 1. Cargar datos necesarios
$excepciones = DataManager::getExcepciones();
$ciudades = DataManager::getCiudades();

// 2. Ordenar excepciones por fecha (las más recientes/futuras primero)
usort($excepciones, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});

include 'includes/header.php';
?>

<div class="admin-content-wrapper">
    
    <div class="admin-page-header">
        <div class="header-titles">
            <h2>🎉 Excepciones y Días sin Medida</h2>
            <p class="subtitle">Programa levantamientos de medida por paros, días cívicos o emergencias.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header-simple">
                    <h3 class="card-title">➕ Nueva Excepción</h3>
                </div>
                <div class="card-body" style="padding: 20px;">
                    <form action="procesar.php" method="POST">
                        <input type="hidden" name="accion" value="guardar_excepcion">
                        
                        <div class="form-group">
                            <label>Fecha del evento</label>
                            <input type="date" name="fecha" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group">
                            <label>Ciudad</label>
                            <select name="ciudad" class="form-control" required>
                                <option value="global">🌍 Todas las Ciudades</option>
                                <option disabled>----------------</option>
                                <?php foreach ($ciudades as $slug => $data): ?>
                                    <option value="<?php echo $slug; ?>"><?php echo $data['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Tipo de Vehículo</label>
                            <select name="vehiculo" class="form-control">
                                <option value="todos">🚗 Todos los vehículos</option>
                                <option disabled>----------------</option>
                                <option value="particular">Particulares</option>
                                <option value="moto">Motos</option>
                                <option value="taxi">Taxis</option>
                                <option value="tpc">Servicio Público</option>
                                <option value="carga">Carga</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Motivo (Se mostrará en la web)</label>
                            <textarea name="motivo" class="form-control" rows="3" placeholder="Ej: Día sin carro y sin moto, Paro de transportadores, Día Cívico..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Guardar Excepción</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <?php if (empty($excepciones)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🔓</div>
                    <h3>No hay excepciones activas</h3>
                    <p>Actualmente el Pico y Placa funciona con normalidad en todas las fechas.</p>
                </div>
            <?php else: ?>
                
                <div class="card">
                    <div class="card-header-simple">
                        <h3>Historial de Levantamientos</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Alcance</th>
                                    <th>Motivo</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $meses = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
                                
                                foreach ($excepciones as $index => $item): 
                                    $timestamp = strtotime($item['fecha']);
                                    $dia = date('d', $timestamp);
                                    $mes = $meses[date('m', $timestamp)];
                                    $year = date('Y', $timestamp);
                                    
                                    $es_pasado = $timestamp < strtotime(date('Y-m-d'));
                                    
                                    // Etiquetas visuales
                                    $ciudad_label = ($item['ciudad'] === 'global') 
                                        ? '<span class="badge badge-blue">🌍 Global</span>' 
                                        : '<span class="badge badge-gray">' . ($ciudades[$item['ciudad']]['nombre'] ?? $item['ciudad']) . '</span>';
                                        
                                    $vehiculo_label = ($item['vehiculo'] === 'todos') 
                                        ? '<span style="font-size:0.8rem; font-weight:600; color:#2d3748;">Todos</span>' 
                                        : '<span style="font-size:0.8rem; color:#718096;">'.ucfirst($item['vehiculo']).'</span>';
                                ?>
                                <tr style="<?php echo $es_pasado ? 'opacity:0.6; background:#fafafa;' : ''; ?>">
                                    <td width="20%">
                                        <div class="date-badge">
                                            <span style="font-size: 1.2rem; font-weight: 700; color: #2d3748;"><?php echo $dia; ?></span>
                                            <span style="font-size: 0.85rem; color: #718096; text-transform: uppercase;"><?php echo $mes . ' ' . $year; ?></span>
                                        </div>
                                    </td>
                                    <td width="30%">
                                        <div style="display:flex; flex-direction:column; gap:5px;">
                                            <div><?php echo $ciudad_label; ?></div>
                                            <div><?php echo $vehiculo_label; ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['motivo']); ?></strong>
                                        <?php if($es_pasado): ?><br><small>(Evento pasado)</small><?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        <a href="procesar.php?accion=eliminar_excepcion&fecha=<?php echo $item['fecha']; ?>&ciudad=<?php echo $item['ciudad']; ?>&vehiculo=<?php echo $item['vehiculo']; ?>" 
                                           class="btn-icon btn-delete" 
                                           onclick="return confirm('¿Seguro que deseas reactivar el Pico y Placa para esta fecha?')" title="Eliminar Excepción">
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
    .date-badge {
        display: flex;
        flex-direction: column;
        line-height: 1.1;
        text-align: center;
        background: #edf2f7;
        padding: 8px;
        border-radius: 8px;
        width: 60px;
    }
</style>

<?php include 'includes/footer.php'; ?>
