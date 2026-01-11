<?php
/**
 * API de Pico y Placa - Endpoints JSON
 * 
 * Endpoints disponibles:
 * GET /api.php?action=info&ciudad=bogota
 * GET /api.php?action=placa&ciudad=bogota&placa=5
 * GET /api.php?action=fecha&ciudad=bogota&fecha=2025-12-25
 * GET /api.php?action=ciudades
 * 
 * Respuestas en JSON con estructura:
 * { "success": true/false, "data": {...}, "error": "..." }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');

require_once 'clases/PicoYPlaca.php';
$config = require_once 'config-ciudades.php';

$ciudades = $config['ciudades'];
$festivos = $config['festivos'];

// Parámetros
$action = $_GET['action'] ?? 'info';
$ciudad = $_GET['ciudad'] ?? 'bogota';
$placa = $_GET['placa'] ?? null;
$fecha = $_GET['fecha'] ?? null;

try {
    switch ($action) {
        
        // ==========================================
        // ACTION: INFO - Información general hoy
        // ==========================================
        case 'info':
            if (!isset($ciudades[$ciudad])) {
                throw new Exception("Ciudad '{$ciudad}' no encontrada");
            }
            
            $fechaObj = $fecha ? new DateTime($fecha) : new DateTime();
            $pyp = new PicoYPlaca($ciudad, $fechaObj, $ciudades, $festivos);
            
            $respuesta = [
                'success' => true,
                'data' => $pyp->getInfo()
            ];
            break;
        
        // ==========================================
        // ACTION: PLACA - Consultar placa específica
        // ==========================================
        case 'placa':
            if ($placa === null) {
                throw new Exception("Parámetro 'placa' requerido (0-9)");
            }
            if (!preg_match('/^[0-9]$/', $placa)) {
                throw new Exception("Placa debe ser un dígito 0-9");
            }
            
            $fechaObj = $fecha ? new DateTime($fecha) : new DateTime();
            $pyp = new PicoYPlaca($ciudad, $fechaObj, $ciudades, $festivos);
            
            $respuesta = [
                'success' => true,
                'data' => $pyp->consultarPlaca($placa)
            ];
            break;
        
        // ==========================================
        // ACTION: FECHA - Consultar fecha específica
        // ==========================================
        case 'fecha':
            if (!$fecha) {
                throw new Exception("Parámetro 'fecha' requerido (YYYY-MM-DD)");
            }
            
            $fechaObj = new DateTime($fecha);
            $pyp = new PicoYPlaca($ciudad, $fechaObj, $ciudades, $festivos);
            
            $respuesta = [
                'success' => true,
                'data' => $pyp->getInfo()
            ];
            break;
        
        // ==========================================
        // ACTION: ACTIVO - Verificar si está activo
        // ==========================================
        case 'activo':
            $pyp = new PicoYPlaca($ciudad, null, $ciudades, $festivos);
            
            $respuesta = [
                'success' => true,
                'data' => [
                    'ciudad' => $pyp->getNombreCiudad(),
                    'esta_activo' => $pyp->estaActivo(),
                    'restricciones' => $pyp->getRestricciones(),
                    'horario' => $pyp->getHorario()
                ]
            ];
            break;
        
        // ==========================================
        // ACTION: TIEMPO - Tiempo hasta pico y placa
        // ==========================================
        case 'tiempo':
            $pyp = new PicoYPlaca($ciudad, null, $ciudades, $festivos);
            
            $tiempo = $pyp->getTiempoHastaPicoYPlaca();
            
            $respuesta = [
                'success' => true,
                'data' => [
                    'ciudad' => $pyp->getNombreCiudad(),
                    'horas' => $tiempo['horas'],
                    'minutos' => $tiempo['minutos'],
                    'segundos' => $tiempo['segundos'],
                    'total_segundos' => $tiempo['timestamp'],
                    'formateado' => sprintf('%02d:%02d:%02d', $tiempo['horas'], $tiempo['minutos'], $tiempo['segundos'])
                ]
            ];
            break;
        
        // ==========================================
        // ACTION: CIUDADES - Listar todas las ciudades
        // ==========================================
        case 'ciudades':
            $listaCiudades = array_map(function($codigo, $info) {
                return [
                    'codigo' => $codigo,
                    'nombre' => $info['nombre'],
                    'departamento' => $info['departamento'] ?? '',
                    'horario' => $info['horario'],
                    'tipo' => $info['tipo'],
                    'latitud' => $info['latitud'] ?? null,
                    'longitud' => $info['longitud'] ?? null,
                    'poblacion' => $info['poblacion'] ?? ''
                ];
            }, array_keys($ciudades), $ciudades);
            
            $respuesta = [
                'success' => true,
                'data' => [
                    'total' => count($listaCiudades),
                    'ciudades' => $listaCiudades
                ]
            ];
            break;
        
        // ==========================================
        // ACTION: COMPARAR - Comparar múltiples ciudades
        // ==========================================
        case 'comparar':
            $ciudadesComparar = explode(',', $_GET['ciudades'] ?? 'bogota,medellin,cali');
            $ciudadesComparar = array_map('trim', $ciudadesComparar);
            
            $comparacion = [];
            foreach ($ciudadesComparar as $cd) {
                if (isset($ciudades[$cd])) {
                    $pyp = new PicoYPlaca($cd, null, $ciudades, $festivos);
                    $comparacion[] = [
                        'ciudad' => $pyp->getNombreCiudad(),
                        'codigo' => $cd,
                        'restricciones' => $pyp->getRestricciones(),
                        'permitidas' => $pyp->getPermitidas(),
                        'esta_activo' => $pyp->estaActivo()
                    ];
                }
            }
            
            $respuesta = [
                'success' => true,
                'data' => [
                    'fecha' => date('Y-m-d'),
                    'total_ciudades' => count($comparacion),
                    'comparacion' => $comparacion
                ]
            ];
            break;
        
        // ==========================================
        // ACTION: RANGO - Consultar rango de fechas
        // ==========================================
        case 'rango':
            if (!$fecha) {
                throw new Exception("Parámetro 'fecha' requerido como fecha inicial (YYYY-MM-DD)");
            }
            
            $dias = (int)($_GET['dias'] ?? 7);
            $dias = min($dias, 90); // Máximo 90 días
            
            $resultado = [];
            $fechaInicio = new DateTime($fecha);
            
            for ($i = 0; $i < $dias; $i++) {
                $fechaActual = clone $fechaInicio;
                $fechaActual->modify("+$i days");
                
                $pyp = new PicoYPlaca($ciudad, $fechaActual, $ciudades, $festivos);
                
                $resultado[] = [
                    'fecha' => $fechaActual->format('Y-m-d'),
                    'dia' => $pyp->getDiaEnEspanol(),
                    'restricciones' => $pyp->getRestricciones(),
                    'es_fin_semana' => $pyp->esFinDeSemana(),
                    'es_festivo' => $pyp->esFestivo()
                ];
            }
            
            $respuesta = [
                'success' => true,
                'data' => [
                    'ciudad' => $pyp->getNombreCiudad(),
                    'fecha_inicio' => $fecha,
                    'dias_consultados' => $dias,
                    'resultados' => $resultado
                ]
            ];
            break;
        
        // ==========================================
        // ACTION: STATUS - Estado general del sistema
        // ==========================================
        case 'status':
            $status = [
                'servidor' => 'activo',
                'timestamp' => time(),
                'fecha_actual' => date('Y-m-d H:i:s'),
                'total_ciudades' => count($ciudades),
                'total_festivos' => count($festivos),
                'version' => '2.0'
            ];
            
            $respuesta = [
                'success' => true,
                'data' => $status
            ];
            break;
        
        // ==========================================
        // DEFAULT - Acción no encontrada
        // ==========================================
        default:
            throw new Exception("Acción '{$action}' no válida. Usa: info, placa, fecha, activo, tiempo, ciudades, comparar, rango, status");
    }
    
} catch (Exception $e) {
    $respuesta = [
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'ERROR'
    ];
    http_response_code(400);
}

// Salida JSON
echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
