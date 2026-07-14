<?php
require_once 'includes/auth.php';
require_once 'db/config.php';

// Página pública, sin login: cualquiera con el enlace puede registrar un comité.

$cfg            = cargarConfiguracion();
$color_primary  = $cfg['color_primario'] ?? '#2563eb';
$color_accent   = $cfg['color_accent']   ?? '#3b82f6';
$logo_b64       = !empty($cfg['logo']) ? base64_encode($cfg['logo']) : '';
$nombre_partido = $cfg['nombre_partido'] ?? 'Sistema';
$siglas         = $cfg['siglas'] ?? '';

$conn = conectarDB();
$candidatos = $conn->query("SELECT id, nombre, cargo, descripcion, foto FROM candidatos WHERE activo = 1 ORDER BY FIELD(cargo,'presidente','senador','diputado','alcalde','regidor'), nombre")->fetch_all(MYSQLI_ASSOC);
$cargos = ['presidente'=>'Presidente','senador'=>'Senador','diputado'=>'Diputado','alcalde'=>'Alcalde','regidor'=>'Regidor'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrá tu Comité — <?php echo htmlspecialchars($siglas ?: $nombre_partido); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --accent:         <?php echo $color_primary; ?>;
    --accent-light:   <?php echo $color_accent; ?>;
    --accent-700:     color-mix(in srgb, var(--accent) 70%, black);
    --accent-tint:    color-mix(in srgb, var(--accent) 8%, white);
    --success:        #16a34a; --success-bg: #e8f9ee;
    --danger:         #ef4444; --danger-bg:  #fef2f2;
    --bg:             #f4f5fa;
    --surface:        #ffffff;
    --text-primary:   #1e1b2e;
    --text-secondary: #5b5678;
    --text-tertiary:  #9691b0;
    --border:         #d8d5e6;
    --shadow-sm:      0 2px 6px rgba(30,27,46,.10), 0 1px 2px rgba(30,27,46,.08);
    --radius:         12px;
    --radius-card:    22px;
}
* { box-sizing: border-box; }
body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text-primary); margin:0; }
.pub-header { display:flex; align-items:center; gap:12px; justify-content:center; padding:28px 20px 8px; text-align:center; }
.pub-logo { width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg,var(--accent),var(--accent-light)); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; overflow:hidden; flex-shrink:0; }
.pub-logo img { width:100%; height:100%; object-fit:contain; }
.pub-title { font-size:15px; font-weight:800; }
.pub-subtitle { font-size:12px; color:var(--text-tertiary); }
.pub-wrap { max-width:720px; margin:0 auto; padding:20px 20px 60px; }
.pub-hero { text-align:center; margin-bottom:24px; }
.pub-hero h1 { font-size:22px; font-weight:800; margin:0 0 6px; }
.pub-hero p { font-size:13px; color:var(--text-secondary); margin:0; }

.stepper { display:flex; justify-content:space-between; margin-bottom:22px; counter-reset:step; }
.step-dot-wrap { flex:1; text-align:center; position:relative; }
.step-dot-wrap:not(:last-child)::after { content:''; position:absolute; top:15px; left:50%; width:100%; height:2px; background:var(--border); z-index:0; }
.step-dot-wrap.done:not(:last-child)::after { background:var(--accent); }
.step-dot { width:30px; height:30px; border-radius:50%; background:#fff; border:2px solid var(--border); color:var(--text-tertiary); display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; position:relative; z-index:1; }
.step-dot-wrap.active .step-dot { border-color:var(--accent); color:var(--accent); background:var(--accent-tint); }
.step-dot-wrap.done .step-dot { border-color:var(--accent); background:var(--accent); color:#fff; }
.step-label { font-size:10.5px; color:var(--text-tertiary); margin-top:6px; display:block; }
.step-dot-wrap.active .step-label { color:var(--accent); font-weight:600; }

.pub-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-card); padding:28px; box-shadow:var(--shadow-sm); }
.step-panel { display:none; }
.step-panel.active { display:block; }
.step-panel h2 { font-size:16px; font-weight:700; margin:0 0 4px; }
.step-panel .step-copy { font-size:12.5px; color:var(--text-tertiary); margin:0 0 18px; }

.fld { width:100%; box-sizing:border-box; border:1px solid var(--border); background:#f7f7fb; border-radius:var(--radius); padding:11px 14px; font-size:13.5px; font-family:inherit; color:var(--text-primary); }
.fld:focus { outline:none; border-color:var(--accent); background:#fff; box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 15%,transparent); }
.lbl { font-size:11.5px; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:6px; }
.field-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
@media (max-width:520px) { .field-row { grid-template-columns:1fr; } }

.cand-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
@media (max-width:520px) { .cand-grid { grid-template-columns:1fr; } }
.cand-select-card { border:1.5px solid var(--border); border-radius:14px; padding:14px; display:flex; align-items:center; gap:12px; cursor:pointer; transition:all .15s; }
.cand-select-card:hover { border-color:var(--accent); }
.cand-select-card.selected { border-color:var(--accent); background:var(--accent-tint); }
.cand-photo { width:44px; height:44px; border-radius:50%; object-fit:cover; flex-shrink:0; }
.cand-photo-fallback { width:44px; height:44px; border-radius:50%; background:linear-gradient(135deg,var(--accent),var(--accent-light)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; flex-shrink:0; }
.cand-name { font-size:13.5px; font-weight:600; }
.cand-cargo { font-size:11px; color:var(--text-tertiary); }

.busqueda-box { display:flex; gap:10px; margin-bottom:14px; }
.persona-preview { display:none; align-items:center; gap:14px; padding:14px; border-radius:14px; background:var(--accent-tint); margin-bottom:16px; }
.persona-preview.show { display:flex; }
.persona-preview img { width:56px; height:56px; border-radius:50%; object-fit:cover; flex-shrink:0; }
.persona-preview-name { font-size:13.5px; font-weight:700; }
.persona-preview-meta { font-size:11.5px; color:var(--text-secondary); }

.miembro-list { display:flex; flex-direction:column; gap:8px; margin-top:16px; }
.miembro-chip { display:flex; align-items:center; gap:10px; padding:10px 12px; background:var(--bg); border-radius:12px; }
.miembro-chip-av { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,var(--accent),var(--accent-light)); color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; flex-shrink:0; object-fit:cover; }
.miembro-chip-name { font-size:12.5px; font-weight:600; flex:1; min-width:0; }
.miembro-chip-cedula { font-size:11px; color:var(--text-tertiary); }
.miembro-chip-remove { color:var(--danger); background:none; border:none; cursor:pointer; font-size:14px; }

.wizard-nav { display:flex; justify-content:space-between; margin-top:22px; gap:10px; }
.btn { border-radius:var(--radius); font-size:13px; font-weight:600; padding:11px 22px; border:1px solid transparent; cursor:pointer; }
.btn-primary { background:var(--accent); color:#fff; border-color:var(--accent); }
.btn-ghost { background:#fff; color:var(--text-secondary); border-color:var(--border); }

.loading-overlay { position:fixed; inset:0; background:rgba(244,245,250,.94); display:none; align-items:center; justify-content:center; z-index:2000; flex-direction:column; gap:16px; }
.loading-overlay.show { display:flex; }
.loading-overlay .spinner-border { color:var(--accent); width:3rem; height:3rem; }
.loading-overlay p { font-size:14px; color:var(--text-secondary); }

#pantallaExito { display:none; }
#pantallaExito.show { display:block; }
.exito-hd { text-align:center; margin-bottom:22px; }
.exito-hd i { font-size:44px; color:var(--success); margin-bottom:10px; display:block; }
.exito-actions { display:flex; gap:10px; justify-content:center; margin-top:20px; flex-wrap:wrap; }

/* Bloque imprimible: mismo lenguaje visual que el membrete interno (ver_comite.php) */
#seccionImprimir { display:none; }
@media print {
    body * { visibility:hidden; }
    #seccionImprimir, #seccionImprimir * { visibility:visible; }
    #seccionImprimir { display:block !important; position:absolute; left:0; top:0; width:100%; }
}
</style>
</head>
<body>

<div class="pub-header">
    <div>
        <div class="pub-logo">
            <?php if ($logo_b64): ?><img src="data:image/png;base64,<?php echo $logo_b64; ?>"><?php else: ?><i class="fas fa-star"></i><?php endif; ?>
        </div>
    </div>
    <div style="text-align:left;">
        <div class="pub-title"><?php echo htmlspecialchars($nombre_partido); ?></div>
        <div class="pub-subtitle">Registro público de comités afectivos</div>
    </div>
</div>

<div class="pub-wrap" id="wizardWrap">
    <div class="pub-hero">
        <h1>Registrá tu Comité</h1>
        <p>Completá los datos en unos minutos. Al finalizar vas a poder imprimir o guardar tu comprobante en PDF.</p>
    </div>

    <div class="stepper" id="stepper">
        <div class="step-dot-wrap active" data-dot="1"><span class="step-dot">1</span><span class="step-label">Comité</span></div>
        <div class="step-dot-wrap" data-dot="2"><span class="step-dot">2</span><span class="step-label">Candidato</span></div>
        <div class="step-dot-wrap" data-dot="3"><span class="step-dot">3</span><span class="step-label">Coordinador</span></div>
        <div class="step-dot-wrap" data-dot="4"><span class="step-dot">4</span><span class="step-label">Miembros</span></div>
    </div>

    <div class="pub-card">

        <!-- PASO 1: DATOS DEL COMITÉ -->
        <div class="step-panel active" data-step="1">
            <h2>Datos del comité</h2>
            <p class="step-copy">Contanos el nombre y la ubicación de tu comité.</p>
            <div style="margin-bottom:16px;">
                <label class="lbl">Nombre del comité</label>
                <input type="text" class="fld" id="p1_nombre" placeholder="Ej: Comité Afectivo Zona Norte" required>
            </div>
            <div class="field-row">
                <div>
                    <label class="lbl">Provincia</label>
                    <input type="text" class="fld" id="p1_provincia" list="provinciaList" placeholder="Ej: Santo Domingo" autocomplete="off" required>
                    <datalist id="provinciaList"></datalist>
                </div>
                <div>
                    <label class="lbl">Municipio</label>
                    <input type="text" class="fld" id="p1_municipio" list="municipioList" placeholder="Seleccioná primero una provincia" autocomplete="off" disabled required>
                    <datalist id="municipioList"></datalist>
                </div>
            </div>
            <div class="field-row" style="margin-bottom:0;">
                <div>
                    <label class="lbl">Zona <span style="font-weight:400;">(opcional)</span></label>
                    <input type="text" class="fld" id="p1_zona" placeholder="Ej: Zona Norte, Sector 4...">
                </div>
                <div>
                    <label class="lbl">Circunscripción <span style="font-weight:400;">(opcional)</span></label>
                    <input type="text" class="fld" id="p1_circunscripcion" list="circunscripcionList" placeholder="Ej: 1ra. Circunscripción" autocomplete="off">
                    <datalist id="circunscripcionList"></datalist>
                </div>
            </div>
        </div>

        <!-- PASO 2: CANDIDATO -->
        <div class="step-panel" data-step="2">
            <h2>Elegí tu candidato</h2>
            <p class="step-copy">Seleccioná el candidato al que representa este comité.</p>
            <?php if (empty($candidatos)): ?>
            <div style="text-align:center;padding:30px 0;color:var(--text-tertiary);">
                <i class="fas fa-user-slash" style="font-size:32px;opacity:.3;display:block;margin-bottom:10px;"></i>
                Todavía no hay candidatos disponibles. Volvé a intentar más tarde.
            </div>
            <?php else: ?>
            <div class="cand-grid" id="candGrid">
                <?php foreach ($candidatos as $c): ?>
                <div class="cand-select-card" data-id="<?php echo $c['id']; ?>">
                    <?php if (!empty($c['foto'])): ?>
                    <img class="cand-photo" src="data:image/jpeg;base64,<?php echo base64_encode($c['foto']); ?>">
                    <?php else: ?>
                    <div class="cand-photo-fallback"><?php echo strtoupper(substr($c['nombre'],0,1)); ?></div>
                    <?php endif; ?>
                    <div>
                        <div class="cand-name"><?php echo htmlspecialchars($c['nombre']); ?></div>
                        <div class="cand-cargo"><?php echo $cargos[$c['cargo']] ?? ucfirst($c['cargo']); ?><?php echo !empty($c['descripcion']) ? ' · '.htmlspecialchars($c['descripcion']) : ''; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- PASO 3: COORDINADOR -->
        <div class="step-panel" data-step="3">
            <h2>Coordinador del comité</h2>
            <p class="step-copy">Buscá al coordinador por su cédula.</p>
            <div class="busqueda-box">
                <input type="text" class="fld" id="p3_cedula" placeholder="000-0000000-0" maxlength="13">
                <button type="button" class="btn btn-primary" id="p3_buscar" style="white-space:nowrap;"><i class="fas fa-search me-1"></i>Buscar</button>
            </div>
            <div id="p3_loading" class="text-center mb-3 d-none"><div class="spinner-border spinner-border-sm" style="color:var(--accent);"></div></div>
            <div id="p3_error" class="alert alert-danger d-none" style="font-size:12.5px;"></div>
            <div class="persona-preview" id="p3_preview">
                <img id="p3_foto" src="" alt="">
                <div>
                    <div class="persona-preview-name" id="p3_nombre_txt"></div>
                    <div class="persona-preview-meta" id="p3_meta_txt"></div>
                </div>
            </div>
            <div class="field-row" style="margin-bottom:0;">
                <div>
                    <label class="lbl">Teléfono</label>
                    <input type="tel" class="fld" id="p3_telefono" placeholder="809-000-0000">
                </div>
                <div>
                    <label class="lbl">Email <span style="font-weight:400;">(opcional)</span></label>
                    <input type="email" class="fld" id="p3_email">
                </div>
            </div>
        </div>

        <!-- PASO 4: MIEMBROS -->
        <div class="step-panel" data-step="4">
            <h2>Miembros del comité</h2>
            <p class="step-copy">Agregá los miembros por cédula (opcional). Podés continuar sin agregar ninguno.</p>
            <div class="busqueda-box">
                <input type="text" class="fld" id="p4_cedula" placeholder="000-0000000-0" maxlength="13">
                <button type="button" class="btn btn-primary" id="p4_buscar" style="white-space:nowrap;"><i class="fas fa-search me-1"></i>Buscar</button>
            </div>
            <div id="p4_loading" class="text-center mb-3 d-none"><div class="spinner-border spinner-border-sm" style="color:var(--accent);"></div></div>
            <div id="p4_error" class="alert alert-danger d-none" style="font-size:12.5px;"></div>
            <div class="persona-preview" id="p4_preview">
                <img id="p4_foto" src="" alt="">
                <div style="flex:1;">
                    <div class="persona-preview-name" id="p4_nombre_txt"></div>
                    <div class="persona-preview-meta" id="p4_meta_txt"></div>
                </div>
            </div>
            <div class="field-row" id="p4_extra" style="display:none;">
                <div>
                    <label class="lbl">Teléfono <span style="font-weight:400;">(opcional)</span></label>
                    <input type="tel" class="fld" id="p4_telefono" placeholder="809-000-0000">
                </div>
                <div style="align-self:end;">
                    <button type="button" class="btn btn-primary w-100" id="p4_agregar"><i class="fas fa-plus me-1"></i>Agregar a la lista</button>
                </div>
            </div>
            <div class="miembro-list" id="miembroList"></div>
        </div>

        <div id="formError" class="alert alert-danger d-none mt-3"></div>

        <div class="wizard-nav">
            <button type="button" class="btn btn-ghost" id="btnAtras" style="visibility:hidden;">Atrás</button>
            <button type="button" class="btn btn-primary" id="btnSiguiente">Siguiente</button>
        </div>
    </div>
</div>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-border" role="status"></div>
    <p>Guardando tu comité...</p>
</div>

<!-- PANTALLA DE ÉXITO / IMPRESIÓN -->
<div class="pub-wrap" id="pantallaExito">
    <div class="pub-card">
        <div class="exito-hd">
            <i class="fas fa-circle-check"></i>
            <h2 style="margin:0 0 6px;">¡Comité registrado con éxito!</h2>
            <p style="font-size:13px;color:var(--text-secondary);margin:0;">Guardá o imprimí tu comprobante para tus registros.</p>
        </div>
        <div class="exito-actions">
            <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fas fa-print me-1"></i>Imprimir / Guardar PDF</button>
            <button type="button" class="btn btn-ghost" onclick="location.reload()"><i class="fas fa-rotate-left me-1"></i>Registrar otro comité</button>
        </div>
    </div>
</div>

<!-- BLOQUE IMPRIMIBLE (membrete estilo CBA, igual lenguaje visual que ver_comite.php) -->
<div id="seccionImprimir" style="font-family:Arial,Helvetica,sans-serif;color:#1e293b;padding:20px;">
    <table width="100%" style="border-collapse:collapse;border:2px solid <?php echo htmlspecialchars($color_primary); ?>;margin-bottom:10px;">
        <tr>
            <td style="width:76px;padding:8px;vertical-align:middle;text-align:center;">
                <?php if ($logo_b64): ?><img src="data:image/png;base64,<?php echo $logo_b64; ?>" style="width:60px;height:60px;object-fit:contain;"><?php endif; ?>
            </td>
            <td style="background:<?php echo htmlspecialchars($color_primary); ?>;color:#fff;text-align:center;padding:10px 14px;vertical-align:middle;">
                <div style="font-size:16px;font-weight:800;letter-spacing:.3px;"><?php echo htmlspecialchars(strtoupper($nombre_partido)); ?><?php echo $siglas ? ' ('.htmlspecialchars($siglas).')' : ''; ?></div>
                <div style="font-size:11px;font-weight:600;margin-top:3px;letter-spacing:.3px;">FORMULARIO COMITÉ AFECTIVO (CBA) — REGISTRO PÚBLICO</div>
                <div id="pi_candidato_linea" style="font-size:10px;margin-top:3px;"></div>
            </td>
            <td style="width:76px;padding:8px;vertical-align:middle;text-align:center;" id="pi_candidato_foto_td"></td>
        </tr>
    </table>

    <table width="100%" style="border-collapse:collapse;margin-bottom:20px;font-size:10px;">
        <tr>
            <td colspan="4" style="background:#fde047;color:#111;font-weight:700;text-align:center;padding:5px;letter-spacing:.3px;border:1px solid #cbd5e1;">DATOS DEL ÁREA GEOGRÁFICA DEL COMITÉ</td>
        </tr>
        <tr>
            <td style="border:1px solid #cbd5e1;padding:5px 8px;width:25%;"><div style="color:#666;font-size:8.5px;">PROVINCIA</div><div style="font-weight:700;" id="pi_provincia"></div></td>
            <td style="border:1px solid #cbd5e1;padding:5px 8px;width:25%;"><div style="color:#666;font-size:8.5px;">CIRCUNSCRIPCIÓN</div><div style="font-weight:700;" id="pi_circunscripcion"></div></td>
            <td style="border:1px solid #cbd5e1;padding:5px 8px;width:25%;"><div style="color:#666;font-size:8.5px;">MUNICIPIO</div><div style="font-weight:700;" id="pi_municipio"></div></td>
            <td style="border:1px solid #cbd5e1;padding:5px 8px;width:25%;"><div style="color:#666;font-size:8.5px;">ZONA</div><div style="font-weight:700;" id="pi_zona"></div></td>
        </tr>
    </table>

    <h2 style="text-align:center;font-size:21px;font-weight:800;color:<?php echo htmlspecialchars($color_primary); ?>;margin:0 0 6px;">COMITÉ DE BASE AFECTIVO</h2>
    <div style="width:100%;height:3px;background:<?php echo htmlspecialchars($color_primary); ?>;margin-bottom:20px;"></div>

    <div style="border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-bottom:16px;page-break-inside:avoid;">
        <table width="100%" style="border-collapse:collapse;">
            <tr>
                <td style="width:90px;vertical-align:top;" id="pi_coord_foto_td"></td>
                <td style="vertical-align:top;padding-left:14px;">
                    <table width="100%" style="border-collapse:collapse;">
                        <tr>
                            <td style="font-size:16px;font-weight:700;" id="pi_coord_nombre"></td>
                            <td style="text-align:right;font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:1px;vertical-align:top;white-space:nowrap;">COORDINADOR</td>
                        </tr>
                    </table>
                    <div style="font-size:12px;margin-top:6px;line-height:1.7;">
                        <div><strong>Cédula:</strong> <span id="pi_coord_cedula"></span></div>
                        <div><strong>Teléfono:</strong> <span id="pi_coord_telefono"></span></div>
                        <div><strong>Municipio:</strong> <span id="pi_coord_municipio"></span></div>
                        <div><strong>Recinto:</strong> <span id="pi_coord_recinto"></span></div>
                        <div><strong>Colegio:</strong> <span id="pi_coord_colegio"></span></div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table width="100%" style="border-collapse:collapse;margin-bottom:22px;">
        <tr>
            <td style="width:50%;vertical-align:top;padding-right:8px;">
                <div style="border:1px solid #e2e8f0;border-radius:8px;padding:14px;font-size:12px;">
                    <table style="border-collapse:collapse;">
                        <tr><td style="font-weight:700;padding:3px 10px 3px 0;white-space:nowrap;">COMITÉ:</td><td style="padding:3px 0;" id="pi_nombre"></td></tr>
                        <tr><td style="font-weight:700;padding:3px 10px 3px 0;white-space:nowrap;">CANDIDATO:</td><td style="padding:3px 0;" id="pi_candidato"></td></tr>
                    </table>
                </div>
            </td>
            <td style="width:50%;vertical-align:top;padding-left:8px;">
                <div style="border:1px solid #e2e8f0;border-radius:8px;padding:14px;font-size:12px;">
                    <table style="border-collapse:collapse;">
                        <tr><td style="font-weight:700;padding:3px 10px 3px 0;white-space:nowrap;">TOTAL MIEMBROS:</td><td style="padding:3px 0;" id="pi_total_miembros"></td></tr>
                        <tr><td style="font-weight:700;padding:3px 10px 3px 0;white-space:nowrap;">FECHA DE REGISTRO:</td><td style="padding:3px 0;" id="pi_fecha"></td></tr>
                        <tr><td style="font-weight:700;padding:3px 10px 3px 0;white-space:nowrap;">ORIGEN:</td><td style="padding:3px 0;color:#94a3b8;">Enlace público</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div id="pi_miembros_wrap"></div>

    <div style="margin-top:22px;font-size:12px;font-weight:700;">NO DE FICHAS DE AFILIACIÓN ANEXAS: <span id="pi_total_miembros2"></span></div>

    <div style="text-align:center;margin-top:60px;page-break-inside:avoid;">
        <div style="width:260px;border-top:1px solid #333;margin:0 auto 8px;"></div>
        <div style="font-size:12px;font-weight:700;">FIRMA DEL COORDINADOR</div>
    </div>
</div>

<script>
const CARGOS = <?php echo json_encode($cargos, JSON_UNESCAPED_UNICODE); ?>;
const state = {
    step: 1,
    comite: { nombre:'', provincia:'', municipio:'', zona:'', circunscripcion:'' },
    candidato: null,
    coordinador: null,
    miembros: []
};

function escapeAttr(str) {
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str == null ? '' : String(str);
    return d.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    const totalSteps = 4;
    const btnAtras = document.getElementById('btnAtras');
    const btnSiguiente = document.getElementById('btnSiguiente');
    const formError = document.getElementById('formError');

    function showError(msg) {
        formError.textContent = msg;
        formError.classList.remove('d-none');
    }
    function clearError() { formError.classList.add('d-none'); }

    function goToStep(n) {
        clearError();
        document.querySelectorAll('.step-panel').forEach(p => p.classList.toggle('active', p.dataset.step == n));
        document.querySelectorAll('.step-dot-wrap').forEach(w => {
            const d = parseInt(w.dataset.dot, 10);
            w.classList.toggle('active', d === n);
            w.classList.toggle('done', d < n);
        });
        btnAtras.style.visibility = n === 1 ? 'hidden' : 'visible';
        btnSiguiente.textContent = n === totalSteps ? 'Finalizar registro' : 'Siguiente';
        state.step = n;
    }

    function validarPaso(n) {
        if (n === 1) {
            state.comite.nombre = document.getElementById('p1_nombre').value.trim();
            state.comite.provincia = document.getElementById('p1_provincia').value.trim();
            state.comite.municipio = document.getElementById('p1_municipio').value.trim();
            state.comite.zona = document.getElementById('p1_zona').value.trim();
            state.comite.circunscripcion = document.getElementById('p1_circunscripcion').value.trim();
            if (!state.comite.nombre || !state.comite.provincia || !state.comite.municipio) {
                showError('Completá nombre, provincia y municipio del comité.');
                return false;
            }
        }
        if (n === 2) {
            if (!state.candidato) {
                showError('Seleccioná un candidato para continuar.');
                return false;
            }
        }
        if (n === 3) {
            const telefono = document.getElementById('p3_telefono').value.trim();
            if (!state.coordinador || !state.coordinador.cedula) {
                showError('Buscá y confirmá los datos del coordinador.');
                return false;
            }
            if (!telefono) {
                showError('Ingresá el teléfono del coordinador.');
                return false;
            }
            state.coordinador.telefono = telefono;
            state.coordinador.email = document.getElementById('p3_email').value.trim();
        }
        return true;
    }

    btnSiguiente.addEventListener('click', function() {
        if (!validarPaso(state.step)) return;
        if (state.step < totalSteps) {
            goToStep(state.step + 1);
        } else {
            finalizarRegistro();
        }
    });
    btnAtras.addEventListener('click', function() {
        if (state.step > 1) goToStep(state.step - 1);
    });

    // ── Paso 1: autocompletado provincia/municipio/circunscripción ──
    (function() {
        const provinciaInput = document.getElementById('p1_provincia');
        const provinciaList  = document.getElementById('provinciaList');
        const municipioInput = document.getElementById('p1_municipio');
        const municipioList  = document.getElementById('municipioList');
        const circunscripcionList = document.getElementById('circunscripcionList');
        let provincias = [];

        function cargarMunicipios(provinciaId, valorPrevio) {
            municipioInput.disabled = true;
            municipioInput.placeholder = 'Cargando municipios...';
            municipioList.innerHTML = '';
            fetch(`api/municipios.php?provincia_id=${provinciaId}`)
                .then(r => r.json())
                .then(json => {
                    if (!json.success) return;
                    municipioList.innerHTML = json.data.map(m => `<option value="${escapeAttr(m.Descripcion)}"></option>`).join('');
                    municipioInput.disabled = false;
                    municipioInput.placeholder = 'Escribí para buscar...';
                    municipioInput.value = valorPrevio || '';
                })
                .catch(() => { municipioInput.placeholder = 'Error al cargar municipios'; });
        }

        provinciaInput.addEventListener('input', function() {
            const match = provincias.find(p => p.Descripcion === provinciaInput.value);
            if (match) {
                cargarMunicipios(match.ID);
            } else {
                municipioInput.disabled = true;
                municipioInput.value = '';
                municipioInput.placeholder = 'Seleccioná primero una provincia';
                municipioList.innerHTML = '';
            }
        });

        fetch('api/provincias.php')
            .then(r => r.json())
            .then(json => {
                if (!json.success) return;
                provincias = json.data;
                provinciaList.innerHTML = provincias.map(p => `<option value="${escapeAttr(p.Descripcion)}"></option>`).join('');
            })
            .catch(() => {});

        fetch('api/circunscripciones.php')
            .then(r => r.json())
            .then(json => {
                if (!json.success) return;
                circunscripcionList.innerHTML = json.data.map(c => `<option value="${escapeAttr(c.Descripcion)}"></option>`).join('');
            })
            .catch(() => {});
    })();

    // ── Paso 2: selección de candidato ──
    document.querySelectorAll('.cand-select-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.cand-select-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            const fotoImg = card.querySelector('.cand-photo');
            state.candidato = {
                id: card.dataset.id,
                nombre: card.querySelector('.cand-name').textContent,
                cargo: card.querySelector('.cand-cargo').textContent,
                foto: fotoImg ? fotoImg.src : ''
            };
            clearError();
        });
    });

    // ── Paso 3: búsqueda de coordinador por cédula ──
    function formatearCedula(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 3)  value = value.substring(0, 3) + '-' + value.substring(3);
            if (value.length > 11) value = value.substring(0, 11) + '-' + value.substring(11, 12);
            e.target.value = value;
        });
    }
    formatearCedula(document.getElementById('p3_cedula'));
    formatearCedula(document.getElementById('p4_cedula'));

    document.getElementById('p3_buscar').addEventListener('click', function() {
        const cedula = document.getElementById('p3_cedula').value.trim();
        const loading = document.getElementById('p3_loading');
        const error = document.getElementById('p3_error');
        const preview = document.getElementById('p3_preview');
        if (!cedula || cedula.replace(/\D/g,'').length < 11) {
            error.textContent = 'Ingresá una cédula válida.';
            error.classList.remove('d-none');
            return;
        }
        loading.classList.remove('d-none');
        error.classList.add('d-none');
        preview.classList.remove('show');
        fetch(`api/consulta.php?cedula=${encodeURIComponent(cedula)}`)
            .then(r => r.json())
            .then(json => {
                loading.classList.add('d-none');
                if (!json.success || !json.data) {
                    error.textContent = json.message || 'No se encontraron resultados para esa cédula.';
                    error.classList.remove('d-none');
                    return;
                }
                const d = json.data;
                const nombreCompleto = `${d.nombres} ${d.apellido1} ${d.apellido2}`;
                document.getElementById('p3_foto').src = d.Imagen ? ('data:image/jpeg;base64,' + d.Imagen) : 'fotos/default.svg';
                document.getElementById('p3_nombre_txt').textContent = nombreCompleto;
                document.getElementById('p3_meta_txt').textContent = `Cédula ${d.Cedula} · ${d.Municipio || ''}`;
                preview.classList.add('show');
                state.coordinador = {
                    cedula: d.Cedula, nombre: nombreCompleto, municipio: d.Municipio || '',
                    recinto: d.Recinto || '', colegio: d.CodigoColegio || '', foto: d.Imagen || '',
                    telefono: '', email: ''
                };
                clearError();
            })
            .catch(() => {
                loading.classList.add('d-none');
                error.textContent = 'Error al consultar. Intentá de nuevo.';
                error.classList.remove('d-none');
            });
    });

    // ── Paso 4: búsqueda/agregado de miembros (opcional) ──
    let miembroEncontrado = null;
    document.getElementById('p4_buscar').addEventListener('click', function() {
        const cedula = document.getElementById('p4_cedula').value.trim();
        const loading = document.getElementById('p4_loading');
        const error = document.getElementById('p4_error');
        const preview = document.getElementById('p4_preview');
        const extra = document.getElementById('p4_extra');
        if (!cedula || cedula.replace(/\D/g,'').length < 11) {
            error.textContent = 'Ingresá una cédula válida.';
            error.classList.remove('d-none');
            return;
        }
        loading.classList.remove('d-none');
        error.classList.add('d-none');
        preview.classList.remove('show');
        extra.style.display = 'none';
        fetch(`api/consulta.php?cedula=${encodeURIComponent(cedula)}`)
            .then(r => r.json())
            .then(json => {
                loading.classList.add('d-none');
                if (!json.success || !json.data) {
                    error.textContent = json.message || 'No se encontraron resultados para esa cédula.';
                    error.classList.remove('d-none');
                    miembroEncontrado = null;
                    return;
                }
                const d = json.data;
                const nombreCompleto = `${d.nombres} ${d.apellido1} ${d.apellido2}`;
                document.getElementById('p4_foto').src = d.Imagen ? ('data:image/jpeg;base64,' + d.Imagen) : 'fotos/default.svg';
                document.getElementById('p4_nombre_txt').textContent = nombreCompleto;
                document.getElementById('p4_meta_txt').textContent = `Cédula ${d.Cedula} · ${d.Municipio || ''}`;
                preview.classList.add('show');
                extra.style.display = 'grid';
                miembroEncontrado = {
                    cedula: d.Cedula, nombre: nombreCompleto, municipio: d.Municipio || '',
                    recinto: d.Recinto || '', colegio: d.CodigoColegio || '', foto: d.Imagen || ''
                };
            })
            .catch(() => {
                loading.classList.add('d-none');
                error.textContent = 'Error al consultar. Intentá de nuevo.';
                error.classList.remove('d-none');
            });
    });

    function renderMiembros() {
        const list = document.getElementById('miembroList');
        list.innerHTML = state.miembros.map((m, i) => `
            <div class="miembro-chip">
                ${m.foto ? `<img class="miembro-chip-av" src="data:image/jpeg;base64,${m.foto}">` : `<div class="miembro-chip-av">${escapeHtml((m.nombre||'?').charAt(0).toUpperCase())}</div>`}
                <div class="miembro-chip-name">${escapeHtml(m.nombre)}</div>
                <div class="miembro-chip-cedula">${escapeHtml(m.cedula)}</div>
                <button type="button" class="miembro-chip-remove" data-idx="${i}"><i class="fas fa-times"></i></button>
            </div>
        `).join('');
        list.querySelectorAll('.miembro-chip-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                state.miembros.splice(parseInt(btn.dataset.idx, 10), 1);
                renderMiembros();
            });
        });
    }

    document.getElementById('p4_agregar').addEventListener('click', function() {
        if (!miembroEncontrado) return;
        if (state.miembros.some(m => m.cedula === miembroEncontrado.cedula)) {
            document.getElementById('p4_error').textContent = 'Esa persona ya está en la lista.';
            document.getElementById('p4_error').classList.remove('d-none');
            return;
        }
        state.miembros.push(Object.assign({}, miembroEncontrado, {
            telefono: document.getElementById('p4_telefono').value.trim(),
            email: ''
        }));
        renderMiembros();
        // reset mini-formulario
        document.getElementById('p4_cedula').value = '';
        document.getElementById('p4_telefono').value = '';
        document.getElementById('p4_preview').classList.remove('show');
        document.getElementById('p4_extra').style.display = 'none';
        miembroEncontrado = null;
    });

    // ── Finalizar registro ──
    function finalizarRegistro() {
        const overlay = document.getElementById('loadingOverlay');
        overlay.classList.add('show');

        const payload = {
            sitio_web: '', // honeypot, debe quedar vacío
            nombre: state.comite.nombre,
            provincia: state.comite.provincia,
            municipio: state.comite.municipio,
            zona: state.comite.zona,
            circunscripcion: state.comite.circunscripcion,
            candidato_id: state.candidato ? state.candidato.id : '',
            coordinador: state.coordinador,
            miembros: state.miembros
        };

        fetch('api/publico_guardar_comite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(r => r.json())
            .then(json => {
                overlay.classList.remove('show');
                if (!json.success) {
                    showError(json.message || 'No se pudo guardar el comité. Intentá de nuevo.');
                    return;
                }
                mostrarPantallaExito();
            })
            .catch(() => {
                overlay.classList.remove('show');
                showError('Error de conexión. Intentá de nuevo.');
            });
    }

    function mostrarPantallaExito() {
        document.getElementById('wizardWrap').style.display = 'none';
        document.getElementById('pantallaExito').classList.add('show');

        document.getElementById('pi_nombre').textContent = state.comite.nombre;
        document.getElementById('pi_provincia').textContent = state.comite.provincia || '—';
        document.getElementById('pi_circunscripcion').textContent = state.comite.circunscripcion || '—';
        document.getElementById('pi_municipio').textContent = state.comite.municipio || '—';
        document.getElementById('pi_zona').textContent = state.comite.zona || '—';
        document.getElementById('pi_candidato').textContent = state.candidato ? `${state.candidato.nombre} (${state.candidato.cargo})` : '—';
        document.getElementById('pi_candidato_linea').textContent = state.candidato ? `${state.candidato.nombre.toUpperCase()} ${state.candidato.cargo ? 'PARA ' + state.candidato.cargo.toUpperCase() : ''}` : '';
        document.getElementById('pi_total_miembros').textContent = state.miembros.length;
        document.getElementById('pi_total_miembros2').textContent = state.miembros.length;
        document.getElementById('pi_fecha').textContent = new Date().toLocaleDateString();

        const c = state.coordinador;
        document.getElementById('pi_coord_nombre').textContent = c.nombre || '—';
        document.getElementById('pi_coord_cedula').textContent = c.cedula || '—';
        document.getElementById('pi_coord_telefono').textContent = c.telefono || '—';
        document.getElementById('pi_coord_municipio').textContent = c.municipio || '—';
        document.getElementById('pi_coord_recinto').textContent = c.recinto || '—';
        document.getElementById('pi_coord_colegio').textContent = c.colegio || '—';
        document.getElementById('pi_coord_foto_td').innerHTML = c.foto
            ? `<img src="data:image/jpeg;base64,${c.foto}" style="width:76px;height:76px;border-radius:6px;object-fit:cover;border:3px solid <?php echo htmlspecialchars($color_primary); ?>;">`
            : `<div style="width:76px;height:76px;border-radius:6px;background:#eee;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;color:#999;border:3px solid <?php echo htmlspecialchars($color_primary); ?>;">${escapeHtml((c.nombre||'?').charAt(0).toUpperCase())}</div>`;

        const candFotoTd = document.getElementById('pi_candidato_foto_td');
        if (state.candidato && state.candidato.foto) {
            candFotoTd.innerHTML = `<img src="${state.candidato.foto}" style="width:60px;height:60px;border-radius:50%;object-fit:cover;border:2px solid <?php echo htmlspecialchars($color_primary); ?>;">`;
        } else if (state.candidato) {
            candFotoTd.innerHTML = `<div style="width:60px;height:60px;border-radius:50%;background:#eee;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#999;border:2px solid <?php echo htmlspecialchars($color_primary); ?>;margin:0 auto;">${escapeHtml((state.candidato.nombre||'?').charAt(0).toUpperCase())}</div>`;
        } else {
            candFotoTd.innerHTML = '';
        }

        const wrap = document.getElementById('pi_miembros_wrap');
        if (state.miembros.length > 0) {
            wrap.innerHTML = `
                <h3 style="text-align:center;font-size:15px;font-weight:700;color:<?php echo htmlspecialchars($color_primary); ?>;margin-bottom:12px;">&#9776; INTEGRANTES DEL COMITÉ</h3>
                <table width="100%" style="border-collapse:collapse;font-size:11px;">
                    <thead><tr style="color:#94a3b8;">
                        <th style="padding:6px;text-align:left;">Foto</th>
                        <th style="padding:6px;text-align:left;">No.</th>
                        <th style="padding:6px;text-align:left;">Nombre Completo</th>
                        <th style="padding:6px;text-align:left;">Cédula</th>
                        <th style="padding:6px;text-align:left;">Teléfono</th>
                        <th style="padding:6px;text-align:left;">Municipio</th>
                    </tr></thead>
                    <tbody>
                    ${state.miembros.map((m, i) => `
                        <tr style="border-top:1px solid #e2e8f0;page-break-inside:avoid;">
                            <td style="padding:6px;">${m.foto ? `<img src="data:image/jpeg;base64,${m.foto}" style="width:38px;height:38px;border-radius:50%;object-fit:cover;">` : `<div style="width:38px;height:38px;border-radius:50%;background:#eee;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#999;">${escapeHtml((m.nombre||'?').charAt(0).toUpperCase())}</div>`}</td>
                            <td style="padding:6px;">${i + 1}</td>
                            <td style="padding:6px;">${escapeHtml(m.nombre)}</td>
                            <td style="padding:6px;">${escapeHtml(m.cedula)}</td>
                            <td style="padding:6px;">${escapeHtml(m.telefono || 'N/A')}</td>
                            <td style="padding:6px;">${escapeHtml(m.municipio)}</td>
                        </tr>
                    `).join('')}
                    </tbody>
                </table>
            `;
        } else {
            wrap.innerHTML = '';
        }
    }
});
</script>
</body>
</html>
