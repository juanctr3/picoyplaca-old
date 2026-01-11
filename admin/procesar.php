<?php
/**
 * admin/procesar.php
 * Controlador principal: Recibe formularios y peticiones para guardar datos.
 * Maneja Ciudades, Vehículos, Festivos, Alertas Múltiples y Excepciones.
 */

require_once 'auth.php';
require_once 'data_manager.php';

// Validar que haya una acción definida
$accion = $_REQUEST['accion'] ?? '';

if (!$accion) {
    header('Location: index.php?error=sin_accion');
    exit;
}

switch ($accion) {
    
    // =========================================================================
    // 1. GESTIÓN DE CIUDADES
    // =========================================================================
    case 'guardar_ciudad':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método incorrecto');

        // Recibir datos básicos
        $id_slug = trim($_POST['id_slug'] ?? '');
        $id_original = trim($_POST['id_original'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        
        if (!$id_slug || !$nombre) {
            die('Error: Faltan datos obligatorios (Slug o Nombre).');
        }

        $ciudades = DataManager::getCiudades();

        // Validar duplicados (solo si es nuevo)
        if (!$id_original && isset($ciudades[$id_slug])) {
            die("Error: Ya existe una ciudad con el identificador '$id_slug'.");
        }

        // Estructura de la ciudad
        $nueva_ciudad = [
            'nombre' => $nombre,
            'vehiculos' => []
        ];

        // Procesar vehículos
        if (isset($_POST['v_keys'])) {
            $keys = $_POST['v_keys'];
            $labels = $_POST['v_labels'];
            $logicas = $_POST['v_logicas'];
            $horarios = $_POST['v_horarios'];
            $configs = $_POST['v_configs']; 

            for ($i = 0; $i < count($keys); $i++) {
                $v_key = trim($keys[$i]);
                if (!$v_key) continue;

                $vehiculo_data = [
                    'label' => $labels[$i],
                    'logica' => $logicas[$i],
                    'horario' => $horarios[$i]
                ];

                $extra_config = json_decode($configs[$i], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($extra_config)) {
                    $vehiculo_data = array_merge($vehiculo_data, $extra_config);
                }

                $nueva_ciudad['vehiculos'][$v_key] = $vehiculo_data;
            }
        }

        // Guardar
        if ($id_original && $id_original !== $id_slug) {
            unset($ciudades[$id_original]);
        }
        $ciudades[$id_slug] = $nueva_ciudad;

        if (DataManager::saveCiudades($ciudades)) {
            header('Location: ciudades.php?msg=Ciudad guardada correctamente');
        } else {
            die('Error crítico al escribir en config.json');
        }
        break;

    case 'eliminar_ciudad':
        $id = $_GET['id'] ?? '';
        if (!$id) die('Falta ID');

        $ciudades = DataManager::getCiudades();
        if (isset($ciudades[$id])) {
            unset($ciudades[$id]);
            DataManager::saveCiudades($ciudades);
        }
        header('Location: ciudades.php?msg=Ciudad eliminada');
        break;

    // =========================================================================
    // 2. GESTIÓN DE FESTIVOS
    // =========================================================================
    case 'agregar_festivo':
        $fecha = $_POST['fecha'] ?? '';
        $nombre = trim($_POST['nombre'] ?? 'Festivo');
        
        if (!$fecha) die('Falta fecha');

        $festivos = DataManager::getFestivos();
        
        // Evitar duplicados
        $existe = false;
        foreach($festivos as $f) {
            $f_fecha = is_array($f) ? $f['fecha'] : $f;
            if ($f_fecha === $fecha) { $existe = true; break; }
        }
        
        if (!$existe) {
            $festivos[] = ['fecha' => $fecha, 'nombre' => $nombre];
            DataManager::saveFestivos($festivos);
        }
        header('Location: festivos.php?msg=Festivo agregado');
        break;

    case 'eliminar_festivo':
        $fecha = $_GET['fecha'] ?? '';
        if (!$fecha) die('Falta fecha');

        $festivos = DataManager::getFestivos();
        $nuevos_festivos = [];
        foreach($festivos as $f) {
            $f_fecha = is_array($f) ? $f['fecha'] : $f;
            if ($f_fecha !== $fecha) $nuevos_festivos[] = $f;
        }
        DataManager::saveFestivos($nuevos_festivos);
        header('Location: festivos.php?msg=Festivo eliminado');
        break;

    // =========================================================================
    // 3. GESTIÓN DE ALERTAS (NUEVO: Múltiples Alertas)
    // =========================================================================
    case 'guardar_alerta':
        $ciudad_id = $_POST['ciudad_id'] ?? 'global';
        $tipo = $_POST['tipo'] ?? 'info';
        $mensaje = trim($_POST['mensaje'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $activa = isset($_POST['activa']) ? true : false;

        if (!$mensaje) die('El mensaje es obligatorio');

        $alertas = DataManager::getAlertas();

        // Crear nueva alerta con ID único
        $nueva_alerta = [
            'id' => uniqid('alert_'), // ID único para poder borrarla después
            'ciudad_id' => $ciudad_id,
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'url' => $url,
            'activa' => $activa,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ];

        // Añadir al inicio del array
        array_unshift($alertas, $nueva_alerta);

        DataManager::saveAlertas($alertas);
        header('Location: alertas.php?msg=Alerta publicada correctamente');
        break;

    case 'eliminar_alerta':
        $id = $_GET['id'] ?? '';
        if (!$id) die('Falta ID de alerta');

        $alertas = DataManager::getAlertas();
        $nuevas_alertas = [];

        foreach ($alertas as $a) {
            // Soporte para alertas antiguas sin ID (si las hubiera) o match por ID
            if (isset($a['id']) && $a['id'] === $id) {
                continue; // Saltar (borrar)
            }
            $nuevas_alertas[] = $a;
        }

        DataManager::saveAlertas($nuevas_alertas);
        header('Location: alertas.php?msg=Alerta eliminada');
        break;

    // =========================================================================
    // 4. GESTIÓN DE EXCEPCIONES (LEVANTAMIENTO DE MEDIDA)
    // =========================================================================
    case 'guardar_excepcion':
        $fecha = $_POST['fecha'] ?? '';
        $ciudad = $_POST['ciudad'] ?? '';
        $vehiculo = $_POST['vehiculo'] ?? 'todos';
        $motivo = trim($_POST['motivo'] ?? '');

        if (!$fecha || !$ciudad || !$motivo) die('Faltan datos para la excepción');

        $excepciones = DataManager::getExcepciones();

        // Evitar duplicados exactos (misma fecha, ciudad y vehículo)
        foreach ($excepciones as $k => $ex) {
            if ($ex['fecha'] === $fecha && $ex['ciudad'] === $ciudad && $ex['vehiculo'] === $vehiculo) {
                unset($excepciones[$k]); // Si existe, la reemplazamos (actualizar motivo)
            }
        }

        $excepciones[] = [
            'fecha' => $fecha,
            'ciudad' => $ciudad,
            'vehiculo' => $vehiculo,
            'motivo' => $motivo
        ];

        DataManager::saveExcepciones($excepciones); // El manager se encarga de ordenar
        header('Location: excepciones.php?msg=Excepción creada correctamente');
        break;

    case 'eliminar_excepcion':
        $fecha = $_GET['fecha'] ?? '';
        $ciudad = $_GET['ciudad'] ?? '';
        $vehiculo = $_GET['vehiculo'] ?? '';

        if (!$fecha || !$ciudad || !$vehiculo) die('Faltan parámetros');

        $excepciones = DataManager::getExcepciones();
        $nuevas_excepciones = [];

        foreach ($excepciones as $ex) {
            // Si coincide todo, lo saltamos (borrar)
            if ($ex['fecha'] === $fecha && $ex['ciudad'] === $ciudad && $ex['vehiculo'] === $vehiculo) {
                continue;
            }
            $nuevas_excepciones[] = $ex;
        }

        DataManager::saveExcepciones($nuevas_excepciones);
        header('Location: excepciones.php?msg=Excepción eliminada y medida restaurada');
        break;

    // =========================================================================
    // DEFAULT
    // =========================================================================
    default:
        die("Acción desconocida: " . htmlspecialchars($accion));
}
?>
