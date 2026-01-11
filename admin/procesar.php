<?php
/**
 * admin/procesar.php
 * Controlador principal: Recibe formularios y peticiones para guardar datos.
 * Maneja Ciudades, Vehículos, Festivos (con nombres) y Alertas.
 */

require_once 'auth.php';
require_once 'data_manager.php';

// Validar que haya una acción definida
$accion = $_REQUEST['accion'] ?? '';

if (!$accion) {
    // Si no hay acción, mandamos al dashboard con error
    header('Location: index.php?error=sin_accion');
    exit;
}

switch ($accion) {
    
    // =========================================================================
    // 1. GESTIÓN DE CIUDADES (GUARDAR / EDITAR)
    // =========================================================================
    case 'guardar_ciudad':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Método incorrecto');

        // 1. Recibir datos básicos
        $id_slug = trim($_POST['id_slug'] ?? '');
        $id_original = trim($_POST['id_original'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        
        if (!$id_slug || !$nombre) {
            die('Error: Faltan datos obligatorios (Slug o Nombre).');
        }

        // 2. Cargar ciudades actuales
        $ciudades = DataManager::getCiudades();

        // 3. Validar duplicados (solo si es nuevo)
        if (!$id_original && isset($ciudades[$id_slug])) {
            die("Error: Ya existe una ciudad con el identificador '$id_slug'.");
        }

        // 4. Preparar estructura de la ciudad
        $nueva_ciudad = [
            'nombre' => $nombre,
            'vehiculos' => []
        ];

        // 5. Procesar vehículos (vienen en arrays paralelos desde el formulario)
        if (isset($_POST['v_keys'])) {
            $keys = $_POST['v_keys'];         // IDs internos (ej: particular)
            $labels = $_POST['v_labels'];     // Nombres visibles (ej: Particulares)
            $logicas = $_POST['v_logicas'];   // Algoritmos (ej: semanal-fijo)
            $horarios = $_POST['v_horarios']; // Texto horario
            $configs = $_POST['v_configs'];   // JSON con reglas extra

            for ($i = 0; $i < count($keys); $i++) {
                $v_key = trim($keys[$i]);
                if (!$v_key) continue;

                // Datos base del vehículo
                $vehiculo_data = [
                    'label' => $labels[$i],
                    'logica' => $logicas[$i],
                    'horario' => $horarios[$i]
                ];

                // Decodificar configuración avanzada (JSON)
                // Esto permite guardar reglas complejas o arrays específicos
                $extra_config = json_decode($configs[$i], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($extra_config)) {
                    $vehiculo_data = array_merge($vehiculo_data, $extra_config);
                }

                $nueva_ciudad['vehiculos'][$v_key] = $vehiculo_data;
            }
        }

        // 6. Guardar en el array principal
        // Si se cambió el slug (aunque el input es readonly en edición), borramos el anterior
        if ($id_original && $id_original !== $id_slug) {
            unset($ciudades[$id_original]);
        }
        
        $ciudades[$id_slug] = $nueva_ciudad;

        // 7. Escribir en archivo JSON
        if (DataManager::saveCiudades($ciudades)) {
            header('Location: ciudades.php?msg=Ciudad guardada correctamente');
        } else {
            die('Error crítico al escribir en config.json');
        }
        break;

    // =========================================================================
    // 2. ELIMINAR CIUDAD
    // =========================================================================
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
    // 3. AGREGAR FESTIVO (CON NOMBRE)
    // =========================================================================
    case 'agregar_festivo':
        $fecha = $_POST['fecha'] ?? '';
        $nombre = trim($_POST['nombre'] ?? 'Festivo');
        
        if (!$fecha) die('Falta fecha');

        $festivos = DataManager::getFestivos();
        
        // Verificar si la fecha ya existe para evitar duplicados
        $existe = false;
        foreach($festivos as $f) {
            // Comprobar compatibilidad con formato antiguo (string) y nuevo (array)
            $f_fecha = is_array($f) ? $f['fecha'] : $f;
            if ($f_fecha === $fecha) {
                $existe = true; 
                break;
            }
        }
        
        if (!$existe) {
            // Guardamos la nueva estructura: Objeto con fecha y nombre
            $festivos[] = [
                'fecha' => $fecha, 
                'nombre' => $nombre
            ];
            // DataManager se encarga de ordenar por fecha
            DataManager::saveFestivos($festivos);
        }
        
        header('Location: festivos.php?msg=Festivo agregado correctamente');
        break;

    // =========================================================================
    // 4. ELIMINAR FESTIVO
    // =========================================================================
    case 'eliminar_festivo':
        $fecha = $_GET['fecha'] ?? '';
        if (!$fecha) die('Falta fecha');

        $festivos = DataManager::getFestivos();
        $nuevos_festivos = [];
        
        // Reconstruimos el array excluyendo la fecha seleccionada
        foreach($festivos as $f) {
            $f_fecha = is_array($f) ? $f['fecha'] : $f;
            if ($f_fecha !== $fecha) {
                $nuevos_festivos[] = $f;
            }
        }
        
        // Si hubo cambios, guardamos (esto reindexa el array automáticamente)
        if (count($nuevos_festivos) !== count($festivos)) {
            DataManager::saveFestivos($nuevos_festivos);
        }
        
        header('Location: festivos.php?msg=Festivo eliminado');
        break;

    // =========================================================================
    // 5. GUARDAR ALERTA GLOBAL (ALERTAS FLASH)
    // =========================================================================
    case 'guardar_alerta':
        // El checkbox 'activa' solo se envía si está marcado
        $alerta = [
            'activa' => isset($_POST['activa']), 
            'mensaje' => trim($_POST['mensaje'] ?? ''),
            'tipo' => $_POST['tipo'] ?? 'info',
            'url' => trim($_POST['url'] ?? '')
        ];

        if (DataManager::saveAlerta($alerta)) {
            header('Location: alertas.php?msg=Configuración de alerta actualizada');
        } else {
            die('Error al guardar alerta');
        }
        break;

    // =========================================================================
    // DEFAULT: ACCIÓN NO RECONOCIDA
    // =========================================================================
    default:
        die("Acción desconocida o no válida: " . htmlspecialchars($accion));
}
?>
