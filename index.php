<?php
/**
 * PICO Y PLACA - Frontend Principal
 * Versión 3.6 - Mensajes Claros y Pronóstico con Nombres
 */

// 1. Cargar Clases y Datos
require_once 'clases/PicoYPlaca.php';

$file_config = __DIR__ . '/datos/config.json';
$file_festivos = __DIR__ . '/datos/festivos.json';
$file_alerta = __DIR__ . '/datos/alertas.json';
$file_excepciones = __DIR__ . '/datos/excepciones.json'; // Cargar excepciones globalmente

// Cargar datos (con fallbacks)
$ciudades = file_exists($file_config) ? json_decode(file_get_contents($file_config), true) : [];
$festivos = file_exists($file_festivos) ? json_decode(file_get_contents($file_festivos), true) : [];
// Filtrar alertas activas
$alertas_raw = file_exists($file_alerta) ? json_decode(file_get_contents($file_alerta), true) : [];
$alertas_activas = is_array($alertas_raw) ? array_filter($alertas_raw, function($a){ return isset($a['activa']) && $a['activa']; }) : [];


// 2. Parámetros URL
$vehiculo_sel = $_GET['vehicle'] ?? 'particular'; 
$ciudad_sel_url = $_GET['city'] ?? 'bogota';

$isDatePage = false;
$dateData = [];

// =============================================================================
// LÓGICA 1: DATOS DE "HOY" + PRONÓSTICO + RELOJ
// =============================================================================
$ahora_global = new DateTime(); 
$datos_hoy = [];

if (!empty($ciudades)) {
    foreach ($ciudades as $codigo => $info) {
        $tipo_v = isset($info['vehiculos'][$vehiculo_sel]) ? $vehiculo_sel : 'particular';
        
        // --- A. Análisis de HOY ---
        $pyp_hoy = new PicoYPlaca($codigo, $ahora_global, $ciudades, $festivos, $tipo_v);
        $info_hoy = $pyp_hoy->getInfo();
        $todas = [0,1,2,3,4,5,6,7,8,9];
        $permitidas = array_values(array_diff($todas, $info_hoy['restricciones']));

        // --- B. Cálculo del PRÓXIMO EVENTO (Reloj Inteligente) ---
        $target_timestamp = 0;
        $estado_reloj = 'sin_datos';
        
        $hora_actual = (int)$ahora_global->format('G');
        $h_ini = 6; $h_fin = 20; 
        
        // Extraer horario numérico aproximado para el reloj
        if (preg_match('/(\d{1,2}).*?(\d{1,2})/', $info_hoy['horario'], $m)) {
            $h_ini = (int)$m[1];
            $h_fin = (strpos(strtolower($info_hoy['horario']), 'p.m') !== false && (int)$m[2] < 12) ? (int)$m[2]+12 : (int)$m[2];
        }

        // Determinar estado actual del reloj
        if (!empty($info_hoy['restricciones'])) {
            if ($hora_actual < $h_ini) {
                $target_timestamp = strtotime(date('Y-m-d') . " $h_ini:00:00");
                $estado_reloj = 'inicia';
            } elseif ($hora_actual >= $h_ini && $hora_actual < $h_fin) {
                $target_timestamp = strtotime(date('Y-m-d') . " $h_fin:00:00");
                $estado_reloj = 'termina';
            } else {
                $estado_reloj = 'proximo';
            }
        } else {
            $estado_reloj = 'proximo';
        }

        // Buscar siguiente día con medida (Loop futuro)
        if ($estado_reloj === 'proximo') {
            $fecha_iter = clone $ahora_global;
            $encontrado = false;
            for ($i = 1; $i <= 15; $i++) {
                $fecha_iter->modify('+1 day');
                $pyp_futuro = new PicoYPlaca($codigo, $fecha_iter, $ciudades, $festivos, $tipo_v);
                $res_futuro = $pyp_futuro->getInfo();
                if (!empty($res_futuro['restricciones'])) {
                    $target_timestamp = strtotime($fecha_iter->format('Y-m-d') . " $h_ini:00:00");
                    $encontrado = true;
                    break;
                }
            }
            if (!$encontrado) $target_timestamp = 0;
        }

        // --- C. Pronóstico 5 Días ---
        $pronostico = [];
        $f_pron = clone $ahora_global;
        $dias_semana_abrev = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

        for($k=0; $k<5; $k++) {
            $f_pron->modify('+1 day');
            $pyp_p = new PicoYPlaca($codigo, $f_pron, $ciudades, $festivos, $tipo_v);
            $inf_p = $pyp_p->getInfo();
            
            // Texto a mostrar: Placas o Nombre del Festivo/Excepción
            $texto_placas = !empty($inf_p['restricciones']) ? implode('-', $inf_p['restricciones']) : '✅';
            
            // Detectar motivo de "Día Libre" para mostrarlo
            $motivo_libre = '';
            if ($inf_p['es_festivo']) $motivo_libre = $inf_p['nombre_festivo'] ?? 'Festivo';
            if ($inf_p['es_excepcion'] ?? false) $motivo_libre = $inf_p['nombre_festivo']; // Reusamos el campo para excepciones

            $pronostico[] = [
                'dia' => $dias_semana_abrev[$f_pron->format('w')],
                'fecha' => $f_pron->format('d/m'),
                'estado' => !empty($inf_p['restricciones']) ? 'ocupado' : ($inf_p['es_festivo'] ? 'festivo' : 'libre'),
                'placas' => $texto_placas,
                'motivo_libre' => $motivo_libre // Nuevo campo para el JS
            ];
        }

        // Guardar datos maestros
        $datos_hoy[$codigo] = [
            'restricciones' => $info_hoy['restricciones'],
            'permitidas' => $permitidas,
            'horario' => $info_hoy['horario'],
            'nombre' => $info['nombre'],
            'vehiculo_label' => $info_hoy['vehiculo_label'],
            'nombre_festivo' => $info_hoy['nombre_festivo'],
            'es_excepcion' => $info_hoy['es_excepcion'] ?? false,
            'target_ts' => $target_timestamp, 
            'estado_reloj' => $estado_reloj,
            'pronostico' => $pronostico
        ];
    }
}
$datos_hoy_json = json_encode($datos_hoy);


// =============================================================================
// LÓGICA 2: PÁGINA FECHA ESPECÍFICA (SEO)
// =============================================================================
if (preg_match('/pico-y-placa\/(\d{4})-(\d{2})-(\d{2})-(\w+)/', $_SERVER['REQUEST_URI'], $matches)) {
    $year = (int)$matches[1]; $month = (int)$matches[2]; $day = (int)$matches[3]; $ciudad_slug = $matches[4];
    
    if (isset($ciudades[$ciudad_slug])) {
        try {
            $fecha_consulta = new DateTime("$year-$month-$day");
            $tipo_v = isset($ciudades[$ciudad_slug]['vehiculos'][$vehiculo_sel]) ? $vehiculo_sel : 'particular';
            $pyp = new PicoYPlaca($ciudad_slug, $fecha_consulta, $ciudades, $festivos, $tipo_v);
            $info_pyp = $pyp->getInfo();
            
            $monthNames = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
            $dateData = [
                'dayNameEs' => $info_pyp['dia_nombre'],
                'dayNum' => (int)$fecha_consulta->format('d'),
                'monthName' => $monthNames[$month - 1],
                'year' => $year, 'month' => $month, 'day' => $day,
                'cityName' => $info_pyp['ciudad_nombre'],
                'citySlug' => $ciudad_slug,
                'restrictions' => $info_pyp['restricciones'],
                'allowed' => array_values(array_diff([0,1,2,3,4,5,6,7,8,9], $info_pyp['restricciones'])),
                'isWeekend' => $info_pyp['es_fin_semana'],
                'isHoliday' => $info_pyp['es_festivo'],
                'holidayName' => $info_pyp['nombre_festivo'],
                'isException' => $info_pyp['es_excepcion'] ?? false,
                'horario' => $info_pyp['horario'],
                'vehiculo' => $info_pyp['vehiculo_label']
            ];
            $isDatePage = true;
            $ciudad_sel_url = $ciudad_slug; 
        } catch (Exception $e) { http_response_code(404); }
    } else { http_response_code(404); }
}

// =============================================================================
// SEO y HEADERS
// =============================================================================
$nombre_vehiculo_seo = $ciudades[$ciudad_sel_url]['vehiculos'][$vehiculo_sel]['label'] ?? 'Particulares';
$ciudad_nombre_seo = $ciudades[$ciudad_sel_url]['nombre'] ?? 'Colombia';

if ($isDatePage) {
    $placas_txt = count($dateData['restrictions']) > 0 ? implode('-', $dateData['restrictions']) : 'Sin restricción';
    if($dateData['isHoliday']) $placas_txt = "Festivo (" . $dateData['holidayName'] . ")";
    if($dateData['isException']) $placas_txt = "Medida Levantada (" . $dateData['holidayName'] . ")"; // En excepciones holidayName trae el motivo
    
    $title = "Pico y placa $nombre_vehiculo_seo el " . ucfirst($dateData['dayNameEs']) . " " . $dateData['dayNum'] . " de " . ucfirst($dateData['monthName']) . " en " . $dateData['cityName'];
    $description = "Restricción $nombre_vehiculo_seo en " . $dateData['cityName'] . " el " . $dateData['dayNameEs'] . ". Estado: $placas_txt";
    $canonical = "https://picoyplacabogota.com.co/pico-y-placa/{$dateData['year']}-" . str_pad($dateData['month'], 2, '0', STR_PAD_LEFT) . "-" . str_pad($dateData['day'], 2, '0', STR_PAD_LEFT) . "-{$dateData['citySlug']}?vehicle=$vehiculo_sel";
} else {
    $title = "Pico y Placa $nombre_vehiculo_seo HOY en $ciudad_nombre_seo 🚗 | " . date('Y');
    $description = "Consulta el pico y placa para $nombre_vehiculo_seo en $ciudad_nombre_seo. Horarios, festivos y mapas actualizados.";
    $canonical = "https://picoyplacabogota.com.co/?city=$ciudad_sel_url&vehicle=$vehiculo_sel";
}

include 'includes/header.php';
?>

<?php 
if (!empty($alertas_activas)) {
    foreach ($alertas_activas as $alerta) {
        // Mostrar si es global o coincide con la ciudad actual
        if ($alerta['ciudad_id'] === 'global' || $alerta['ciudad_id'] === $ciudad_sel_url) {
            ?>
            <div class="container mt-3">
                <div class="alert alert-<?php echo $alerta['tipo'] ?? 'info'; ?>" style="border-left: 5px solid rgba(0,0,0,0.2); box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                    <strong>📢 <?php echo ($alerta['ciudad_id'] !== 'global') ? $ciudades[$alerta['ciudad_id']]['nombre'] . ':' : 'ATENCIÓN:'; ?></strong> 
                    <?php echo htmlspecialchars($alerta['mensaje']); ?>
                    <?php if (!empty($alerta['url'])): ?>
                        <a href="<?php echo htmlspecialchars($alerta['url']); ?>" style="font-weight:bold; text-decoration:underline; margin-left:5px;">Ver más →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
    }
}
?>


<?php if (!$isDatePage): ?>
    
    <div class="date-search-section">
        <h2 style="margin-bottom: 12px; font-size: 1.2rem;">📅 Buscar otra fecha</h2>
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
        <div class="info-card"><h3>🚫 Estado</h3><p id="today-status" style="font-size: 1.1rem;">--</p></div>
        <div class="info-card"><h3>🕐 Horario</h3><p id="city-schedule" style="font-size: 0.95rem;">--</p></div>
    </div>
    
    <div id="countdownContainer">
        <h3 id="countdownTitle">⏳ Cargando...</h3>
        <div class="countdown-display" id="countdownDisplay">
            <div class="countdown-item"><div id="countdownHours">00</div><small>Horas</small></div>
            <span class="countdown-separator">:</span>
            <div class="countdown-item"><div id="countdownMinutes">00</div><small>Minutos</small></div>
            <span class="countdown-separator">:</span>
            <div class="countdown-item"><div id="countdownSeconds">00</div><small>Segundos</small></div>
        </div>
        <div id="countdownMessage" style="text-align:center; margin-top:10px; font-size:0.9rem; opacity:0.8;"></div>
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
                <input type="text" id="plate-input" placeholder="Ej: 5" maxlength="1" inputmode="numeric" style="text-align:center; font-size:1.2rem; width:80px; border-radius:8px; border:2px solid #ddd;">
                <button type="button" class="btn-search" style="flex:1;" onclick="searchPlate()">Consultar</button>
            </div>
            <div id="result-box" class="result-box"></div>
        </div>
        
        <div class="restrictions-today">
            <h2 style="margin-bottom: 12px;">Restricciones HOY</h2>
            <h3 id="city-today" style="color: #667eea; margin-bottom: 10px;">--</h3>
            
            <div id="dynamic-message-container" style="margin-bottom:15px; display:none; background:#f0fff4; color:#276749; padding:10px; border-radius:8px; border:1px solid #c6f6d5; font-weight:600;"></div>

            <p id="label-restricted" style="margin-bottom: 5px; font-weight: 600;">🚫 No circulan:</p>
            <div class="plates-list" id="plates-restricted-today"></div>
            
            <p id="label-allowed" style="margin: 15px 0 5px 0; font-weight: 600;">✅ Pueden circular:</p>
            <div class="plates-list" id="plates-allowed-today"></div>

            <div style="margin-top: 25px; padding-top: 20px; border-top: 2px dashed #eee;">
                <h4 style="font-size: 1rem; color: #555; margin-bottom: 15px;">📅 Próximos días:</h4>
                <div id="forecast-container" style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 10px;">
                    </div>
            </div>
        </div>
    </div>
    
    <div style="background: #fff5f5; border: 1px solid #fed7d7; padding: 20px; border-radius: 15px; margin-bottom: 20px;">
        <h3 style="color: #c53030; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
            👮‍♂️ ¡Evita la Multa!
        </h3>
        <p style="margin-top: 10px; font-size: 0.95rem; color: #742a2a;">
            La sanción por incumplir la medida es la <strong>C.14</strong>:
        </p>
        <ul style="margin: 10px 0 10px 20px; font-size: 0.95rem; color: #742a2a;">
            <li>Multa de <strong>15 SMDLV</strong> (Aprox. $650.000 COP).</li>
            <li><strong>Inmovilización</strong> del vehículo (Grúa + Patios).</li>
        </ul>
        <small style="color: #c53030; font-weight: 600;">¡Mejor consulta antes de salir!</small>
    </div>

    <div class="info-section">
        <h2>ℹ️ Información General</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <div><strong>🚗 Exentos:</strong><p style="margin: 5px 0 0 0; opacity: 0.9; font-size:0.9rem;">Híbridos, Eléctricos, Carro compartido (según ciudad).</p></div>
            <div><strong>🎉 Festivos:</strong><p style="margin: 5px 0 0 0; opacity: 0.9; font-size:0.9rem;">No aplica medida general.</p></div>
        </div>
    </div>

<?php else: ?>

    <button class="back-btn" onclick="backToHome()" style="display: inline-block; margin-bottom: 20px; padding: 10px 20px; background: white; color: #667eea; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; min-height: 44px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        ← Volver a Hoy
    </button>

    <div style="background: white; padding: 30px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
        <h2 style="margin-bottom: 5px; font-size: 2rem;">
            📅 <?php echo htmlspecialchars($dateData['dayNum']); ?> de <?php echo ucfirst($dateData['monthName']); ?>
        </h2>
        <p style="color: #666; margin-bottom: 20px; font-size: 1.1rem;"><?php echo $dateData['year']; ?> • <?php echo ucfirst($dateData['dayNameEs']); ?></p>

        <h3 style="color: #667eea; margin-bottom: 15px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
            🚗 Pico y Placa: <?php echo htmlspecialchars($dateData['cityName']); ?>
        </h3>
        
        <div style="background: #f8fafc; padding: 20px; border-radius: 10px; margin: 20px 0; border: 1px solid #e2e8f0;">
            <p style="margin-bottom: 8px;"><strong>🕐 Horario:</strong> <?php echo htmlspecialchars($dateData['horario']); ?></p>
            <p><strong>📊 Estado:</strong> 
                <?php 
                if ($dateData['isException']) {
                    $motivo = !empty($dateData['holidayName']) ? $dateData['holidayName'] : 'Medida Levantada';
                    echo '<span class="no-restriction" style="color:#27ae60; font-weight:bold;">🔓 ' . htmlspecialchars($motivo) . '</span>';
                } elseif ($dateData['isWeekend']) {
                    echo '<span class="no-restriction" style="color:#27ae60; font-weight:bold;">✅ Sin restricción (Fin de semana)</span>';
                } elseif ($dateData['isHoliday']) {
                    $festivo_nombre = !empty($dateData['holidayName']) ? $dateData['holidayName'] : 'Día Festivo';
                    echo '<span class="no-restriction" style="color:#27ae60; font-weight:bold;">🎉 ' . htmlspecialchars($festivo_nombre) . ' (Sin restricción)</span>';
                } else {
                    echo count($dateData['restrictions']) > 0 
                        ? '<span class="has-restriction" style="color:#e74c3c; font-weight:bold;">⚠️ Aplica Medida</span>' 
                        : '<span class="no-restriction" style="color:#27ae60; font-weight:bold;">✅ Día Libre</span>';
                }
                ?>
            </p>
        </div>
        
        <div class="row">
            <div style="margin-top: 10px;">
                <p style="margin-bottom: 10px; font-weight: 600; color: #e74c3c;">🚫 Placas con restricción:</p>
                <div class="plates-list">
                    <?php
                    if ($dateData['isWeekend'] || $dateData['isHoliday'] || $dateData['isException']) {
                        // Mensaje claro en fecha específica también
                        echo '<p class="no-restriction" style="color:#555;">No aplica, pueden circular todos.</p>';
                    } elseif (count($dateData['restrictions']) > 0) {
                        foreach ($dateData['restrictions'] as $p) echo '<span class="plate-badge restricted">' . $p . '</span>';
                    } else {
                        echo '<p class="no-restriction">Ninguna (Día sin restricción)</p>';
                    }
                    ?>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <p style="margin-bottom: 10px; font-weight: 600; color: #27ae60;">✅ Placas habilitadas:</p>
                <div class="plates-list">
                    <?php
                    if ($dateData['isWeekend'] || $dateData['isHoliday'] || $dateData['isException'] || count($dateData['restrictions']) === 0) {
                        echo '<span class="plate-badge wide">Todas las placas están autorizadas</span>';
                    } else {
                        foreach ($dateData['allowed'] as $p) echo '<span class="plate-badge">' . $p . '</span>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
<?php endif; ?>

<script>
    <?php if (!$isDatePage): ?>
    
    const DATA_PYP = <?php echo $datos_hoy_json; ?>;
    let selectedCity = '<?php echo $ciudad_sel_url; ?>';
    let countdownInterval;
    
    function updateUI() {
        const data = DATA_PYP[selectedCity];
        if (!data) return;

        // 1. Info Básica
        document.getElementById('city-today').textContent = data.nombre;
        document.getElementById('city-schedule').textContent = data.horario;
        
        const today = new Date();
        const options = {weekday: 'long', day: 'numeric', month: 'long'};
        document.getElementById('today-date').textContent = today.toLocaleDateString('es-CO', options);

        // 2. Estado Restricción (Texto Inteligente)
        const statusEl = document.getElementById('today-status');
        const restrictedContainer = document.getElementById('plates-restricted-today');
        const allowedContainer = document.getElementById('plates-allowed-today');
        const labelRestricted = document.getElementById('label-restricted');
        const msgContainer = document.getElementById('dynamic-message-container');
        
        // Reset
        restrictedContainer.innerHTML = '';
        allowedContainer.innerHTML = '';
        labelRestricted.style.display = 'block';
        msgContainer.style.display = 'none';

        // Lógica de Mensajes
        if (data.es_excepcion) {
            // MEDIDA LEVANTADA (Excepción)
            statusEl.innerHTML = '<span style="color:#27ae60;">🔓 Medida Levantada</span>';
            msgContainer.style.display = 'block';
            msgContainer.innerHTML = '✨ ' + (data.nombre_festivo || 'Medida levantada temporalmente');
            
            labelRestricted.style.display = 'none';
            allowedContainer.innerHTML = '<span class="plate-badge wide">Todas las placas están autorizadas</span>';
            
        } else if (data.nombre_festivo) {
            // FESTIVO
            statusEl.innerHTML = '<span style="color:#27ae60;">🎉 ' + data.nombre_festivo + '</span>';
            msgContainer.style.display = 'block';
            msgContainer.innerHTML = '🎉 Hoy es ' + data.nombre_festivo + ', pueden circular todos los vehículos.';
            
            labelRestricted.style.display = 'none'; // Ocultamos "No circulan"
            allowedContainer.innerHTML = '<span class="plate-badge wide">Todas las placas están autorizadas</span>';

        } else if (data.restricciones.length > 0) {
            // HAY RESTRICCIÓN NORMAL
            statusEl.innerHTML = '<span style="color:#e74c3c;">🚫 Hay Pico y Placa</span>';
            data.restricciones.forEach(p => restrictedContainer.innerHTML += `<span class="plate-badge restricted">${p}</span>`);
            data.permitidas.forEach(p => allowedContainer.innerHTML += `<span class="plate-badge">${p}</span>`);

        } else {
            // DÍA LIBRE (Fin de semana o día sin medida normal)
            statusEl.innerHTML = '<span style="color:#27ae60;">✅ Sin Restricción</span>';
            msgContainer.style.display = 'block';
            msgContainer.innerHTML = '✅ Hoy no aplica la medida en ' + data.nombre + '.';
            
            labelRestricted.style.display = 'none';
            allowedContainer.innerHTML = '<span class="plate-badge wide">Todas las placas están autorizadas</span>';
        }

        // 3. Actualizar Reloj
        startCountdown(data.target_ts, data.estado_reloj);

        // 4. Pronóstico
        renderForecast(data.pronostico);
    }

    function startCountdown(targetTimestamp, estado) {
        clearInterval(countdownInterval);
        const titleEl = document.getElementById('countdownTitle');
        const msgEl = document.getElementById('countdownMessage');
        
        // ... (Lógica del reloj igual, solo limpiamos mensajes si no hay datos) ...
        if (estado === 'sin_datos' || targetTimestamp === 0) {
            titleEl.textContent = '✅ Libre';
            msgEl.textContent = 'No hay restricciones próximas.';
            return;
        }
        
        let titulo = '', mensaje = '';
        if (estado === 'inicia') { titulo = '⏳ Inicia en:'; mensaje = 'La medida comienza hoy.'; }
        else if (estado === 'termina') { titulo = '🚨 Termina en:'; mensaje = 'Restricción activa.'; }
        else if (estado === 'proximo') { 
            titulo = '📅 Próxima:'; 
            const d = new Date(targetTimestamp * 1000);
            const dia = d.toLocaleDateString('es-CO', {weekday:'long'});
            const hora = d.getHours() > 12 ? (d.getHours()-12)+' PM' : d.getHours()+' AM';
            mensaje = `Inicia el ${dia} a las ${hora}`;
        }
        
        titleEl.textContent = titulo;
        msgEl.textContent = mensaje;

        function tick() {
            const now = Math.floor(Date.now() / 1000);
            const diff = targetTimestamp - now;
            if (diff <= 0) { location.reload(); return; }
            const h = Math.floor(diff / 3600).toString().padStart(2,'0');
            const m = Math.floor((diff % 3600) / 60).toString().padStart(2,'0');
            const s = (diff % 60).toString().padStart(2,'0');
            document.getElementById('countdownHours').textContent = h;
            document.getElementById('countdownMinutes').textContent = m;
            document.getElementById('countdownSeconds').textContent = s;
        }
        tick();
        countdownInterval = setInterval(tick, 1000);
    }

    function renderForecast(dias) {
        const container = document.getElementById('forecast-container');
        container.innerHTML = '';
        
        dias.forEach(dia => {
            const esLibre = dia.estado === 'libre' || dia.estado === 'festivo';
            const colorBg = esLibre ? '#f0fff4' : '#fff5f5';
            const colorBorde = esLibre ? '#c6f6d5' : '#fed7d7';
            const icono = dia.estado === 'festivo' ? '🎉' : (esLibre ? '✅' : '🚫');
            
            // Si es festivo/excepción mostramos el Nombre, si no las placas
            let contenidoCentral = dia.placas;
            let estiloFuente = "font-size: 0.85rem; font-weight: 700; color: #333;";
            
            if (dia.motivo_libre) {
                contenidoCentral = dia.motivo_libre; // Ej: "Jueves Santo"
                estiloFuente = "font-size: 0.75rem; font-weight: 600; color: #276749; line-height:1.1;";
            }

            const html = `
                <div style="min-width: 90px; background: ${colorBg}; border: 1px solid ${colorBorde}; border-radius: 8px; padding: 8px; text-align: center; display: flex; flex-direction: column; justify-content: space-between;">
                    <div style="font-size: 0.8rem; font-weight: bold; color: #555;">${dia.dia}</div>
                    <div style="font-size: 0.75rem; color: #999;">${dia.fecha}</div>
                    <div style="font-size: 1.2rem; margin: 5px 0;">${icono}</div>
                    <div style="${estiloFuente}">${contenidoCentral}</div>
                </div>
            `;
            container.innerHTML += html;
        });
    }

    function selectCity(cityCode) {
        selectedCity = cityCode;
        document.querySelectorAll('.city-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('btn-'+cityCode).classList.add('active');
        updateUI();
        const url = new URL(window.location);
        url.searchParams.set('city', cityCode);
        window.history.pushState({}, '', url);
    }

    function searchPlate() {
        const val = document.getElementById('plate-input').value;
        if (!val || isNaN(val)) { alert('Ingresa un número'); return; }
        const digit = parseInt(val);
        const data = DATA_PYP[selectedCity];
        
        // Si hay excepción o festivo, no hay restricción
        if (data.nombre_festivo || data.es_excepcion || data.restricciones.length === 0) {
            document.getElementById('result-box').className = 'result-box result-success';
            document.getElementById('result-box').innerHTML = `<strong>✅ Habilitado:</strong> Hoy no aplica medida para ninguna placa.`;
            document.getElementById('result-box').style.display = 'block';
            return;
        }

        const restricted = data.restricciones.includes(digit);
        const box = document.getElementById('result-box');
        box.style.display = 'block';
        if (restricted) {
            box.className = 'result-box result-restricted';
            box.innerHTML = `<strong>⚠️ Restricción:</strong> Tu placa ${digit} NO puede circular hoy.`;
        } else {
            box.className = 'result-box result-success';
            box.innerHTML = `<strong>✅ Habilitado:</strong> Puedes circular hoy con placa ${digit}.`;
        }
    }
    
    function scrollCities(dir) {
        const el = document.getElementById('citiesSlider');
        el.scrollBy({ left: dir==='left'?-150:150, behavior: 'smooth' });
    }
    function searchByDate(e) {
        e.preventDefault();
        const d = document.getElementById('dateInput').value;
        const c = document.getElementById('citySelect').value;
        if(d && c) window.location.href = `/pico-y-placa/${d}-${c}`;
    }
    function backToHome() { window.location.href = '/'; }

    document.addEventListener('DOMContentLoaded', updateUI);
    
    <?php endif; ?>
</script>

<style>
    .plate-badge.wide { width: auto; padding: 5px 15px; border-radius: 6px; font-weight: 600; background: #e6fffa; color: #2c7a7b; border: 1px solid #b2f5ea; }
</style>

<?php include 'includes/footer.php'; ?>
