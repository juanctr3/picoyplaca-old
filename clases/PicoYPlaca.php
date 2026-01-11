<?php
/**
 * Clase Principal PicoYPlaca
 * Se encarga de toda la lógica de cálculo de restricciones.
 * Versión 3.0: Soporte para Festivos con nombre y Excepciones (Levantamiento de medida).
 */

class PicoYPlaca {
    private $ciudad_id;
    private $fecha;      // Objeto DateTime
    private $ciudades;   // Configuración completa
    private $festivos_map; // Mapa de festivos: 'YYYY-MM-DD' => 'Nombre'
    private $excepciones;  // Lista de excepciones
    private $vehiculo;
    private $config_v;   // Configuración específica del vehículo seleccionado

    public function __construct($ciudad_id, DateTime $fecha, $ciudades, $festivos_raw, $vehiculo = 'particular') {
        $this->ciudad_id = $ciudad_id;
        $this->fecha = $fecha;
        $this->ciudades = $ciudades;
        $this->vehiculo = $vehiculo;
        
        // 1. Procesar Festivos (Soporte mixto: array de strings o array de objetos)
        $this->festivos_map = [];
        foreach ($festivos_raw as $f) {
            if (is_array($f) && isset($f['fecha'])) {
                $this->festivos_map[$f['fecha']] = $f['nombre'] ?? 'Festivo';
            } elseif (is_string($f)) {
                $this->festivos_map[$f] = 'Festivo';
            }
        }

        // 2. Cargar Excepciones automáticamente
        // Leemos el archivo directamente para garantizar que la regla se aplique 
        // sin necesidad de modificar el index.php para pasarlo por parámetro.
        $file_excepciones = __DIR__ . '/../datos/excepciones.json';
        if (file_exists($file_excepciones)) {
            $this->excepciones = json_decode(file_get_contents($file_excepciones), true) ?? [];
        } else {
            $this->excepciones = [];
        }
        
        // 3. Cargar Configuración del Vehículo
        // Si no existe el vehículo solicitado, fallback a particular
        $this->config_v = $ciudades[$ciudad_id]['vehiculos'][$vehiculo] ?? $ciudades[$ciudad_id]['vehiculos']['particular'];
    }

    /**
     * Devuelve toda la información calculada para la fecha y ciudad.
     */
    public function getInfo() {
        // A. Verificar Excepciones (Levantamiento de medida)
        $excepcion = $this->buscarExcepcion();
        if ($excepcion) {
            return [
                'ciudad_nombre' => $this->ciudades[$this->ciudad_id]['nombre'],
                'vehiculo_label' => $this->config_v['label'],
                'horario' => $this->config_v['horario'],
                'restricciones' => [], // Vacío porque se levantó la medida
                'dia_nombre' => $this->getDiaNombre(),
                'es_festivo' => false, // Prioridad a la excepción visualmente
                'nombre_festivo' => $excepcion['motivo'], // Usamos este campo para mostrar el motivo
                'es_fin_semana' => $this->esFinDeSemana(),
                'es_excepcion' => true
            ];
        }

        // B. Cálculo Normal
        return [
            'ciudad_nombre' => $this->ciudades[$this->ciudad_id]['nombre'],
            'vehiculo_label' => $this->config_v['label'],
            'horario' => $this->config_v['horario'],
            'restricciones' => $this->calcularRestricciones(),
            'dia_nombre' => $this->getDiaNombre(),
            'es_festivo' => $this->esFestivo(),
            'nombre_festivo' => $this->getNombreFestivo(),
            'es_fin_semana' => $this->esFinDeSemana(),
            'es_excepcion' => false
        ];
    }

    /**
     * Busca si existe una excepción configurada para hoy, esta ciudad y este vehículo.
     */
    private function buscarExcepcion() {
        $fecha_str = $this->fecha->format('Y-m-d');
        
        foreach ($this->excepciones as $ex) {
            // 1. Coincidir Fecha
            if ($ex['fecha'] !== $fecha_str) continue;
            
            // 2. Coincidir Ciudad (o Global)
            if ($ex['ciudad'] !== 'global' && $ex['ciudad'] !== $this->ciudad_id) continue;
            
            // 3. Coincidir Vehículo (o Todos)
            if ($ex['vehiculo'] !== 'todos' && $ex['vehiculo'] !== $this->vehiculo) continue;
            
            // ¡Coincidencia encontrada!
            return $ex;
        }
        return null;
    }

    private function calcularRestricciones() {
        $logica = $this->config_v['logica'];
        $dia_w = (int)$this->fecha->format('N'); // 1=Lun, 7=Dom
        $es_festivo = $this->esFestivo();

        // 1. Filtros Globales (Sábado, Domingo, Festivo)
        $aplica_sab = $this->config_v['aplica_sabados'] ?? false;
        $aplica_dom = $this->config_v['aplica_domingos'] ?? false;
        $aplica_fest = $this->config_v['aplica_festivos'] ?? false;

        if ($dia_w == 6 && !$aplica_sab) return [];
        if ($dia_w == 7 && !$aplica_dom) return [];
        if ($es_festivo && !$aplica_fest) return [];

        // 2. Selector de Algoritmo
        switch ($logica) {
            
            // --- BOGOTÁ (Pares e Impares) ---
            case 'bogota-particular':
                $dia_mes = (int)$this->fecha->format('d');
                // Días pares circulan placas pares (restricción impares) y viceversa?
                // En Bogotá: 
                // Días PARES: NO circulan placas terminadas en par (0,2,4,6,8) -> PERO cambiaron.
                // REGLA ACTUAL: Días PARES -> Restricción 1,2,3,4,5. Días IMPARES -> 6,7,8,9,0.
                // Usamos la configuración del JSON para ser flexibles
                return ($dia_mes % 2 == 0) ? $this->config_v['reglas']['par'] : $this->config_v['reglas']['impar'];

            // --- SEMANAL FIJO (Medellín, Cali, etc.) ---
            case 'semanal-fijo':
                return $this->config_v['reglas'][$this->fecha->format('l')] ?? [];

            // --- SECUENCIAS / ROTACIONES ---
            case 'secuencia-laboral-sabado':
                return $this->calcularSecuencia($this->config_v['ref_fecha'], $this->config_v['ref_digitos'], $this->config_v['salto'], true);

            case 'secuencia-continua':
                return $this->calcularSecuencia($this->config_v['ref_fecha'], $this->config_v['ref_digitos'], $this->config_v['salto'], false);

            case 'secuencia-retroceso-laboral':
                return $this->calcularRetrocesoLaboral();

            // --- CASOS ESPECIALES ---
            
            case 'pereira-taxis':
                return $this->calcularPereiraTaxis();

            case 'armenia-taxis':
                return $this->calcularArmeniaTaxis();

            case 'santa-marta-taxis':
                return $this->calcularSantaMartaTaxis();

            case 'bucaramanga-rotacion-sabado':
                if ($dia_w != 6) return $this->config_v['reglas_lv'][$this->fecha->format('l')] ?? [];
                // Lógica de rotación sabatina Bucaramanga
                $ref = new DateTime($this->config_v['ref_sabado_fecha']);
                $weeks = floor($this->fecha->diff($ref)->days / 7);
                $base = $this->config_v['ref_sabado_digitos'];
                // Avanza 2 dígitos cada sábado? Depende de la config. Asumimos rotación estándar.
                // En BGA rota trimestralmente, pero el sábado rota distinto. 
                // Simplificación basada en config.json actual:
                return [($base[0] + ($weeks*2))%10, ($base[1] + ($weeks*2))%10];
            
            case 'bucaramanga-taxis':
                 $ref = new DateTime($this->config_v['ref_fecha']);
                 $lunes_actual = clone $this->fecha;
                 if ($dia_w != 1) $lunes_actual->modify('last monday');
                 $weeks = floor($lunes_actual->diff($ref)->days / 7);
                 $base = $this->config_v['ref_digitos'];
                 return [($base[0] + ($weeks*2))%10, ($base[1] + ($weeks*2))%10];

            case 'bogota-carga-sabado':
                 $ref = new DateTime('2025-11-29'); // Fecha base conocida
                 $weeks = floor($this->fecha->diff($ref)->days / 7);
                 return ($weeks % 2 == 0) ? [1,3,5,7,9] : [0,2,4,6,8];

            case 'ibague-semestral':
                // Cambio de semestre
                if ($this->fecha->format('Y-m-d') >= $this->config_v['fecha_cambio']) {
                    return $this->config_v['semestre_siguiente'][$this->fecha->format('l')] ?? [];
                }
                return $this->config_v['semestre_actual'][$this->fecha->format('l')] ?? [];

            case 'ibague-tpc':
                $ref = new DateTime($this->config_v['ref_fecha']);
                $days = $this->fecha->diff($ref)->days;
                $idx = $days % 5;
                return $this->config_v['ciclo'][$idx];
                
            case 'villavicencio-carga':
                return [0,1,2,3,4,5,6,7,8,9];
                
            case 'sin-restriccion':
                return [];

            default:
                return [];
        }
    }

    // --- ALGORITMOS MATEMÁTICOS AUXILIARES ---

    private function calcularSecuencia($ref_str, $base_digitos, $salto, $saltar_domingos) {
        $ref = new DateTime($ref_str);
        $pasos = 0;
        
        if ($saltar_domingos) {
            // Contamos días reales saltando domingos
            $temp = clone $ref;
            while($temp < $this->fecha) {
                $temp->modify('+1 day');
                if ($temp->format('N') != 7) $pasos++;
            }
        } else {
            // Días calendario simples
            $pasos = $this->fecha->diff($ref)->days;
        }

        $nuevos = [];
        foreach($base_digitos as $d) {
            $nuevos[] = ($d + ($pasos * $salto)) % 10;
        }
        return $nuevos;
    }

    private function calcularRetrocesoLaboral() {
        $ref = new DateTime($this->config_v['ref_fecha']);
        $habiles = 0;
        $temp = clone $ref;
        // Contamos días hábiles (Lun-Vie y NO festivos)
        while($temp < $this->fecha) {
            $w = $temp->format('N');
            $f = isset($this->festivos_map[$temp->format('Y-m-d')]);
            if ($w <= 5 && !$f) $habiles++;
            $temp->modify('+1 day');
        }
        $base = $this->config_v['ref_digitos'][0];
        // Retrocede 1 dígito por día hábil
        $res = ($base - $habiles) % 10;
        if ($res < 0) $res += 10;
        return [$res];
    }
    
    private function calcularPereiraTaxis() {
        // Base fija semanal + rotación
        $bases_dias = [1=>4, 2=>0, 3=>6, 4=>3, 5=>7, 7=>9]; // Sábados no aplica general
        $dia_w = (int)$this->fecha->format('N');
        if (!isset($bases_dias[$dia_w])) return [];

        $ref = new DateTime('2025-11-24');
        $lunes_actual = clone $this->fecha;
        if ($dia_w != 1) $lunes_actual->modify('last monday');
        $weeks = floor($lunes_actual->diff($ref)->days / 7);
        // Avanza 1 dígito por semana
        return [($bases_dias[$dia_w] + $weeks) % 10];
    }

    private function calcularArmeniaTaxis() {
        $ref = new DateTime('2025-11-24');
        $dias = $this->fecha->diff($ref)->days;
        // Cuenta domingos para saltar lógica si fuera necesario, pero Armenia es secuencia corrida
        $domingos = 0;
        $temp = clone $ref;
        while($temp < $this->fecha) {
            if ($temp->format('N') == 7) $domingos++;
            $temp->modify('+1 day');
        }
        // Lógica específica Armenia: 2 dígitos Lun-Sáb, Todos Dom? 
        // Ajustado a lógica estándar secuencial:
        $avance = $dias + $domingos; 
        $base = 5; 
        $val = ($base + $avance) % 10;
        if ($this->fecha->format('N') == 7) return [$val, ($val+1)%10];
        return [$val];
    }

    private function calcularSantaMartaTaxis() {
        $ref = new DateTime('2025-11-24');
        $lunes_actual = clone $this->fecha;
        $dia_w = (int)$this->fecha->format('N');
        if ($dia_w != 1) $lunes_actual->modify('last monday');
        $weeks = floor($lunes_actual->diff($ref)->days / 7);
        // Retrocede 2 dígitos por semana
        $b1 = (5 - ($weeks * 2)) % 10; if($b1<0) $b1+=10;
        $b2 = (6 - ($weeks * 2)) % 10; if($b2<0) $b2+=10;
        
        if ($dia_w <= 4) { 
            // Lun-Jue
            $add = ($dia_w - 1) * 2; 
            return [($b1+$add)%10, ($b2+$add)%10]; 
        }
        if ($dia_w == 5) return [($b1+8)%10]; // Vie
        if ($dia_w == 6) return [($b1+9)%10]; // Sab
        return [];
    }

    // --- UTILS ---

    private function esFestivo() { 
        return isset($this->festivos_map[$this->fecha->format('Y-m-d')]); 
    }
    
    private function getNombreFestivo() {
        return $this->festivos_map[$this->fecha->format('Y-m-d')] ?? null;
    }

    private function esFinDeSemana() { 
        $n = $this->fecha->format('N'); 
        return ($n==6 || $n==7); 
    }

    private function getDiaNombre() {
        $d = ['1'=>'Lunes','2'=>'Martes','3'=>'Miércoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sábado','7'=>'Domingo'];
        return $d[$this->fecha->format('N')];
    }
}
?>
