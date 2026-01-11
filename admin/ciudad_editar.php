<?php
/**
 * admin/ciudad_editar.php
 * Formulario maestro para Crear o Editar una ciudad y sus vehículos.
 */

require_once 'auth.php';

// 1. Cargar datos
$archivo_datos = __DIR__ . '/../datos/config.json';
$ciudades = [];

if (file_exists($archivo_datos)) {
    $json_content = file_get_contents($archivo_datos);
    $ciudades = json_decode($json_content, true) ?? [];
}

// 2. Detectar si editamos o creamos
$id_ciudad = $_GET['id'] ?? null;
$ciudad = null;
$es_nuevo = true;

if ($id_ciudad && isset($ciudades[$id_ciudad])) {
    $ciudad = $ciudades[$id_ciudad];
    $es_nuevo = false;
}

// Valores por defecto
$nombre_ciudad = $ciudad['nombre'] ?? '';
$vehiculos = $ciudad['vehiculos'] ?? [];

include 'includes/header.php';
?>

<div class="admin-content-wrapper">
    
    <div class="admin-page-header">
        <div class="header-titles">
            <h2><?= $es_nuevo ? '✨ Nueva Ciudad' : '✏️ Editar Ciudad' ?></h2>
            <p class="subtitle"><?= $es_nuevo ? 'Configura una nueva zona de restricción.' : 'Modificando reglas para: <strong>' . htmlspecialchars($nombre_ciudad) . '</strong>' ?></p>
        </div>
        <div class="header-actions">
            <a href="ciudades.php" class="btn btn-secondary">← Volver</a>
        </div>
    </div>

    <form action="procesar.php" method="POST" id="formCiudad">
        <input type="hidden" name="accion" value="guardar_ciudad">
        <input type="hidden" name="id_original" value="<?= htmlspecialchars($id_ciudad ?? '') ?>">

        <div class="card mb-4">
            <h3 class="card-title">📍 Datos Generales</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="id_slug">Identificador (URL Slug) <span class="text-danger">*</span></label>
                    <input type="text" name="id_slug" id="id_slug" class="form-control" 
                           value="<?= htmlspecialchars($id_ciudad ?? '') ?>" 
                           placeholder="ej: bogota" 
                           <?= !$es_nuevo ? 'readonly' : '' ?> required pattern="[a-z0-9-_]+">
                    <small class="form-text">Solo minúsculas, números y guiones. No se puede cambiar después.</small>
                </div>
                <div class="form-group">
                    <label for="nombre">Nombre de la Ciudad <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="nombre" class="form-control" 
                           value="<?= htmlspecialchars($nombre_ciudad) ?>" 
                           placeholder="ej: Bogotá D.C." required>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header-flex">
                <h3 class="card-title">🚗 Vehículos y Reglas</h3>
                <button type="button" class="btn btn-sm btn-primary" onclick="agregarVehiculo()">+ Agregar Vehículo</button>
            </div>
            
            <div id="contenedor-vehiculos">
                <?php 
                // Renderizar vehículos existentes
                if (!empty($vehiculos)) {
                    foreach ($vehiculos as $key => $v) {
                        // Extraemos la configuración extra (todo lo que no sea label, logica, horario)
                        $config_extra = array_diff_key($v, array_flip(['label', 'logica', 'horario']));
                        $json_config = json_encode($config_extra, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        include 'includes/vehiculo_template.php'; // Usaremos un template parcial
                    }
                }
                ?>
            </div>
            
            <?php if(empty($vehiculos)): ?>
                <div id="empty-vehiculos" class="empty-state-small">
                    No hay vehículos configurados. Haz clic en "+ Agregar Vehículo".
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions-footer">
            <button type="submit" class="btn btn-lg btn-success">💾 Guardar Cambios</button>
        </div>
    </form>
</div>

<template id="template-vehiculo">
    <?php 
    // Variables dummy para el template
    $key = ''; 
    $v = ['label'=>'', 'logica'=>'semanal-fijo', 'horario'=>'']; 
    $json_config = "{\n    \"reglas\": {\n        \"Monday\": [1,2],\n        \"Tuesday\": [3,4],\n        \"Wednesday\": [5,6],\n        \"Thursday\": [7,8],\n        \"Friday\": [9,0]\n    }\n}";
    include 'includes/vehiculo_template.php'; 
    ?>
</template>

<script>
    // Script para manejar la adición dinámica de vehículos
    function agregarVehiculo() {
        const contenedor = document.getElementById('contenedor-vehiculos');
        const template = document.getElementById('template-vehiculo');
        const emptyState = document.getElementById('empty-vehiculos');

        // Clonar el template
        const clone = template.content.cloneNode(true);
        
        // Generar un ID temporal único para los inputs
        const uniqueId = 'new_' + Date.now();
        // (Aquí podríamos ajustar nombres de inputs si fuera necesario, pero usaremos arrays en PHP)
        
        contenedor.appendChild(clone);
        if(emptyState) emptyState.style.display = 'none';
    }

    function eliminarVehiculo(btn) {
        if(confirm('¿Eliminar este vehículo?')) {
            btn.closest('.vehiculo-card').remove();
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
