<?php
/**
 * admin/data_manager.php
 * Controlador central para lectura y escritura segura de archivos JSON.
 * Se encarga de manejar la persistencia de datos del sistema.
 */

class DataManager {
    
    // Ruta base a la carpeta de datos
    const PATH_DATOS = __DIR__ . '/../datos/';
    
    // Nombres de archivos definidos como constantes
    const FILE_CONFIG = 'config.json';
    const FILE_FESTIVOS = 'festivos.json';
    const FILE_ALERTAS = 'alertas.json';
    const FILE_EXCEPCIONES = 'excepciones.json'; // Nuevo archivo para levantar medidas

    /**
     * Lee un archivo JSON y devuelve su contenido como Array.
     * @param string $archivo Nombre del archivo (ej: 'config.json')
     * @return array Datos decodificados o array vacío si no existe.
     */
    public static function get(string $archivo) {
        $ruta = self::PATH_DATOS . $archivo;
        
        if (!file_exists($ruta)) {
            return []; // Retorna array vacío si el archivo es nuevo o no existe
        }
        
        $contenido = file_get_contents($ruta);
        $datos = json_decode($contenido, true);
        
        // Si json_decode falla (null), retornamos array vacío para evitar errores
        return $datos ?? [];
    }

    /**
     * Guarda un array en un archivo JSON con formato legible.
     * @param string $archivo Nombre del archivo
     * @param array $datos Array de datos a guardar
     * @return bool True si se guardó correctamente
     */
    public static function save(string $archivo, array $datos) {
        // 1. Asegurar que la carpeta 'datos' exista
        if (!is_dir(self::PATH_DATOS)) {
            if (!mkdir(self::PATH_DATOS, 0755, true)) {
                error_log("DataManager: No se pudo crear el directorio " . self::PATH_DATOS);
                return false;
            }
        }

        $ruta = self::PATH_DATOS . $archivo;
        
        // 2. Codificar a JSON
        // JSON_PRETTY_PRINT: Para que el archivo sea legible por humanos.
        // JSON_UNESCAPED_UNICODE: Para guardar tildes y ñ correctamente.
        $json = json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($json === false) {
            error_log("DataManager: Error al codificar JSON para $archivo: " . json_last_error_msg());
            return false;
        }

        // 3. Escribir archivo
        if (file_put_contents($ruta, $json) === false) {
            error_log("DataManager: Error al escribir en el archivo $ruta");
            return false;
        }

        return true;
    }

    // --- Helpers Específicos: Ciudades ---

    public static function getCiudades() {
        return self::get(self::FILE_CONFIG);
    }

    public static function saveCiudades($datos) {
        return self::save(self::FILE_CONFIG, $datos);
    }

    // --- Helpers Específicos: Festivos ---

    public static function getFestivos() {
        return self::get(self::FILE_FESTIVOS);
    }

    public static function saveFestivos($datos) {
        // Ordenar por fecha (soporta formato antiguo string y nuevo array con 'fecha')
        usort($datos, function($a, $b) {
            $fa = is_array($a) ? $a['fecha'] : $a;
            $fb = is_array($b) ? $b['fecha'] : $b;
            return strcmp($fa, $fb);
        });
        
        // Eliminar duplicados de fecha
        $unicos = [];
        $fechas_vistas = [];
        foreach($datos as $d) {
            $fecha = is_array($d) ? $d['fecha'] : $d;
            if(!in_array($fecha, $fechas_vistas)) {
                $fechas_vistas[] = $fecha;
                $unicos[] = $d;
            }
        }
        
        return self::save(self::FILE_FESTIVOS, $unicos);
    }

    // --- Helpers Específicos: Alertas (Actualizado) ---

    public static function getAlertas() {
        return self::get(self::FILE_ALERTAS);
    }
    
    public static function saveAlertas($datos) {
        // Ahora guardamos un array de alertas, no una sola alerta global
        return self::save(self::FILE_ALERTAS, $datos);
    }

    // --- Helpers Específicos: Excepciones (NUEVO) ---

    public static function getExcepciones() {
        return self::get(self::FILE_EXCEPCIONES);
    }

    public static function saveExcepciones($datos) {
        // Ordenar cronológicamente por fecha
        usort($datos, function($a, $b) {
            return strcmp($a['fecha'], $b['fecha']);
        });
        return self::save(self::FILE_EXCEPCIONES, $datos);
    }
}
?>
