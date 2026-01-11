<?php
/**
 * PICO Y PLACA - Frontend Principal (Versión 5.0 Final)
 * Correcciones: Bogotá Default, Reloj Exacto, Diseño Mejorado
 */

// 1. Cargar Clases y Datos
require_once 'clases/PicoYPlaca.php';

$file_config = __DIR__ . '/datos/config.json';
$file_festivos = __DIR__ . '/datos/festivos.json';
$file_alerta = __DIR__ . '/datos/alertas.json';

// Cargar datos
$ciudades = file_exists($file_config) ? json_decode(file_get_contents($file_config), true) : [];
$festivos = file_exists($file_festivos) ? json_decode(file_get_contents($file_festivos), true) : [];
$alertas_raw = file_exists($file_alerta) ? json_decode(file_get_contents($file_alerta), true) : [];
$alertas_activas = is_array($alertas_raw) ? array_filter($alertas_raw, function($a){ return isset($a['activa']) && $a['activa']; }) : [];

// Ordenar ciudades alfabéticamente para el Grid
uasort($ciudades, function($a, $b) {
    return strcmp($a['nombre'], $b['nombre']);
});

// Obtener lista de vehículos para el buscador
$tipos_vehiculos_global = [];
foreach ($ciudades as $c) {
    foreach ($c['vehiculos'] as $k => $v) {
        $tipos_vehiculos_global[$k] = $v['label'];
    }
}
// Ordenar vehículos: Particular primero
$prioridad = ['particular' => 1, 'moto' => 2, 'taxi' => 3];
uksort($tipos_vehiculos_global, function($a, $b) use ($prioridad) {
    $pa = $prioridad[$a] ?? 99;
    $pb = $prioridad[$b] ?? 99;
    return $pa <=> $pb;
});

// 2. Parámetros URL (Lógica Bogotá Default)
$vehiculo_sel = $_GET['vehicle'] ?? 'particular';
// Si no hay parámetro 'city', forzamos 'bogota'. Si hay, usamos el get.
$ciudad_sel_url = $_GET['city'] ?? 'bogota';

// Validación de seguridad: si la ciudad no existe, volver a bogota
if (!isset($ciudades[$ciudad_sel_url])) {
    $ciudad_sel_url = 'bogota';
}

$isDatePage = false;
$dateData = [];

// =============================================================================
// LÓGICA 1: DATOS DE "HOY" + RELOJ EXACTO
// =============================================================================
$ahora_global = new DateTime(); 
$datos_hoy = [];

if (!empty($ciudades)) {
    foreach ($ciudades as $codigo => $info) {
        // Fallback de vehículo
        $tipo_v = isset($info['vehiculos'][$vehiculo_sel]) ? $vehiculo_sel : 'particular';
        if (!isset($info['vehiculos'][$tipo_v])) {
            $tipo_v = array_key_first($info['vehiculos']);
        }
        
        // --- A. Análisis de HOY ---
        $pyp_hoy = new PicoYPlaca($codigo, $ahora_global, $ciudades, $festivos, $tipo_v);
        $info_hoy = $pyp_hoy->getInfo();
        $todas = [0,1,2,3,4,5,6,7,8,9];
        $permitidas = array_values(array_diff($todas, $info_hoy['restricciones']));

        // --- B. Reloj Inteligente (Lógica Mejorada) ---
        $target_timestamp = 0;
        $estado_reloj = 'sin_datos'; // inicia, termina, proximo, libre
        $hora_actual = (int)$ahora_global->format('G'); // 0-23
        
        // Si es festivo, fin de semana (sin restricción) o excepción, el reloj no cuenta
        if ($info_hoy['es_festivo'] || $info_hoy['es_fin_semana'] || ($info_hoy['es_excepcion'] ?? false)) {
            $estado_reloj = 'libre';
        } 
        elseif (empty($info_hoy['restricciones'])) {
             $estado_reloj = 'libre'; // Día hábil pero sin pico y placa para este vehículo
        }
        else {
            // Intentar parsear horario específico (Ej: "6:00 a.m. - 9:00 p.m.")
            // Buscamos patrones simples de hora inicio y fin
            $h_ini = 6; $h_fin = 20; // Default
            
            if (preg_match('/(\d{1,2})[:\.]?(\d{0,2}).*?(a\.?m\.?|p\.?m\.?).*?(\d{1,2})[:\.]?(\d{0,2}).*?(a\.?m\.?|p\.?m\.?)/i', $info_hoy['horario'], $m)) {
                // $m[1] hora ini, $m[3] am/pm ini, $m[4] hora fin, $m[6] am/pm fin
                $h_ini = (int)$m[1];
                if (stripos($m[3], 'p') !== false && $h_ini < 12) $h_ini += 12;
                
                $h_fin = (int)$m[4];
                if (stripos($m[6], 'p') !== false && $h_fin < 12) $h_fin += 12;
            }

            // Lógica de estado
            if ($hora_actual < $h_ini) {
                // Aún no empieza
                $target_timestamp = strtotime(date('Y-m-d') . " $h_ini:00:00");
                $estado_reloj = 'inicia';
            } elseif ($hora_actual >= $h_ini && $hora_actual < $h_fin) {
                // Estamos en medio de la restricción
                $target_timestamp = strtotime(date('Y-m-d') . " $h_fin:00:00");
                $estado_reloj = 'termina';
            } else {
                // Ya terminó por hoy -> Buscar mañana
                $estado_reloj = 'proximo';
            }
        }
        
        // Si el estado es 'proximo' (ya acabó hoy), buscamos el siguiente día hábil
        if ($estado_reloj === 'proximo') {
            $fecha_iter = clone $ahora_global;
            $encontrado = false;
            // Iterar máx 7 días
            for ($i = 1; $i <= 7; $i++) {
                $fecha_iter->modify('+1 day');
                $pyp_futuro = new PicoYPlaca($codigo, $fecha_iter, $ciudades, $festivos, $tipo_v);
                $res_futuro = $pyp_futuro->getInfo();
                
                if (!empty($res_futuro['restricciones']) && !$res_futuro['es_festivo'] && !$res_futuro['es_excepcion']) {
                    // Encontramos el próximo día con medida
                    // Necesitamos la hora de inicio de ESE día (asumimos misma config horario general)
                    // (Simplificación: usamos la misma hora de inicio detectada hoy o default 6am)
                    $h_next = isset($h_ini) ? $h_ini : 6; 
                    $target_timestamp = strtotime($fecha_iter->format('Y-m-d') . " $h_next:00:00");
                    $encontrado = true;
                    break;
                }
            }
            if (!$encontrado) $estado_reloj = 'libre'; // No encontró restricción próxima
        }

        // --- C. Pronóstico ---
        $pronostico = [];
        $f_pron = clone $ahora_global;
        $dias_semana_abrev = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

        for($k=0; $k<5; $k++) {
            $f_pron->modify('+1 day');
            $pyp_p = new PicoYPlaca($codigo, $f_pron, $ciudades, $festivos, $tipo_v);
            $inf_p = $pyp_p->getInfo();
            $texto_placas = !empty($inf_p['restricciones']) ? implode('-', $inf_p['restricciones']) : '✅';
            
            $motivo_libre = '';
            if ($inf_p['es_festivo']) $motivo_libre = $inf_p['nombre_festivo'] ?? 'Festivo';
            if ($inf_p['es_excepcion'] ?? false) $motivo_libre = $inf_p['nombre_festivo'];

            $pronostico[] = [
                'dia' => $dias_semana_abrev[$f_pron->format('w')],
                'fecha' => $f_pron->format('d/m'),
                'estado' => !empty($inf_p['restricciones']) ? 'ocupado' : ($inf_p['es_festivo'] ? 'festivo' : 'libre'),
                'placas' => $texto_placas,
                'motivo_libre' => $motivo_libre
            ];
        }

        $lista_vehiculos_ciudad = [];
        foreach($info['vehiculos'] as $k_v => $d_v) {
            $lista_vehiculos_ciudad[$k_v] = $d_v['label'];
        }

        $datos_hoy[$codigo] = [
            'restricciones' => $info_hoy['restricciones'],
            'permitidas' => $permitidas,
            'horario' => $info_hoy['horario'],
            'nombre' => $info['nombre'],
            'vehiculo_actual_key' => $tipo_v,
            'vehiculo_label' => $info_hoy['vehiculo_label'],
            'vehiculos_disponibles' => $lista_vehiculos_ciudad,
            'nombre_festivo' => $info_hoy['nombre_festivo'],
            'es_excepcion' => $info_hoy['es_excepcion'] ?? false,
            'es_fin_semana' => $info_hoy['es_fin_semana'],
            'target_ts' => $target_timestamp, 
            'estado_reloj' => $estado_reloj,
            'pronostico' => $pronostico
        ];
    }
}
$datos_hoy_json = json_encode($datos_hoy);

// =============================================================================
// LÓGICA 2: PÁGINA FECHA ESPECÍFICA
// =============================================================================
if (preg_match('/pico-y-placa\/(\d{4})-(\d{2})-(\d{2})-(\w+)/', $_SERVER['REQUEST_URI'], $matches)) {
    $year = (int)$matches[1]; $month = (int)$matches[2]; $day = (int)$matches[3]; $ciudad_slug = $matches[4];
    
    if (isset($ciudades[$ciudad_slug])) {
        $fecha_consulta = new DateTime("$year-$month-$day");
        $tipo_v = isset($ciudades[$ciudad_slug]['vehiculos'][$vehiculo_sel]) ? $vehiculo_sel : 'particular';
        
        $pyp = new PicoYPlaca($ciudad_slug, $fecha_consulta, $ciudades, $festivos, $tipo_v);
        $info_pyp = $pyp->getInfo();
        
        $monthNames = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        
        $otros_vehiculos = [];
        foreach($ciudades[$ciudad_slug]['vehiculos'] as $kv => $dv) {
            $otros_vehiculos[$kv] = $dv['label'];
        }

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
            'vehiculo' => $info_pyp['vehiculo_label'],
            'vehiculo_key' => $tipo_v,
            'otros_vehiculos' => $otros_vehiculos
        ];
        $isDatePage = true;
        $ciudad_sel_url = $ciudad_slug; 
    }
}

// =============================================================================
// HEADERS Y SEO
// =============================================================================
$nombre_vehiculo_seo = $ciudades[$ciudad_sel_url]['vehiculos'][$vehiculo_sel]['label'] ?? 'Particulares';
$ciudad_nombre_seo = $ciudades[$ciudad_sel_url]['nombre'] ?? 'Colombia';

if ($isDatePage) {
    $placas_txt = count($dateData['restrictions']) > 0 ? implode('-', $dateData['restrictions']) : 'Sin restricción';
    if($dateData['isHoliday']) $placas_txt = "Festivo";
    
    $title = "Pico y placa $nombre_vehiculo_seo el " . ucfirst($dateData['dayNameEs']) . " " . $dateData['dayNum'] . " de " . ucfirst($dateData['monthName']) . " en " . $dateData['cityName'];
    $description = "Consulta la restricción para $nombre_vehiculo_seo en " . $dateData['cityName'] . ". Placas: $placas_txt.";
    $canonical = "https://picoyplacabogota.com.co/pico-y-placa/{$dateData['year']}-" . str_pad($dateData['month'], 2, '0', STR_PAD_LEFT) . "-" . str_pad($dateData['day'], 2, '0', STR_PAD_LEFT) . "-{$dateData['citySlug']}?vehicle=$vehiculo_sel";
} else {
    $title = "Pico y Placa $nombre_vehiculo_seo HOY en $ciudad_nombre_seo 🚗 | " . date('Y');
    $description = "Estado actual del Pico y Placa $nombre_vehiculo_seo en $ciudad_nombre_seo. Horarios y rotación al día.";
    $canonical = "https://picoyplacabogota.com.co/?city=$ciudad_sel_url&vehicle=$vehiculo_sel";
}

include 'includes/header.php';
?>

<?php if (!empty($alertas_activas)): ?>
    <?php foreach ($alertas_activas as $alerta): ?>
        <?php if ($alerta['ciudad_id'] === 'global' || $alerta['ciudad_id'] === $ciudad_sel_url): ?>
            <div class="container mt-3">
                <div class="alert alert-<?php echo $alerta['tipo'] ?? 'info'; ?>" style="border-left: 5px solid rgba(0,0,0,0.2); box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 15px; background: #fff; border-radius: 8px; margin-bottom: 20px;">
                    <strong>📢 <?php echo ($alerta['ciudad_id'] !== 'global') ? $ciudades[$alerta['ciudad_id']]['nombre'] . ':' : 'ATENCIÓN:'; ?></strong> 
                    <?php echo htmlspecialchars($alerta['mensaje']); ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>


<?php if (!$isDatePage): ?>
    
    <div class="date-search-section">
        <h2 style="margin-bottom: 12px; font-size: 1.2rem; color: #333; font-weight: 700;">📅 Buscar Pico y Placa</h2>
        <form onsubmit="searchByDate(event)" style="display: flex; gap: 10px; flex-wrap: wrap;">
            
            <div style="flex: 1; min-width: 140px;">
                <label style="font-size: 0.8rem; font-weight: 600; color: #666; display: block; margin-bottom: 4px;">Fecha:</label>
                <input type="date" id="dateInput" required class="form-control" style="width: 100%;">
            </div>

            <div style="flex: 1; min-width: 140px;">
                <label style="font-size: 0.8rem; font-weight: 600; color: #666; display: block; margin-bottom: 4px;">Ciudad:</label>
                <select id="citySelect" class="form-control" style="width: 100%;">
                    <?php foreach ($ciudades as $codigo => $info): ?>
                    <option value="<?php echo $codigo; ?>" <?php echo ($codigo === $ciudad_sel_url) ? 'selected' : ''; ?>>
                        <?php echo $info['nombre']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1; min-width: 140px;">
                <label style="font-size: 0.8rem; font-weight: 600; color: #666; display: block; margin-bottom: 4px;">Vehículo:</label>
                <select id="vehicleSelect" class="form-control" style="width: 100%;">
                    <?php foreach ($tipos_vehiculos_global as $k => $label): ?>
                    <option value="<?php echo $k; ?>" <?php echo ($k === $vehiculo_sel) ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex-basis: 100%; height: 0; margin: 0;"></div> 
            
            <button type="submit" class="btn-search" style="flex: 1; min-width: 100%;">🔍 Consultar</button>
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
            <div class="cities-grid-section">
                <div class="slider-header"><h2>📍 Selecciona tu Ciudad</h2></div>
                <div class="cities-grid-wrapper">
                    <?php foreach ($ciudades as $codigo => $info): ?>
                    <button type="button" class="city-grid-item <?php echo ($codigo === $ciudad_sel_url) ? 'active' : ''; ?>" 
                            id="btn-<?php echo $codigo; ?>" 
                            onclick="selectCity('<?php echo $codigo; ?>')">
                        <?php echo $info['nombre']; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #eee;">

            <label style="display: block; margin: 0 0 10px 0; font-weight: 700; color: #444;">
                🔢 Verificar mi placa (último dígito):
            </label>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="plate-input" placeholder="#" maxlength="1" inputmode="numeric" 
                       style="text-align:center; font-size:1.4rem; width:80px; border-radius:12px; border:2px solid #ddd; font-weight: bold;">
                <button type="button" class="btn-search" style="flex:1;" onclick="searchPlate()">Verificar</button>
            </div>
            <div id="result-box" class="result-box"></div>
        </div>
        
        <div class="results-wrapper"> <div class="restrictions-today results-card">
                
                <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                    <div>
                        <h2 style="margin: 0; font-size: 1.5rem;">Restricciones HOY</h2>
                        <h3 id="city-today" style="color: #667eea; margin: 5px 0 0 0; font-size: 1.2rem;">--</h3>
                    </div>
                    <span id="vehicle-badge-current" style="background: #e2e8f0; color: #4a5568; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">
                        --
                    </span>
                </div>
                
                <div id="vehicle-tabs-container" style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px;"></div>

                <div id="dynamic-message-container" style="margin-bottom:15px; display:none; background:#f0fff4; color:#276749; padding:12px; border-radius:10px; border:1px solid #c6f6d5; font-weight:600;"></div>

                <div id="restricted-section">
                    <p id="label-restricted" style="margin-bottom: 8px; font-weight: 700; color: #e53e3e;">🚫 No circulan:</p>
                    <div class="plates-list" id="plates-restricted-today"></div>
                </div>
                
                <div id="allowed-section">
                    <p id="label-allowed" style="margin: 20px 0 8px 0; font-weight: 700; color: #38a169;">✅ Pueden circular:</p>
                    <div class="plates-list" id="plates-allowed-today"></div>
                </div>

                <div style="margin-top: 25px; padding-top: 20px; border-top: 2px dashed #eee;">
                    <h4 style="font-size: 1rem; color: #555; margin-bottom: 15px;">📅 Próximos días:</h4>
                    <div id="forecast-container" style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 10px;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="info-section">
        <h2>ℹ️ Información General</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div><strong>🚗 Exentos:</strong><p style="margin: 5px 0 0 0; opacity: 0.9; font-size:0.9rem;">Híbridos, Eléctricos, Carro compartido (varía por ciudad).</p></div>
            <div><strong>💸 Multa (C.14):</strong><p style="margin: 5px 0 0 0; opacity: 0.9; font-size:0.9rem;">15 SMDLV (~$650.000) + Inmovilización.</p></div>
        </div>
    </div>

<?php else: ?>

    <button class="back-btn" onclick="backToHome()" style="display: inline-flex; align-items: center; gap: 5px; margin-bottom: 20px; padding: 10px 20px; background: white; color: #667eea; border: none; border-radius: 50px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: transform 0.2s;">
        <span>←</span> Volver al inicio
    </button>

    <div style="background: white; padding: 30px; border-radius: 20px; margin-bottom: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); max-width: 800px; margin-left: auto; margin-right: auto;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="margin-bottom: 5px; font-size: 2.5rem; color: #2d3748;">
                📅 <?php echo htmlspecialchars($dateData['dayNum']); ?> de <?php echo ucfirst($dateData['monthName']); ?>
            </h2>
            <p style="color: #718096; font-size: 1.2rem; font-weight: 500;"><?php echo $dateData['year']; ?> • <?php echo ucfirst($dateData['dayNameEs']); ?></p>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #edf2f7; padding-bottom: 15px; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
            <h3 style="color: #667eea; margin: 0; font-size: 1.5rem;">
                🚗 <?php echo htmlspecialchars($dateData['cityName']); ?>
                <span style="font-size: 0.9rem; color: #a0aec0; display: block; margin-top: 5px;">
                    Vehículo: <strong><?php echo ucfirst($dateData['vehiculo']); ?></strong>
                </span>
            </h3>

            <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                <?php foreach($dateData['otros_vehiculos'] as $vk => $vl): ?>
                    <a href="?city=<?php echo $dateData['citySlug']; ?>&vehicle=<?php echo $vk; ?>" 
                       style="text-decoration: none; padding: 6px 12px; border-radius: 15px; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; 
                       <?php echo ($vk == $dateData['vehiculo_key']) ? 'background: #667eea; color: white;' : 'background: #edf2f7; color: #4a5568;'; ?>">
                       <?php echo $vl; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div style="background: #f7fafc; padding: 25px; border-radius: 15px; margin-bottom: 25px; border: 1px solid #e2e8f0;">
            <div style="margin-bottom: 15px;">
                <strong style="color: #4a5568;">🕐 Horario:</strong> 
                <span style="font-weight: 600; color: #2d3748;"><?php echo htmlspecialchars($dateData['horario']); ?></span>
            </div>
            
            <div>
                <strong style="color: #4a5568;">📊 Estado:</strong> 
                <?php 
                if ($dateData['isException']) {
                    $motivo = !empty($dateData['holidayName']) ? $dateData['holidayName'] : 'Medida Levantada';
                    echo '<span class="no-restriction" style="color:#27ae60; font-weight:800; background: #c6f6d5; padding: 2px 8px; border-radius: 4px;">🔓 ' . htmlspecialchars($motivo) . '</span>';
                } elseif ($dateData['isWeekend']) {
                    echo '<span class="no-restriction" style="color:#27ae60; font-weight:800;">✅ Sin restricción (Fin de semana)</span>';
                } elseif ($dateData['isHoliday']) {
                    $festivo_nombre = !empty($dateData['holidayName']) ? $dateData['holidayName'] : 'Día Festivo';
                    echo '<span class="no-restriction" style="color:#27ae60; font-weight:800;">🎉 ' . htmlspecialchars($festivo_nombre) . '</span>';
                } else {
                    echo count($dateData['restrictions']) > 0 
                        ? '<span class="has-restriction" style="color:#e53e3e; font-weight:800; background: #fed7d7; padding: 2px 8px; border-radius: 4px;">⚠️ Aplica Medida</span>' 
                        : '<span class="no-restriction" style="color:#27ae60; font-weight:800;">✅ Día Libre</span>';
                }
                ?>
            </div>
        </div>
        
        <div class="row">
            <div style="margin-top: 10px;">
                <p style="margin-bottom: 12px; font-weight: 700; color: #e53e3e; font-size: 1.1rem;">🚫 Placas con restricción:</p>
                <div class="plates-list">
                    <?php
                    if ($dateData['isWeekend'] || $dateData['isHoliday'] || $dateData['isException']) {
                        echo '<span style="color:#718096; font-style:italic;">No aplica</span>';
                    } elseif (count($dateData['restrictions']) > 0) {
                        foreach ($dateData['restrictions'] as $p) echo '<span class="plate-badge restricted" style="font-size: 1.2rem; padding: 10px 18px;">' . $p . '</span>';
                    } else {
                        echo '<span class="no-restriction">Ninguna</span>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
<?php endif; ?>

<script>
    function backToHome() { window.location.href = '/'; }
    
    <?php if (!$isDatePage): ?>
    
    function searchByDate(e) {
        e.preventDefault();
        const d = document.getElementById('dateInput').value;
        const c = document.getElementById('citySelect').value;
        const v = document.getElementById('vehicleSelect').value;
        
        if(d && c) {
            window.location.href = `/pico-y-placa/${d}-${c}?vehicle=${v}`;
        }
    }

    // Estilos dinámicos para Grid de ciudades
    const extraStyles = `
        .cities-grid-wrapper {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .city-grid-item {
            background: #f7fafc;
            border: 2px solid #edf2f7;
            padding: 10px 5px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            color: #4a5568;
            transition: all 0.2s;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .city-grid-item:hover {
            border-color: #cbd5e0;
            transform: translateY(-2px);
        }
        .city-grid-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.4);
        }
        .vehicle-tab-btn {
            background: white; border: 1px solid #e2e8f0; padding: 6px 12px; 
            border-radius: 20px; font-size: 0.8rem; cursor: pointer; color: #718096; font-weight: 600;
            transition: all 0.2s;
        }
        .vehicle-tab-btn:hover { background: #edf2f7; }
        .vehicle-tab-btn.active {
            background: #667eea; color: white; border-color: #667eea;
        }
    `;
    const styleSheet = document.createElement("style");
    styleSheet.innerText = extraStyles;
    document.head.appendChild(styleSheet);

    <?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
