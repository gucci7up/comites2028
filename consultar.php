<?php
require_once 'includes/auth.php';
require_once 'db/config.php';

requiereAutenticacion();

$titulo_pagina    = 'Consultar Cédula';
$subtitulo_pagina = 'Buscá datos de un ciudadano por su cédula';
include 'includes/header.php';
?>

<style>
.consulta-wrap { display:flex; flex-direction:column; align-items:center; }
.consulta-card { width:100%; max-width:640px; background:#fff; border-radius:var(--radius-card); padding:28px; box-shadow:var(--shadow-sm); margin-bottom:22px; }
.consulta-search { display:flex; gap:12px; }
.consulta-result-hd { display:flex; align-items:center; gap:16px; margin-bottom:22px; }
.consulta-avatar { width:64px; height:64px; border-radius:50%; background:linear-gradient(135deg,var(--accent),var(--accent-light)); color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:700; flex-shrink:0; overflow:hidden; }
.consulta-avatar img { width:100%; height:100%; object-fit:cover; }
.consulta-name { font-size:17px; font-weight:700; }
.consulta-cedula { font-size:12.5px; color:var(--text-tertiary); }
.consulta-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px 24px; }
.consulta-field-label { font-size:11px; font-weight:600; color:var(--text-tertiary); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.consulta-field-value { font-size:13.5px; font-weight:600; }
.consulta-footer { margin-top:22px; padding-top:20px; border-top:1px solid #f0f0f6; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.consulta-cta { display:inline-flex; align-items:center; gap:8px; background:var(--accent-tint); color:var(--accent); font-size:12.5px; font-weight:700; padding:9px 16px; border-radius:10px; text-decoration:none; }
.consulta-actions { display:flex; gap:8px; }
.consulta-empty { text-align:center; padding:40px 20px; color:var(--text-tertiary); }
.consulta-empty i { font-size:40px; opacity:.25; margin-bottom:12px; display:block; }
</style>

<div class="consulta-wrap">
  <div class="consulta-card">
    <form id="formBusqueda" class="consulta-search">
        <input type="text" class="fld" style="flex:1;" id="cedula" name="cedula" placeholder="000-0000000-0" maxlength="13" required>
        <button class="btn btn-primary" type="submit" id="buscarBtn" style="white-space:nowrap;">
            <i class="fas fa-search me-1"></i> Buscar
        </button>
    </form>
    <div id="loading" class="text-center mt-3 d-none">
        <div class="spinner-border" style="color:var(--accent);" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="mt-2 mb-0" style="font-size:13px;color:var(--text-secondary);">Consultando datos...</p>
    </div>
    <div id="error" class="alert alert-danger d-none mt-3" role="alert">
        <i class="fas fa-exclamation-triangle me-1"></i> <span id="errorMessage"></span>
    </div>
  </div>

  <div class="consulta-card d-none" id="resultados">
    <div class="consulta-result-hd">
        <div class="consulta-avatar" id="avatarWrap"><span id="avatarInicial"></span><img id="foto" src="" alt="Foto" style="display:none;"></div>
        <div>
            <div class="consulta-name" id="nombreCompleto"></div>
            <div class="consulta-cedula">Cédula <span id="cedulaResultado"></span></div>
        </div>
    </div>
    <div class="consulta-grid">
        <div><div class="consulta-field-label">Fecha de nacimiento</div><div class="consulta-field-value" id="fechaNacimiento"></div></div>
        <div><div class="consulta-field-label">Sexo</div><div class="consulta-field-value" id="sexo"></div></div>
        <div><div class="consulta-field-label">Estado civil</div><div class="consulta-field-value" id="estadoCivil"></div></div>
        <div><div class="consulta-field-label">Nacionalidad</div><div class="consulta-field-value" id="nacionalidad"></div></div>
        <div><div class="consulta-field-label">Provincia</div><div class="consulta-field-value" id="provincia"></div></div>
        <div><div class="consulta-field-label">Municipio</div><div class="consulta-field-value" id="municipio"></div></div>
        <div><div class="consulta-field-label">Zona</div><div class="consulta-field-value" id="zona"></div></div>
        <div><div class="consulta-field-label">Recinto</div><div class="consulta-field-value" id="recinto"></div></div>
        <div><div class="consulta-field-label">Recinto votación</div><div class="consulta-field-value" id="recintoVotacion"></div></div>
        <div><div class="consulta-field-label">Colegio</div><div class="consulta-field-value" id="colegio"></div></div>
        <div><div class="consulta-field-label">Circunscripción</div><div class="consulta-field-value" id="circunscripcion"></div></div>
    </div>
    <div class="consulta-footer">
        <span style="font-size:12.5px;color:var(--text-tertiary);">¿Agregar como miembro a un comité?</span>
        <div class="consulta-actions">
            <button id="btnImprimir" class="action-link" title="Imprimir"><i class="fas fa-print"></i></button>
            <button id="btnPDF" class="action-link" title="Guardar PDF"><i class="fas fa-file-pdf"></i></button>
            <a href="crear_comite.php" class="consulta-cta"><i class="fas fa-user-plus"></i>Agregar</a>
        </div>
    </div>
  </div>

  <div class="consulta-empty" id="noResults">
    <i class="fas fa-database"></i>
    <p style="font-size:13px;margin:0;">Ingresá un número de cédula para ver los resultados.</p>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const formBusqueda = document.getElementById('formBusqueda');
    const cedulaInput  = document.getElementById('cedula');
    const loading      = document.getElementById('loading');
    const error        = document.getElementById('error');
    const errorMessage = document.getElementById('errorMessage');
    const resultados   = document.getElementById('resultados');
    const noResults    = document.getElementById('noResults');

    cedulaInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 3)  value = value.substring(0, 3) + '-' + value.substring(3);
        if (value.length > 11) value = value.substring(0, 11) + '-' + value.substring(11, 12);
        e.target.value = value;
    });

    formBusqueda.addEventListener('submit', function(e) {
        e.preventDefault();
        const cedula = cedulaInput.value.trim();

        if (!cedula || cedula.replace(/\D/g, '').length < 11) {
            error.classList.remove('d-none');
            errorMessage.textContent = 'Ingrese una cédula válida';
            resultados.classList.add('d-none');
            return;
        }

        loading.classList.remove('d-none');
        error.classList.add('d-none');
        resultados.classList.add('d-none');
        noResults.classList.add('d-none');

        fetch(`api/consulta.php?cedula=${encodeURIComponent(cedula)}`)
            .then(response => {
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                return response.json();
            })
            .then(json => {
                loading.classList.add('d-none');

                if (!json.success) {
                    error.classList.remove('d-none');
                    errorMessage.textContent = json.message || 'Error en la consulta';
                    noResults.classList.remove('d-none');
                    return;
                }

                const d = json.data;
                const foto = document.getElementById('foto');
                const nombreCompleto = `${d.nombres} ${d.apellido1} ${d.apellido2}`;
                if (d.Imagen) {
                    foto.src = 'data:image/jpeg;base64,' + d.Imagen;
                    foto.style.display = 'block';
                    document.getElementById('avatarInicial').style.display = 'none';
                } else {
                    foto.style.display = 'none';
                    document.getElementById('avatarInicial').style.display = 'block';
                    document.getElementById('avatarInicial').textContent = (d.nombres || '?').charAt(0).toUpperCase();
                }

                document.getElementById('nombreCompleto').textContent  = nombreCompleto;
                document.getElementById('cedulaResultado').textContent = d.Cedula;
                document.getElementById('fechaNacimiento').textContent = d.FechaNacimiento;
                document.getElementById('sexo').textContent            = d.SexoDescripcion;
                document.getElementById('estadoCivil').textContent     = d.EstadoCivil;
                document.getElementById('nacionalidad').textContent    = d.Nacionalidad;
                document.getElementById('provincia').textContent       = d.Provincia;
                document.getElementById('municipio').textContent       = d.Municipio;
                document.getElementById('zona').textContent            = d.Zona;
                document.getElementById('recinto').textContent         = d.Recinto;
                document.getElementById('recintoVotacion').textContent = d.RecintoVotacion;
                document.getElementById('colegio').textContent         = d.CodigoColegio;
                document.getElementById('circunscripcion').textContent = d.Circunscripcion;

                resultados.classList.remove('d-none');
            })
            .catch(err => {
                loading.classList.add('d-none');
                error.classList.remove('d-none');
                errorMessage.textContent = 'Error al realizar la consulta. Intente nuevamente.';
                console.error('Error en la consulta:', err);
            });
    });

    document.getElementById('btnImprimir').addEventListener('click', function() {
        const printableArea = document.createElement('div');
        printableArea.id = 'printable-area';
        printableArea.style.padding = '20px';
        printableArea.style.backgroundColor = 'white';

        const resultCard = document.getElementById('resultados').cloneNode(true);
        resultCard.style.display = 'block';
        resultCard.style.boxShadow = 'none';
        resultCard.style.border = '1px solid #ddd';
        const footer = resultCard.querySelector('.consulta-footer');
        if (footer) footer.remove();

        const header = document.createElement('div');
        header.style.borderBottom = '2px solid #4e73df';
        header.style.paddingBottom = '10px';
        header.style.marginBottom = '20px';
        header.innerHTML = `
            <h2 style="color: #4e73df; margin: 0;">Consulta de Cédula</h2>
            <p style="margin: 5px 0; color: #666;">${new Date().toLocaleDateString()}</p>
        `;

        printableArea.appendChild(header);
        printableArea.appendChild(resultCard);
        document.body.appendChild(printableArea);
        window.print();
        setTimeout(() => { printableArea.remove(); }, 100);
    });

    document.getElementById('btnPDF').addEventListener('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        doc.setFontSize(18);
        doc.setTextColor(78, 115, 223);
        doc.text('Consulta de Cédula', 105, 20, null, null, 'center');

        doc.setFontSize(11);
        doc.setTextColor(100, 100, 100);
        doc.text(`Generado: ${new Date().toLocaleDateString()}`, 105, 28, null, null, 'center');

        const data = [
            ['Cédula', document.getElementById('cedulaResultado').textContent],
            ['Nombre', document.getElementById('nombreCompleto').textContent],
            ['Nacimiento', document.getElementById('fechaNacimiento').textContent],
            ['Sexo', document.getElementById('sexo').textContent],
            ['Estado Civil', document.getElementById('estadoCivil').textContent],
            ['Nacionalidad', document.getElementById('nacionalidad').textContent],
            ['Provincia', document.getElementById('provincia').textContent],
            ['Municipio', document.getElementById('municipio').textContent],
            ['Zona', document.getElementById('zona').textContent],
            ['Recinto', document.getElementById('recinto').textContent],
            ['Recinto Votación', document.getElementById('recintoVotacion').textContent],
            ['Colegio', document.getElementById('colegio').textContent],
            ['Circunscripción', document.getElementById('circunscripcion').textContent]
        ];

        doc.setFontSize(12);
        doc.setTextColor(0, 0, 0);
        doc.autoTable({
            startY: 40,
            head: [['Campo', 'Valor']],
            body: data,
            theme: 'grid',
            headStyles: { fillColor: [78, 115, 223], textColor: [255, 255, 255], fontStyle: 'bold' },
            styles: { fontSize: 11 }
        });

        const imgElement = document.getElementById('foto');
        if (imgElement && imgElement.src && imgElement.style.display !== 'none') {
            try {
                doc.addImage(imgElement.src, 'JPEG', 15, doc.autoTable.previous.finalY + 10, 40, 50);
            } catch (e) {
                console.error('Error al agregar imagen al PDF:', e);
            }
        }

        doc.save(`consulta-cedula-${document.getElementById('cedulaResultado').textContent}.pdf`);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
