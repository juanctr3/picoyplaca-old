<?php
/**
 * SITEMAP.XML.PHP - Generador de Sitemap XML Dinámico
 * Genera automáticamente URLs de pico y placa para los próximos 30 días
 * 
 * Acceso: https://picoyplacabogota.com.co/sitemap.xml.php
 * O: https://picoyplacabogota.com.co/sitemap.xml (con rewrite en .htaccess)
 */

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=86400'); // 24 horas

$config = require_once 'config-ciudades.php';
$ciudades = $config['ciudades'];

$dominio = 'https://picoyplacabogota.com.co';
$hoy = new DateTime();
$proximosDias = 30;

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0">' . "\n";

// ==========================================
// 1. URL HOME - Prioridad Alta
// ==========================================
echo "  <!-- HOME -->\n";
echo "  <url>\n";
echo "    <loc>$dominio/</loc>\n";
echo "    <lastmod>" . $hoy->format('Y-m-d') . "</lastmod>\n";
echo "    <changefreq>daily</changefreq>\n";
echo "    <priority>1.0</priority>\n";
echo "    <mobile:mobile/>\n";
echo "  </url>\n\n";

// ==========================================
// 2. URLS DINÁMICAS - Próximos 30 días x todas las ciudades
// ==========================================
echo "  <!-- PRÓXIMOS 30 DÍAS DE PICO Y PLACA -->\n";

$interval = new DateInterval('P1D');
$startDate = clone $hoy;
$endDate = clone $hoy;
$endDate->modify("+$proximosDias days");

$period = new DatePeriod($startDate, $interval, $endDate);
$urlCount = 0;

foreach ($period as $date) {
    foreach ($ciudades as $codigo => $ciudad) {
        // Limitar a 50,000 URLs (límite de Google)
        if ($urlCount >= 50000) {
            break 2;
        }
        
        $yearStr = $date->format('Y');
        $monthStr = $date->format('m');
        $dayStr = $date->format('d');
        $dateStr = "$yearStr-$monthStr-$dayStr";
        
        // Calcular prioridad según proximidad
        $diasDiferencia = $hoy->diff($date)->days;
        
        if ($diasDiferencia === 0) {
            // HOY - Máxima prioridad
            $priority = '0.95';
            $changefreq = 'hourly';
        } elseif ($diasDiferencia <= 3) {
            // Próximos 3 días - Alta prioridad
            $priority = '0.85';
            $changefreq = 'daily';
        } elseif ($diasDiferencia <= 7) {
            // Esta semana - Media-alta prioridad
            $priority = '0.75';
            $changefreq = 'daily';
        } elseif ($diasDiferencia <= 14) {
            // Próximas 2 semanas - Media prioridad
            $priority = '0.65';
            $changefreq = 'weekly';
        } else {
            // Resto - Baja prioridad
            $priority = '0.50';
            $changefreq = 'weekly';
        }
        
        echo "  <url>\n";
        echo "    <loc>$dominio/pico-y-placa/$dateStr-$codigo</loc>\n";
        echo "    <lastmod>" . $date->format('Y-m-d') . "</lastmod>\n";
        echo "    <changefreq>$changefreq</changefreq>\n";
        echo "    <priority>$priority</priority>\n";
        echo "    <mobile:mobile/>\n";
        echo "  </url>\n";
        
        $urlCount++;
    }
}

echo "\n";

// ==========================================
// 3. CIUDADES PRINCIPALES - Prioridad Alta
// ==========================================
echo "  <!-- PÁGINAS DE CIUDADES -->\n";

$ciudadesPrincipales = ['bogota', 'medellin', 'cali', 'barranquilla', 'cartagena'];

foreach ($ciudadesPrincipales as $codigo) {
    if (isset($ciudades[$codigo])) {
        $ciudad = $ciudades[$codigo];
        echo "  <url>\n";
        echo "    <loc>$dominio/ciudad/$codigo</loc>\n";
        echo "    <lastmod>" . $hoy->format('Y-m-d') . "</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.8</priority>\n";
        echo "    <mobile:mobile/>\n";
        echo "  </url>\n";
    }
}

echo "\n";

// ==========================================
// 4. INFORMACIÓN Y PÁGINAS ESTÁTICAS
// ==========================================
echo "  <!-- PÁGINAS ESTÁTICAS -->\n";

$paginas_estaticas = [
    ['url' => '/info', 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['url' => '/api', 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['url' => '/privacidad', 'changefreq' => 'yearly', 'priority' => '0.4'],
    ['url' => '/terminos', 'changefreq' => 'yearly', 'priority' => '0.4'],
];

foreach ($paginas_estaticas as $pagina) {
    echo "  <url>\n";
    echo "    <loc>$dominio" . $pagina['url'] . "</loc>\n";
    echo "    <lastmod>" . $hoy->format('Y-m-d') . "</lastmod>\n";
    echo "    <changefreq>" . $pagina['changefreq'] . "</changefreq>\n";
    echo "    <priority>" . $pagina['priority'] . "</priority>\n";
    echo "    <mobile:mobile/>\n";
    echo "  </url>\n";
}

echo "\n";

// ==========================================
// 5. API ENDPOINTS (Informativo)
// ==========================================
echo "  <!-- API ENDPOINTS -->\n";

$api_endpoints = [
    '/api.php?action=ciudades',
    '/api.php?action=status',
];

foreach ($api_endpoints as $endpoint) {
    echo "  <url>\n";
    echo "    <loc>$dominio$endpoint</loc>\n";
    echo "    <lastmod>" . $hoy->format('Y-m-d') . "</lastmod>\n";
    echo "    <changefreq>daily</changefreq>\n";
    echo "    <priority>0.5</priority>\n";
    echo "  </url>\n";
}

echo "</urlset>";

// ==========================================
// ESTADÍSTICAS (opcional, para debugging)
// ==========================================
// Descomenta para ver estadísticas en la consola:
/*
error_log("=== SITEMAP GENERADO ===");
error_log("URLs generadas: " . $urlCount);
error_log("Ciudades: " . count($ciudades));
error_log("Días: " . $proximosDias);
error_log("URL total: " . ($urlCount + count($ciudadesPrincipales) + count($paginas_estaticas) + count($api_endpoints) + 1));
error_log("Tamaño aproximado: " . round(($urlCount * 0.2), 2) . " KB");
error_log("=======================");
*/

?>