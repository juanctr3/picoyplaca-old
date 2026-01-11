<?php
class PicoYPlaca {
    private $ciudad_id;
    private $fecha;
    private $ciudades;
    private $festivos_map; // Guardaremos como array asociativo fecha => nombre
    private $vehiculo;
    private $config_v;

    public function __construct($ciudad_id, DateTime $fecha, $ciudades, $festivos_raw, $vehiculo = 'particular') {
        $this->ciudad_id = $ciudad_id;
        $this->fecha = $fecha;
        $this->ciudades = $ciudades;
        $this->vehiculo = $vehiculo;
        
        // Transformar la lista de festivos a un mapa para búsqueda rápida: '2025-01-01' => 'Año Nuevo'
        $this->festivos_map = [];
        foreach ($festivos_raw as $f) {
            if (is_array($f) && isset($f['fecha'])) {
                $this->festivos_map[$f['fecha']] = $f['nombre'] ?? 'Festivo';
            } elseif (is_string($f)) {
                // Soporte retroactivo por si acaso
                $this->festivos_map[$f] = 'Festivo';
            }
        }
        
        $this->config_v = $ciudades[$ciudad_id]['vehiculos'][$vehiculo] ?? $ciudades[$ciudad_id]['vehiculos']['particular'];
    }

    public function getInfo() {
        return [
            'ciudad_nombre' => $this->ciudades[$this->ciudad_id]['nombre'],
            'vehiculo_label' => $this->config_v['label'],
            'horario' => $this->config_v['horario'],
            'restricciones' => $this->calcularRestricciones(),
            'dia_nombre' => $this->getDiaNombre(),
            'es_festivo' => $this->esFestivo(),
            'nombre_festivo' => $this->getNombreFestivo(), // Nuevo dato
            'es_fin_semana' => $this->esFinDeSemana()
        ];
    }

    private function calcularRestricciones() {
        $logica = $this->config_v['logica'];
        $dia_w = (int)$this->fecha->format('N'); // 1=Lun, 7=Dom
        $es_festivo = $this->esFestivo();

        $aplica_sab = $this->config_v['aplica_sabados'] ?? false;
        $aplica_dom = $this->config_v['aplica_domingos'] ?? false;
        $aplica_fest = $this->config_v['aplica_festivos'] ?? false;

        if ($dia_w == 6 && !$aplica_sab) return [];
        if ($dia_w == 7 && !$aplica_dom) return [];
        if ($es_festivo && !$aplica_fest) return [];

        switch ($logica) {
            case 'bogota-particular':
                $dia_mes = (int)$this->fecha->format('d');
                return ($dia_mes % 2 == 0) ? $this->config_v['reglas']['par'] : $this->config_v['reglas']['impar'];

            case 'semanal-fijo':
                return $this->config_v['reglas'][$this->fecha->format('l')] ?? [];

            case 'secuencia-laboral-sabado':
                return $this->calcularSecuencia($this->config_v['ref_fecha'], $this->config_v['ref_digitos'], $this->config_v['salto'], true);

            case 'secuencia-continua':
                return $this->calcularSecuencia($this->config_v['ref_fecha'], $this->config_v['ref_digitos'], $this->config_v['salto'], false);

            case 'secuencia-retroceso-laboral':
                return $this->calcularRetrocesoLaboral();

            case 'pereira-taxis':
                return $this->calcularPereiraTaxis();

            case 'armenia-taxis':
                return $this->calcularArmeniaTaxis();

            case 'santa-marta-taxis':
                return $this->calcularSantaMartaTaxis();

            case 'bucaramanga-rotacion-sabado':
                if ($dia_w != 6) return $this->config_v['reglas_lv'][$this->fecha->format('l')] ?? [];
                $ref = new DateTime($this->config_v['ref_sabado_fecha']);
                $weeks = floor($this->fecha->diff($ref)->days / 7);
                $base = $this->config_v['ref_sabado_digitos'];
                return [($base[0] + ($weeks*2))%10, ($base[1] + ($weeks*2))%10];
            
            case 'bucaramanga-taxis':
                 $ref = new DateTime($this->config_v['ref_fecha']);
                 $lunes_actual = clone $this->fecha;
                 if ($dia_w != 1) $lunes_actual->modify('last monday');
                 $weeks = floor($lunes_actual->diff($ref)->days / 7);
                 $base = $this->config_v['ref_digitos'];
                 return [($base[0] + ($weeks*2))%10, ($base[1] + ($weeks*2))%10];

            case 'bogota-carga-sabado':
                 $ref = new DateTime('2025-11-29');
                 $weeks = floor($this->fecha->diff($ref)->days / 7);
                 return ($weeks % 2 == 0) ? [1,3,5,7,9] : [0,2,4,6,8];

            case 'ibague-semestral':
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

    private function calcularSecuencia($ref_str, $base_digitos, $salto, $saltar_domingos) {
        $ref = new DateTime($ref_str);
        $pasos = 0;
        
        if ($saltar_domingos) {
            $temp = clone $ref;
            while($temp < $this->fecha) {
                $temp->modify('+1 day');
                if ($temp->format('N') != 7) $pasos++;
            }
        } else {
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
        while($temp < $this->fecha) {
            $w = $temp->format('N');
            // Usamos el mapa de festivos para la lógica de días hábiles
            $f = isset($this->festivos_map[$temp->format('Y-m-d')]);
            if ($w <= 5 && !$f) $habiles++;
            $temp->modify('+1 day');
        }
        $base = $this->config_v['ref_digitos'][0];
        $res = ($base - $habiles) % 10;
        if ($res < 0) $res += 10;
        return [$res];
    }
    
    private function calcularPereiraTaxis() {
        $bases_dias = [1=>4, 2=>0, 3=>6, 4=>3, 5=>7, 7=>9];
        $dia_w = (int)$this->fecha->format('N');
        if (!isset($bases_dias[$dia_w])) return [];

        $ref = new DateTime('2025-11-24');
        $lunes_actual = clone $this->fecha;
        if ($dia_w != 1) $lunes_actual->modify('last monday');
        $weeks = floor($lunes_actual->diff($ref)->days / 7);
        return [($bases_dias[$dia_w] + $weeks) % 10];
    }

    private function calcularArmeniaTaxis() {
        $ref = new DateTime('2025-11-24');
        $dias = $this->fecha->diff($ref)->days;
        $domingos = 0;
        $temp = clone $ref;
        while($temp < $this->fecha) {
            if ($temp->format('N') == 7) $domingos++;
            $temp->modify('+1 day');
        }
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
        $b1 = (5 - ($weeks * 2)) % 10; if($b1<0) $b1+=10;
        $b2 = (6 - ($weeks * 2)) % 10; if($b2<0) $b2+=10;
        if ($dia_w <= 4) { $add = ($dia_w - 1) * 2; return [($b1+$add)%10, ($b2+$add)%10]; }
        if ($dia_w == 5) return [($b1+8)%10]; 
        if ($dia_w == 6) return [($b1+9)%10];
        return [];
    }

    private function esFestivo() { 
        return isset($this->festivos_map[$this->fecha->format('Y-m-d')]); 
    }
    
    private function getNombreFestivo() {
        return $this->festivos_map[$this->fecha->format('Y-m-d')] ?? null;
    }

    private function esFinDeSemana() { $n = $this->fecha->format('N'); return ($n==6 || $n==7); }
    private function getDiaNombre() {
        $d = ['1'=>'Lunes','2'=>'Martes','3'=>'Miércoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sábado','7'=>'Domingo'];
        return $d[$this->fecha->format('N')];
    }
}
?>
