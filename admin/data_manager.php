<?php
/**
 * admin/data_manager.php
 * Controlador central para lectura y escritura segura de archivos JSON.
 * Se encarga de manejar la persistencia de datos del sistema.
 */

class DataManager {
    
    // Ruta base a la carpeta de datos
    const PATH_DATOS = __DIR__ . '/../datos/';
    
    // Nombres de archivos definidos como constantes para evitar errores de dedo
    const FILE_CONFIG = 'config.json';
    const FILE_FESTIVOS = 'festivos.json';
    const FILE_ALERTAS = 'alertas.json';

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
        
        // Si json_decode falla (null), retornamos array vacío para evitar errores en foreach
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
        // JSON_PRETTY_PRINT: Para que el archivo sea legible por humanos si lo abres.
        // JSON_UNESCAPED_UNICODE: Para que las tildes y ñ se guarden bien (no como \u00f1).
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

    // --- Helpers Específicos (Atajos) ---

    public static function getCiudades() {
        return self::get(self::FILE_CONFIG);
    }

    public static function saveCiudades($datos) {
        return self::save(self::FILE_CONFIG, $datos);
    }

    public static function getFestivos() {
        return self::get(self::FILE_FESTIVOS);
    }

    public static function saveFestivos($datos) {
        // Ordenar array de objetos por la clave "fecha"
        usort($datos, function($a, $b) {
            return strcmp($a['fecha'], $b['fecha']);
        });
        
        // Eliminar duplicados de fecha
        $unicos = [];
        $fechas_vistas = [];
        foreach($datos as $d) {
            if(!in_array($d['fecha'], $fechas_vistas)) {
                $fechas_vistas[] = $d['fecha'];
                $unicos[] = $d;
            }
        }
        
        return self::save(self::FILE_FESTIVOS, $unicos);
    }

    public static function getAlerta() {
        return self::get(self::FILE_ALERTAS);
    }
    
    public static function saveAlerta($datos) {
        return self::save(self::FILE_ALERTAS, $datos);
    }
}
?>
