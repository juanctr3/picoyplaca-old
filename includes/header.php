<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Pico y PL">
    <meta name="language" content="es-CO">
    <meta name="author" content="Pico y Placa Colombia">
    <meta name="theme-color" content="#667eea">
    <meta name="robots" content="index, follow">
    
    <link rel="manifest" href="/manifest.json">
    <link rel="sitemap" type="application/xml" href="/sitemap.xml.php">
    
    <link rel="stylesheet" href="styles.css?v=4.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <title><?php echo htmlspecialchars($title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
    
    <link rel="canonical" href="<?php echo $canonical; ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 192 192'><rect fill='%23667eea' width='192' height='192'/><text x='50%' y='50%' font-size='120' font-weight='bold' text-anchor='middle' dy='.3em' fill='white' font-family='Arial'>🚗</text></svg>">
    
    <meta property="og:title" content="<?php echo htmlspecialchars($title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($description); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $canonical; ?>">
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "Pico y Placa Colombia",
        "description": "Consulta en tiempo real el pico y placa en Colombia.",
        "url": "<?php echo $canonical; ?>",
        "applicationCategory": "UtilityApplication",
        "offers": {"@type": "Offer", "price": "0"}
    }
    </script>
    
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-2L2EV10ZWW"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-2L2EV10ZWW');
    </script>
</head>
<body>
    <div class="container">
        <header>
            <h1 id="pageTitle">
            <?php if ($isDatePage): ?>
                🚗 Pico y placa el <?php echo ucfirst($dateData['dayNameEs']) . ' ' . $dateData['dayNum'] . ' de ' . ucfirst($dateData['monthName']); ?> en <?php echo htmlspecialchars($dateData['cityName']); ?>
            <?php else: ?>
                <?php 
                // Título dinámico servidor-side (coincide con la ciudad seleccionada)
                $vLabel = ($vehiculo_sel == 'particular') ? '' : $nombre_vehiculo_seo;
                echo "🚗 Pico y placa $vLabel hoy en $ciudad_nombre_seo"; 
                ?>
            <?php endif; ?>
            </h1>
            
            <?php if (!$isDatePage): ?>
            <p class="subtitle" id="dynamicSubtitle">
                Evita multas y mantente informado sobre las restricciones vehiculares en 
                <span id="cityNameSubtitle" style="font-weight: 800; border-bottom: 2px solid rgba(255,255,255,0.4);">
                    <?php echo htmlspecialchars($ciudad_nombre_seo); ?>
                </span>. 
                <br>Consulta horarios, rotación y excepciones al instante ⚡.
            </p>
            <?php endif; ?>
        </header>
