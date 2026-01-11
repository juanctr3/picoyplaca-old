<?php
/**
 * Configuración Maestra - Pico y Placa Colombia 2025/2026
 * Soporte Multi-Vehículo y Lógicas Avanzadas
 */

$festivos = [
    '2025-01-01','2025-01-06','2025-03-24','2025-04-17','2025-04-18','2025-05-01','2025-05-29','2025-06-19','2025-06-23','2025-06-30','2025-07-20','2025-08-07','2025-08-18','2025-10-13','2025-11-03','2025-11-17','2025-12-08','2025-12-25',
    '2026-01-01','2026-01-06','2026-03-23','2026-04-02','2026-04-03','2026-05-01','2026-05-18','2026-06-08','2026-06-15','2026-06-22','2026-07-20','2026-08-07','2026-08-17','2026-10-12','2026-11-02','2026-11-16','2026-12-08','2026-12-25'
];

$ciudades = [
    'bogota' => [
        'nombre' => 'Bogotá',
        'vehiculos' => [
            'particular' => [
                'label' => 'Particulares',
                'logica' => 'bogota-particular',
                'horario' => '6:00 a.m. - 9:00 p.m.',
                'reglas' => ['impar' => [6,7,8,9,0], 'par' => [1,2,3,4,5]]
            ],
            'taxi' => [
                'label' => 'Taxis',
                'logica' => 'secuencia-laboral-sabado', // 1-2, 3-4... avanza Lun-Sab
                'horario' => '5:30 a.m. - 9:00 p.m.',
                'ref_fecha' => '2025-12-23', 'ref_digitos' => [1,2], 'salto' => 2,
                'aplica_festivos' => false, 'aplica_sabados' => true, 'aplica_domingos' => false
            ],
            'carga_20' => [
                'label' => 'Carga (>20 años)',
                'logica' => 'bogota-carga-sabado', // Solo sábados rotativo impar/par
                'horario' => '5:00 a.m. - 9:00 p.m. (Sábados)',
                'aplica_festivos' => false, 'aplica_sabados' => true
            ]
        ]
    ],
    'pereira' => [
        'nombre' => 'Pereira',
        'vehiculos' => [
            'particular' => [
                'label' => 'Particulares',
                'logica' => 'semanal-fijo',
                'horario' => '6:00 a.m. - 8:00 p.m.',
                'reglas' => ['Monday'=>[0,1], 'Tuesday'=>[2,3], 'Wednesday'=>[4,5], 'Thursday'=>[6,7], 'Friday'=>[8,9]]
            ],
            'moto' => [
                'label' => 'Motos',
                'logica' => 'semanal-fijo',
                'horario' => '6:00 a.m. - 8:00 p.m.',
                'reglas' => ['Monday'=>[0,1], 'Tuesday'=>[2,3], 'Wednesday'=>[4,5], 'Thursday'=>[6,7], 'Friday'=>[8,9]]
            ],
            'taxi' => [
                'label' => 'Taxis',
                'logica' => 'pereira-taxis', // Secuencia vertical +1
                'horario' => '7:00 a.m. - 3:00 a.m.',
                'aplica_festivos' => true, 'aplica_sabados' => false, 'aplica_domingos' => true
            ]
        ]
    ],
    'cali' => [
        'nombre' => 'Cali',
        'vehiculos' => [
            'particular' => [
                'label' => 'Particulares',
                'logica' => 'semanal-fijo',
                'horario' => '6:00 a.m. - 7:00 p.m.',
                'reglas' => ['Monday'=>[3,4], 'Tuesday'=>[5,6], 'Wednesday'=>[7,8], 'Thursday'=>[9,0], 'Friday'=>[1,2]]
            ],
            'tpc' => [
                'label' => 'TP Colectivo',
                'logica' => 'secuencia-continua', // Avanza todos los días +2
                'horario' => '5:00 a.m. - 10:00 p.m.',
                'ref_fecha' => '2025-12-23', 'ref_digitos' => [2,3], 'salto' => 2,
                'aplica_festivos' => true, 'aplica_sabados' => true, 'aplica_domingos' => true
            ]
        ]
    ],
    'medellin' => [
        'nombre' => 'Medellín',
        'vehiculos' => [
            'particular' => [
                'label' => 'Particulares',
                'logica' => 'semanal-fijo', 'horario' => '5:00 a.m. - 8:00 p.m.',
                'reglas' => ['Monday'=>[6,9], 'Tuesday'=>[5,7], 'Wednesday'=>[1,8], 'Thursday'=>[0,2], 'Friday'=>[3,4]]
            ],
            'moto' => [
                'label' => 'Motos',
                'logica' => 'semanal-fijo', 'horario' => '5:00 a.m. - 8:00 p.m.',
                'reglas' => ['Monday'=>[6,9], 'Tuesday'=>[5,7], 'Wednesday'=>[1,8], 'Thursday'=>[0,2], 'Friday'=>[3,4]]
            ]
        ]
    ],
    'armenia' => [
        'nombre' => 'Armenia',
        'vehiculos' => [
            'particular' => [
                'label' => 'Particulares',
                'logica' => 'semanal-fijo',
                'horario' => '7:00 a.m. - 7:00 p.m.',
                'reglas' => ['Monday'=>[5,6], 'Tuesday'=>[7,8], 'Wednesday'=>[9,0], 'Thursday'=>[1,2], 'Friday'=>[3,4]]
            ],
            'moto' => [
                'label' => 'Motos',
                'logica' => 'semanal-fijo',
                'horario' => '7:00 a.m. - 7:00 p.m.',
                'reglas' => ['Monday'=>[5,6], 'Tuesday'=>[7,8], 'Wednesday'=>[9,0], 'Thursday'=>[1,2], 'Friday'=>[3,4]]
            ],
            'taxi' => [
                'label' => 'Taxis',
                'logica' => 'armenia-taxis', // Secuencia +1 diaria, domingo consume 2
                'horario' => 'Todo el día',
                'aplica_festivos' => true, 'aplica_sabados' => true, 'aplica_domingos' => true
            ]
        ]
    ],
    'bucaramanga' => [
        'nombre' => 'Bucaramanga',
        'vehiculos' => [
            'particular' => [
                'label' => 'Particulares',
                'logica' => 'bucaramanga-rotacion-sabado', // Fijo L-V, Sábado rota
                'horario' => '6:00 a.m. - 8:00 p.m.',
                'reglas_lv' => ['Monday'=>[3,4], 'Tuesday'=>[5,6], 'Wednesday'=>[7,8], 'Thursday'=>[9,0], 'Friday'=>[1,2]],
                'ref_sabado_fecha' => '2025-11-29', 'ref_sabado_digitos' => [5,6]
            ],
            'moto' => [
                'label' => 'Motos',
                'logica' => 'bucaramanga-rotacion-sabado',
                'horario' => '6:00 a.m. - 8:00 p.m.',
                'reglas_lv' => ['Monday'=>[3,4], 'Tuesday'=>[5,6], 'Wednesday'=>[7,8], 'Thursday'=>[9,0], 'Friday'=>[1,2]],
                'ref_sabado_fecha' => '2025-11-29', 'ref_sabado_digitos' => [5,6]
            ],
            'tpc' => [
                'label' => 'TP Colectivo',
                'logica' => 'bucaramanga-rotacion-sabado',
                'horario' => '6:00 a.m. - 8:00 p.m.',
                'reglas_lv' => ['Monday'=>[3,4], 'Tuesday'=>[5,6], 'Wednesday'=>[7,8], 'Thursday'=>[9,0], 'Friday'=>[1,2]],
                'ref_sabado_fecha' => '2025-11-29', 'ref_sabado_digitos' => [5,6]
            ],
            'taxi' => [
                'label' => 'Taxis',
                'logica' => 'bucaramanga-taxis', // Rota semanalmente +2
                'horario' => '7:00 a.m. - 9:00 p.m.',
                'ref_fecha' => '2025-11-24', 'ref_digitos' => [1,2],
                'aplica_festivos' => false, 'aplica_sabados' => false, 'aplica_domingos' => false
            ]
        ]
    ],
    'cucuta' => [
        'nombre' => 'Cúcuta',
        'vehiculos' => [
            'particular' => [
                'label' => 'Particulares',
                'logica' => 'semanal-fijo',
                'horario' => '7am-8:30am, 11:30am-2:30pm, 5:30pm-7:30pm',
                'reglas' => ['Monday'=>[1,2], 'Tuesday'=>[3,4], 'Wednesday'=>[5,6], 'Thursday'=>[7,8], 'Friday'=>[9,0]]
            ],
            'taxi' => [
                'label' => 'Taxis',
                'logica' => 'secuencia-retroceso-laboral', // Retrocede 1 (4,3,2...) solo días hábiles
                'horario' => '7:00 a.m. - 11:00 p.m.',
                'ref_fecha' => '2025-11-24', 'ref_digitos' => [4],
                'aplica_festivos' => false, 'aplica_sabados' => false, 'aplica_domingos' => false
            ]
        ]
    ],
    'ibague' => [
        'nombre' => 'Ibagué',
        'vehiculos' => [
            'particular' => [
                'label' => 'Particulares',
                'logica' => 'ibague-semestral', // Lógica de rotación Enero/Junio
                'horario' => '6:00 a.m. - 9:00 p.m.',
                // Reglas Semestre 2-2025
                'semestre_actual' => ['Monday'=>[0,1], 'Tuesday'=>[2,3], 'Wednesday'=>[4,5], 'Thursday'=>[6,7], 'Friday'=>[8,9]],
                // Reglas Semestre 1-2026 (Rotación)
                'semestre_siguiente' => ['Monday'=>[8,9], 'Tuesday'=>[0,1], 'Wednesday'=>[2,3], 'Thursday'=>[4,5], 'Friday'=>[6,7]],
                'fecha_cambio' => '2026-01-01'
            ],
            'tpc' => [
                'label' => 'TP Colectivo',
                'logica' => 'ibague-tpc', // Ciclo 5 pares repetitivo
                'horario' => 'Todo el día',
                'ciclo' => [[1,2], [0,3], [4,9], [5,6], [7,8]],
                'ref_fecha' => '2025-11-24'
            ],
            'taxi' => [
                'label' => 'Taxis',
                'logica' => 'secuencia-continua', // +1 diario
                'horario' => 'Todo el día',
                'ref_fecha' => '2025-12-25', 'ref_digitos' => [1], 'salto' => 1,
                'aplica_festivos' => true, 'aplica_sabados' => true, 'aplica_domingos' => true
            ]
        ]
    ],
    'santa_marta' => [
        'nombre' => 'Santa Marta',
        'vehiculos' => [
            'particular' => [
                'label' => 'Particulares',
                'logica' => 'semanal-fijo', 'horario' => '7am-9am | 11:30am-2pm | 5pm-8pm',
                'reglas' => ['Monday'=>[1,2], 'Tuesday'=>[3,4], 'Wednesday'=>[5,6], 'Thursday'=>[7,8], 'Friday'=>[9,0]]
            ],
            'moto' => [
                'label' => 'Motos',
                'logica' => 'semanal-fijo', 'horario' => '7:00 a.m. - 8:00 p.m.',
                'reglas' => ['Monday'=>[1,2], 'Tuesday'=>[3,4], 'Wednesday'=>[5,6], 'Thursday'=>[7,8], 'Friday'=>[9,0]]
            ],
            'taxi' => [
                'label' => 'Taxis',
                'logica' => 'santa-marta-taxis', // Base semanal -2, diario +2
                'horario' => 'Todo el día',
                'aplica_festivos' => false, 'aplica_sabados' => true, 'aplica_domingos' => false
            ]
        ]
    ],
    'villavicencio' => [
        'nombre' => 'Villavicencio',
        'vehiculos' => [
            'particular' => [
                'label' => 'Particulares',
                'logica' => 'semanal-fijo', 'horario' => '6:30-9:30am | 5-8pm',
                'reglas' => ['Monday'=>[7,8], 'Tuesday'=>[9,0], 'Wednesday'=>[1,2], 'Thursday'=>[3,4], 'Friday'=>[5,6]]
            ],
            'carga' => [
                'label' => 'Carga',
                'logica' => 'villavicencio-carga', // Todos restringidos en horario
                'horario' => '6-8am | 5-7:30pm',
                'aplica_festivos' => false, 'aplica_sabados' => false, 'aplica_domingos' => false
            ],
            'taxi' => [
                'label' => 'Taxis',
                'logica' => 'secuencia-continua', // +1 diario
                'horario' => '6:00 a.m. - 12:00 p.m.',
                'ref_fecha' => '2025-11-24', 'ref_digitos' => [0], 'salto' => 1,
                'aplica_festivos' => true, 'aplica_sabados' => true, 'aplica_domingos' => true
            ]
        ]
    ],
    'barranquilla' => [
        'nombre' => 'Barranquilla',
        'vehiculos' => [
            'particular' => [
                'label' => 'Particulares',
                'logica' => 'sin-restriccion', 'horario' => 'Sin restricción', 'reglas' => []
            ],
            'taxi' => [
                'label' => 'Taxis',
                'logica' => 'secuencia-laboral-sabado', // Avanza Mon-Sab, aplica Mon-Fri
                'horario' => 'Todo el día',
                'ref_fecha' => '2025-12-23', 'ref_digitos' => [9,0], 'salto' => 2,
                'aplica_festivos' => false, 'aplica_sabados' => false, 'aplica_domingos' => false
            ]
        ]
    ]
];

return ['ciudades' => $ciudades, 'festivos' => $festivos];
?>