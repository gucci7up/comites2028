<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'includes/estadisticas.php';
require_once 'db/config.php';

// Verificar que el usuario esté autenticado
requiereAutenticacion();

// Obtener estadísticas para el dashboard
$total_comites = obtenerTotalComites();
$total_miembros = obtenerTotalMiembros();
$total_coordinadores = obtenerTotalCoordinadores();
$comites_recientes = obtenerComitesRecientes(5);
$miembros_recientes = obtenerMiembrosRecientes(5);
$estadisticas_municipio = obtenerEstadisticasPorMunicipio(5);
$estadisticas_usuario = obtenerEstadisticasPorUsuario(5);

// Obtener comités creados por el usuario actual
$conn = conectarDB();
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM comites WHERE creado_por = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$resultado = $stmt->get_result();
$fila = $resultado->fetch_assoc();
$mis_comites = $fila['total'];

// Título de la página
$titulo_pagina = 'Dashboard';

// Incluir el header
include 'includes/header.php';
?>

<!-- Dashboard Content -->
<div class="row mb-4">
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Comités</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_comites; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Miembros</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_miembros; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-friends fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Coordinadores</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $total_coordinadores; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Mis Comités</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $mis_comites; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Segunda fila de estadísticas -->
<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Comités por Municipio</h6>
            </div>
            <div class="card-body">
                <?php if (count($estadisticas_municipio) > 0): ?>
                <div class="chart-bar">
                    <canvas id="municipiosChart"></canvas>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <p>No hay datos suficientes para mostrar estadísticas.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Comités por Usuario</h6>
            </div>
            <div class="card-body">
                <?php if (count($estadisticas_usuario) > 0): ?>
                <div class="chart-pie">
                    <canvas id="usuariosChart"></canvas>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <p>No hay datos suficientes para mostrar estadísticas.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Comités Recientes -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Comités Recientes</h6>
        <a href="comites.php" class="btn btn-sm btn-primary">
            <i class="fas fa-list fa-sm"></i> Ver Todos
        </a>
    </div>
    <div class="card-body">
        <?php if (count($comites_recientes) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Municipio</th>
                        <th>Coordinador</th>
                        <th>Miembros</th>
                        <th>Creado por</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comites_recientes as $comite): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($comite['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($comite['municipio']); ?></td>
                        <td>
                            <?php 
                            // Buscar coordinador en la tabla comite_coordinador
                            $stmt = $conn->prepare("SELECT c.nombre_completo FROM coordinadores c 
                                                    JOIN comite_coordinador cc ON c.id = cc.coordinador_id 
                                                    WHERE cc.comite_id = ?");
                            $stmt->bind_param("i", $comite['id']);
                            $stmt->execute();
                            $resultado = $stmt->get_result();
                            $coordinador = $resultado->fetch_assoc();
                            echo $coordinador ? htmlspecialchars($coordinador['nombre_completo']) : 'No asignado';
                            ?>
                        </td>
                        <td>
                            <?php 
                            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM comite_miembro WHERE comite_id = ?");
                            $stmt->bind_param("i", $comite['id']);
                            $stmt->execute();
                            $resultado = $stmt->get_result();
                            $fila = $resultado->fetch_assoc();
                            echo $fila['total']; 
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($comite['creador_nombre']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($comite['fecha_creacion'])); ?></td>
                        <td>
                            <a href="ver_comite.php?id=<?php echo $comite['id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="editar_comite.php?id=<?php echo $comite['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-folder-open fa-3x text-gray-300 mb-3"></i>
            <p>No hay comités creados aún.</p>
            <a href="crear_comite.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Crear Comité
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Miembros Recientes -->
    <div class="col-md-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Miembros Recientes</h6>
            </div>
            <div class="card-body">
                <?php if (count($miembros_recientes) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Cédula</th>
                                <th>Comité</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($miembros_recientes as $miembro): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($miembro['nombre_completo']); ?></td>
                                <td><?php echo htmlspecialchars($miembro['cedula']); ?></td>
                                <td><?php echo htmlspecialchars($miembro['comite_nombre'] ?? 'No asignado'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($miembro['fecha_registro'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <p>No hay miembros registrados aún.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Actividad Reciente -->
    <div class="col-md-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Actividad Reciente</h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php 
                    // Obtener actividad reciente (últimas 5 acciones)
                    $stmt = $conn->prepare("SELECT c.nombre as comite_nombre, c.id as comite_id, u.nombre as usuario_nombre, 
                                          c.fecha_creacion, c.fecha_modificacion 
                                          FROM comites c 
                                          LEFT JOIN usuarios u ON c.creado_por = u.id 
                                          ORDER BY GREATEST(c.fecha_creacion, IFNULL(c.fecha_modificacion, '1900-01-01')) DESC 
                                          LIMIT 5");
                    $stmt->execute();
                    $resultado = $stmt->get_result();
                    $actividades = [];
                    while ($fila = $resultado->fetch_assoc()) {
                        $actividades[] = $fila;
                    }
                    
                    if (count($actividades) > 0):
                        foreach ($actividades as $actividad):
                            $es_creacion = $actividad['fecha_modificacion'] === null || $actividad['fecha_creacion'] > $actividad['fecha_modificacion'];
                            $fecha = $es_creacion ? $actividad['fecha_creacion'] : $actividad['fecha_modificacion'];
                    ?>
                    <div class="timeline-item">
                        <div class="timeline-item-marker">
                            <div class="timeline-item-marker-indicator bg-<?php echo $es_creacion ? 'success' : 'primary'; ?>">
                                <i class="fas fa-<?php echo $es_creacion ? 'plus' : 'edit'; ?>"></i>
                            </div>
                        </div>
                        <div class="timeline-item-content">
                            <?php echo $actividad['usuario_nombre']; ?> 
                            <?php echo $es_creacion ? 'creó' : 'modificó'; ?> el comité 
                            <a href="ver_comite.php?id=<?php echo $actividad['comite_id']; ?>">
                                <?php echo htmlspecialchars($actividad['comite_nombre']); ?>
                            </a>
                            <div class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($fecha)); ?></div>
                        </div>
                    </div>
                    <?php 
                        endforeach; 
                    else:
                    ?>
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-gray-300 mb-3"></i>
                        <p>No hay actividad reciente.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Timeline styles */
.timeline {
    position: relative;
    padding-left: 1.5rem;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0.75rem;
    height: 100%;
    width: 1px;
    background-color: #e3e6f0;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-item-marker {
    position: absolute;
    left: -1.5rem;
    width: 15px;
    height: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.timeline-item-marker-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 100%;
    color: #fff;
    font-size: 0.875rem;
}

.timeline-item-content {
    padding-left: 0.75rem;
}

/* Card styles */
.card {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

/* Chart styles */
.chart-bar, .chart-pie {
    position: relative;
    height: 20rem;
    width: 100%;
}

@media (max-width: 768px) {
    .chart-bar, .chart-pie {
        height: 15rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Preparar datos para los gráficos si existen estadísticas
<?php if (count($estadisticas_municipio) > 0): ?>
// Gráfico de Comités por Municipio
var municipiosCtx = document.getElementById('municipiosChart').getContext('2d');
var municipiosData = {
    labels: [<?php echo implode(', ', array_map(function($item) { return '"' . htmlspecialchars($item['municipio']) . '"'; }, $estadisticas_municipio)); ?>],
    datasets: [{
        label: 'Comités',
        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
        borderColor: '#ffffff',
        data: [<?php echo implode(', ', array_map(function($item) { return $item['total']; }, $estadisticas_municipio)); ?>]
    }]
};

new Chart(municipiosCtx, {
    type: 'bar',
    data: municipiosData,
    options: {
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
<?php endif; ?>

<?php if (count($estadisticas_usuario) > 0): ?>
// Gráfico de Comités por Usuario
var usuariosCtx = document.getElementById('usuariosChart').getContext('2d');
var usuariosData = {
    labels: [<?php echo implode(', ', array_map(function($item) { return '"' . htmlspecialchars($item['nombre']) . '"'; }, $estadisticas_usuario)); ?>],
    datasets: [{
        label: 'Comités',
        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
        borderColor: '#ffffff',
        data: [<?php echo implode(', ', array_map(function($item) { return $item['total']; }, $estadisticas_usuario)); ?>]
    }]
};

new Chart(usuariosCtx, {
    type: 'pie',
    data: usuariosData,
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
<?php endif; ?>
</script>