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
    
    <title><?php echo htmlspecialchars($title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($keywords); ?>">
    
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
        "name": "Pico y Placa Colombia - <?php echo $label_vehiculo_seo; ?>",
        "description": "Consulta en tiempo real el pico y placa para <?php echo $label_vehiculo_seo; ?>",
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
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 10px; color: #333; transition: background 0.3s; }
        body.pico-activo { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); }
        body.sin-pico { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); }
        .container { max-width: 1200px; margin: 0 auto; }
        
        header { text-align: center; color: white; margin-bottom: 20px; padding: 15px 10px; }
        h1 { font-size: clamp(1.5rem, 8vw, 3rem); margin-bottom: 8px; font-weight: 800; }
        .subtitle { font-size: clamp(0.85rem, 3vw, 1.1rem); opacity: 0.95; }
        
        /* TABS VEHÍCULOS */
        .vehicle-tabs { display: flex; justify-content: center; gap: 10px; margin: 15px 0 25px 0; flex-wrap: wrap; }
        .v-tab { text-decoration: none; color: rgba(255,255,255,0.8); border: 2px solid rgba(255,255,255,0.3); padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; transition: all 0.2s; white-space: nowrap; }
        .v-tab:hover { background: rgba(255,255,255,0.1); color: white; }
        .v-tab.active { background: white; color: #667eea; border-color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }

        .install-btn { position: absolute; top: 10px; right: 10px; background: white; color: #667eea; border: none; padding: 8px 16px; border-radius: 20px; font-weight: 600; cursor: pointer; font-size: 0.85rem; display: none; }
        .install-btn.show { display: block; }
        
        .date-search-section, .search-box, .restrictions-today { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); margin-bottom: 20px; }
        @media (min-width: 600px) { .date-search-section, .search-box, .restrictions-today { padding: 30px; } }
        
        .today-info { background: white; padding: 15px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 12px; }
        .info-card { padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; text-align: center; }
        .info-card h3 { font-size: 0.75rem; text-transform: uppercase; margin-bottom: 8px; }
        .info-card p { font-size: clamp(1rem, 4vw, 1.5rem); font-weight: 800; }
        
        .main-content { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        @media (max-width: 900px) { .main-content { grid-template-columns: 1fr; } }
        
        .cities-grid { display: flex; gap: 8px; overflow-x: auto; flex-wrap: nowrap; padding: 10px 0; }
        .city-btn { padding: 10px 15px; border: 2px solid #e0e0e0; background: white; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; min-height: 44px; white-space: nowrap; }
        .city-btn.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-color: #667eea; }
        
        input[type="text"], input[type="date"], select { padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem; min-height: 44px; }
        .btn-search { padding: 12px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; min-height: 44px; }
        
        .result-box { margin-top: 20px; padding: 20px; border-radius: 12px; display: none; }
        .result-box.show { display: block; }
        .result-success { background: #d4edda; border: 2px solid #28a745; color: #155724; }
        .result-restricted { background: #f8d7da; border: 2px solid #dc3545; color: #721c24; }
        
        .plates-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .plate-badge { background: #84fab0; color: #333; padding: 8px 14px; border-radius: 20px; font-weight: 700; }
        .plate-badge.restricted { background: #f093fb; color: white; }
        
        .info-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        
        /* COUNTDOWN */
        #countdownContainer { 
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(245,245,245,0.95) 100%);
            border: 3px solid #667eea;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.2);
            border-radius: 20px;
            padding: 30px 20px;
            margin: 20px 0;
            display: block !important;
        }
        #countdownTitle { color: #667eea; font-size: 1.3rem; font-weight: 700; text-align: center; margin-bottom: 25px; text-transform: uppercase; letter-spacing: 1.5px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .countdown-display { display: flex; justify-content: center; align-items: center; gap: 15px; flex-wrap: wrap; margin: 0; }
        .countdown-item { display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px; padding: 20px 25px; min-width: 90px; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3); animation: countdown-bounce 0.6s ease-in-out infinite; }
        .countdown-item:nth-child(1) { animation-delay: 0s; }
        .countdown-item:nth-child(3) { animation-delay: 0.1s; }
        .countdown-item:nth-child(5) { animation-delay: 0.2s; }
        .countdown-item div:first-child { font-size: 2.2rem; font-weight: 800; line-height: 1; margin-bottom: 8px; }
        .countdown-item small { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; opacity: 0.9; }
        .countdown-separator { font-size: 2rem; font-weight: 800; color: #667eea; margin: 0 5px; }
        @keyframes countdown-bounce { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.08); } }
        
        body.pico-activo #countdownContainer { border-color: #ff6b6b; box-shadow: 0 10px 40px rgba(255, 107, 107, 0.2); }
        body.pico-activo #countdownTitle { color: #ff6b6b; }
        body.pico-activo .countdown-item { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3); }
        body.pico-activo .countdown-separator { color: #ff6b6b; }
        
        body.sin-pico #countdownContainer { border-color: #27ae60; box-shadow: 0 10px 40px rgba(39, 174, 96, 0.2); }
        body.sin-pico #countdownTitle { color: #27ae60; }
        body.sin-pico .countdown-item { background: linear-gradient(135deg, #27ae60 0%, #229954 100%); box-shadow: 0 8px 25px rgba(39, 174, 96, 0.3); }
        body.sin-pico .countdown-separator { color: #27ae60; }
        
        .no-restriction { color: #28a745; font-weight: 700; }
        .has-restriction { color: #dc3545; font-weight: 700; }
        
        footer { text-align: center; color: white; padding: 15px; opacity: 0.9; }
        
        @media (max-width: 480px) { .date-search-section, .search-box, .restrictions-today { padding: 15px; } input, select { font-size: 16px; } }
                          
        /* PWA */
        #pwaBtnContainer { position: fixed; bottom: 70px; right: 15px; display: flex; flex-direction: column; gap: 10px; z-index: 9999; animation: slideUpPwa 0.5s ease-out; }
        #pwaBtnContainer.show { display: flex !important; }
        @keyframes slideUpPwa { from { opacity: 0; transform: translateY(100px); } to { opacity: 1; transform: translateY(0); } }
        
        .floating-install-btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 14px 18px; border-radius: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4); transition: all 0.3s; font-size: 1rem; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 8px; white-space: nowrap; min-width: 150px; justify-content: center; }
        .floating-install-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(102, 126, 234, 0.6); }
        .floating-install-btn-close { background: #ff6b6b; color: white; border: none; width: 50px; height: 50px; border-radius: 50%; font-weight: 700; cursor: pointer; box-shadow: 0 6px 16px rgba(255, 107, 107, 0.3); transition: all 0.3s; font-size: 1.3rem; font-family: 'Poppins', sans-serif; display: flex; align-items: center; justify-content: center; padding: 0; }
        .floating-install-btn-close:hover { transform: scale(1.1); }
        
        @media (max-width: 768px) { #pwaBtnContainer { bottom: 80px; right: 10px; left: 10px; flex-direction: row; gap: 8px; justify-content: flex-end; } .floating-install-btn-primary { flex: 1; min-width: auto; padding: 12px 14px; font-size: 0.95rem; } }
        @media (max-width: 480px) { #pwaBtnContainer { bottom: 70px; right: 8px; left: 8px; } .floating-install-btn-close { width: 40px; height: 40px; font-size: 1rem; } }
        
        #iosModalPwa { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; align-items: center; justify-content: center; padding: 20px; }
        #iosModalPwa.show { display: flex; }
        .ios-modal-content { background: white; padding: 25px; border-radius: 20px; max-width: 400px; text-align: center; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); animation: scaleIn 0.3s ease-out; }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
        .ios-modal-content h2 { color: #667eea; margin-bottom: 15px; }
        .ios-steps { text-align: left; background: #f5f5f5; padding: 15px; border-radius: 10px; margin: 15px 0; font-size: 0.9rem; }
        .ios-steps ol { margin: 10px 0; padding-left: 20px; }
        .ios-modal-close { background: #667eea; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; }
                                
        .subtitle { font-size: 1.1rem; opacity: 0.95; line-height: 1.5; transition: all 0.3s ease; }
        #cityNameSubtitle { transition: all 0.4s ease; }
        @media (max-width: 768px) { .subtitle { font-size: 0.95rem; } }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div id="pwaBtnContainer" style="display: none;">
                <button id="installPwaBtn" class="floating-install-btn-primary">⬇️ <span id="installBtnText">Instalar App</span></button>
                <button id="closePwaBtn" class="floating-install-btn-close">✕</button>
            </div>
            
            <h1 id="pageTitle">
            <?php if ($isDatePage): ?>
                🚗 Pico y placa el <?php echo ucfirst($dateData['dayNameEs']) . ' ' . $dateData['dayNum'] . ' de ' . ucfirst($dateData['monthName']); ?> en <?php echo htmlspecialchars($dateData['cityName']); ?>
            <?php else: ?>
                <?php echo ($vehiculo_sel == 'particular') ? "🚗 Pico y placa hoy en Bogotá" : "🚗 Pico y placa $label_vehiculo_seo hoy en Bogotá"; ?>
            <?php endif; ?>
            </h1>
            
            <?php if (!$isDatePage): ?>
            <div class="vehicle-tabs">
                <?php 
                $lista_vehiculos = $ciudades['bogota']['vehiculos'];
                foreach ($lista_vehiculos as $key => $vdata): 
                ?>
                <a href="?city=<?php echo $ciudad_sel_url; ?>&vehicle=<?php echo $key; ?>" 
                   class="v-tab <?php echo ($vehiculo_sel == $key) ? 'active' : ''; ?>">
                   <?php echo $vdata['label']; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!$isDatePage): ?>
            <p class="subtitle" id="dynamicSubtitle">
                Que no te pille el Poli 🚓 ni las Cámaras 📸 Mantente informado y 🪰 sobre las restricciones vehiculares en 
                <span id="cityNameSubtitle" style="color: #000000; font-weight: 900; background: rgba(255, 255, 255, 0.9); padding: 4px 10px; border-radius: 6px; text-shadow: none;">Bogotá</span>
                y evita perder hasta <span style="color: #000000; font-weight: 900; background: rgba(255, 255, 255, 0.9); padding: 2px 6px; border-radius: 4px; text-shadow: none;">$1.4 millones</span> 💸. Luego no 😩
            </p>
            <?php endif; ?>
        </header>
