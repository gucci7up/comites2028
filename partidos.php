<?php
require_once 'includes/auth.php';
require_once 'db/config.php';

requiereSuperAdmin();
$conn = conectarDB();

// Crear/editar partido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_partido'])) {
    $pid    = (int)($_POST['partido_id'] ?? 0);
    $nombre = trim($_POST['nombre']);
    $siglas = strtoupper(trim($_POST['siglas']));
    $cp     = $_POST['color_primario'] ?? '#2563eb';
    $cs     = $_POST['color_sidebar']  ?? '#0d1b2a';
    $ca     = $_POST['color_accent']   ?? '#3b82f6';

    if ($pid > 0) {
        if (!empty($_FILES['logo']['tmp_name'])) {
            $logo = file_get_contents($_FILES['logo']['tmp_name']);
            $s = $conn->prepare("UPDATE partidos SET nombre=?,siglas=?,color_primario=?,color_sidebar=?,color_accent=?,logo=? WHERE id=?");
            $null=null;
            $s->bind_param("sssssbi",$nombre,$siglas,$cp,$cs,$ca,$null,$pid);
            $s->send_long_data(5,$logo);
        } else {
            $s = $conn->prepare("UPDATE partidos SET nombre=?,siglas=?,color_primario=?,color_sidebar=?,color_accent=? WHERE id=?");
            $s->bind_param("sssssi",$nombre,$siglas,$cp,$cs,$ca,$pid);
        }
        $s->execute();
        $_SESSION['mensaje'] = "Partido actualizado.";
    } else {
        if (!empty($_FILES['logo']['tmp_name'])) {
            $logo = file_get_contents($_FILES['logo']['tmp_name']);
            $s = $conn->prepare("INSERT INTO partidos (nombre,siglas,color_primario,color_sidebar,color_accent,logo) VALUES (?,?,?,?,?,?)");
            $null=null;
            $s->bind_param("sssssb",$nombre,$siglas,$cp,$cs,$ca,$null);
            $s->send_long_data(5,$logo);
        } else {
            $s = $conn->prepare("INSERT INTO partidos (nombre,siglas,color_primario,color_sidebar,color_accent) VALUES (?,?,?,?,?)");
            $s->bind_param("sssss",$nombre,$siglas,$cp,$cs,$ca);
        }
        $s->execute();
        $_SESSION['mensaje'] = "Partido creado.";
    }
    $_SESSION['tipo_mensaje'] = "success";
    header('Location: partidos.php'); exit;
}

// Crear owner y asignar partido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_owner'])) {
    $nombre   = trim($_POST['owner_nombre']);
    $usuario  = trim($_POST['owner_usuario']);
    $password = trim($_POST['owner_password']);
    $email    = trim($_POST['owner_email']);
    $partido  = (int)$_POST['owner_partido'];
    $uid      = (int)($_POST['owner_id'] ?? 0);

    if ($uid > 0) {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $s = $conn->prepare("UPDATE usuarios SET nombre=?,email=?,usuario=?,password=?,partido_id=? WHERE id=?");
            $s->bind_param("ssssii",$nombre,$email,$usuario,$hash,$partido,$uid);
        } else {
            $s = $conn->prepare("UPDATE usuarios SET nombre=?,email=?,usuario=?,partido_id=? WHERE id=?");
            $s->bind_param("sssii",$nombre,$email,$usuario,$partido,$uid);
        }
        $s->execute();
        $_SESSION['mensaje'] = "Owner actualizado.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $s = $conn->prepare("INSERT INTO usuarios (nombre,email,usuario,password,rol,partido_id) VALUES (?,?,?,?,'owner',?)");
        $s->bind_param("ssssi",$nombre,$email,$usuario,$hash,$partido);
        $s->execute();
        $_SESSION['mensaje'] = "Owner creado.";
    }
    $_SESSION['tipo_mensaje'] = "success";
    header('Location: partidos.php'); exit;
}

// Eliminar partido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_partido'])) {
    $pid = (int)$_POST['partido_id'];
    $conn->query("DELETE FROM partidos WHERE id=$pid");
    $_SESSION['mensaje'] = "Partido eliminado."; $_SESSION['tipo_mensaje'] = "success";
    header('Location: partidos.php'); exit;
}

$partidos = $conn->query("SELECT * FROM partidos ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$owners   = $conn->query("SELECT u.*, p.nombre as partido_nombre FROM usuarios u LEFT JOIN partidos p ON u.partido_id=p.id WHERE u.rol='owner' ORDER BY u.nombre")->fetch_all(MYSQLI_ASSOC);

$titulo_pagina = 'Partidos Políticos';
include 'includes/header.php';
?>

<style>
.party-chip { display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700; }
.action-link { width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;border:1px solid var(--border);color:var(--text-secondary);background:none;cursor:pointer;transition:all .15s; }
.action-link:hover { border-color:var(--accent);color:var(--accent);background:#eff6ff; }
.action-link.danger:hover { border-color:var(--danger);color:var(--danger);background:#fef2f2; }
.party-color-dot { width:14px;height:14px;border-radius:50%;display:inline-block; }
</style>

<?php if (isset($_SESSION['mensaje'])): ?>
<div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show mb-4">
    <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje'],$_SESSION['tipo_mensaje']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- Partidos -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-flag me-2 text-primary"></i>Partidos Políticos</span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalPartido" onclick="resetPartidoModal()">
                    <i class="fas fa-plus me-1"></i> Nuevo Partido
                </button>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Partido</th><th>Siglas</th><th>Colores</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($partidos as $p): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($p['logo'])): ?>
                                <img src="data:image/png;base64,<?php echo base64_encode($p['logo']); ?>" style="width:28px;height:28px;object-fit:contain;border-radius:4px;background:#f1f5f9;padding:2px;">
                                <?php endif; ?>
                                <span style="font-weight:500;font-size:13px;"><?php echo htmlspecialchars($p['nombre']); ?></span>
                            </div>
                        </td>
                        <td><span class="party-chip" style="background:<?php echo $p['color_primario']; ?>22;color:<?php echo $p['color_primario']; ?>"><?php echo htmlspecialchars($p['siglas']); ?></span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <span class="party-color-dot" style="background:<?php echo $p['color_primario']; ?>" title="Principal"></span>
                                <span class="party-color-dot" style="background:<?php echo $p['color_sidebar']; ?>" title="Sidebar"></span>
                                <span class="party-color-dot" style="background:<?php echo $p['color_accent']; ?>" title="Acento"></span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="action-link" onclick="editarPartido(<?php echo htmlspecialchars(json_encode($p)); ?>)"><i class="fas fa-pen"></i></button>
                                <button class="action-link danger" onclick="eliminarPartido(<?php echo $p['id']; ?>,'<?php echo htmlspecialchars(addslashes($p['nombre'])); ?>')"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Owners -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-user-shield me-2 text-primary"></i>Owners</span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalOwner" onclick="resetOwnerModal()">
                    <i class="fas fa-plus me-1"></i> Nuevo Owner
                </button>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Nombre</th><th>Partido</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($owners as $o): ?>
                    <tr>
                        <td>
                            <div style="font-weight:500;font-size:13px;"><?php echo htmlspecialchars($o['nombre']); ?></div>
                            <div style="font-size:11px;color:var(--text-secondary);">@<?php echo htmlspecialchars($o['usuario']); ?></div>
                        </td>
                        <td style="font-size:12px;"><?php echo htmlspecialchars($o['partido_nombre'] ?? '—'); ?></td>
                        <td>
                            <button class="action-link" onclick="editarOwner(<?php echo htmlspecialchars(json_encode($o)); ?>)"><i class="fas fa-pen"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($owners)): ?>
                    <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--text-secondary);font-size:13px;">Sin owners creados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Partido -->
<div class="modal fade" id="modalPartido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h6 class="modal-title fw-semibold" id="modalPartidoTitle">Nuevo Partido</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="partido_id" id="pId">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Nombre</label>
                        <input type="text" name="nombre" id="pNombre" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Siglas</label>
                        <input type="text" name="siglas" id="pSiglas" class="form-control form-control-sm" maxlength="10" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Logo</label>
                        <input type="file" name="logo" class="form-control form-control-sm" accept="image/*">
                    </div>
                    <div class="row g-2 mb-4">
                        <div class="col-4">
                            <label class="form-label" style="font-size:12px;">Color Principal</label>
                            <input type="color" name="color_primario" id="pCp" class="form-control form-control-color w-100 form-control-sm" value="#2563eb">
                        </div>
                        <div class="col-4">
                            <label class="form-label" style="font-size:12px;">Sidebar</label>
                            <input type="color" name="color_sidebar" id="pCs" class="form-control form-control-color w-100 form-control-sm" value="#0d1b2a">
                        </div>
                        <div class="col-4">
                            <label class="form-label" style="font-size:12px;">Acento</label>
                            <input type="color" name="color_accent" id="pCa" class="form-control form-control-color w-100 form-control-sm" value="#3b82f6">
                        </div>
                    </div>
                    <button type="submit" name="guardar_partido" class="btn btn-primary w-100 btn-sm">
                        <i class="fas fa-save me-1"></i> Guardar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Owner -->
<div class="modal fade" id="modalOwner" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h6 class="modal-title fw-semibold" id="modalOwnerTitle">Nuevo Owner</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="owner_id" id="oId">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Nombre</label>
                        <input type="text" name="owner_nombre" id="oNombre" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Usuario</label>
                        <input type="text" name="owner_usuario" id="oUsuario" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Email</label>
                        <input type="email" name="owner_email" id="oEmail" class="form-control form-control-sm">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Contraseña</label>
                        <input type="password" name="owner_password" id="oPassword" class="form-control form-control-sm">
                        <div class="form-text">Dejar en blanco para mantener la actual.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Partido Asignado</label>
                        <select name="owner_partido" id="oPartido" class="form-select form-select-sm" required>
                            <option value="">Seleccionar partido...</option>
                            <?php foreach ($partidos as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?> (<?php echo $p['siglas']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="guardar_owner" class="btn btn-primary w-100 btn-sm">
                        <i class="fas fa-save me-1"></i> Guardar Owner
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Hidden forms -->
<form method="POST" id="formEliminarPartido" style="display:none;">
    <input type="hidden" name="partido_id" id="elimPId">
    <input type="hidden" name="eliminar_partido" value="1">
</form>

<script>
function resetPartidoModal() {
    document.getElementById('modalPartidoTitle').textContent = 'Nuevo Partido';
    document.getElementById('pId').value = '';
    document.getElementById('pNombre').value = '';
    document.getElementById('pSiglas').value = '';
    document.getElementById('pCp').value = '#2563eb';
    document.getElementById('pCs').value = '#0d1b2a';
    document.getElementById('pCa').value = '#3b82f6';
}
function editarPartido(p) {
    document.getElementById('modalPartidoTitle').textContent = 'Editar Partido';
    document.getElementById('pId').value     = p.id;
    document.getElementById('pNombre').value = p.nombre;
    document.getElementById('pSiglas').value = p.siglas;
    document.getElementById('pCp').value     = p.color_primario;
    document.getElementById('pCs').value     = p.color_sidebar;
    document.getElementById('pCa').value     = p.color_accent;
    new bootstrap.Modal(document.getElementById('modalPartido')).show();
}
function eliminarPartido(id, nombre) {
    if (confirm(`¿Eliminar el partido ${nombre}? Se eliminarán todos sus candidatos.`)) {
        document.getElementById('elimPId').value = id;
        document.getElementById('formEliminarPartido').submit();
    }
}
function resetOwnerModal() {
    document.getElementById('modalOwnerTitle').textContent = 'Nuevo Owner';
    ['oId','oNombre','oUsuario','oEmail','oPassword'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('oPartido').value = '';
}
function editarOwner(o) {
    document.getElementById('modalOwnerTitle').textContent = 'Editar Owner';
    document.getElementById('oId').value       = o.id;
    document.getElementById('oNombre').value   = o.nombre;
    document.getElementById('oUsuario').value  = o.usuario;
    document.getElementById('oEmail').value    = o.email ?? '';
    document.getElementById('oPassword').value = '';
    document.getElementById('oPartido').value  = o.partido_id ?? '';
    new bootstrap.Modal(document.getElementById('modalOwner')).show();
}
</script>

<?php include 'includes/footer.php'; ?>
