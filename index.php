<?php
/**
 * PICO Y PLACA - Frontend Principal
 * Versión 3.0 - Modular (JSON + Admin)
 */

// 1. Cargar Clases y Datos
require_once 'clases/PicoYPlaca.php';

// Rutas a los archivos de datos (gestionados por el Admin)
$file_config = __DIR__ . '/datos/config.json';
$file_festivos = __DIR__ . '/datos/festivos.json';

// Si no existen los datos (primera vez), iniciamos arrays vacíos para no romper la web
$ciudades = file_exists($file_config) ? json_decode(file_get_contents($file_config), true) : [];
$festivos = file_exists($file_festivos) ? json_decode(file_get_contents($file_festivos), true) : [];

// Cargar alerta global (si existe)
$file_alerta = __DIR__ . '/datos/alertas.json';
$alerta_global = file_exists($file_alerta) ? json_decode(file_get_contents($file_alerta), true) : null;

// 2. Detección de Parámetros URL
$vehiculo_sel = $_GET['vehicle'] ?? 'particular'; 
$ciudad_sel_url = $_GET['city'] ?? 'bogota';

$isDatePage = false;
$dateData = [];

// =============================================================================
// LÓGICA 1: PROCESAR DATOS DE "HOY" (Para el Home y el Widget JS)
// =============================================================================
$ahora = new DateTime(); // Fecha de hoy
$datos_hoy = [];

if (!empty($ciudades)) {
    foreach ($ciudades as $codigo => $info) {
        // Validar si la ciudad tiene el vehículo seleccionado, si no, usar el primero o default
        $tipo_vehiculo_local = isset($info['vehiculos'][$vehiculo_sel]) ? $vehiculo_sel : 'particular';
        
        // Instanciar la clase calculadora
        $pyp = new PicoYPlaca($codigo, $ahora, $ciudades, $festivos, $tipo_vehiculo_local);
        $info_pyp = $pyp->getInfo();

        // Calcular placas permitidas (inversa de las restringidas)
        $todas = [0,1,2,3,4,5,6,7,8,9];
        $permitidas = array_values(array_diff($todas, $info_pyp['restricciones']));

        // Extraer horas de inicio/fin para el contador JS (formato simple)
        $h_ini = 6; $h_fin = 20; 
        if (preg_match('/(\d{1,2})/', $info_pyp['horario'], $m)) $h_ini = (int)$m[1];
        
        // Preparar array para el Frontend (Javascript)
        $datos_hoy[$codigo] = [
            'restricciones' => $info_pyp['restricciones'],
            'permitidas' => $permitidas,
            'horario' => $info_pyp['horario'],
            'nombre' => $info['nombre'],
            'vehiculo_label' => $info_pyp['vehiculo_label'],
            'nombre_festivo' => $info_pyp['nombre_festivo'], // Dato nuevo
            'horarioInicio' => $h_ini, 
            'horarioFin' => 20 
        ];
    }
}
$datos_hoy_json = json_encode($datos_hoy);

// =============================================================================
// LÓGICA 2: DETECTAR SI ES UNA PÁGINA DE FECHA ESPECÍFICA (SEO)
// =============================================================================
// Patrón URL: /pico-y-placa/2025-12-25-bogota
if (preg_match('/pico-y-placa\/(\d{4})-(\d{2})-(\d{2})-(\w+)/', $_SERVER['REQUEST_URI'], $matches)) {
    $year = (int)$matches[1];
    $month = (int)$matches[2];
    $day = (int)$matches[3];
    $ciudad_slug = $matches[4];
    
    if (isset($ciudades[$ciudad_slug])) {
        try {
            $fecha_consulta = new DateTime("$year-$month-$day");
            $tipo_vehiculo_local = isset($ciudades[$ciudad_slug]['vehiculos'][$vehiculo_sel]) ? $vehiculo_sel : 'particular';
            
            $pyp = new PicoYPlaca($ciudad_slug, $fecha_consulta, $ciudades, $festivos, $tipo_vehiculo_local);
            $info_pyp = $pyp->getInfo();

            $monthNames = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            $permitidas = array_values(array_diff([0,1,2,3,4,5,6,7,8,9], $info_pyp['restricciones']));

            $dateData = [
                'dayNameEs' => $info_pyp['dia_nombre'],
                'dayNum' => (int)$fecha_consulta->format('d'),
                'monthName' => $monthNames[$month - 1],
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'cityName' => $info_pyp['ciudad_nombre'],
                'citySlug' => $ciudad_slug,
                'restrictions' => $info_pyp['restricciones'],
                'allowed' => $permitidas,
                'isWeekend' => $info_pyp['es_fin_semana'],
                'isHoliday' => $info_pyp['es_festivo'],
                'holidayName' => $info_pyp['nombre_festivo'], // Dato nuevo
                'horario' => $info_pyp['horario'],
                'vehiculo' => $info_pyp['vehiculo_label']
            ];
            $isDatePage = true;
            
            // Actualizar variables globales para el header
            $ciudad_sel_url = $ciudad_slug; 

        } catch (Exception $e) { http_response_code(404); }
    } else { http_response_code(404); }
}

// =============================================================================
// LÓGICA 3: GENERACIÓN DE META TAGS (SEO)
// =============================================================================
$nombre_vehiculo_seo = isset($ciudades[$ciudad_sel_url]['vehiculos'][$vehiculo_sel]) 
    ? $ciudades[$ciudad_sel_url]['vehiculos'][$vehiculo_sel]['label'] 
    : 'Particulares';

$ciudad_nombre_seo = $ciudades[$ciudad_sel_url]['nombre'] ?? 'Colombia';

if ($isDatePage) {
    // Título para fecha específica
    $title = "Pico y placa $nombre_vehiculo_seo el " . ucfirst($dateData['dayNameEs']) . " " . $dateData['dayNum'] . " de " . ucfirst($dateData['monthName']) . " en " . $dateData['cityName'];
    
    // Descripción con placas
    $placas_txt = count($dateData['restrictions']) > 0 ? implode('-', $dateData['restrictions']) : 'Sin restricción';
    if($dateData['isHoliday']) $placas_txt = "Festivo (" . $dateData['holidayName'] . ")";
    
    $description = "Restricción para $nombre_vehiculo_seo en " . $dateData['cityName'] . " el " . $dateData['dayNameEs'] . ". Placas: $placas_txt";
    $keywords = "pico y placa $nombre_vehiculo_seo " . $dateData['cityName'] . ", restriccion $nombre_vehiculo_seo, pico y placa " . $dateData['dayNameEs'];
    
    $canonical = "https://picoyplacabogota.com.co/pico-y-placa/{$dateData['year']}-" . str_pad($dateData['month'], 2, '0', STR_PAD_LEFT) . "-" . str_pad($dateData['day'], 2, '0', STR_PAD_LEFT) . "-{$dateData['citySlug']}?vehicle=$vehiculo_sel";

} else {
    // Título para Home / Hoy
    $title = "Pico y Placa $nombre_vehiculo_seo HOY en $ciudad_nombre_seo 🚗 | " . date('Y');
    $description = "Consulta el pico y placa para $nombre_vehiculo_seo en $ciudad_nombre_seo y toda Colombia. Horarios, rotaciones y festivos actualizados.";
    $keywords = "pico y placa $nombre_vehiculo_seo, pico y placa hoy $nombre_vehiculo_seo, pico y placa colombia";
    $canonical = "https://picoyplacabogota.com.co/?city=$ciudad_sel_url&vehicle=$vehiculo_sel";
}

// =============================================================================
// VISTA: RENDERIZADO HTML
// =============================================================================
include 'includes/header.php';
?>

<?php if ($alerta_global && isset($alerta_global['activa']) && $alerta_global['activa']): ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $alerta_global['tipo'] ?? 'info'; ?>" style="border-left: 5px solid rgba(0,0,0,0.2); box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            <strong>📢 ATENCIÓN:</strong> <?php echo htmlspecialchars($alerta_global['mensaje']); ?>
            <?php if (!empty($alerta_global['url'])): ?>
                <a href="<?php echo htmlspecialchars($alerta_global['url']); ?>" style="font-weight:bold; text-decoration:underline; margin-left:5px;">Ver más detalles →</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>


<?php if (!$isDatePage): ?>
    
    <div class="date-search-section">
        <h2 style="margin-bottom: 12px;">📅 Buscar por Fecha</h2>
        <form onsubmit="searchByDate(event)" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="date" id="dateInput" required class="form-control" style="flex: 1;">
            <select id="citySelect" class="form-control" style="flex: 1; min-width: 150px;">
                <?php foreach ($ciudades as $codigo => $info): ?>
                <option value="<?php echo $codigo; ?>" <?php echo ($codigo === $ciudad_sel_url) ? 'selected' : ''; ?>>
                    <?php echo $info['nombre']; ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-search">Buscar</button>
        </form>
    </div>
    
    <div class="today-info">
        <div class="info-card"><h3>📅 Hoy</h3><p id="today-date">--</p></div>
        <div class="info-card"><h3>🚫 Restricción</h3><p id="today-status" style="font-size: 1.2rem;">--</p></div>
        <div class="info-card"><h3>🕐 Horario</h3><p id="city-schedule" style="font-size: 1rem;">--</p></div>
    </div>
    
    <div id="countdownContainer">
        <h3 id="countdownTitle">⏰ Estado de la Medida</h3>
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
                    <button type="button" class="slider-btn" id="citiesPrev" onclick="scrollCities('left')">‹</button>
                    <div class="slider-content" id="citiesSlider">
                        <?php foreach ($ciudades as $codigo => $info): ?>
                        <button type="button" class="city-btn <?php echo ($codigo === $ciudad_sel_url) ? 'active' : ''; ?>" 
                                id="btn-<?php echo $codigo; ?>" 
                                onclick="selectCity('<?php echo $codigo; ?>')">
                            <?php echo $info['nombre']; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="slider-btn" id="citiesNext" onclick="scrollCities('right')">›</button>
                </div>
            </div>
            
            <label style="display: block; margin: 20px 0 10px 0; font-weight: 700;">Verificar mi placa (último dígito):</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="plate-input" placeholder="Ej: 5" maxlength="1" inputmode="numeric" style="text-align:center; font-size:1.2rem; width:80px;">
                <button type="button" class="btn-search" style="flex:1;" onclick="searchPlate()">Consultar</button>
            </div>
            
            <div id="result-box" class="result-box"></div>
        </div>
        
        <div class="restrictions-today">
            <h2 style="margin-bottom: 12px;">Restricciones HOY</h2>
            <h3 id="city-today" style="color: #667eea; margin-bottom: 10px;">--</h3>
            
            <p style="margin-bottom: 10px; font-weight: 600;" id="restriction-label">Estado:</p>
            <div class="plates-list" id="plates-restricted-today">
                </div>
            
            <p style="margin: 20px 0 10px 0; font-weight: 600;">✅ Placas Habilitadas:</p>
            <div class="plates-list" id="plates-allowed-today">
                </div>
        </div>
    </div>
    
    <div class="info-section">
        <h2>ℹ️ Información General</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <div><strong>🚗 Exentos:</strong><p style="margin: 5px 0 0 0; opacity: 0.9; font-size:0.9rem;">Híbridos, Eléctricos, Carro compartido (en algunas ciudades).</p></div>
            <div><strong>📅 Fin de Semana:</strong><p style="margin: 5px 0 0 0; opacity: 0.9; font-size:0.9rem;">Generalmente libre (salvo excepciones regionales).</p></div>
            <div><strong>🎉 Festivos:</strong><p style="margin: 5px 0 0 0; opacity: 0.9; font-size:0.9rem;">No aplica medida general.</p></div>
            <div><strong>⚠️ Multas:</strong><p style="margin: 5px 0 0 0; opacity: 0.9; font-size:0.9rem;">Aprox. 15 SMDLV + Inmovilización.</p></div>
        </div>
    </div>

<?php else: ?>

    <button class="back-btn" onclick="backToHome()" style="display: inline-block; margin-bottom: 20px; padding: 10px 20px; background: white; color: #667eea; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; min-height: 44px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        ← Volver a Hoy
    </button>

    <p class="subtitle" style="margin-bottom: 20px;">
        Viendo el pronóstico de restricciones para 
        <span style="color: #000; font-weight: 900; background: rgba(255, 255, 255, 0.9); padding: 4px 10px; border-radius: 6px;">
            <?php echo htmlspecialchars($dateData['cityName']); ?>
        </span>
    </p>

    <div style="background: white; padding: 30px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
        <h2 style="margin-bottom: 5px; font-size: 2rem;">
            📅 <?php echo htmlspecialchars($dateData['dayNum']); ?> de <?php echo ucfirst($dateData['monthName']); ?>
        </h2>
        <p style="color: #666; margin-bottom: 20px; font-size: 1.1rem;"><?php echo $dateData['year']; ?> • <?php echo ucfirst($dateData['dayNameEs']); ?></p>

        <h3 style="color: #667eea; margin-bottom: 15px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
            🚗 Pico y Placa: <?php echo htmlspecialchars($dateData['cityName']); ?>
        </h3>
        
        <?php if (!empty($dateData['vehiculo'])): ?>
            <span class="badge badge-blue" style="margin-bottom: 20px; font-size: 0.9rem; padding: 6px 12px; background: #e0e7ff; color: #4338ca; border-radius: 20px;">
                Vehículo: <?php echo $dateData['vehiculo']; ?>
            </span>
        <?php endif; ?>
                
        <div style="background: #f8fafc; padding: 20px; border-radius: 10px; margin: 20px 0; border: 1px solid #e2e8f0;">
            <p style="margin-bottom: 8px;"><strong>🕐 Horario:</strong> <?php echo htmlspecialchars($dateData['horario']); ?></p>
            <p><strong>📊 Estado:</strong> 
                <?php 
                if ($dateData['isWeekend']) {
                    echo '<span class="no-restriction" style="color:#27ae60; font-weight:bold;">✅ Sin restricción (Fin de semana)</span>';
                } elseif ($dateData['isHoliday']) {
                    // AQUÍ MOSTRAMOS EL NOMBRE DEL FESTIVO
                    $festivo_nombre = !empty($dateData['holidayName']) ? $dateData['holidayName'] : 'Día Festivo';
                    echo '<span class="no-restriction" style="color:#27ae60; font-weight:bold;">🎉 ' . htmlspecialchars($festivo_nombre) . ' (Sin restricción)</span>';
                } else {
                    echo count($dateData['restrictions']) > 0 
                        ? '<span class="has-restriction" style="color:#e74c3c; font-weight:bold;">⚠️ Aplica Medida</span>' 
                        : '<span class="no-restriction" style="color:#27ae60; font-weight:bold;">✅ Día Libre (Sin pico y placa)</span>';
                }
                ?>
            </p>
        </div>
        
        <div class="row">
            <div style="margin-top: 10px;">
                <p style="margin-bottom: 10px; font-weight: 600; color: #e74c3c;">🚫 Placas con restricción:</p>
                <div class="plates-list">
                    <?php
                    if ($dateData['isWeekend'] || $dateData['isHoliday']) {
                        echo '<p class="no-restriction">✅ No aplica</p>';
                    } elseif (count($dateData['restrictions']) > 0) {
                        foreach ($dateData['restrictions'] as $p) {
                            echo '<span class="plate-badge restricted">' . $p . '</span>';
                        }
                    } else {
                        echo '<p class="no-restriction">✅ Ninguna</p>';
                    }
                    ?>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <p style="margin-bottom: 10px; font-weight: 600; color: #27ae60;">✅ Placas habilitadas:</p>
                <div class="plates-list">
                    <?php
                    if ($dateData['isWeekend'] || $dateData['isHoliday'] || count($dateData['restrictions']) === 0) {
                        echo '<span class="plate-badge">0</span><span class="plate-badge">1</span><span class="plate-badge">2</span><span class="plate-badge">3</span><span class="plate-badge">4</span><span class="plate-badge">5</span><span class="plate-badge">6</span><span class="plate-badge">7</span><span class="plate-badge">8</span><span class="plate-badge">9</span>';
                    } else {
                        foreach ($dateData['allowed'] as $p) {
                            echo '<span class="plate-badge">' . $p . '</span>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
