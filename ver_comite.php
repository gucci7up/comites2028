<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'db/config.php';

// Verificar que el usuario esté autenticado
requiereAutenticacion();

// Verificar que se proporcionó un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: comites.php');
    exit;
}

$comite_id = $_GET['id'];
$conn = conectarDB();

// Obtener información del comité
$stmt = $conn->prepare("SELECT c.*, u.nombre as creador 
                      FROM comites c 
                      LEFT JOIN usuarios u ON c.creado_por = u.id 
                      WHERE c.id = ?");
$stmt->bind_param("i", $comite_id);
$stmt->execute();
$resultado = $stmt->get_result();
$comite = $resultado->fetch_assoc();

// Verificar que el comité existe
if (!$comite) {
    header('Location: comites.php');
    exit;
}

// Obtener información del coordinador
$coordinador = null;
// Buscar coordinador en la tabla comite_coordinador
$stmt = $conn->prepare("SELECT c.* FROM coordinadores c 
                      JOIN comite_coordinador cc ON c.id = cc.coordinador_id 
                      WHERE cc.comite_id = ?");
$stmt->bind_param("i", $comite_id);
$stmt->execute();
$resultado = $stmt->get_result();
$coordinador = $resultado->fetch_assoc();

// Obtener miembros del comité
$stmt = $conn->prepare("SELECT m.* FROM miembros m 
                      JOIN comite_miembro cm ON m.id = cm.miembro_id 
                      WHERE cm.comite_id = ? ORDER BY m.fecha_registro");
$stmt->bind_param("i", $comite_id);
$stmt->execute();
$resultado = $stmt->get_result();
$miembros = [];
while ($fila = $resultado->fetch_assoc()) {
    $miembros[] = $fila;
}

// Título de la página
$titulo_pagina = 'Detalles del Comité';

// Incluir el header
include 'includes/header.php';
?>

<!-- Botones de Acción -->
<div class="mb-4">
    <a href="comites.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver a Comités
    </a>
    <a href="editar_comite.php?id=<?php echo $comite_id; ?>" class="btn btn-primary">
        <i class="fas fa-edit"></i> Editar Comité
    </a>
    <button class="btn btn-success" onclick="imprimirComite()">
        <i class="fas fa-print"></i> Imprimir
    </button>
</div>

<!-- Información del Comité -->
<div class="row">
    <!-- Detalles del Comité -->
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Información del Comité</h6>
            </div>
            <div class="card-body">
                <h4 class="font-weight-bold text-primary mb-3"><?php echo htmlspecialchars($comite['nombre']); ?></h4>
                
                <div class="mb-3">
                    <strong><i class="fas fa-map-marker-alt text-primary mr-2"></i> Municipio:</strong>
                    <p><?php echo htmlspecialchars($comite['municipio']); ?></p>
                </div>
                
                <div class="mb-3">
                    <strong><i class="fas fa-calendar-alt text-primary mr-2"></i> Fecha de Creación:</strong>
                    <p><?php echo date('d/m/Y', strtotime($comite['fecha_creacion'])); ?></p>
                </div>
                
                <div class="mb-3">
                    <strong><i class="fas fa-user text-primary mr-2"></i> Creado por:</strong>
                    <p><?php echo htmlspecialchars($comite['creador']); ?></p>
                </div>
                
                <?php if ($comite['fecha_modificacion']): ?>
                <div class="mb-3">
                    <strong><i class="fas fa-edit text-primary mr-2"></i> Última Modificación:</strong>
                    <p><?php echo date('d/m/Y H:i', strtotime($comite['fecha_modificacion'])); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <strong><i class="fas fa-users text-primary mr-2"></i> Total Miembros:</strong>
                    <p><?php echo count($miembros); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Información del Coordinador -->
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Coordinador del Comité</h6>
            </div>
            <div class="card-body">
                <?php if ($coordinador): ?>
                <div class="row">
                    <div class="col-md-3 text-center mb-3">
                        <?php if (!empty($coordinador['foto'])): ?>
                        <img src="data:image/jpeg;base64,<?php echo $coordinador['foto']; ?>" alt="Foto" class="img-fluid rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white" style="width: 120px; height: 120px; font-size: 48px; margin: 0 auto;">
                            <?php echo strtoupper(substr($coordinador['nombre'], 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-9">
                        <h4 class="font-weight-bold"><?php echo htmlspecialchars($coordinador['nombre']); ?></h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Cédula:</strong> <?php echo htmlspecialchars($coordinador['cedula']); ?></p>
                                <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($coordinador['telefono']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($coordinador['email']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Municipio:</strong> <?php echo htmlspecialchars($coordinador['municipio']); ?></p>
                                <p><strong>Recinto:</strong> <?php echo htmlspecialchars($coordinador['recinto']); ?></p>
                                <p><strong>Colegio:</strong> <?php echo htmlspecialchars($coordinador['colegio']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-user-slash fa-3x text-gray-300 mb-3"></i>
                    <p>No hay coordinador asignado a este comité.</p>
                    <a href="editar_comite.php?id=<?php echo $comite_id; ?>" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Asignar Coordinador
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Miembros del Comité -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Miembros del Comité</h6>
        <a href="editar_comite.php?id=<?php echo $comite_id; ?>#miembros" class="btn btn-primary btn-sm">
            <i class="fas fa-user-plus"></i> Agregar Miembros
        </a>
    </div>
    <div class="card-body">
        <?php if (count($miembros) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered" id="miembrosTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Municipio</th>
                        <th>Recinto</th>
                        <th>Colegio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($miembros as $miembro): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($miembro['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($miembro['cedula']); ?></td>
                        <td><?php echo htmlspecialchars($miembro['telefono']); ?></td>
                        <td><?php echo htmlspecialchars($miembro['email']); ?></td>
                        <td><?php echo htmlspecialchars($miembro['municipio']); ?></td>
                        <td><?php echo htmlspecialchars($miembro['recinto']); ?></td>
                        <td><?php echo htmlspecialchars($miembro['colegio']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-users-slash fa-3x text-gray-300 mb-3"></i>
            <p>No hay miembros registrados en este comité.</p>
            <a href="editar_comite.php?id=<?php echo $comite_id; ?>#miembros" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Agregar Miembros
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Sección para imprimir -->
<div class="d-none" id="seccion-imprimir">
    <div class="text-center mb-4">
        <img src="logo1.png" alt="Logo PRM" style="max-width: 120px;">
        <h2 class="mt-3">PARTIDO REVOLUCIONARIO MODERNO</h2>
        <h3>Comité Afectivo</h3>
    </div>
    
    <div class="mb-4">
        <h4><?php echo htmlspecialchars($comite['nombre']); ?></h4>
        <p><strong>Municipio:</strong> <?php echo htmlspecialchars($comite['municipio']); ?></p>
        <p><strong>Fecha de Creación:</strong> <?php echo date('d/m/Y', strtotime($comite['fecha_creacion'])); ?></p>
    </div>
    
    <?php if ($coordinador): ?>
    <div class="mb-4">
        <h4>Coordinador</h4>
        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($coordinador['nombre']); ?></p>
        <p><strong>Cédula:</strong> <?php echo htmlspecialchars($coordinador['cedula']); ?></p>
        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($coordinador['telefono']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($coordinador['email']); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="mb-4">
        <h4>Miembros</h4>
        <?php if (count($miembros) > 0): ?>
        <table class="table table-bordered" width="100%">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Cédula</th>
                    <th>Teléfono</th>
                    <th>Municipio</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($miembros as $miembro): ?>
                <tr>
                    <td><?php echo htmlspecialchars($miembro['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($miembro['cedula']); ?></td>
                    <td><?php echo htmlspecialchars($miembro['telefono']); ?></td>
                    <td><?php echo htmlspecialchars($miembro['municipio']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No hay miembros registrados en este comité.</p>
        <?php endif; ?>
    </div>
    
    <div class="row mt-5">
        <div class="col-6 text-center">
            <div style="border-top: 1px solid #000; padding-top: 10px; width: 70%; margin: 0 auto;">
                Firma del Coordinador
            </div>
        </div>
        <div class="col-6 text-center">
            <div style="border-top: 1px solid #000; padding-top: 10px; width: 70%; margin: 0 auto;">
                Sello
            </div>
        </div>
    </div>
</div>

<script>
// Inicializar DataTable
$(document).ready(function() {
    $('#miembrosTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
        }
    });
});

// Función para imprimir el comité
function imprimirComite() {
    const contenidoOriginal = document.body.innerHTML;
    const contenidoImprimir = document.getElementById('seccion-imprimir').innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            ${contenidoImprimir}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = contenidoOriginal;
    
    // Reinicializar scripts después de restaurar el contenido
    $('#miembrosTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
        }
    });
    
    // Reinicializar eventos del sidebar y dropdown
    document.getElementById('toggle-sidebar')?.addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('show');
    });
    
    document.getElementById('user-dropdown-toggle')?.addEventListener('click', function() {
        document.getElementById('user-dropdown-menu').classList.toggle('show');
    });
}
</script>

<?php include 'includes/footer.php'; ?>