<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Ubicaciones - Royal</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background: #050505; color: #fff; font-family: 'Poppins', sans-serif; padding: 20px; }
        .container { max-width: 600px; margin: auto; padding-bottom: 50px; }
        
        .header-title { color: #FFD700; text-align: center; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }

        /* MAPA */
        #map { 
            height: 250px; width: 100%; border-radius: 12px; 
            border: 2px solid #333; margin-bottom: 15px; z-index: 1; 
        }

        .card-form { background: #151515; padding: 20px; border-radius: 15px; border: 1px solid #333; margin-bottom: 20px; }
        
        .form-control { 
            width: 100%; padding: 12px; background: #222; border: 1px solid #444; 
            color: #fff; border-radius: 8px; margin-bottom: 12px; outline: none;
        }
        
        .tags-row { display: flex; gap: 8px; margin-bottom: 15px; overflow-x: auto; padding-bottom: 5px; }
        .tag-btn {
            background: #333; color: #ccc; border: 1px solid #444; padding: 8px 15px;
            border-radius: 20px; cursor: pointer; font-size: 0.8rem; white-space: nowrap;
        }
        .tag-btn.active { background: #FFD700; color: #000; border-color: #FFD700; font-weight: bold; }

        .btn-save {
            width: 100%; background: linear-gradient(45deg, #FFD700, #b7892b); 
            color: #000; padding: 14px; border: none; border-radius: 8px; 
            font-weight: bold; cursor: pointer; font-size: 1rem;
        }

        .addr-card {
            background: #111; border: 1px solid #333; border-radius: 12px; padding: 15px;
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;
        }
        .btn-del { color: #ef5350; background: none; border: none; cursor: pointer; font-size: 1.2rem; }
    </style>
</head>
<body>

<div class="container">
    <h3 class="header-title">Nueva Ubicación</h3>

    <div class="card-form">
        <label style="color:#888; font-size:0.8rem;">Mueve el pin a tu ubicación exacta:</label>
        <div id="map"></div>
        <small id="statusGPS" style="color:#4caf50; display:block; margin-bottom:10px;"><i class="fa-solid fa-location-crosshairs"></i> Localizando...</small>

        <form id="formDireccion">
            <input type="hidden" name="lat" id="lat">
            <input type="hidden" name="lon" id="lon">

            <div class="tags-row">
                <div class="tag-btn active" onclick="setTag('Casa', this)">Casa</div>
                <div class="tag-btn" onclick="setTag('Trabajo', this)">Trabajo</div>
                <div class="tag-btn" onclick="setTag('Pareja', this)">Pareja</div>
                <div class="tag-btn" onclick="setTag('Otro', this)">Otro</div>
            </div>
            
            <input type="text" name="etiqueta" id="inputEtiqueta" value="Casa" type="hidden" style="display:none;">
            
            <input type="text" name="direccion" id="direccionTxt" class="form-control" placeholder="Dirección escrita (Ej: Av. Grau 123)" required>
            <input type="text" name="referencia" class="form-control" placeholder="Referencia (Ej: Portón negro, piso 2)">
            
            <button type="submit" class="btn-save">GUARDAR DIRECCIÓN</button>
        </form>
    </div>

    <h4 style="color:#666; text-transform:uppercase; font-size:0.8rem; margin-bottom:10px;">Mis Lugares Guardados</h4>
    <div id="listaDirecciones"></div>

    <a href="index.php" style="display:block; text-align:center; color:#888; margin-top:20px; text-decoration:none;">
        <i class="fa-solid fa-arrow-left"></i> Volver
    </a>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    let map, marker;
    
    // Iniciar Mapa (Centrado en Lima por defecto)
    function initMap(lat = -12.0464, lon = -77.0428) {
        map = L.map('map').setView([lat, lon], 16);
        
        // Capa Oscura (Dark Matter) para combinar con el diseño
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
            maxZoom: 20
        }).addTo(map);

        marker = L.marker([lat, lon], {draggable: true}).addTo(map);

        // Al mover el pin, actualizar inputs
        marker.on('dragend', function(e) {
            let coord = marker.getLatLng();
            updateInputs(coord.lat, coord.lng);
        });

        updateInputs(lat, lon);
    }

    function updateInputs(lat, lon) {
        document.getElementById('lat').value = lat;
        document.getElementById('lon').value = lon;
    }

    // Obtener GPS del usuario
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                document.getElementById('statusGPS').innerText = "✅ GPS Encontrado";
                initMap(pos.coords.latitude, pos.coords.longitude);
            },
            () => {
                document.getElementById('statusGPS').innerText = "⚠️ GPS desactivado, usando centro por defecto.";
                initMap(); // Falla GPS, carga defecto
            },
            { enableHighAccuracy: true }
        );
    } else {
        initMap();
    }

    // Lógica del Formulario
    function setTag(val, btn) {
        document.getElementById('inputEtiqueta').value = val;
        document.querySelectorAll('.tag-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    document.getElementById('formDireccion').addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        
        fetch('../api/direcciones.php?action=guardar', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    Swal.fire({icon:'success', title:'Guardado', timer:1500, showConfirmButton:false, background:'#222', color:'#fff'});
                    this.reset();
                    cargarLista();
                } else {
                    Swal.fire('Error', d.message, 'error');
                }
            });
    });

    function cargarLista() {
        fetch('../api/direcciones.php?action=listar')
            .then(r => r.json())
            .then(data => {
                const div = document.getElementById('listaDirecciones');
                div.innerHTML = '';
                if(data.length === 0) div.innerHTML = '<p style="color:#444; text-align:center;">Vacío</p>';
                
                data.forEach(d => {
                    div.innerHTML += `
                        <div class="addr-card">
                            <div>
                                <strong style="color:#FFD700">${d.etiqueta}</strong><br>
                                <span style="font-size:0.9rem; color:#ccc;">${d.direccion}</span>
                            </div>
                            <button class="btn-del" onclick="borrar(${d.id})"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    `;
                });
            });
    }

    function borrar(id) {
        if(confirm('¿Borrar?')) {
            let fd = new FormData(); fd.append('id', id);
            fetch('../api/direcciones.php?action=borrar', { method: 'POST', body: fd })
                .then(() => cargarLista());
        }
    }

    cargarLista();
</script>
</body>
</html>