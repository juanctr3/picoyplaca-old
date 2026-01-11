<?php
/**
 * admin/ciudades.php
 * Listado general de ciudades y gestión principal.
 */

// 1. Verificación de seguridad
require_once 'auth.php';

// 2. Cargar datos actuales
$archivo_datos = __DIR__ . '/../datos/config.json';
$ciudades = [];

if (file_exists($archivo_datos)) {
    $contenido = file_get_contents($archivo_datos);
    $ciudades = json_decode($contenido, true) ?? [];
}

// 3. Incluir Header del Admin
include 'includes/header.php';
?>

<div class="admin-content-wrapper">
    
    <div class="admin-page-header">
        <div class="header-titles">
            <h2>🏙️ Gestión de Ciudades</h2>
            <p class="subtitle">Administra las ciudades y sus reglas de Pico y Placa.</p>
        </div>
        <div class="header-actions">
            <a href="ciudad_editar.php" class="btn btn-primary">
                ➕ Agregar Nueva Ciudad
            </a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($ciudades)): ?>
        <div class="empty-state">
            <div class="empty-icon">🌍</div>
            <h3>No hay ciudades configuradas</h3>
            <p>El sistema está vacío. Comienza agregando la primera ciudad.</p>
            <a href="ciudad_editar.php" class="btn btn-primary">Crear Ciudad</a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th width="15%">ID (Slug)</th>
                            <th width="25%">Nombre</th>
                            <th width="40%">Vehículos Configurados</th>
                            <th width="20%" class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ciudades as $id => $ciudad): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-gray"><?= htmlspecialchars($id) ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($ciudad['nombre']) ?></strong>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($ciudad['vehiculos'])) {
                                        $labels = [];
                                        foreach($ciudad['vehiculos'] as $v) {
                                            $labels[] = $v['label'];
                                        }
                                        // Muestra etiquetas bonitas
                                        foreach($labels as $l) {
                                            echo "<span class='badge badge-blue'>$l</span> ";
                                        }
                                    } else {
                                        echo '<span class="text-muted text-sm">Sin vehículos</span>';
                                    }
                                    ?>
                                </td>
                                <td class="actions-cell text-right">
                                    <a href="ciudad_editar.php?id=<?= urlencode($id) ?>" class="btn-icon btn-edit" title="Editar">
                                        ✏️
                                    </a>
                                    <a href="procesar.php?accion=eliminar_ciudad&id=<?= urlencode($id) ?>" 
                                       class="btn-icon btn-delete" 
                                       title="Eliminar"
                                       onclick="return confirm('¿Estás seguro de que deseas eliminar <?= htmlspecialchars($ciudad['nombre']) ?>? Esta acción no se puede deshacer.');">
                                        🗑️
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
