<?php
/**
 * PÁGINA: ¿A QUÉ HORA SE ACABA EL PICO Y PLACA?
 * URL: /a-que-hora-se-acaba-el-pico-y-placa
 * 
 * Muestra countdown hasta que TERMINA el pico y placa
 * con opción de seleccionar ciudad
 * 
 * ACTUALIZADO: Noviembre 2025 - Información corregida para todas las ciudades
 */

require_once 'clases/PicoYPlaca.php';
$config = require_once 'config-ciudades.php';
$ciudades = $config['ciudades'];

$ciudad_seleccionada = $_GET['city'] ?? 'bogota';

// Validar ciudad
if (!isset($ciudades[$ciudad_seleccionada])) {
    $ciudad_seleccionada = 'bogota';
}

$ciudad_info = $ciudades[$ciudad_seleccionada];
$ahora = new DateTime();
$pyp = new PicoYPlaca($ciudad_seleccionada, $ahora, $ciudades, $config['festivos']);

// Meta tags para SEO
$titulo = "¿A qué hora se acaba el pico y placa en " . $ciudad_info['nombre'] . "? | HOY";
$descripcion = "Descubre a qué hora termina el pico y placa en " . $ciudad_info['nombre'] . " hoy. Horario: " . $ciudad_info['horario'] . ". Cuenta regresiva en tiempo real.";
$palabras_clave = "a que hora se acaba pico y placa, fin pico y placa " . strtolower($ciudad_info['nombre']) . ", cuando termina pico y placa, restriccion vehicular";

// URL canónica
$url_canonica = "https://picoyplacabogota.com.co/a-que-hora-se-acaba-el-pico-y-placa?city=" . $ciudad_seleccionada;

$horaInicio = $ciudad_info['horarioInicio'];
$horaFin = $ciudad_info['horarioFin'];

// JSON para JavaScript
$datos_ciudades_json = json_encode(array_map(function($codigo, $info) {
    return [
        'codigo' => $codigo,
        'nombre' => $info['nombre'],
        'horarioInicio' => $info['horarioInicio'],
        'horarioFin' => $info['horarioFin'],
        'horario' => $info['horario']
    ];
}, array_keys($ciudades), $ciudades));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#667eea">
    
    <title><?php echo htmlspecialchars($titulo); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($descripcion); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($palabras_clave); ?>" "A que hora se acaba el pico y placa">
    <link rel="canonical" href="<?php echo $url_canonica; ?>">
    
    <!-- Open Graph para social -->
    <meta property="og:title" content="<?php echo htmlspecialchars($titulo); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($descripcion); ?>">
    <meta property="og:url" content="<?php echo $url_canonica; ?>">
    <meta property="og:type" content="website">
    
    <!-- Schema Markup -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": {
            "@type": "Question",
            "name": "¿A qué hora se acaba el pico y placa en <?php echo $ciudad_info['nombre']; ?>?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "El pico y placa en <?php echo $ciudad_info['nombre']; ?> termina a las <?php echo $horaFin; ?>:00. Horario completo: <?php echo $ciudad_info['horario']; ?>"
            }
        }
    }
    </script>
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-2L2EV10ZWW"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-2L2EV10ZWW');
</script>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            padding: 20px 0;
        }

        header h1 {
            font-size: clamp(1.8rem, 6vw, 2.8rem);
            margin-bottom: 10px;
            font-weight: 800;
        }

        header p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        /* Selector de ciudades */
        .ciudades-selector {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .ciudades-selector h2 {
            color: #333;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }

        .ciudades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
        }

        .ciudad-btn {
            padding: 12px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            color: #333;
            display: block;
        }

        .ciudad-btn:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .ciudad-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        /* Main Content */
        .main-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }

        .ciudad-titulo {
            color: #667eea;
            font-size: 1.8rem;
            margin-bottom: 10px;
            font-weight: 800;
        }

        .ciudad-subtitulo {
            color: #999;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        /* Countdown */
        .countdown-section {
            text-align: center;
            margin: 40px 0;
        }

        .countdown-label {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .countdown-display {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin: 30px 0;
        }

        .countdown-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 15px;
            padding: 25px 30px;
            min-width: 100px;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .countdown-item:nth-child(1) { animation-delay: 0s; }
        .countdown-item:nth-child(3) { animation-delay: 0.2s; }
        .countdown-item:nth-child(5) { animation-delay: 0.4s; }

        .countdown-number {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 8px;
        }

        .countdown-label-small {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
            opacity: 0.9;
        }

        .countdown-separator {
            font-size: 2rem;
            font-weight: 800;
            color: #28a745;
            margin: 0 10px;
        }

        /* Info Cards */
        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 40px 0;
        }

        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #28a745;
        }

        .info-card h3 {
            color: #28a745;
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .info-card p {
            font-size: 1.4rem;
            font-weight: 800;
            color: #333;
        }

        /* Botón CTA */
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 18px 35px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
            position: relative;
            overflow: hidden;
            margin: 20px 0;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.5s;
        }

        .cta-button:hover::before {
            left: 100%;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(40, 167, 69, 0.4);
        }

        .cta-button:active {
            transform: translateY(-1px);
        }

        /* Status */
        .status-banner {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            font-weight: 600;
        }

        .status-banner.activo {
            background: #cfe2ff;
            border-color: #0d6efd;
            color: #084298;
        }

        .status-banner.sin-restriccion {
            background: #f8f9fa;
            border-color: #6c757d;
            color: #495057;
        }

        /* Info adicional */
        .info-adicional {
            background: #f0f8ff;
            border-left: 4px solid #0d6efd;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
            line-height: 1.8;
        }

        .info-adicional h3 {
            color: #0d6efd;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .info-adicional ul {
            margin-left: 20px;
            margin-top: 10px;
        }

        .info-adicional li {
            margin-bottom: 8px;
        }

        .notice-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 25px;
            }

            .countdown-display {
                gap: 10px;
            }

            .countdown-item {
                padding: 20px;
                min-width: 80px;
            }

            .countdown-number {
                font-size: 1.8rem;
            }

            .cta-button {
                width: 100%;
            }

            header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🎉 ¿A QUÉ HORA SE ACABA EL PICO Y PLACA?</h1>
            <p>Cuenta regresiva en tiempo real</p>
        </header>

        <!-- Selector de Ciudades -->
        <div class="ciudades-selector">
            <h2>📍 Selecciona tu ciudad:</h2>
            <div class="ciudades-grid">
                <?php foreach ($ciudades as $codigo => $info): ?>
                    <a href="?city=<?php echo $codigo; ?>" 
                       class="ciudad-btn <?php echo ($codigo === $ciudad_seleccionada) ? 'active' : ''; ?>">
                        <?php echo $info['nombre']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="main-content">
            <h2 class="ciudad-titulo"><?php echo $ciudad_info['nombre']; ?></h2>
            <p class="ciudad-subtitulo">Horario: <?php echo $ciudad_info['horario']; ?></p>

            <!-- Noticia especial para Barranquilla -->
            <?php if ($ciudad_seleccionada === 'barranquilla'): ?>
            <div class="notice-box">
                ✅ SIN RESTRICCIÓN - Barranquilla no tiene pico y placa para vehículos particulares
            </div>
            <?php endif; ?>

            <!-- Noticia especial para Bucaramanga -->
            <?php if ($ciudad_seleccionada === 'bucaramanga'): ?>
            <div class="notice-box">
                ⚠️ NOTA: Los sábados tienen restricción 09:00am-1:00pm con rotación especial
            </div>
            <?php endif; ?>

            <!-- Noticia especial para Santa Marta -->
            <?php if ($ciudad_seleccionada === 'santa_marta'): ?>
            <div class="notice-box">
                🕐 HORARIOS ESPECIALES: 7am-9am | 11:30am-2pm | 5pm-8pm
            </div>
            <?php endif; ?>

            <!-- Info Cards -->
            <div class="info-cards">
                <div class="info-card">
                    <h3>⏱️ Hora de Inicio</h3>
                    <p><?php echo sprintf("%02d:%02d", $horaInicio, 0); ?></p>
                </div>
                <div class="info-card">
                    <h3>🎉 Hora de Fin</h3>
                    <p><?php echo sprintf("%02d:%02d", $horaFin, 0); ?></p>
                </div>
                <div class="info-card">
                    <h3>⏳ Duración</h3>
                    <p><?php echo ($horaFin - $horaInicio); ?> horas</p>
                </div>
            </div>

            <!-- Status -->
            <div id="statusBanner"></div>

            <!-- Countdown -->
            <div class="countdown-section">
                <div class="countdown-label" id="countdownLabel">
                    ⏳ Tiempo para que TERMINE el pico y placa:
                </div>
                <div class="countdown-display">
                    <div class="countdown-item">
                        <div class="countdown-number" id="hours">00</div>
                        <div class="countdown-label-small">Horas</div>
                    </div>
                    <div class="countdown-separator">:</div>
                    <div class="countdown-item">
                        <div class="countdown-number" id="minutes">00</div>
                        <div class="countdown-label-small">Minutos</div>
                    </div>
                    <div class="countdown-separator">:</div>
                    <div class="countdown-item">
                        <div class="countdown-number" id="seconds">00</div>
                        <div class="countdown-label-small">Segundos</div>
                    </div>
                </div>
            </div>

            <!-- Info Adicional -->
            <div class="info-adicional">
                <h3>💡 ¿Qué debes saber?</h3>
                <ul>
                    <li>El pico y placa termina a las <strong><?php echo sprintf("%02d:%02d", $horaFin, 0); ?></strong> en <?php echo $ciudad_info['nombre']; ?></li>
                    <li>A partir de esa hora puedes circular sin restricción</li>
                    <li>Verifica tu placa antes de salir</li>
                    <li>Los festivos NO hay pico y placa</li>
                </ul>
            </div>

            <!-- CTA Button -->
            <center>
                <a href="/?city=<?php echo $ciudad_seleccionada; ?>" class="cta-button">
                    🚗 VER PICO Y PLACA HOY EN <?php echo strtoupper($ciudad_info['nombre']); ?>
                </a>
            </center>
        </div>
    </div>

    <script>
        const ciudadData = {
            codigo: '<?php echo $ciudad_seleccionada; ?>',
            horarioInicio: <?php echo $horaInicio; ?>,
            horarioFin: <?php echo $horaFin; ?>,
            nombre: '<?php echo $ciudad_info['nombre']; ?>'
        };

        function actualizarCountdown() {
            const ahora = new Date();
            const horaActual = ahora.getHours();
            const minActual = ahora.getMinutes();
            const segActual = ahora.getSeconds();

            let proximoTiempo = 0;
            let titulo = '';
            let estado = 'default';

            // Si está activo
            if (horaActual >= ciudadData.horarioInicio && horaActual < ciudadData.horarioFin) {
                titulo = '⏳ Tiempo para que TERMINE el pico y placa:';
                estado = 'activo';
                const finDate = new Date(ahora);
                finDate.setHours(ciudadData.horarioFin, 0, 0);
                proximoTiempo = Math.max(0, (finDate - ahora) / 1000);
            } 
            // Si no ha iniciado hoy
            else if (horaActual < ciudadData.horarioInicio) {
                titulo = '📅 Pico y placa inicia hoy a las ' + String(ciudadData.horarioInicio).padStart(2, '0') + ':00 y termina a las ' + String(ciudadData.horarioFin).padStart(2, '0') + ':00';
                const inicioDate = new Date(ahora);
                inicioDate.setHours(ciudadData.horarioFin, 0, 0);
                proximoTiempo = (inicioDate - ahora) / 1000;
            } 
            // Ya pasó hoy
            else {
                titulo = '✅ Pico y placa TERMINÓ hoy. Mañana inicia a las ' + String(ciudadData.horarioInicio).padStart(2, '0') + ':00';
                const mañana = new Date(ahora);
                mañana.setDate(mañana.getDate() + 1);
                mañana.setHours(ciudadData.horarioFin, 0, 0);
                proximoTiempo = (mañana - ahora) / 1000;
            }

            const horas = Math.floor(proximoTiempo / 3600);
            const minutos = Math.floor((proximoTiempo % 3600) / 60);
            const segundos = Math.floor(proximoTiempo % 60);

            document.getElementById('countdownLabel').textContent = titulo;
            document.getElementById('hours').textContent = String(horas).padStart(2, '0');
            document.getElementById('minutes').textContent = String(minutos).padStart(2, '0');
            document.getElementById('seconds').textContent = String(segundos).padStart(2, '0');

            // Actualizar status
            if (horaActual >= ciudadData.horarioInicio && horaActual < ciudadData.horarioFin) {
                document.getElementById('statusBanner').innerHTML = 
                    '<div class="status-banner activo">🚗 PICO Y PLACA ACTIVO - Faltan ' + String(horas).padStart(2, '0') + ':' + String(minutos).padStart(2, '0') + ' para terminar</div>';
            } else if (ciudadData.horarioFin === 24) {
                // Sin restricción
                document.getElementById('statusBanner').innerHTML = 
                    '<div class="status-banner sin-restriccion">✅ SIN RESTRICCIÓN - Este municipio no tiene pico y placa</div>';
            } else if (horaActual < ciudadData.horarioInicio) {
                document.getElementById('statusBanner').innerHTML = 
                    '<div class="status-banner">⏰ Pico y placa inicia en ' + String(horas).padStart(2, '0') + ' horas</div>';
            } else {
                document.getElementById('statusBanner').innerHTML = 
                    '<div class="status-banner">✅ Pico y placa TERMINÓ - Puedes circular libremente</div>';
            }
        }

        // Actualizar cada segundo
        actualizarCountdown();
        setInterval(actualizarCountdown, 1000);

        // Analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'page_view', {
                'page_title': '¿A qué hora se acaba pico y placa?',
                'page_path': '/a-que-hora-se-acaba-el-pico-y-placa',
                'ciudad': ciudadData.nombre
            });
        }
    </script>
</body>
</html>