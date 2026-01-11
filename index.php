<?php
/**
 * PICO Y PLACA - Sistema de Consulta de Restricciones Vehiculares
 * Versión 2.6 - Modularizada
 */
$file_config = __DIR__ . '/datos/config.json';
$file_festivos = __DIR__ . '/datos/festivos.json';
require_once 'clases/PicoYPlaca.php';
$ciudades = json_decode(file_get_contents($file_config), true);
$ciudades = $config['ciudades'];
$festivos = json_decode(file_get_contents($file_festivos), true);

// -- DETECCIÓN DE PARÁMETROS --
$vehiculo_sel = $_GET['vehicle'] ?? 'particular'; 
$ciudad_sel_url = $_GET['city'] ?? 'bogota';

$isDatePage = false;
$dateData = [];

// ==========================================
// 1. PROCESAR DATOS DE HOY (Bucle Principal)
// ==========================================
$ahora = new DateTime();
$datos_hoy = [];

foreach ($ciudades as $codigo => $info) {
    $tipo_vehiculo_local = isset($info['vehiculos'][$vehiculo_sel]) ? $vehiculo_sel : 'particular';
    $pyp = new PicoYPlaca($codigo, $ahora, $ciudades, $festivos, $tipo_vehiculo_local);
    $info_pyp = $pyp->getInfo();

    $todas = [0,1,2,3,4,5,6,7,8,9];
    $permitidas = array_values(array_diff($todas, $info_pyp['restricciones']));

    $h_ini = 6; $h_fin = 21; 
    if (preg_match('/(\d{1,2})/', $info_pyp['horario'], $m)) $h_ini = (int)$m[1];
    
    $datos_hoy[$codigo] = [
        'restricciones' => $info_pyp['restricciones'],
        'permitidas' => $permitidas,
        'horario' => $info_pyp['horario'],
        'nombre' => $info['nombre'],
        'vehiculo_label' => $info_pyp['vehiculo_label'],
        'horarioInicio' => $h_ini, 
        'horarioFin' => 20 
    ];
}
$datos_hoy_json = json_encode($datos_hoy);

// ==========================================
// 2. PROCESAR URL DE FECHA ESPECÍFICA
// ==========================================
if (preg_match('/pico-y-placa\/(\d{4})-(\d{2})-(\d{2})-(\w+)/', $_SERVER['REQUEST_URI'], $matches)) {
    $year = (int)$matches[1];
    $month = (int)$matches[2];
    $day = (int)$matches[3];
    $ciudad = $matches[4];
    
    if (isset($ciudades[$ciudad])) {
        try {
            $fecha = new DateTime("$year-$month-$day");
            $tipo_vehiculo_local = isset($ciudades[$ciudad]['vehiculos'][$vehiculo_sel]) ? $vehiculo_sel : 'particular';
            
            $pyp = new PicoYPlaca($ciudad, $fecha, $ciudades, $festivos, $tipo_vehiculo_local);
            $info_pyp = $pyp->getInfo();

            $monthNames = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            $permitidas = array_values(array_diff([0,1,2,3,4,5,6,7,8,9], $info_pyp['restricciones']));

            $dateData = [
                'dayNameEs' => $info_pyp['dia_nombre'],
                'dayNum' => (int)$fecha->format('d'),
                'monthName' => $monthNames[$month - 1],
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'cityName' => $info_pyp['ciudad_nombre'],
                'city' => $ciudad,
                'restrictions' => $info_pyp['restricciones'],
                'allowed' => $permitidas,
                'isWeekend' => $info_pyp['es_fin_semana'],
                'isHoliday' => $info_pyp['es_festivo'],
                'horario' => $info_pyp['horario'],
                'estado' => count($info_pyp['restricciones']) > 0 ? 'con_restriccion' : 'sin_restriccion',
                'vehiculo' => $info_pyp['vehiculo_label']
            ];
            $isDatePage = true;
        } catch (Exception $e) { http_response_code(404); }
    } else { http_response_code(404); }
}

// ==========================================
// 3. GENERAR META TAGS
// ==========================================
$nombre_vehiculo_seo = ($vehiculo_sel == 'particular') ? '' : ucfirst($vehiculo_sel);
$label_vehiculo_seo = isset($ciudades['bogota']['vehiculos'][$vehiculo_sel]) ? $ciudades['bogota']['vehiculos'][$vehiculo_sel]['label'] : 'Particulares';

if ($isDatePage) {
    $title = "Pico y placa $label_vehiculo_seo el " . ucfirst($dateData['dayNameEs']) . " " . $dateData['dayNum'] . " de " . ucfirst($dateData['monthName']) . " en " . $dateData['cityName'];
    $description = "Restricción para $label_vehiculo_seo en " . $dateData['cityName'] . " el " . $dateData['dayNameEs'] . ". Placas: " . (count($dateData['restrictions']) > 0 ? implode('-', $dateData['restrictions']) : 'Sin restricción');
    $keywords = "pico y placa $label_vehiculo_seo " . $dateData['cityName'] . ", restriccion $label_vehiculo_seo";
    $canonical = "https://picoyplacabogota.com.co/pico-y-placa/{$dateData['year']}-" . str_pad($dateData['month'], 2, '0', STR_PAD_LEFT) . "-" . str_pad($dateData['day'], 2, '0', STR_PAD_LEFT) . "-{$dateData['city']}?vehicle=$vehiculo_sel";
} else {
    $ciudad_actual_nombre = $ciudades[$ciudad_sel_url]['nombre'];
    $title = "Pico y Placa $label_vehiculo_seo HOY en $ciudad_actual_nombre 🚗 | 2025";
    $description = "Consulta el pico y placa para $label_vehiculo_seo en $ciudad_actual_nombre y toda Colombia. Horarios y rotaciones actualizadas.";
    $keywords = "pico y placa $label_vehiculo_seo, pico y placa hoy $label_vehiculo_seo";
    $canonical = "https://picoyplacabogota.com.co/?city=$ciudad_sel_url&vehicle=$vehiculo_sel";
}

// ==========================================
// 4. RENDERIZADO (INCLUDES)
// ==========================================

include 'includes/header.php';
?>

<?php if (!$isDatePage): ?>
    
    <div class="date-search-section">
        <h2 style="margin-bottom: 12px;">📅 Buscar por Fecha</h2>
        <form onsubmit="searchByDate(event)" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="date" id="dateInput" required>
            <select id="citySelect" style="flex: 1; min-width: 100px;">
                <?php foreach ($ciudades as $codigo => $info): ?>
                <option value="<?php echo $codigo; ?>"><?php echo $info['nombre']; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-search">Buscar</button>
        </form>
    </div>
    
    <div class="today-info">
        <div class="info-card"><h3>📅 Hoy</h3><p id="today-date">--</p></div>
        <div class="info-card"><h3>🚫 Restricción</h3><p id="today-status">--</p></div>
        <div class="info-card"><h3>🕐 Horario</h3><p id="city-schedule">--</p></div>
    </div>
    
    <div id="countdownContainer">
        <h3 id="countdownTitle">⏰ Pico y Placa Activo</h3>
        <div class="countdown-display" id="countdownDisplay">
            <div class="countdown-item"><div id="countdownHours">00</div><small>Horas</small></div>
            <span class="countdown-separator">:</span>
            <div class="countdown-item"><div id="countdownMinutes">00</div><small>Minutos</small></div>
            <span class="countdown-separator">:</span>
            <div class="countdown-item"><div id="countdownSeconds">00</div><small>Segundos</small></div>
        </div>
    </div>
    
    <div class="main-content">
        <div class="search-box">
            <div class="slider-container">
                <div class="slider-header"><h2>Tu ciudad</h2></div>
                <div class="slider-wrapper">
                    <button type="button" class="slider-btn" id="citiesPrev" onclick="scrollCities('left')" title="Anterior">‹</button>
                    <div class="slider-content" id="citiesSlider">
                        <?php foreach ($ciudades as $codigo => $info): ?>
                        <button type="button" class="city-btn" id="btn-<?php echo $codigo; ?>" onclick="selectCity('<?php echo $codigo; ?>')">
                            <?php echo $info['nombre']; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="slider-btn" id="citiesNext" onclick="scrollCities('right')" title="Siguiente">›</button>
                </div>
            </div>
            
            <label style="display: block; margin: 15px 0 12px 0; font-weight: 700;">Última placa (0-9)</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="plate-input" placeholder="5" maxlength="1" inputmode="numeric">
                <button type="button" class="btn-search" onclick="searchPlate()">Consultar</button>
            </div>
            
            <div id="result-box" class="result-box"></div>
        </div>
        
        <div class="restrictions-today">
            <h2 style="margin-bottom: 12px;">Restricciones HOY</h2>
            <h3 id="city-today" style="color: #667eea; margin-bottom: 10px;">Bogotá</h3>
            <p style="margin-bottom: 10px; font-weight: 600;" id="restriction-label">🚫 Con restricción:</p>
            <div class="plates-list" id="plates-restricted-today"></div>
            <p style="margin: 15px 0 10px 0; font-weight: 600;">✅ Habilitadas:</p>
            <div class="plates-list" id="plates-allowed-today"></div>
        </div>
    </div>
    
    <div class="info-section">
        <h2>ℹ️ Información</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <div><strong>🚗 Exentos:</strong><p style="margin: 5px 0 0 0; opacity: 0.9;">Eléctricos, híbridos, gas natural</p></div>
            <div><strong>📅 Fin de Semana:</strong><p style="margin: 5px 0 0 0; opacity: 0.9;">Sin restricción</p></div>
            <div><strong>🎉 Festivos:</strong><p style="margin: 5px 0 0 0; opacity: 0.9;">Sin restricción</p></div>
            <div><strong>⚠️ Multas:</strong><p style="margin: 5px 0 0 0; opacity: 0.9;">$600K - $900K</p></div>
        </div>
    </div>

<?php else: ?>

    <button class="back-btn" onclick="backToHome()" style="display: inline-block; margin-bottom: 20px; padding: 10px 20px; background: white; color: #667eea; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; min-height: 44px;">Ver Pico Y Placa Hoy Y Más Fechas</button>

    <p class="subtitle" style="margin-bottom: 20px;">
        Que no te pille el Poli 🚓 ni las Cámaras 📸 Mantente informado y 🪰 sobre las restricciones vehiculares en 
        <span style="color: #000000; font-weight: 900; background: rgba(255, 255, 255, 0.9); padding: 4px 10px; border-radius: 6px; text-shadow: none;">
            <?php echo htmlspecialchars($dateData['cityName']); ?>
        </span>
        y evita perder hasta <span style="color: #000000; font-weight: 900; background: rgba(255, 255, 255, 0.9); padding: 2px 6px; border-radius: 4px; text-shadow: none;">$1.4 millones</span> 💸. Luego no 😩
    </p>

    <div style="background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px;">
        <h2 style="margin-bottom: 15px;">📅 <?php echo htmlspecialchars($dateData['dayNum'] . ' de ' . $dateData['monthName'] . ' de ' . $dateData['year']); ?></h2>
        <h3 style="color: #667eea; margin-bottom: 15px;">🚗 Pico y Placa en <?php echo htmlspecialchars($dateData['cityName']); ?></h3>
        <?php if (!empty($dateData['vehiculo'])): ?><h4 style="margin-bottom:15px; color:#555">Vehículo: <?php echo $dateData['vehiculo']; ?></h4><?php endif; ?>
                
        <div style="background: #f0f0f0; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
            <p><strong>📅 Día:</strong> <?php echo ucfirst($dateData['dayNameEs']); ?></p>
            <p><strong>🕐 Horario:</strong> <?php echo htmlspecialchars($dateData['horario']); ?></p>
            <p><strong>📊 Estado:</strong> 
                <?php 
                if ($dateData['isWeekend']) {
                    echo '<span class="no-restriction">✅ Sin restricción (Fin de semana)</span>';
                } elseif ($dateData['isHoliday']) {
                    echo '<span class="no-restriction">✅ Sin restricción (Día festivo)</span>';
                } else {
                    echo count($dateData['restrictions']) > 0 ? '<span class="has-restriction">⚠️ Hay restricción</span>' : '<span class="no-restriction">✅ Hoy no hay restricción</span>';
                }
                ?>
            </p>
        </div>
        
        <p style="margin-bottom: 10px; font-weight: 600;">🚫 Placas con restricción:</p>
        <div class="plates-list">
            <?php
            if ($dateData['isWeekend']) {
                echo '<p class="no-restriction">✅ Fin de semana</p>';
            } elseif ($dateData['isHoliday']) {
                echo '<p class="no-restriction">✅ Día festivo</p>';
            } elseif (count($dateData['restrictions']) > 0) {
                foreach ($dateData['restrictions'] as $p) echo '<span class="plate-badge restricted">' . $p . '</span>';
            } else {
                echo '<p class="no-restriction">✅ Hoy no hay restricción</p>';
            }
            ?>
        </div>
        
        <p style="margin: 15px 0 10px 0; font-weight: 600;">✅ Placas habilitadas:</p>
        <div class="plates-list">
            <?php
            if ($dateData['isWeekend'] || $dateData['isHoliday']) {
                echo '<p class="no-restriction">✅ Todas (0-9)</p>';
            } elseif (count($dateData['restrictions']) > 0) {
                foreach ($dateData['allowed'] as $p) echo '<span class="plate-badge">' . $p . '</span>';
            } else {
                echo '<p class="no-restriction">✅ Todas (0-9)</p>';
            }
            ?>
        </div>
    </div>
    
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
