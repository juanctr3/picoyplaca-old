<?php
/**
 * admin/login.php
 * Pantalla de acceso al panel de administración.
 */

session_start();

// 1. Configuración de Acceso (CAMBIA ESTO)
// ---------------------------------------------------------
$USUARIO_ADMIN = 'admin';
$PASSWORD_ADMIN = 'admin123'; // ¡Por favor, cambia esto antes de subir a producción!
// ---------------------------------------------------------

// 2. Si ya está logueado, enviar al Dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// 3. Procesar el formulario
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['usuario'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($user === $USUARIO_ADMIN && $pass === $PASSWORD_ADMIN) {
        // Login exitoso
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_time'] = time();
        
        // Redirigir
        header('Location: index.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Pico y Placa Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .icon { font-size: 3rem; margin-bottom: 10px; display: block; }
        h1 { font-size: 1.5rem; color: #333; margin-bottom: 5px; font-weight: 700; }
        p { color: #666; font-size: 0.9rem; margin-bottom: 30px; }
        
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.85rem; color: #4a5568; }
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            outline: none;
            font-family: inherit;
        }
        input:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(118, 75, 162, 0.3); }
        
        .error-msg {
            background: #fff5f5;
            color: #c53030;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            border: 1px solid #fed7d7;
        }
        
        .back-link { margin-top: 20px; display: block; font-size: 0.85rem; color: #a0aec0; text-decoration: none; }
        .back-link:hover { color: #667eea; }
    </style>
</head>
<body>

    <div class="login-card">
        <span class="icon">🔐</span>
        <h1>Acceso Admin</h1>
        <p>Gestiona el sistema de Pico y Placa</p>

        <?php if ($error): ?>
            <div class="error-msg">⚠️ <?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="usuario" placeholder="Ej: admin" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit">Entrar al Panel</button>
        </form>

        <a href="/" class="back-link">← Volver al sitio web</a>
    </div>

</body>
</html>
