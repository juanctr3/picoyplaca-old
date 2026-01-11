<?php
/**
 * admin/procesar.php
 * Controlador que recibe formularios y peticiones para guardar datos.
 */

require_once 'auth.php';
require_once 'data_manager.php';

// Validar que haya una acción
$accion = $_REQUEST['accion'] ?? '';

if (!$accion) {
    header('Location: index.php?error=sin_accion');
    exit;
}

switch ($accion) {
    
    // -------------------------------------------------------------------------
    // 1. GUARDAR / EDITAR CIUDAD
    // -------------------------------------------------------------------------
    case 'guardar_ciudad':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método incorrecto');

        // Recibir datos
        $id_slug = trim($_POST['id_slug'] ?? '');
        $id_original = trim($_POST['id_original'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        
        if (!$id_slug || !$nombre) {
            die('Error: Faltan datos obligatorios (Slug o Nombre).');
        }

        // Cargar ciudades actuales
        $ciudades = DataManager::getCiudades();

        // Validar duplicados si es nuevo
        if (!$id_original && isset($ciudades[$id_slug])) {
            die("Error: Ya existe una ciudad con el identificador '$id_slug'.");
        }

        // Estructura base de la ciudad
        $nueva_ciudad = [
            'nombre' => $nombre,
            'vehiculos' => []
        ];

        // Procesar vehículos (vienen en arrays paralelos)
        if (isset($_POST['v_keys'])) {
            $keys = $_POST['v_keys'];
            $labels = $_POST['v_labels'];
            $logicas = $_POST['v_logicas'];
            $horarios = $_POST['v_horarios'];
            $configs = $_POST['v_configs']; // JSON strings

            for ($i = 0; $i < count($keys); $i++) {
                $v_key = trim($keys[$i]);
                if (!$v_key) continue;

                // Datos básicos del vehículo
                $vehiculo_data = [
                    'label' => $labels[$i],
                    'logica' => $logicas[$i],
                    'horario' => $horarios[$i]
                ];

                // Decodificar configuración extra (JSON)
                $extra_config = json_decode($configs[$i], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($extra_config)) {
                    $vehiculo_data = array_merge($vehiculo_data, $extra_config);
                }

                $nueva_ciudad['vehiculos'][$v_key] = $vehiculo_data;
            }
        }

        // Guardar en el array principal
        // Si estamos editando y cambiaron el ID (aunque el form lo bloquea), borramos el anterior
        if ($id_original && $id_original !== $id_slug) {
            unset($ciudades[$id_original]);
        }
        
        $ciudades[$id_slug] = $nueva_ciudad;

        // Escribir archivo
        if (DataManager::saveCiudades($ciudades)) {
            header('Location: ciudades.php?msg=Ciudad guardada correctamente');
        } else {
            die('Error al escribir en config.json');
        }
        break;

    // -------------------------------------------------------------------------
    // 2. ELIMINAR CIUDAD
    // -------------------------------------------------------------------------
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

    // -------------------------------------------------------------------------
    // 3. AGREGAR FESTIVO
    // -------------------------------------------------------------------------
    case 'agregar_festivo':
        $fecha = $_POST['fecha'] ?? '';
        if (!$fecha) die('Falta fecha');

        $festivos = DataManager::getFestivos();
        
        if (!in_array($fecha, $festivos)) {
            $festivos[] = $fecha;
            DataManager::saveFestivos($festivos);
        }
        header('Location: festivos.php?msg=Festivo agregado');
        break;

    // -------------------------------------------------------------------------
    // 4. ELIMINAR FESTIVO
    // -------------------------------------------------------------------------
    case 'eliminar_festivo':
        $fecha = $_GET['fecha'] ?? '';
        if (!$fecha) die('Falta fecha');

        $festivos = DataManager::getFestivos();
        $key = array_search($fecha, $festivos);
        
        if ($key !== false) {
            unset($festivos[$key]);
            // Reindexar array para evitar huecos en JSON (ej: {"0":"...", "2":"..."})
            $festivos = array_values($festivos); 
            DataManager::saveFestivos($festivos);
        }
        header('Location: festivos.php?msg=Festivo eliminado');
        break;

    // -------------------------------------------------------------------------
    // 5. GUARDAR ALERTA GLOBAL
    // -------------------------------------------------------------------------
    case 'guardar_alerta':
        $alerta = [
            'activa' => isset($_POST['activa']), // Checkbox envía '1' o nada
            'mensaje' => trim($_POST['mensaje'] ?? ''),
            'tipo' => $_POST['tipo'] ?? 'info',
            'url' => trim($_POST['url'] ?? '')
        ];

        DataManager::saveAlerta($alerta);
        header('Location: alertas.php?msg=Configuración de alerta actualizada');
        break;

    // -------------------------------------------------------------------------
    // DEFAULT
    // -------------------------------------------------------------------------
    default:
        die("Acción desconocida: " . htmlspecialchars($accion));
}
?>
