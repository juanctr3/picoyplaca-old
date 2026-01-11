<?php
/**
 * admin/index.php
 * Dashboard principal. Muestra resumen del estado del sistema.
 */

require_once 'auth.php';
require_once 'data_manager.php';

// 1. Cargar datos para el resumen
$ciudades = DataManager::getCiudades();
$festivos = DataManager::getFestivos();
$alerta   = DataManager::getAlerta();

// 2. Calcular estadísticas rápidas
$total_ciudades = count($ciudades);
$total_vehiculos = 0;
foreach ($ciudades as $c) {
    if (isset($c['vehiculos'])) {
        $total_vehiculos += count($c['vehiculos']);
    }
}

// 3. Calcular Próximo festivo (CORREGIDO PARA SOPORTAR NUEVO FORMATO)
$proximo_festivo = 'Ninguno pronto';
$dias_para_festivo = '';
$hoy = date('Y-m-d');

// Ordenamos el array usando una función anónima para manejar objetos o strings
usort($festivos, function($a, $b) {
    $fa = is_array($a) ? $a['fecha'] : $a;
    $fb = is_array($b) ? $b['fecha'] : $b;
    return strcmp($fa, $fb);
});

foreach ($festivos as $f) {
    // Detectar si es el formato nuevo (Array) o antiguo (String)
    $fecha_f = is_array($f) ? $f['fecha'] : $f;
    $nombre_f = is_array($f) ? ($f['nombre'] ?? 'Festivo') : 'Festivo';

    if ($fecha_f >= $hoy) {
        $timestamp = strtotime($fecha_f);
        
        // Formato legible: 15 de Ene
        $meses = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
        $mes_txt = $meses[date('m', $timestamp)];
        $proximo_festivo = date('d', $timestamp) . ' de ' . $mes_txt;
        
        // Agregar el nombre de la festividad si cabe
        if(strlen($nombre_f) < 20) {
            $proximo_festivo .= " ($nombre_f)";
        }

        // Calcular días restantes
        $diff = (new DateTime($hoy))->diff(new DateTime($fecha_f))->days;
        if ($diff == 0) $dias_para_festivo = '(¡Es Hoy!)';
        elseif ($diff == 1) $dias_para_festivo = '(Mañana)';
        else $dias_para_festivo = "(en $diff días)";
        
        break; // Encontramos el primero, paramos
    }
}

include 'includes/header.php';
?>

<div class="admin-content-wrapper">
    
    <div class="welcome-banner" style="margin-bottom:30px;">
        <h2>👋 ¡Hola, Administrador!</h2>
        <p>Bienvenido al panel de control de <strong>Pico y Placa Colombia</strong>.</p>
    </div>

    <div class="dashboard-grid">
        
        <div class="dash-card blue">
            <div class="dash-icon">🏙️</div>
            <div class="dash-info">
                <h3><?= $total_ciudades ?></h3>
                <p>Ciudades Activas</p>
            </div>
            <a href="ciudades.php" class="dash-link">Gestionar →</a>
        </div>

        <div class="dash-card purple">
            <div class="dash-icon">🚗</div>
            <div class="dash-info">
                <h3><?= $total_vehiculos ?></h3>
                <p>Reglas de Vehículos</p>
            </div>
            <span class="dash-subtext">Configurados globalmente</span>
        </div>

        <div class="dash-card green">
            <div class="dash-icon">📅</div>
            <div class="dash-info">
                <h3 style="font-size:1.4rem;"><?= $proximo_festivo ?></h3>
                <p>Próximo Festivo</p>
                <small><?= $dias_para_festivo ?></small>
            </div>
            <a href="festivos.php" class="dash-link">Ver Calendario →</a>
        </div>

        <div class="dash-card <?= ($alerta['activa'] ?? false) ? 'red' : 'gray' ?>">
            <div class="dash-icon">📢</div>
            <div class="dash-info">
                <h3><?= ($alerta['activa'] ?? false) ? 'ACTIVA' : 'Inactiva' ?></h3>
                <p>Alerta Global</p>
            </div>
            <a href="alertas.php" class="dash-link">Configurar →</a>
        </div>

    </div>

    <div class="row mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header-simple">
                    <h3>🚀 Accesos Rápidos</h3>
                </div>
                <div class="list-group list-group-flush" style="padding:10px;">
                    <a href="ciudad_editar.php" class="btn btn-secondary btn-block mb-2 text-left">
                        ➕ Agregar Nueva Ciudad
                    </a>
                    <a href="alertas.php" class="btn btn-secondary btn-block mb-2 text-left">
                        📢 Crear aviso de emergencia
                    </a>
                    <a href="../" target="_blank" class="btn btn-primary btn-block text-left">
                        🌐 Ver Sitio Web (Frontend)
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card bg-light" style="background:#f8fafc;">
                <div class="card-body" style="padding:20px;">
                    <h4>ℹ️ Estado del Sistema</h4>
                    <ul class="list-unstyled mt-3" style="line-height:2;">
                        <li>✅ <strong>Sistema:</strong> JSON (Sin base de datos)</li>
                        <li>✅ <strong>Zona Horaria:</strong> <?= date_default_timezone_get() ?></li>
                        <li>🕒 <strong>Hora del Servidor:</strong> <?= date('H:i:s') ?></li>
                        <li>📅 <strong>Fecha del Servidor:</strong> <?= date('Y-m-d') ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
/* Estilos específicos del Dashboard */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.dash-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    position: relative;
    overflow: hidden;
    transition: transform 0.2s;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 160px;
}
.dash-card:hover { transform: translateY(-5px); }

.dash-icon { font-size: 2.5rem; margin-bottom: 10px; opacity: 0.8; }
.dash-info h3 { font-size: 1.8rem; font-weight: 800; margin: 0; color: #2d3748; }
.dash-info p { margin: 0; color: #718096; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
.dash-subtext { font-size: 0.8rem; color: #a0aec0; margin-top: auto; }
.dash-link { margin-top: 15px; color: #667eea; font-weight: 700; text-decoration: none; font-size: 0.9rem; }
.dash-link:hover { text-decoration: underline; }

/* Variantes de color */
.dash-card.blue { border-left: 5px solid #3498db; }
.dash-card.purple { border-left: 5px solid #9b59b6; }
.dash-card.green { border-left: 5px solid #2ecc71; }
.dash-card.red { border-left: 5px solid #e74c3c; background: #fff5f5; }
.dash-card.gray { border-left: 5px solid #bdc3c7; opacity: 0.8; }
</style>

<?php include 'includes/footer.php'; ?>
