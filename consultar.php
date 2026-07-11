<?php
require_once 'includes/auth.php';
require_once 'db/config.php';

// Verificar que el usuario esté autenticado
requiereAutenticacion();

// Título de la página
$titulo_pagina = 'Consultar Cédula';

// Incluir el header
include 'includes/header.php';
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Consulta de Cédula</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <form id="formBusqueda">
                    <div class="form-group mb-4">
                        <label for="cedula" class="form-label">Ingrese el número de cédula:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="cedula" name="cedula" placeholder="000-0000000-0" maxlength="13">
                            <button class="btn btn-primary" type="submit" id="buscarBtn">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                        <div class="form-text text-muted">Formato: 000-0000000-0</div>
                    </div>
                </form>
                
                <div id="loading" class="text-center d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Consultando datos...</p>
                </div>
                
                <div id="error" class="alert alert-danger d-none" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <span id="errorMessage"></span>
                </div>
            </div>
        </div>
        
        <!-- Resultados de la búsqueda -->
        <div id="resultados" class="d-none">
            <hr class="my-4">
            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <div class="img-container mb-3">
                        <img id="foto" src="" alt="Foto" class="img-fluid rounded" style="max-height: 200px;">
                    </div>
                </div>
                <div class="col-md-8">
                    <h4 class="mb-3" id="nombreCompleto"></h4>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Cédula:</span>
                                    <span id="cedulaResultado"></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Fecha de Nacimiento:</span>
                                    <span id="fechaNacimiento"></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Sexo:</span>
                                    <span id="sexo"></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Estado Civil:</span>
                                    <span id="estadoCivil"></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Nacionalidad:</span>
                                    <span id="nacionalidad"></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Provincia:</span>
                                    <span id="provincia"></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Municipio:</span>
                                    <span id="municipio"></span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Zona:</span>
                                    <span id="zona"></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Recinto:</span>
                                    <span id="recinto"></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Recinto Votación:</span>
                                    <span id="recintoVotacion"></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Colegio:</span>
                                    <span id="colegio"></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Circunscripción:</span>
                                    <span id="circunscripcion"></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button id="btnImprimir" class="btn btn-success">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <button id="btnPDF" class="btn btn-primary ml-2">
                            <i class="fas fa-file-pdf"></i> Guardar PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formBusqueda = document.getElementById('formBusqueda');
    const cedulaInput = document.getElementById('cedula');
    const loading = document.getElementById('loading');
    const error = document.getElementById('error');
    const errorMessage = document.getElementById('errorMessage');
    const resultados = document.getElementById('resultados');
    
    // Formatear la cédula mientras se escribe
    cedulaInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 0) {
            if (value.length <= 3) {
                value = value;
            } else if (value.length <= 10) {
                value = value.substring(0, 3) + '-' + value.substring(3);
            } else {
                value = value.substring(0, 3) + '-' + value.substring(3, 10) + '-' + value.substring(10, 11);
            }
        }
        e.target.value = value;
    });
    
    // Función de búsqueda
    formBusqueda.addEventListener('submit', function(e) {
        e.preventDefault();
        const cedula = cedulaInput.value.trim();
        
        if (!cedula || cedula.replace(/\D/g, '').length < 11) {
            error.classList.remove('d-none');
            errorMessage.textContent = 'Ingrese una cédula válida';
            resultados.classList.add('d-none');
            return;
        }
        
        // Mostrar loading y ocultar mensajes anteriores
        loading.classList.remove('d-none');
        error.classList.add('d-none');
        resultados.classList.add('d-none');
        
        // Realizar la consulta a la API
        fetch(`api/consulta.php?cedula=${encodeURIComponent(cedula)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(json => {
                loading.classList.add('d-none');
                
                if (!json.success) {
                    error.classList.remove('d-none');
                    errorMessage.textContent = json.message || 'Error en la consulta';
                    return;
                }
                
                const d = json.data;
                
                // Mostrar resultados
                if (d.Imagen) {
                    document.getElementById('foto').src = 'data:image/jpeg;base64,' + d.Imagen;
                } else {
                    document.getElementById('foto').src = 'fotos/default.svg';
                }
                
                document.getElementById('nombreCompleto').textContent = `${d.nombres} ${d.apellido1} ${d.apellido2}`;
                document.getElementById('cedulaResultado').textContent = d.Cedula;
                document.getElementById('fechaNacimiento').textContent = d.FechaNacimiento;
                document.getElementById('sexo').textContent = d.SexoDescripcion;
                document.getElementById('estadoCivil').textContent = d.EstadoCivil;
                document.getElementById('nacionalidad').textContent = d.Nacionalidad;
                document.getElementById('provincia').textContent = d.Provincia;
                document.getElementById('municipio').textContent = d.Municipio;
                document.getElementById('zona').textContent = d.Zona;
                document.getElementById('recinto').textContent = d.Recinto;
                document.getElementById('recintoVotacion').textContent = d.RecintoVotacion;
                document.getElementById('colegio').textContent = d.CodigoColegio;
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
    
    // Función para imprimir resultados
    document.getElementById('btnImprimir').addEventListener('click', function() {
        // Crear un área imprimible
        const printableArea = document.createElement('div');
        printableArea.id = 'printable-area';
        printableArea.style.padding = '20px';
        printableArea.style.backgroundColor = 'white';
        
        // Copiar el contenido de la tarjeta de resultados
        const resultCard = document.getElementById('resultados').cloneNode(true);
        resultCard.style.display = 'block';
        resultCard.style.boxShadow = 'none';
        resultCard.style.border = '1px solid #ddd';
        resultCard.querySelector('.mt-4').remove(); // Quitar botones
        
        // Agregar encabezado
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
        
        // Agregar al documento
        document.body.appendChild(printableArea);
        
        // Imprimir
        window.print();
        
        // Eliminar el área imprimible después de imprimir
        setTimeout(() => {
            printableArea.remove();
        }, 100);
    });
    
    // Función para exportar a PDF
    document.getElementById('btnPDF').addEventListener('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Título
        doc.setFontSize(18);
        doc.setTextColor(78, 115, 223);
        doc.text('Consulta de Cédula', 105, 20, null, null, 'center');
        
        // Fecha
        doc.setFontSize(11);
        doc.setTextColor(100, 100, 100);
        doc.text(`Generado: ${new Date().toLocaleDateString()}`, 105, 28, null, null, 'center');
        
        // Datos
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
        
        // Tabla
        doc.setFontSize(12);
        doc.setTextColor(0, 0, 0);
        doc.autoTable({
            startY: 40,
            head: [['Campo', 'Valor']],
            body: data,
            theme: 'grid',
            headStyles: { 
                fillColor: [78, 115, 223],
                textColor: [255, 255, 255],
                fontStyle: 'bold'
            },
            styles: { fontSize: 11 }
        });
        
        // Imagen (si está disponible)
        const imgElement = document.getElementById('foto');
        if (imgElement && imgElement.src) {
            try {
                const imgData = imgElement.src;
                doc.addImage(imgData, 'JPEG', 15, doc.autoTable.previous.finalY + 10, 40, 50);
            } catch (e) {
                console.error('Error al agregar imagen al PDF:', e);
            }
        }
        
        // Guardar PDF
        doc.save(`consulta-cedula-${document.getElementById('cedulaResultado').textContent}.pdf`);
    });
});
</script>

<?php include 'includes/footer.php'; ?>