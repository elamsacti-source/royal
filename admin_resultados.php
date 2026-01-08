<?php include_once 'session.php'; 
// Validación simple de admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir Resultados - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .card-upload { max-width: 500px; margin: 30px auto; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: none; }
        .header-upload { background: #2563eb; color: white; padding: 20px; border-radius: 15px 15px 0 0; text-align: center; }
        .success-box { display: none; background: #dcfce7; border: 1px solid #22c55e; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center; }
        .btn-wsp-send { background: #25D366; color: white; font-weight: bold; width: 100%; border: none; padding: 10px; border-radius: 8px; margin-top: 10px; }
        .btn-wsp-send:hover { background: #128C7E; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card card-upload">
            <div class="header-upload">
                <h3 class="mb-0"><i class="fas fa-microscope me-2"></i>Cargar Resultados</h3>
                <a href="admin.php" class="text-white small text-decoration-none">Volver al menú</a>
            </div>
            <div class="card-body p-4">
                <form id="formUpload" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">DNI Paciente</label>
                        <input type="number" id="dni" name="dni" class="form-control" required placeholder="Ej: 12345678">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre Completo</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" required placeholder="Ej: Juan Perez">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Celular (WhatsApp)</label>
                        <input type="tel" id="telefono" name="telefono" class="form-control" required placeholder="Ej: 999999999">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Archivo PDF</label>
                        <input type="file" id="pdf" name="pdf" class="form-control" accept="application/pdf" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg" id="btnSubir">Registrar y Generar Código</button>
                    </div>
                </form>

                <div id="successArea" class="success-box">
                    <h5 class="text-success fw-bold"><i class="fas fa-check-circle"></i> ¡Registrado!</h5>
                    <p class="mb-1">Código de seguridad generado:</p>
                    <h2 class="fw-bold text-dark" id="generatedCode">----</h2>
                    <p class="small text-muted mb-2">Envía el aviso al paciente ahora:</p>
                    <a href="#" id="wspLink" target="_blank" class="btn btn-wsp-send">
                        <i class="fab fa-whatsapp fa-lg me-2"></i> Enviar Código por WhatsApp
                    </a>
                    <button onclick="location.reload()" class="btn btn-outline-secondary btn-sm mt-3 w-100">Nuevo Registro</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('formUpload').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSubir');
            btn.disabled = true; btn.innerText = "Subiendo...";

            const formData = new FormData(e.target);

            try {
                const res = await fetch('api_resultados.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    document.getElementById('formUpload').style.display = 'none';
                    document.getElementById('successArea').style.display = 'block';
                    document.getElementById('generatedCode').innerText = data.codigo;

                    // Preparar enlace de WhatsApp
                    const dni = document.getElementById('dni').value;
                    const tel = document.getElementById('telefono').value;
                    // Cambia 'tuweb.com' por tu dominio real de InfinityFree
                    const linkWeb = window.location.origin + "/resultados.php"; 
                    
                    const mensaje = `Hola *${document.getElementById('nombre').value}*, tus resultados de laboratorio están listos 📄.\n\nPara descargarlos ingresa aquí:\n🔗 ${linkWeb}\n\nUsa tus credenciales:\n🆔 DNI: *${dni}*\n🔑 Código: *${data.codigo}*`;
                    
                    document.getElementById('wspLink').href = `https://wa.me/51${tel}?text=${encodeURIComponent(mensaje)}`;
                } else {
                    alert("Error: " + data.error);
                    btn.disabled = false; btn.innerText = "Reintentar";
                }
            } catch (err) {
                alert("Error de red");
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>