<?php
/**
 * ERROR 404 - Página de Error Personalizada
 * Diseño de lujo con redirecciones inteligentes
 * 
 * Ubicación: /var/www/html/404.php
 */

header("HTTP/1.0 404 Not Found");
header('Content-Type: text/html; charset=utf-8');

$pagina_solicitada = isset($_SERVER['REQUEST_URI']) ? htmlspecialchars($_SERVER['REQUEST_URI']) : 'desconocida';

// Obtener ciudad desde URL si es posible
$ciudad_sugerida = 'bogota';
if (strpos($pagina_solicitada, 'medellin') !== false) $ciudad_sugerida = 'medellin';
elseif (strpos($pagina_solicitada, 'cali') !== false) $ciudad_sugerida = 'cali';
elseif (strpos($pagina_solicitada, 'barranquilla') !== false) $ciudad_sugerida = 'barranquilla';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada | Pico y Placa</title>
    <meta name="description" content="La página que buscas no existe, pero tenemos lo que necesitas para conocer el pico y placa de hoy.">
    <meta name="robots" content="noindex, follow">
    <link rel="canonical" href="https://picoyplacabogota.com.co/">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
        }

        /* Animación de fondo */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .background-element {
            position: absolute;
            opacity: 0.1;
            font-size: 200px;
            font-weight: 800;
            animation: float 6s ease-in-out infinite;
            pointer-events: none;
        }

        .bg-404 {
            top: -50px;
            left: -100px;
            animation-delay: 0s;
            color: white;
        }

        .bg-error {
            bottom: -50px;
            right: -100px;
            animation-delay: 2s;
            color: white;
        }

        .container {
            position: relative;
            z-index: 10;
            max-width: 900px;
            background: white;
            border-radius: 30px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-visual {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .error-visual::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -150px;
            right: -150px;
        }

        .error-visual::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            bottom: -100px;
            left: -100px;
        }

        .error-code {
            font-size: 120px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            animation: bounce 0.6s ease-out;
        }

        @keyframes bounce {
            0% {
                opacity: 0;
                transform: scale(0.5);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .error-icon {
            font-size: 80px;
            margin: 20px 0;
            animation: spin 3s linear infinite;
            position: relative;
            z-index: 1;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            font-size: 18px;
            text-align: center;
            position: relative;
            z-index: 1;
            opacity: 0.95;
        }

        .error-content {
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .error-title {
            font-size: 28px;
            font-weight: 800;
            color: #333;
            margin-bottom: 15px;
        }

        .error-subtitle {
            font-size: 14px;
            color: #999;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .suggestions {
            margin-bottom: 30px;
        }

        .suggestion-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background: #f5f5f5;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }

        .suggestion-item:hover {
            background: #e0e0e0;
            transform: translateX(5px);
        }

        .suggestion-icon {
            font-size: 20px;
            margin-right: 12px;
            min-width: 30px;
        }

        .suggestion-text {
            flex: 1;
        }

        .suggestion-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            font-weight: 600;
        }

        .suggestion-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            font-size: 14px;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
            min-width: 150px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            flex: 1;
            min-width: 150px;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .footer-404 {
            font-size: 12px;
            color: #ccc;
            text-align: center;
            margin-top: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }

            .error-visual {
                padding: 30px;
                min-height: 300px;
            }

            .error-code {
                font-size: 80px;
            }

            .error-icon {
                font-size: 60px;
            }

            .error-content {
                padding: 30px;
            }

            .error-title {
                font-size: 22px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                min-width: auto;
            }
        }

        /* Animación de entrada de sugerencias */
        .suggestion-item {
            animation: slideIn 0.5s ease-out;
        }

        .suggestion-item:nth-child(1) { animation-delay: 0.1s; }
        .suggestion-item:nth-child(2) { animation-delay: 0.2s; }
        .suggestion-item:nth-child(3) { animation-delay: 0.3s; }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <!-- Elementos de fondo animados -->
    <div class="background-element bg-404">404</div>
    <div class="background-element bg-error">!</div>

    <!-- Contenedor principal -->
    <div class="container">
        <!-- Lado Visual -->
        <div class="error-visual">
            <div class="error-code">404</div>
            <div class="error-icon">🚗</div>
            <div class="error-message">¡Oops! La página no existe</div>
        </div>

        <!-- Lado de Contenido -->
        <div class="error-content">
            <h1 class="error-title">Página no encontrada</h1>
            
            <p class="error-subtitle">
                Parece que tomaste una ruta equivocada. Pero no te preocupes, 
                tenemos exactamente lo que necesitas para tu pico y placa.
            </p>

            <!-- Sugerencias -->
            <div class="suggestions">
                <a href="/" class="suggestion-item">
                    <div class="suggestion-icon">🏠</div>
                    <div class="suggestion-text">
                        <div class="suggestion-label">Ir a</div>
                        <div class="suggestion-title">Página Principal</div>
                    </div>
                </a>

                <a href="/?city=<?php echo $ciudad_sugerida; ?>" class="suggestion-item">
                    <div class="suggestion-icon">📍</div>
                    <div class="suggestion-text">
                        <div class="suggestion-label">Consultar Hoy en</div>
                        <div class="suggestion-title">
                            <?php 
                            $ciudades = [
                                'bogota' => 'Bogotá',
                                'medellin' => 'Medellín',
                                'cali' => 'Cali',
                                'barranquilla' => 'Barranquilla'
                            ];
                            echo $ciudades[$ciudad_sugerida] ?? 'Tu Ciudad';
                            ?>
                        </div>
                    </div>
                </a>

                <a href="/?action=ciudades" class="suggestion-item">
                    <div class="suggestion-icon">🏙️</div>
                    <div class="suggestion-text">
                        <div class="suggestion-label">Ver todas</div>
                        <div class="suggestion-title">Las Ciudades Disponibles</div>
                    </div>
                </a>
            </div>

            <!-- Botones de Acción -->
            <div class="action-buttons">
                <a href="/" class="btn btn-primary">⬅️ Volver al Inicio</a>
                <a href="javascript:history.back()" class="btn btn-secondary">Atrás</a>
            </div>

            <!-- Footer -->
            <div class="footer-404">
                Código de error: 404 | Recurso no disponible
            </div>
        </div>
    </div>

    <script>
        // Rastrear errores 404 en Google Analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'exception', {
                description: '404 Error - Página no encontrada: <?php echo $pagina_solicitada; ?>',
                fatal: false
            });
        }

        // Intentar buscar si es una búsqueda fallida
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('s')) {
            console.log('Búsqueda no encontrada:', urlParams.get('s'));
        }
    </script>
</body>
</html>