<?php
require_once 'includes/auth.php';
require_once 'db/config.php';

requiereOwner();

$conn      = conectarDB();
$partido_id = $_SESSION['partido_id'] ?? 0;

if (!$partido_id) {
    die('<div class="alert alert-danger m-4">Tu cuenta no tiene un partido asignado. Contacta al Super Administrador.</div>');
}

// ── Guardar tema ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_tema'])) {
    $cp = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color_primario'] ?? '') ? $_POST['color_primario'] : '#2563eb';
    $cs = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color_sidebar']  ?? '') ? $_POST['color_sidebar']  : '#0d1b2a';
    $ca = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color_accent']   ?? '') ? $_POST['color_accent']   : '#3b82f6';
    $nombre = trim($_POST['nombre_partido'] ?? '');
    $siglas  = strtoupper(trim($_POST['siglas'] ?? ''));

    // Logo upload
    $logo_sql = '';
    if (!empty($_FILES['logo']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png','jpg','jpeg','svg','webp'])) {
            $logo_data = file_get_contents($_FILES['logo']['tmp_name']);
            $stmt = $conn->prepare("UPDATE partidos SET nombre=?,siglas=?,color_primario=?,color_sidebar=?,color_accent=?,logo=? WHERE id=?");
            $null = null;
            $stmt->bind_param("sssssbi", $nombre,$siglas,$cp,$cs,$ca,$null,$partido_id);
            $stmt->send_long_data(5, $logo_data);
            $stmt->execute();
        }
    } else {
        $stmt = $conn->prepare("UPDATE partidos SET nombre=?,siglas=?,color_primario=?,color_sidebar=?,color_accent=? WHERE id=?");
        $stmt->bind_param("sssssi", $nombre,$siglas,$cp,$cs,$ca,$partido_id);
        $stmt->execute();
    }

    $_SESSION['mensaje'] = "Configuración del partido guardada.";
    $_SESSION['tipo_mensaje'] = "success";
    header('Location: config.php'); exit;
}

// ── Guardar candidato ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_candidato'])) {
    $nombre = trim($_POST['cand_nombre']);
    $cargo  = $_POST['cand_cargo'];
    $desc   = trim($_POST['cand_desc'] ?? '');
    $cid    = (int)($_POST['cand_id'] ?? 0);
    $cargos_validos = ['presidente','senador','diputado','alcalde','regidor'];
    if (!empty($nombre) && in_array($cargo, $cargos_validos)) {
        if ($cid > 0) {
            if (!empty($_FILES['cand_foto']['tmp_name'])) {
                $foto = file_get_contents($_FILES['cand_foto']['tmp_name']);
                $stmt = $conn->prepare("UPDATE candidatos SET nombre=?,cargo=?,descripcion=?,foto=? WHERE id=? AND partido_id=?");
                $null = null;
                $stmt->bind_param("sssbii",$nombre,$cargo,$desc,$null,$cid,$partido_id);
                $stmt->send_long_data(3,$foto);
            } else {
                $stmt = $conn->prepare("UPDATE candidatos SET nombre=?,cargo=?,descripcion=? WHERE id=? AND partido_id=?");
                $stmt->bind_param("sssii",$nombre,$cargo,$desc,$cid,$partido_id);
            }
            $stmt->execute();
        } else {
            if (!empty($_FILES['cand_foto']['tmp_name'])) {
                $foto = file_get_contents($_FILES['cand_foto']['tmp_name']);
                $stmt = $conn->prepare("INSERT INTO candidatos (partido_id,nombre,cargo,descripcion,foto) VALUES (?,?,?,?,?)");
                $null = null;
                $stmt->bind_param("isssb",$partido_id,$nombre,$cargo,$desc,$null);
                $stmt->send_long_data(4,$foto);
            } else {
                $stmt = $conn->prepare("INSERT INTO candidatos (partido_id,nombre,cargo,descripcion) VALUES (?,?,?,?)");
                $stmt->bind_param("isss",$partido_id,$nombre,$cargo,$desc);
            }
            $stmt->execute();
        }
        $_SESSION['mensaje'] = "Candidato guardado.";
        $_SESSION['tipo_mensaje'] = "success";
    }
    header('Location: config.php#candidatos'); exit;
}

// ── Eliminar candidato ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_candidato'])) {
    $cid = (int)$_POST['cand_id'];
    $stmt = $conn->prepare("DELETE FROM candidatos WHERE id=? AND partido_id=?");
    $stmt->bind_param("ii",$cid,$partido_id);
    $stmt->execute();
    $_SESSION['mensaje'] = "Candidato eliminado.";
    $_SESSION['tipo_mensaje'] = "success";
    header('Location: config.php#candidatos'); exit;
}

// ── Cargar datos ──────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM partidos WHERE id=?");
$stmt->bind_param("i",$partido_id); $stmt->execute();
$partido = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT * FROM candidatos WHERE partido_id=? ORDER BY FIELD(cargo,'presidente','senador','diputado','alcalde','regidor'), nombre");
$stmt->bind_param("i",$partido_id); $stmt->execute();
$candidatos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$logo_b64 = !empty($partido['logo']) ? base64_encode($partido['logo']) : '';

$cargos = ['presidente'=>'Presidente','senador'=>'Senador','diputado'=>'Diputado','alcalde'=>'Alcalde','regidor'=>'Regidor'];

$titulo_pagina = 'Configuración del Partido';
include 'includes/header.php';
?>

<style>
.config-section { margin-bottom: 32px; }
.color-preview {
    display: flex; align-items: center; gap: 8px;
    margin-top: 8px;
}
.color-swatch {
    width: 32px; height: 32px; border-radius: 8px;
    border: 2px solid rgba(0,0,0,.1);
    cursor: pointer;
    transition: transform .15s;
}
.color-swatch:hover { transform: scale(1.1); }

/* Live preview */
.theme-preview {
    border-radius: 12px; overflow: hidden;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 16px rgba(0,0,0,.08);
    margin-top: 16px;
}
.preview-sidebar {
    background: var(--preview-sidebar, #0d1b2a);
    padding: 16px;
    display: flex; flex-direction: column; gap: 8px;
}
.preview-sidebar-brand {
    display: flex; align-items: center; gap: 8px; margin-bottom: 8px;
}
.preview-sidebar-logo {
    width: 36px; height: 36px; border-radius: 8px;
    background: rgba(255,255,255,.12);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; color: #fff; overflow: hidden;
}
.preview-sidebar-logo img { width: 100%; height: 100%; object-fit: contain; }
.preview-nav-item {
    padding: 8px 10px; border-radius: 7px;
    color: rgba(255,255,255,.6); font-size: 12px;
    display: flex; align-items: center; gap: 8px;
}
.preview-nav-item.active {
    background: var(--preview-primary, #2563eb);
    color: #fff;
}
.preview-topbar {
    background: #fff; padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: space-between;
    font-size: 13px; font-weight: 600;
}
.preview-btn {
    background: var(--preview-primary, #2563eb);
    color: #fff; font-size: 11px; padding: 5px 12px;
    border-radius: 6px; border: none;
}
.preview-content { background: #f1f5f9; padding: 14px; }
.preview-card {
    background: #fff; border-radius: 8px;
    padding: 12px; font-size: 11px; color: #64748b;
    border-left: 3px solid var(--preview-accent, #3b82f6);
}

/* Candidates */
.cand-card {
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 12px; padding: 16px;
    display: flex; align-items: center; gap: 14px;
}
.cand-photo {
    width: 56px; height: 56px; border-radius: 50%;
    object-fit: cover; flex-shrink: 0;
    border: 2px solid #e2e8f0;
}
.cand-photo-placeholder {
    width: 56px; height: 56px; border-radius: 50%;
    background: var(--accent); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; font-weight: 700; flex-shrink: 0;
}
.cargo-pill {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .05em; padding: 2px 8px; border-radius: 10px;
    background: #eff6ff; color: var(--accent);
}
.upload-zone {
    border: 2px dashed #e2e8f0; border-radius: 12px;
    padding: 32px; text-align: center; cursor: pointer;
    transition: all .2s; background: #fafafa;
}
.upload-zone:hover { border-color: var(--accent); background: #f0f7ff; }
.upload-zone input { display: none; }
.logo-preview-box {
    width: 100%; max-height: 140px;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid #e2e8f0; border-radius: 10px;
    background: #f8fafc; overflow: hidden; margin-top: 10px;
    padding: 12px;
}
.logo-preview-box img { max-height: 120px; max-width: 100%; object-fit: contain; }
</style>

<?php if (isset($_SESSION['mensaje'])): ?>
<div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show mb-4">
    <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje'],$_SESSION['tipo_mensaje']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- LEFT: Logo + Colors -->
    <div class="col-lg-5">

        <!-- Logo upload -->
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-palette me-2 text-primary"></i>Identidad del Partido</div>
            <div class="p-4">
                <form method="POST" enctype="multipart/form-data" id="formTema">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Partido</label>
                        <input type="text" name="nombre_partido" class="form-control form-control-sm"
                               value="<?php echo htmlspecialchars($partido['nombre']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Siglas</label>
                        <input type="text" name="siglas" class="form-control form-control-sm" maxlength="10"
                               value="<?php echo htmlspecialchars($partido['siglas']); ?>">
                    </div>

                    <!-- Logo -->
                    <div class="mb-3">
                        <label class="form-label">Logo del Partido</label>
                        <div class="upload-zone" onclick="document.getElementById('logoInput').click()">
                            <input type="file" id="logoInput" name="logo" accept="image/*" onchange="onLogoChange(this)">
                            <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color:#94a3b8;"></i>
                            <div style="font-size:13px;color:#64748b;">Haz clic para subir el logo</div>
                            <div style="font-size:11px;color:#94a3b8;">PNG, JPG, SVG — los colores se extraerán automáticamente</div>
                        </div>
                        <div class="logo-preview-box" id="logoPreviewBox" style="display:<?php echo $logo_b64 ? 'flex' : 'none'; ?>">
                            <img id="logoPreviewImg" src="<?php echo $logo_b64 ? 'data:image/png;base64,'.$logo_b64 : ''; ?>" alt="Logo">
                        </div>
                    </div>

                    <!-- Colors -->
                    <div class="mb-3">
                        <label class="form-label">Colores del tema</label>
                        <div class="row g-2">
                            <div class="col-4">
                                <div style="font-size:11px;color:#64748b;margin-bottom:4px;">Principal</div>
                                <input type="color" name="color_primario" id="cpPrimario" class="form-control form-control-sm form-control-color w-100"
                                       value="<?php echo $partido['color_primario']; ?>" oninput="updatePreview()">
                            </div>
                            <div class="col-4">
                                <div style="font-size:11px;color:#64748b;margin-bottom:4px;">Sidebar</div>
                                <input type="color" name="color_sidebar" id="cpSidebar" class="form-control form-control-sm form-control-color w-100"
                                       value="<?php echo $partido['color_sidebar']; ?>" oninput="updatePreview()">
                            </div>
                            <div class="col-4">
                                <div style="font-size:11px;color:#64748b;margin-bottom:4px;">Acento</div>
                                <input type="color" name="color_accent" id="cpAccent" class="form-control form-control-sm form-control-color w-100"
                                       value="<?php echo $partido['color_accent']; ?>" oninput="updatePreview()">
                            </div>
                        </div>
                        <div class="mt-2 d-flex gap-2 flex-wrap" id="presetBtns">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPreset('#2563eb','#0d1b2a','#3b82f6')">
                                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#2563eb;margin-right:4px;"></span>PRM Azul
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPreset('#7c3aed','#1e1035','#8b5cf6')">
                                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#7c3aed;margin-right:4px;"></span>PLD Morado
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPreset('#16a34a','#0a1f12','#22c55e')">
                                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#16a34a;margin-right:4px;"></span>FDP Verde
                            </button>
                        </div>
                    </div>

                    <button type="submit" name="guardar_tema" class="btn btn-primary w-100 btn-sm">
                        <i class="fas fa-save me-1"></i> Guardar Configuración
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- RIGHT: Live Preview -->
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-eye me-2 text-primary"></i>Vista Previa del Tema</div>
            <div class="p-4">
                <div class="theme-preview">
                    <div style="display:flex;">
                        <!-- Preview Sidebar -->
                        <div class="preview-sidebar" id="previewSidebar" style="width:140px;flex-shrink:0;">
                            <div class="preview-sidebar-brand">
                                <div class="preview-sidebar-logo" id="previewLogo">
                                    <?php if ($logo_b64): ?>
                                    <img src="data:image/png;base64,<?php echo $logo_b64; ?>" alt="">
                                    <?php else: ?><i class="fas fa-star"></i><?php endif; ?>
                                </div>
                                <div style="color:#fff;font-size:10px;font-weight:600;line-height:1.2;">
                                    <?php echo htmlspecialchars($partido['siglas'] ?: 'PARTIDO'); ?>
                                </div>
                            </div>
                            <div class="preview-nav-item active" id="previewNavActive">
                                <i class="fas fa-chart-pie" style="font-size:11px;"></i> Dashboard
                            </div>
                            <div class="preview-nav-item">
                                <i class="fas fa-layer-group" style="font-size:11px;"></i> Comités
                            </div>
                            <div class="preview-nav-item">
                                <i class="fas fa-id-card" style="font-size:11px;"></i> Cédula
                            </div>
                        </div>
                        <!-- Preview Content -->
                        <div style="flex:1;min-width:0;">
                            <div class="preview-topbar">
                                <span>Dashboard</span>
                                <button class="preview-btn" id="previewBtn">+ Comité</button>
                            </div>
                            <div class="preview-content">
                                <div class="preview-card" id="previewCard">
                                    <div style="font-weight:600;color:#0f172a;margin-bottom:4px;">Total Comités</div>
                                    <div style="font-size:20px;font-weight:700;color:#0f172a;">12</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats card preview with actual partido logo -->
        <div class="card">
            <div class="card-header"><i class="fas fa-print me-2 text-primary"></i>Vista previa al imprimir</div>
            <div class="p-4">
                <div style="border:1px solid #e2e8f0;border-radius:10px;padding:20px;background:#fafafa;text-align:center;">
                    <?php if ($logo_b64): ?>
                    <img src="data:image/png;base64,<?php echo $logo_b64; ?>" style="max-height:60px;max-width:120px;object-fit:contain;margin-bottom:10px;" alt="Logo">
                    <?php endif; ?>
                    <div style="font-weight:700;font-size:14px;"><?php echo htmlspecialchars($partido['nombre']); ?></div>
                    <div style="font-size:11px;color:#64748b;margin-bottom:12px;">Comité Afectivo</div>
                    <div style="display:flex;justify-content:center;gap:16px;align-items:center;flex-wrap:wrap;">
                        <div style="text-align:center;">
                            <div style="width:56px;height:56px;border-radius:50%;background:#e2e8f0;margin:0 auto 6px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#94a3b8;"><i class="fas fa-user"></i></div>
                            <div style="font-size:11px;font-weight:600;">Candidato</div>
                            <div style="font-size:10px;color:#64748b;">Cargo</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── CANDIDATOS ─────────────────────────────────────────────── -->
<div class="card mt-2" id="candidatos">
    <div class="card-header">
        <span><i class="fas fa-users me-2 text-primary"></i>Candidatos del Partido</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCandidato" onclick="resetCandModal()">
            <i class="fas fa-plus me-1"></i> Agregar Candidato
        </button>
    </div>
    <div class="p-4">
        <?php if (empty($candidatos)): ?>
        <div style="text-align:center;padding:40px;color:#94a3b8;">
            <i class="fas fa-user-slash" style="font-size:36px;opacity:.3;display:block;margin-bottom:12px;"></i>
            <p style="font-size:13px;">No hay candidatos. Agrega el primero.</p>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($candidatos as $c): ?>
            <div class="col-lg-6">
                <div class="cand-card">
                    <?php if (!empty($c['foto'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($c['foto']); ?>" class="cand-photo" alt="Foto">
                    <?php else: ?>
                    <div class="cand-photo-placeholder"><?php echo strtoupper(substr($c['nombre'],0,1)); ?></div>
                    <?php endif; ?>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;font-size:13px;margin-bottom:3px;"><?php echo htmlspecialchars($c['nombre']); ?></div>
                        <span class="cargo-pill"><?php echo ucfirst($c['cargo']); ?></span>
                        <?php if (!empty($c['descripcion'])): ?>
                        <div style="font-size:11px;color:var(--text-secondary);margin-top:3px;">
                            <i class="fas fa-map-pin me-1"></i><?php echo htmlspecialchars($c['descripcion']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-column gap-1">
                        <button class="action-link" title="Editar"
                            onclick="editarCandidato(<?php echo $c['id']; ?>,'<?php echo htmlspecialchars(addslashes($c['nombre'])); ?>','<?php echo $c['cargo']; ?>','<?php echo htmlspecialchars(addslashes($c['descripcion']??'')); ?>')">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="action-link danger" title="Eliminar"
                            onclick="eliminarCandidato(<?php echo $c['id']; ?>,'<?php echo htmlspecialchars(addslashes($c['nombre'])); ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Candidato -->
<div class="modal fade" id="modalCandidato" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);border:1px solid var(--border);">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h6 class="modal-title fw-semibold" id="modalCandTitle">Nuevo Candidato</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="formCandidato">
                    <input type="hidden" name="cand_id" id="candId">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Nombre Completo</label>
                        <input type="text" name="cand_nombre" id="candNombre" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Cargo</label>
                        <select name="cand_cargo" id="candCargo" class="form-select form-select-sm" required onchange="toggleDesc(this.value)">
                            <option value="">Seleccionar cargo...</option>
                            <option value="presidente">Presidente</option>
                            <option value="senador">Senador</option>
                            <option value="diputado">Diputado</option>
                            <option value="alcalde">Alcalde</option>
                            <option value="regidor">Regidor</option>
                        </select>
                    </div>
                    <div class="mb-3" id="descGroup">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Zona / Descripción <span style="color:var(--text-secondary);font-weight:400;">(opcional)</span></label>
                        <input type="text" name="cand_desc" id="candDesc" class="form-control form-control-sm"
                               placeholder="Ej: Zona Norte, Circunscripción 1, Municipio Cabral...">
                        <div class="form-text">Útil para distinguir cuando hay varios del mismo cargo.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Foto del Candidato</label>
                        <input type="file" name="cand_foto" class="form-control form-control-sm" accept="image/*" onchange="previewCandFoto(this)">
                        <div id="candFotoPreview" style="display:none;margin-top:10px;text-align:center;">
                            <img id="candFotoImg" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid var(--border);">
                        </div>
                    </div>
                    <button type="submit" name="guardar_candidato" class="btn btn-primary w-100 btn-sm">
                        <i class="fas fa-save me-1"></i> Guardar Candidato
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete candidate form (hidden) -->
<form method="POST" id="formEliminarCand" style="display:none;">
    <input type="hidden" name="cand_id" id="elimCandId">
    <input type="hidden" name="eliminar_candidato" value="1">
</form>

<style>
.action-link { width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;border:1px solid var(--border);color:var(--text-secondary);background:none;cursor:pointer;transition:all .15s; }
.action-link:hover { border-color:var(--accent);color:var(--accent);background:#eff6ff; }
.action-link.danger:hover { border-color:var(--danger);color:var(--danger);background:#fef2f2; }
</style>

<!-- ColorThief for auto color extraction -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/color-thief/2.3.0/color-thief.umd.js"></script>
<script>
/* ── LIVE PREVIEW ──────────────────────────────────── */
function updatePreview() {
    const cp = document.getElementById('cpPrimario').value;
    const cs = document.getElementById('cpSidebar').value;
    const ca = document.getElementById('cpAccent').value;

    document.getElementById('previewSidebar').style.background   = cs;
    document.getElementById('previewNavActive').style.background = cp;
    document.getElementById('previewBtn').style.background       = cp;
    document.getElementById('previewCard').style.borderLeftColor = ca;
}
updatePreview();

function setPreset(cp, cs, ca) {
    document.getElementById('cpPrimario').value = cp;
    document.getElementById('cpSidebar').value  = cs;
    document.getElementById('cpAccent').value   = ca;
    updatePreview();
}

/* ── LOGO UPLOAD + COLOR EXTRACTION ──────────────── */
function onLogoChange(input) {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const src = e.target.result;
        const img = document.getElementById('logoPreviewImg');
        img.src = src;
        document.getElementById('logoPreviewBox').style.display = 'flex';

        // Update preview logo
        document.getElementById('previewLogo').innerHTML = `<img src="${src}" alt="" style="width:100%;height:100%;object-fit:contain;">`;

        // Extract colors with ColorThief after image loads
        const tmpImg = new Image();
        tmpImg.crossOrigin = 'Anonymous';
        tmpImg.onload = function() {
            try {
                const ct = new ColorThief();
                const dominant = ct.getColor(tmpImg);
                const palette  = ct.getPalette(tmpImg, 3);

                const toHex = ([r,g,b]) => '#' + [r,g,b].map(v => v.toString(16).padStart(2,'0')).join('');
                const darken = ([r,g,b], f) => [Math.round(r*f), Math.round(g*f), Math.round(b*f)];

                const primary = toHex(dominant);
                const sidebar  = toHex(darken(dominant, 0.25));
                const accent   = toHex(palette[1] || dominant);

                document.getElementById('cpPrimario').value = primary;
                document.getElementById('cpSidebar').value  = sidebar;
                document.getElementById('cpAccent').value   = accent;
                updatePreview();
            } catch(err) { console.warn('ColorThief error', err); }
        };
        tmpImg.src = src;
    };
    reader.readAsDataURL(input.files[0]);
}

/* ── CANDIDATE MODAL ─────────────────────────────── */
function toggleDesc(cargo) {
    // Para cargos donde puede haber múltiples, el campo descripción es más relevante
    const multi = ['regidor','diputado','alcalde'];
    const el = document.getElementById('descGroup');
    if (multi.includes(cargo)) {
        el.style.borderLeft = '3px solid var(--accent)';
        el.style.paddingLeft = '10px';
    } else {
        el.style.borderLeft = '';
        el.style.paddingLeft = '';
    }
}

function resetCandModal() {
    document.getElementById('modalCandTitle').textContent = 'Nuevo Candidato';
    document.getElementById('candId').value      = '';
    document.getElementById('candNombre').value  = '';
    document.getElementById('candCargo').value   = '';
    document.getElementById('candDesc').value    = '';
    document.getElementById('candFotoPreview').style.display = 'none';
    document.getElementById('descGroup').style.borderLeft = '';
    document.getElementById('descGroup').style.paddingLeft = '';
}

function editarCandidato(id, nombre, cargo, desc) {
    document.getElementById('modalCandTitle').textContent = 'Editar Candidato';
    document.getElementById('candId').value      = id;
    document.getElementById('candNombre').value  = nombre;
    document.getElementById('candCargo').value   = cargo;
    document.getElementById('candDesc').value    = desc || '';
    toggleDesc(cargo);
    new bootstrap.Modal(document.getElementById('modalCandidato')).show();
}

function eliminarCandidato(id, nombre) {
    if (confirm(`¿Eliminar a ${nombre}? Esta acción no se puede deshacer.`)) {
        document.getElementById('elimCandId').value = id;
        document.getElementById('formEliminarCand').submit();
    }
}

function previewCandFoto(input) {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('candFotoImg').src = e.target.result;
        document.getElementById('candFotoPreview').style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}
</script>

<?php include 'includes/footer.php'; ?>
