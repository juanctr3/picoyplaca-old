<?php
/**
 * PICO Y PLACA - Sistema de Consulta de Restricciones Vehiculares
 * Versión 2.5 - Multi-Vehículo SEO (Fusionada)
 */

require_once 'clases/PicoYPlaca.php';
$config = require_once 'config-ciudades.php';
$ciudades = $config['ciudades'];
$festivos = $config['festivos'];

// -- DETECCIÓN DE PARÁMETROS --
$vehiculo_sel = $_GET['vehicle'] ?? 'particular'; // Por defecto particular
$ciudad_sel_url = $_GET['city'] ?? 'bogota';

$isDatePage = false;
$dateData = [];

// ==========================================
// 1. PROCESAR DATOS DE HOY (Bucle Principal)
// ==========================================

$ahora = new DateTime();
$datos_hoy = [];

// Recorremos todas las ciudades para generar el JSON que usa tu JS original
foreach ($ciudades as $codigo => $info) {
    // Validamos si el vehículo existe en esta ciudad, si no, fallback a particular
    $tipo_vehiculo_local = isset($info['vehiculos'][$vehiculo_sel]) ? $vehiculo_sel : 'particular';
    
    // Instanciamos la NUEVA clase con el parámetro de vehículo
    $pyp = new PicoYPlaca($codigo, $ahora, $ciudades, $festivos, $tipo_vehiculo_local);
    $info_pyp = $pyp->getInfo(); // Obtenemos la data nueva

    // Calculamos permitidas (0-9 menos las restricciones)
    $todas = [0,1,2,3,4,5,6,7,8,9];
    $permitidas = array_values(array_diff($todas, $info_pyp['restricciones']));

    // Parseamos horarios para el JS (asumiendo formato simple "HH:MM")
    // La nueva config tiene 'horario' texto. Intentamos extraer horas inicio/fin de la config nueva si existen,
    // o usamos valores por defecto para que el countdown funcione.
    $h_ini = 6; $h_fin = 21; // Defaults
    // Intenta leer de la config nueva si definimos propiedades específicas
    // (Nota: En la config nueva no pusimos horarioInicio/Fin explícitos en todos, 
    // pero para mantener tu JS funcionando, extraeremos o usaremos defaults).
    if (preg_match('/(\d{1,2})/', $info_pyp['horario'], $m)) $h_ini = (int)$m[1];
    
    // Mapeamos a la estructura que TU JAVASCRIPT ESPERA
    $datos_hoy[$codigo] = [
        'restricciones' => $info_pyp['restricciones'],
        'permitidas' => $permitidas,
        'horario' => $info_pyp['horario'],
        'nombre' => $info['nombre'],
        'vehiculo_label' => $info_pyp['vehiculo_label'], // Dato extra para UI
        // Mantenemos estos campos para que el countdown original funcione
        'horarioInicio' => $h_ini, 
        'horarioFin' => 20 // Ajuste genérico si no está parseado
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
            // Usamos el vehículo seleccionado también en páginas de fecha
            $tipo_vehiculo_local = isset($ciudades[$ciudad]['vehiculos'][$vehiculo_sel]) ? $vehiculo_sel : 'particular';
            
            $pyp = new PicoYPlaca($ciudad, $fecha, $ciudades, $festivos, $tipo_vehiculo_local);
            $info_pyp = $pyp->getInfo();

            $monthNames = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            
            // Calculamos permitidas
            $permitidas = array_values(array_diff([0,1,2,3,4,5,6,7,8,9], $info_pyp['restricciones']));

            $dateData = [
                'dayNameEs' => $info_pyp['dia_nombre'], // Usamos el de la clase nueva
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
        } catch (Exception $e) {
            http_response_code(404);
        }
    } else {
        http_response_code(404);
    }
}

// ==========================================
// 3. GENERAR META TAGS (SEO DINÁMICO)
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Pico y PL">
    <meta name="language" content="es-CO">
    <meta name="author" content="Pico y Placa Colombia">
    <meta name="theme-color" content="#667eea">
    <meta name="robots" content="index, follow">
    
    <link rel="manifest" href="/manifest.json">
    <link rel="sitemap" type="application/xml" href="/sitemap.xml.php">
    
    <title><?php echo htmlspecialchars($title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($keywords); ?>">
    
    <link rel="canonical" href="<?php echo $canonical; ?>">
    
    <link rel="icon" type="image/png" sizes="192x192" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 192 192'><rect fill='%23667eea' width='192' height='192'/><text x='50%' y='50%' font-size='120' font-weight='bold' text-anchor='middle' dy='.3em' fill='white' font-family='Arial'>🚗</text></svg>">
    
    <meta property="og:title" content="<?php echo htmlspecialchars($title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($description); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $canonical; ?>">
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Pico y Placa Colombia - <?php echo $label_vehiculo_seo; ?>",
        "description": "Consulta en tiempo real el pico y placa para <?php echo $label_vehiculo_seo; ?>",
        "url": "<?php echo $canonical; ?>",
        "applicationCategory": "UtilityApplication",
        "offers": {"@type": "Offer", "price": "0"}
    }
    </script>
    
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-2L2EV10ZWW"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-2L2EV10ZWW');
    </script>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 10px; color: #333; transition: background 0.3s; }
        body.pico-activo { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); }
        body.sin-pico { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); }
        .container { max-width: 1200px; margin: 0 auto; }
        
        header { text-align: center; color: white; margin-bottom: 20px; padding: 15px 10px; }
        h1 { font-size: clamp(1.5rem, 8vw, 3rem); margin-bottom: 8px; font-weight: 800; }
        .subtitle { font-size: clamp(0.85rem, 3vw, 1.1rem); opacity: 0.95; }
        
        /* ESTILOS NUEVOS PARA TABS DE VEHÍCULOS */
        .vehicle-tabs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 15px 0 25px 0;
            flex-wrap: wrap;
        }
        .v-tab {
            text-decoration: none;
            color: rgba(255,255,255,0.8);
            border: 2px solid rgba(255,255,255,0.3);
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .v-tab:hover { background: rgba(255,255,255,0.1); color: white; }
        .v-tab.active {
            background: white;
            color: #667eea;
            border-color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        /* FIN ESTILOS NUEVOS */

        .install-btn { position: absolute; top: 10px; right: 10px; background: white; color: #667eea; border: none; padding: 8px 16px; border-radius: 20px; font-weight: 600; cursor: pointer; font-size: 0.85rem; display: none; }
        .install-btn.show { display: block; }
        
        .date-search-section, .search-box, .restrictions-today { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); margin-bottom: 20px; }
        @media (min-width: 600px) { .date-search-section, .search-box, .restrictions-today { padding: 30px; } }
        
        .today-info { background: white; padding: 15px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 12px; }
        .info-card { padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; text-align: center; }
        .info-card h3 { font-size: 0.75rem; text-transform: uppercase; margin-bottom: 8px; }
        .info-card p { font-size: clamp(1rem, 4vw, 1.5rem); font-weight: 800; }
        
        .main-content { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        @media (max-width: 900px) { .main-content { grid-template-columns: 1fr; } }
        
        .cities-grid { display: flex; gap: 8px; overflow-x: auto; flex-wrap: nowrap; padding: 10px 0; }
        .city-btn { padding: 10px 15px; border: 2px solid #e0e0e0; background: white; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; min-height: 44px; white-space: nowrap; }
        .city-btn.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-color: #667eea; }
        
        input[type="text"], input[type="date"], select { padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem; min-height: 44px; }
        .btn-search { padding: 12px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; min-height: 44px; }
        
        .result-box { margin-top: 20px; padding: 20px; border-radius: 12px; display: none; }
        .result-box.show { display: block; }
        .result-success { background: #d4edda; border: 2px solid #28a745; color: #155724; }
        .result-restricted { background: #f8d7da; border: 2px solid #dc3545; color: #721c24; }
        
        .plates-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .plate-badge { background: #84fab0; color: #333; padding: 8px 14px; border-radius: 20px; font-weight: 700; }
        .plate-badge.restricted { background: #f093fb; color: white; }
        
        .info-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        
        /* COUNTDOWN ORIGINAL CONSERVADO */
        #countdownContainer { 
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(245,245,245,0.95) 100%);
            border: 3px solid #667eea;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.2);
            border-radius: 20px;
            padding: 30px 20px;
            margin: 20px 0;
            display: block !important;
        }
        
        #countdownTitle {
            color: #667eea;
            font-size: 1.3rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .countdown-display {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin: 0;
        }
        
        .countdown-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px 25px;
            min-width: 90px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            animation: countdown-bounce 0.6s ease-in-out infinite;
        }
        
        .countdown-item:nth-child(1) { animation-delay: 0s; }
        .countdown-item:nth-child(3) { animation-delay: 0.1s; }
        .countdown-item:nth-child(5) { animation-delay: 0.2s; }
        
        .countdown-item div:first-child {
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 8px;
        }
        
        .countdown-item small {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            opacity: 0.9;
        }
        
        .countdown-separator {
            font-size: 2rem;
            font-weight: 800;
            color: #667eea;
            margin: 0 5px;
        }
        
        @keyframes countdown-bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }
        
        /* ESTADOS DEL COUNTDOWN */
        body.pico-activo #countdownContainer { border-color: #ff6b6b; box-shadow: 0 10px 40px rgba(255, 107, 107, 0.2); }
        body.pico-activo #countdownTitle { color: #ff6b6b; }
        body.pico-activo .countdown-item { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3); }
        body.pico-activo .countdown-separator { color: #ff6b6b; }
        
        body.sin-pico #countdownContainer { border-color: #27ae60; box-shadow: 0 10px 40px rgba(39, 174, 96, 0.2); }
        body.sin-pico #countdownTitle { color: #27ae60; }
        body.sin-pico .countdown-item { background: linear-gradient(135deg, #27ae60 0%, #229954 100%); box-shadow: 0 8px 25px rgba(39, 174, 96, 0.3); }
        body.sin-pico .countdown-separator { color: #27ae60; }
        
        .no-restriction { color: #28a745; font-weight: 700; }
        .has-restriction { color: #dc3545; font-weight: 700; }
        
        footer { text-align: center; color: white; padding: 15px; opacity: 0.9; }
        
        @media (max-width: 480px) {
            .date-search-section, .search-box, .restrictions-today { padding: 15px; }
            input, select { font-size: 16px; }
        }
                          
        /* Contenedor flotante PWA */
        #pwaBtnContainer {
            position: fixed; bottom: 70px; right: 15px; display: flex; flex-direction: column; gap: 10px; z-index: 9999; animation: slideUpPwa 0.5s ease-out;
        }
        #pwaBtnContainer.show { display: flex !important; }
        @keyframes slideUpPwa { from { opacity: 0; transform: translateY(100px); } to { opacity: 1; transform: translateY(0); } }
        
        .floating-install-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 14px 18px; border-radius: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4); transition: all 0.3s; font-size: 1rem; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 8px; white-space: nowrap; min-width: 150px; justify-content: center;
        }
        .floating-install-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(102, 126, 234, 0.6); }
        .floating-install-btn-close {
            background: #ff6b6b; color: white; border: none; width: 50px; height: 50px; border-radius: 50%; font-weight: 700; cursor: pointer; box-shadow: 0 6px 16px rgba(255, 107, 107, 0.3); transition: all 0.3s; font-size: 1.3rem; font-family: 'Poppins', sans-serif; display: flex; align-items: center; justify-content: center; padding: 0;
        }
        .floating-install-btn-close:hover { transform: scale(1.1); }
        
        @media (max-width: 768px) {
            #pwaBtnContainer { bottom: 80px; right: 10px; left: 10px; flex-direction: row; gap: 8px; justify-content: flex-end; }
            .floating-install-btn-primary { flex: 1; min-width: auto; padding: 12px 14px; font-size: 0.95rem; }
        }
        @media (max-width: 480px) {
            #pwaBtnContainer { bottom: 70px; right: 8px; left: 8px; }
            .floating-install-btn-close { width: 40px; height: 40px; font-size: 1rem; }
        }
        
        #iosModalPwa {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; align-items: center; justify-content: center; padding: 20px;
        }
        #iosModalPwa.show { display: flex; }
        .ios-modal-content {
            background: white; padding: 25px; border-radius: 20px; max-width: 400px; text-align: center; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); animation: scaleIn 0.3s ease-out;
        }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
        .ios-modal-content h2 { color: #667eea; margin-bottom: 15px; }
        .ios-steps { text-align: left; background: #f5f5f5; padding: 15px; border-radius: 10px; margin: 15px 0; font-size: 0.9rem; }
        .ios-steps ol { margin: 10px 0; padding-left: 20px; }
        .ios-modal-close { background: #667eea; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; }
                                
        .subtitle { font-size: 1.1rem; opacity: 0.95; line-height: 1.5; transition: all 0.3s ease; }
        #cityNameSubtitle { transition: all 0.4s ease; }
        @media (max-width: 768px) { .subtitle { font-size: 0.95rem; } }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div id="pwaBtnContainer" style="display: none;">
                <button id="installPwaBtn" class="floating-install-btn-primary">⬇️ <span id="installBtnText">Instalar App</span></button>
                <button id="closePwaBtn" class="floating-install-btn-close">✕</button>
            </div>
            
            <h1 id="pageTitle">
            <?php if ($isDatePage): ?>
                🚗 Pico y placa el <?php echo ucfirst($dateData['dayNameEs']) . ' ' . $dateData['dayNum'] . ' de ' . ucfirst($dateData['monthName']); ?> en <?php echo htmlspecialchars($dateData['cityName']); ?>
            <?php else: ?>
                <?php echo ($vehiculo_sel == 'particular') ? "🚗 Pico y placa hoy en Bogotá" : "🚗 Pico y placa $label_vehiculo_seo hoy en Bogotá"; ?>
            <?php endif; ?>
            </h1>
            
            <?php if (!$isDatePage): ?>
            <div class="vehicle-tabs">
                <?php 
                // Usamos Bogotá como referencia de vehículos disponibles (o podríamos mergear todas)
                $lista_vehiculos = $ciudades['bogota']['vehiculos'];
                foreach ($lista_vehiculos as $key => $vdata): 
                ?>
                <a href="?city=<?php echo $ciudad_sel_url; ?>&vehicle=<?php echo $key; ?>" 
                   class="v-tab <?php echo ($vehiculo_sel == $key) ? 'active' : ''; ?>">
                   <?php echo $vdata['label']; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!$isDatePage): ?>
            <p class="subtitle" id="dynamicSubtitle">
                Que no te pille el Poli 🚓 ni las Cámaras 📸 Mantente informado y 🪰 sobre las restricciones vehiculares en 
                <span id="cityNameSubtitle" style="color: #000000; font-weight: 900; background: rgba(255, 255, 255, 0.9); padding: 4px 10px; border-radius: 6px; text-shadow: none;">Bogotá</span>
                y evita perder hasta <span style="color: #000000; font-weight: 900; background: rgba(255, 255, 255, 0.9); padding: 2px 6px; border-radius: 4px; text-shadow: none;">$1.4 millones</span> 💸. Luego no 😩
            </p>
            <?php endif; ?>
            
        </header>
        
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
        
        <footer>
            <p><strong>Pico y PL</strong> - Colombia 2025 | Versión 2.5</p>
        </footer>
    </div>
    
    <script>
        // RECUPERAMOS EL JSON GENERADO POR PHP CON LA NUEVA LÓGICA DE VEHÍCULOS
        let selectedCity = '<?php echo $ciudad_sel_url; ?>';
        const datosHoy = JSON.parse('<?php echo $datos_hoy_json; ?>');
        const festivosColombia = <?php echo json_encode($festivos); ?>;
        const currentVehicle = '<?php echo $vehiculo_sel; ?>'; // Variable JS para saber el contexto
        
        let countdownInterval;
        
        function updateTodayInfo() {
            const data = datosHoy[selectedCity];
            
            if (!data) { console.error('❌ Ciudad no encontrada:', selectedCity); return; }
            
            console.log('\n📍 Actualizando:', selectedCity);
            
            const today = new Date();
            const options = {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'};
            const dateStr = today.toLocaleDateString('es-CO', options);
            
            document.getElementById('today-date').textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
            document.getElementById('city-today').textContent = data.nombre;
            document.getElementById('city-schedule').textContent = data.horario;
            
            // TITULO Y SUBTITULO
            const isDatePage = window.location.pathname.includes('/pico-y-placa/');
            if (!isDatePage) {
                // Si es particular no ponemos label, si es otro sí
                let vLabel = data.vehiculo_label === 'Particulares' ? '' : data.vehiculo_label;
                document.getElementById('pageTitle').textContent = '🚗 Pico y Placa ' + vLabel + ' hoy en ' + data.nombre;
            }
            
            const cityNameSubtitle = document.getElementById('cityNameSubtitle');
            if (cityNameSubtitle) cityNameSubtitle.textContent = data.nombre;
            
            const diaSemana = today.getDay();
            const esFinDeSemana = diaSemana === 0 || diaSemana === 6;
            
            const restricciones = data.restricciones;
            const permitidas = data.permitidas;
            const horarioInicio = parseInt(data.horarioInicio, 10);
            const horarioFin = parseInt(data.horarioFin, 10);
            
            function esFestivo(fecha) {
                const fechaStr = fecha.toISOString().split('T')[0];
                return festivosColombia.includes(fechaStr);
            }
            const hoyFestivo = esFestivo(today);
            
            // CASO BARRANQUILLA (Sin restricción particular) - Mantener lógica original
            if (selectedCity === 'barranquilla' && currentVehicle === 'particular') {
                document.getElementById('today-status').textContent = '✅ SIN RESTRICCIONES';
                document.getElementById('restriction-label').innerHTML = '🎉 Sin pico y placa:';
                document.getElementById('plates-restricted-today').innerHTML = '<p class="no-restriction" style="font-size: 1.1rem; font-weight: 800; background: #c8e6c9; padding: 15px; border-radius: 8px;">Esta ciudad NO tiene restricciones de circulación para vehículos particulares</p>';
                document.getElementById('plates-allowed-today').innerHTML = '<p class="no-restriction">✅ Todos los vehículos (0-9) pueden circular</p>';
                document.body.className = 'sin-pico';
                if (document.getElementById('countdownContainer')) document.getElementById('countdownContainer').style.display = 'none';
                return;
            }
            
            // ACTUALIZAR COUNTDOWN
            updateCountdown(horarioInicio, horarioFin);
            
            // RENDERIZAR RESTRICCIONES
            if (hoyFestivo) {
                document.getElementById('today-status').textContent = '🎉 Festivo';
                document.getElementById('restriction-label').innerHTML = '✅ Sin restricción';
                document.getElementById('plates-restricted-today').innerHTML = '<p class="no-restriction">🎉 Día Festivo - Sin restricción</p>';
                document.getElementById('plates-allowed-today').innerHTML = '<p class="no-restriction">✅ Todos los vehículos (0-9)</p>';
                document.body.className = 'sin-pico';
            } else if (esFinDeSemana && data.vehiculo_label === 'Particulares') { // Solo particulares libres finde por defecto
                document.getElementById('today-status').textContent = 'Libre - Fin de Semana';
                document.getElementById('restriction-label').innerHTML = '✅ Sin restricción';
                document.getElementById('plates-restricted-today').innerHTML = '<p class="no-restriction">✅ Fin de Semana - Sin restricción</p>';
                document.getElementById('plates-allowed-today').innerHTML = '<p class="no-restriction">✅ Todos los vehículos (0-9)</p>';
                document.body.className = 'sin-pico';
            } else {
                if (restricciones && restricciones.length > 0) {
                    document.getElementById('today-status').textContent = restricciones.join(', ');
                    document.getElementById('restriction-label').innerHTML = '🚫 Con restricción:';
                    document.getElementById('plates-restricted-today').innerHTML = restricciones.map(p => '<span class="plate-badge restricted">' + p + '</span>').join('');
                    document.getElementById('plates-allowed-today').innerHTML = permitidas.map(p => '<span class="plate-badge">' + p + '</span>').join('');
                } else {
                    document.getElementById('today-status').textContent = 'Libre';
                    document.getElementById('restriction-label').innerHTML = '✅ Hoy no hay restricción';
                    document.getElementById('plates-restricted-today').innerHTML = '<p class="no-restriction">✅ Hoy no hay restricción</p>';
                    document.getElementById('plates-allowed-today').innerHTML = permitidas.map(p => '<span class="plate-badge">' + p + '</span>').join('');
                    document.body.className = 'sin-pico';
                }
            }
        }
        
        function updateCountdown(inicio, fin) {
            clearInterval(countdownInterval);
            inicio = parseInt(inicio, 10);
            fin = parseInt(fin, 10);
            
            function esFestivo(fecha) { const fechaStr = fecha.toISOString().split('T')[0]; return festivosColombia.includes(fechaStr); }
            function esFinDeSemana(fecha) { const dia = fecha.getDay(); return dia === 0 || dia === 6; }
            function siguienteDiaHabil(fechaInicio) {
                let fecha = new Date(fechaInicio);
                fecha.setDate(fecha.getDate() + 1);
                fecha.setHours(inicio, 0, 0, 0);
                let intentos = 0;
                while ((esFinDeSemana(fecha) || esFestivo(fecha)) && intentos < 14) {
                    fecha.setDate(fecha.getDate() + 1);
                    intentos++;
                }
                return fecha;
            }
            
            function calcularTiempo() {
                const ahora = new Date();
                const horaActual = ahora.getHours();
                const hoyEsFinDeSemana = esFinDeSemana(ahora);
                const hoyEsFestivo = esFestivo(ahora);
                
                let proximoTiempo = 0, titulo = '', mensaje = '';
                
                // Si es finde y el vehículo es Particular (asumimos libre), o festivo
                // Nota: Tu nueva lógica de Taxis puede que SÍ tenga pico y placa finde.
                // El JS original asume que finde es libre. Para ser exactos, deberíamos leer de datosHoy.restricciones
                const tieneRestriccionHoy = datosHoy[selectedCity].restricciones.length > 0;
                
                if (!tieneRestriccionHoy) {
                    const proximoDiaHabil = siguienteDiaHabil(ahora);
                    titulo = '🎉 SIN PICO Y PLACA HOY';
                    mensaje = '📅 Próxima restricción el ' + proximoDiaHabil.toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long' }) + ':';
                    proximoTiempo = (proximoDiaHabil.getTime() - ahora.getTime()) / 1000;
                    document.body.className = 'sin-pico';
                } else if (horaActual >= inicio && horaActual < fin) {
                    titulo = '🚨 PICO Y PLACA ACTIVO';
                    mensaje = '⏱️ Falta para terminar:';
                    const finHoy = new Date(ahora);
                    finHoy.setHours(fin, 0, 0, 0);
                    proximoTiempo = Math.max(0, (finHoy.getTime() - ahora.getTime()) / 1000);
                    document.body.className = 'pico-activo';
                } else if (horaActual < inicio) {
                    titulo = '✅ PICO Y PLACA HOY';
                    mensaje = '⏳ Falta para iniciar:';
                    const inicioHoy = new Date(ahora);
                    inicioHoy.setHours(inicio, 0, 0, 0);
                    proximoTiempo = (inicioHoy.getTime() - ahora.getTime()) / 1000;
                    document.body.className = 'sin-pico';
                } else {
                    const proximoDiaHabil = siguienteDiaHabil(ahora);
                    titulo = '✅ PRÓXIMO PICO Y PLACA';
                    mensaje = '📅 Inicia el ' + proximoDiaHabil.toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long' }) + ':';
                    proximoTiempo = (proximoDiaHabil.getTime() - ahora.getTime()) / 1000;
                    document.body.className = 'sin-pico';
                }
                
                const horas = Math.floor(proximoTiempo / 3600);
                const minutos = Math.floor((proximoTiempo % 3600) / 60);
                const segundos = Math.floor(proximoTiempo % 60);
                
                const titleEl = document.getElementById('countdownTitle');
                if (titleEl) titleEl.innerHTML = titulo + '<br><small style="font-size: 0.8rem; font-weight: 500;">' + mensaje + '</small>';
                
                document.getElementById('countdownHours').textContent = String(horas).padStart(2, '0');
                document.getElementById('countdownMinutes').textContent = String(minutos).padStart(2, '0');
                document.getElementById('countdownSeconds').textContent = String(segundos).padStart(2, '0');
                
                const container = document.getElementById('countdownContainer');
                if (container && !container.classList.contains('show')) container.classList.add('show');
            }
            calcularTiempo();
            countdownInterval = setInterval(calcularTiempo, 1000);
        }
        
        function selectCity(ciudad) {
            console.log('\n🏙️ Cambiando a ciudad:', ciudad);
            selectedCity = ciudad;
            document.querySelectorAll('.city-btn').forEach(b => b.classList.remove('active'));
            const btnCity = document.getElementById('btn-' + ciudad);
            if (btnCity) btnCity.classList.add('active');
            
            const data = datosHoy[ciudad];
            if (!data) return;
            
            const isDatePage = window.location.pathname.includes('/pico-y-placa/');
            if (!isDatePage) {
                let vLabel = data.vehiculo_label === 'Particulares' ? '' : data.vehiculo_label;
                const newTitle = `Pico y placa ${vLabel} hoy en ${data.nombre} 🚗 | Consulta en Tiempo Real`;
                document.title = newTitle;
                
                // Actualizar URL sin recargar para mantener el parámetro vehicle si existe
                const url = new URL(window.location);
                url.searchParams.set('city', ciudad);
                // No cambiamos vehicle aquí, el usuario debe usar los tabs
                window.history.pushState({}, '', url);
            }
            updateTodayInfo();
            document.getElementById('result-box').innerHTML = '';
            document.getElementById('plate-input').value = '';
        }
        
        function searchPlate() {
            const plate = document.getElementById('plate-input').value;
            if (!plate || isNaN(plate)) return alert('Solo 0-9');
            
            const data = datosHoy[selectedCity];
            const tiene_restriccion = data.restricciones.includes(parseInt(plate));
            
            const box = document.getElementById('result-box');
            if (tiene_restriccion) {
                box.className = 'result-box show result-restricted';
                box.innerHTML = '<h3>⚠️ ¡RESTRICCIÓN!</h3><p>Tu placa ' + plate + ' NO puede circular hoy en ' + data.nombre + '</p>';
            } else {
                box.className = 'result-box show result-success';
                box.innerHTML = '<h3>✅ Puedes circular</h3><p>Tu placa ' + plate + ' puede circular hoy en ' + data.nombre + '</p>';
            }
        }
        
        function searchByDate(e) {
            e.preventDefault();
            const date = document.getElementById('dateInput').value;
            const city = document.getElementById('citySelect').value;
            if (date) {
                const [year, month, day] = date.split('-');
                // Mantenemos el vehículo seleccionado en la búsqueda de fecha
                window.location.href = `/pico-y-placa/${year}-${month}-${day}-${city}?vehicle=${currentVehicle}`;
            }
        }
        
        function backToHome() { window.location.href = '/'; }
        
        function initSliders() {
            const citiesSlider = document.getElementById('citiesSlider');
            const citiesPrevBtn = document.getElementById('citiesPrev');
            const citiesNextBtn = document.getElementById('citiesNext');
            if (citiesSlider && citiesPrevBtn && citiesNextBtn) {
                citiesPrevBtn.onclick = () => { citiesSlider.scrollBy({ left: -150, behavior: 'smooth' }); };
                citiesNextBtn.onclick = () => { citiesSlider.scrollBy({ left: 150, behavior: 'smooth' }); };
                updateScrollButtons();
            }
        }
        
        function scrollCities(direction) {
            const slider = document.getElementById('citiesSlider');
            if (!slider) return;
            if (direction === 'left') slider.scrollBy({ left: -150, behavior: 'smooth' });
            else slider.scrollBy({ left: 150, behavior: 'smooth' });
        }
        
        function updateScrollButtons() {
            const citiesSlider = document.getElementById('citiesSlider');
            const citiesPrevBtn = document.getElementById('citiesPrev');
            const citiesNextBtn = document.getElementById('citiesNext');
            if (!citiesSlider || !citiesPrevBtn || !citiesNextBtn) return;
            citiesPrevBtn.disabled = citiesSlider.scrollLeft === 0;
            citiesNextBtn.disabled = citiesSlider.scrollLeft >= (citiesSlider.scrollWidth - citiesSlider.clientWidth);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar con la ciudad de la URL o Bogotá
            selectCity(selectedCity);
            initSliders();
            
            const plateInput = document.getElementById('plate-input');
            if (plateInput) {
                plateInput.addEventListener('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); });
                plateInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') searchPlate(); });
            }
        });
        
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js').catch(e => console.log('SW:', e));
        }
        
        // LOGICA PWA (Mantenida intacta)
        let deferredPrompt;
        function getOS() {
            const ua = navigator.userAgent;
            if (ua.indexOf('iPhone') > -1 || ua.indexOf('iPad') > -1) return 'ios';
            if (ua.indexOf('Android') > -1) return 'android';
            return 'desktop';
        }
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('✅ PWA lista para instalar');
            e.preventDefault();
            deferredPrompt = e;
            showPwaButton();
        });
        function showPwaButton() {
            const container = document.getElementById('pwaBtnContainer');
            if (container) { container.classList.add('show'); container.style.display = 'flex'; }
        }
        function hidePwaButton() {
            const container = document.getElementById('pwaBtnContainer');
            if (container) { container.classList.remove('show'); setTimeout(() => { container.style.display = 'none'; }, 300); }
        }
        document.getElementById('installPwaBtn').addEventListener('click', async () => {
            const os = getOS();
            if (os === 'ios') { showIOSInstructions(); } 
            else if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                deferredPrompt = null;
                hidePwaButton();
            }
        });
        document.getElementById('closePwaBtn').addEventListener('click', hidePwaButton);
        function showIOSInstructions() {
            let modal = document.getElementById('iosModalPwa');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'iosModalPwa';
                modal.innerHTML = `<div class="ios-modal-content"><h2>📱 Instalar en iOS</h2><p>Sigue estos pasos:</p><div class="ios-steps"><ol><li>Toca <strong>Compartir</strong> (↗️)</li><li>Toca <strong>"Añadir a pantalla de inicio"</strong></li><li>¡Listo! La app aparecerá en tu pantalla de inicio</li></ol></div><button class="ios-modal-close" onclick="this.parentElement.parentElement.classList.remove('show')">Entendido</button></div>`;
                document.body.appendChild(modal);
            }
            modal.classList.add('show');
        }
        window.addEventListener('appinstalled', () => { hidePwaButton(); });
    </script>
</body>
</html>