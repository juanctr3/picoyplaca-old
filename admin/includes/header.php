<?php
// Obtener el nombre del archivo actual para marcar el menú activo
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Pico y Placa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --text-dark: #2d3748;
            --text-light: #718096;
            --bg-light: #f7fafc;
            --white: #ffffff;
            --danger: #e53e3e;
            --success: #48bb78;
            --warning: #ecc94b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            height: 100vh;
            display: flex;
            overflow: hidden; /* Evitamos scroll en el body entero */
        }

        /* --- SIDEBAR (Barra Lateral) --- */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            display: flex;
            flex-direction: column;
            padding: 20px;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            z-index: 100;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .brand {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
        }
        
        .nav-menu {
            list-style: none;
            flex: 1;
            padding: 0;
        }
        
        .nav-item { margin-bottom: 8px; }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: var(--white);
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: var(--white);
            color: var(--primary);
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .user-info {
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
            font-size: 0.85rem;
            opacity: 0.9;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .btn-logout {
            color: #ffcccc;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s;
        }
        .btn-logout:hover { color: var(--white); }

        /* --- MAIN CONTENT (Contenido Principal) --- */
        .main-content {
            flex: 1;
            overflow-y: auto; /* El scroll ocurre solo aquí */
            padding: 30px;
            position: relative;
            background-color: var(--bg-light);
        }

        /* --- COMPONENTES GLOBALES --- */
        .admin-content-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 50px; }
        
        .admin-page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-titles h2 { font-size: 1.8rem; font-weight: 700; color: var(--text-dark); margin-bottom: 5px; }
        .subtitle { color: var(--text-light); font-size: 0.95rem; }

        /* Cards */
        .card { background: var(--white); border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 25px; overflow: hidden; border: 1px solid #edf2f7; }
        .card-header-flex { padding: 20px 25px; border-bottom: 1px solid #edf2f7; display: flex; justify-content: space-between; align-items: center; }
        .card-header-simple { padding: 20px 25px; border-bottom: 1px solid #edf2f7; }
        .card-title { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); margin: 0; }
        
        /* Botones */
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; font-size: 0.9rem; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .btn-primary { background: var(--primary); color: var(--white); }
        .btn-secondary { background: #e2e8f0; color: var(--text-dark); }
        .btn-success { background: var(--success); color: var(--white); }
        .btn-danger { background: var(--danger); color: var(--white); }
        .btn-block { width: 100%; justify-content: center; }
        
        .btn-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; text-decoration: none; transition: background 0.2s; }
        .btn-icon-sm { width: 24px; height: 24px; font-size: 12px; padding: 0; border: none; border-radius: 4px; cursor: pointer; }

        .btn-edit { background: #ebf8ff; color: #3182ce; }
        .btn-edit:hover { background: #bee3f8; }
        .btn-delete { background: #fff5f5; color: #e53e3e; }
        .btn-delete:hover { background: #fed7d7; }

        /* Formularios */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.9rem; color: var(--text-dark); }
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; font-family: inherit; transition: border-color 0.2s; background: white; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .text-danger { color: var(--danger); }
        .text-muted { color: var(--text-light); }
        
        /* Tablas */
        .table-responsive { overflow-x: auto; }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th { text-align: left; padding: 15px 20px; background: #f8fafc; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; color: var(--text-light); border-bottom: 2px solid #edf2f7; }
        .admin-table td { padding: 15px 20px; border-bottom: 1px solid #edf2f7; vertical-align: middle; }
        .admin-table tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }

        /* Badges */
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .badge-gray { background: #e2e8f0; color: #4a5568; }
        .badge-blue { background: #ebf8ff; color: #2b6cb0; }
        
        /* Estados vacíos */
        .empty-state { text-align: center; padding: 50px 20px; }
        .empty-state-small { text-align: center; padding: 30px; color: var(--text-light); background: #f8fafc; border-radius: 10px; border: 2px dashed #e2e8f0; }
        .empty-icon { font-size: 3rem; margin-bottom: 15px; opacity: 0.5; }

        /* Vehículo Card (Editar) */
        .vehiculo-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 15px; transition: all 0.2s; position: relative; }
        .vehiculo-card:hover { border-color: #cbd5e0; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .v-header { padding: 10px 15px; background: #edf2f7; border-bottom: 1px solid #e2e8f0; border-radius: 10px 10px 0 0; display: flex; justify-content: space-between; align-items: center; font-weight: 600; font-size: 0.9rem; color: #4a5568; }
        .v-body { padding: 15px; }
        .v-drag-handle { cursor: move; margin-right: 10px; color: #a0aec0; }
        .form-row { display: flex; gap: 15px; margin-bottom: 10px; }
        .form-row .col { flex: 1; }
        
        /* Alerts */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid transparent; font-size: 0.95rem; }
        .alert-success { background: #f0fff4; color: #276749; border-color: #c6f6d5; }
        .alert-danger { background: #fff5f5; color: #c53030; border-color: #fed7d7; }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            body { flex-direction: column; height: auto; overflow: auto; }
            .sidebar { width: 100%; padding: 15px; flex-direction: row; justify-content: space-between; align-items: center; position: sticky; top: 0; }
            .brand { margin-bottom: 0; font-size: 1.2rem; }
            .nav-menu { display: none; } /* En móvil simplificado ocultamos menú o necesitaríamos JS para toggle. */
            
            /* Solución simple móvil: Menú horizontal scrollable */
            .sidebar { display: block; overflow-x: auto; white-space: nowrap; padding: 10px; }
            .brand { display: inline-block; margin-right: 20px; margin-bottom: 10px; }
            .nav-menu { display: flex; gap: 5px; overflow-x: auto; padding-bottom: 5px; }
            .nav-item { display: inline-block; margin: 0; }
            .nav-link { padding: 8px 12px; font-size: 0.85rem; }
            .user-info { display: none; } /* Ocultar info usuario en móvil para ahorrar espacio */
            
            .main-content { padding: 15px; overflow: visible; }
            .form-row { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">
            🚗 PicoYPlaca <span style="font-weight:400; font-size:0.8em; opacity:0.8;">Admin</span>
        </div>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>">
                    📊 Inicio
                </a>
            </li>
            <li class="nav-item">
                <a href="ciudades.php" class="nav-link <?= ($current_page == 'ciudades.php' || $current_page == 'ciudad_editar.php') ? 'active' : '' ?>">
                    🏙️ Ciudades
                </a>
            </li>
            <li class="nav-item">
                <a href="festivos.php" class="nav-link <?= ($current_page == 'festivos.php') ? 'active' : '' ?>">
                    📅 Festivos
                </a>
            </li>
            <li class="nav-item">
                <a href="alertas.php" class="nav-link <?= ($current_page == 'alertas.php') ? 'active' : '' ?>">
                    📢 Alertas
                </a>
            </li>
            <li class="nav-item" style="margin-top: 20px;">
                <a href="../" target="_blank" class="nav-link">
                    🌐 Ver Sitio Web
                </a>
            </li>
        </ul>

        <div class="user-info">
            <div>Usuario: <strong>admin</strong></div>
            <a href="logout.php" class="btn-logout">🔓 Cerrar Sesión</a>
        </div>
    </aside>

    <main class="main-content">
