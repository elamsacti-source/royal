<?php
include_once 'session.php';
include 'db_config.php';

date_default_timezone_set('America/Lima');
$generado_el = date('d/m/Y h:i A');

// 1. OBTENER LISTA DE ÁREAS PARA EL FORMULARIO
$areas_disponibles = [];
$res_areas = $conn->query("SELECT id, nombre FROM checklist_areas ORDER BY orden ASC");
if($res_areas) {
    while($r = $res_areas->fetch_assoc()) $areas_disponibles[] = $r;
}

// 2. LOGICA DE FILTRADO (Si se envió el formulario)
$data = [];
$mostrar_reporte = false;
$areas_seleccionadas = [];

if (isset($_GET['generar'])) {
    $mostrar_reporte = true;
    
    // Si se marcaron áreas, sanitizamos los IDs
    if (isset($_GET['areas']) && is_array($_GET['areas'])) {
        $areas_seleccionadas = array_map('intval', $_GET['areas']);
        $ids_str = implode(',', $areas_seleccionadas);
        $filtro_sql = "AND a.id IN ($ids_str)";
    } else {
        // Si no marcó nada pero dio generar, mostramos todo o nada (decisión: todo)
        $filtro_sql = ""; 
    }

    $sql = "SELECT 
                a.id AS area_id, 
                a.nombre AS area_nombre, 
                a.emoji, 
                act.nombre AS actividad, 
                act.criterio, 
                act.frecuencia
            FROM checklist_areas a
            JOIN checklist_activities act ON a.id = act.area_id
            WHERE act.activo = 1 $filtro_sql
            ORDER BY a.orden ASC, a.id ASC, act.orden ASC";

    $result = $conn->query($sql);

    if ($result) {
        while($row = $result->fetch_assoc()) {
            $data[$row['area_id']]['info'] = [
                'nombre' => $row['area_nombre'],
                'emoji' => $row['emoji']
            ];
            $data[$row['area_id']]['items'][] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Imprimir Checklist - Selección</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700" rel="stylesheet">
    <style>
        :root { --primary: #D32F2F; --dark: #1e293b; --border: #cbd5e1; }
        body { font-family: 'Montserrat', sans-serif; background: #f8fafc; color: var(--dark); margin: 0; padding: 20px; font-size: 12px; }

        /* --- ESTILOS DEL SELECTOR (SOLO PANTALLA) --- */
        .selector-box {
            background: white; max-width: 600px; margin: 0 auto 40px; padding: 25px;
            border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;
        }
        .selector-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 15px; color: var(--primary); text-align: center; }
        .area-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; margin-bottom: 20px; }
        .check-label {
            display: flex; align-items: center; gap: 8px; padding: 10px;
            background: #f1f5f9; border-radius: 6px; cursor: pointer; border: 1px solid transparent;
            font-size: 13px;
        }
        .check-label:hover { background: #e2e8f0; }
        .check-label input:checked + span { font-weight: bold; color: var(--primary); }
        .btn-gen {
            width: 100%; padding: 12px; background: var(--primary); color: white; border: none;
            border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 14px;
        }
        .btn-gen:hover { background: #b71c1c; }

        /* --- ESTILOS DEL REPORTE --- */
        .report-container { background: white; max-width: 210mm; margin: 0 auto; padding: 20px; display: none; }
        .report-container.visible { display: block; }

        /* HEADER */
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--primary); padding-bottom: 15px; margin-bottom: 20px; }
        .logos { display: flex; gap: 15px; align-items: center; }
        
        /* TRUCO PARA LOGOS BLANCOS */
        .logo-img {
            height: 50px; width: auto; object-fit: contain;
            /* Invierte los colores: Blanco -> Negro. Ideal para imprimir logos blancos sobre papel blanco */
            filter: invert(1) grayscale(1) contrast(100); 
        }

        .doc-info { text-align: right; }
        .doc-title { font-size: 18px; font-weight: 700; color: var(--primary); text-transform: uppercase; margin: 0; }
        .doc-meta { font-size: 10px; color: #666; margin-top: 4px; }

        /* CAMPOS MANUALES */
        .manual-fields { display: flex; gap: 20px; margin-bottom: 20px; border: 1px solid var(--border); padding: 10px; border-radius: 5px; background: #fff; }
        .field { flex: 1; display: flex; flex-direction: column; }
        .field label { font-weight: 700; font-size: 10px; text-transform: uppercase; color: #666; margin-bottom: 5px; }
        .field-line { border-bottom: 1px solid #000; height: 20px; }

        /* TABLAS */
        .area-section { margin-bottom: 20px; page-break-inside: avoid; }
        .area-header {
            background: var(--primary); color: white; padding: 5px 10px; font-weight: 700;
            text-transform: uppercase; font-size: 13px; border-radius: 4px 4px 0 0;
            display: flex; align-items: center; gap: 8px;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        table { width: 100%; border-collapse: collapse; border: 1px solid var(--border); }
        th, td { border: 1px solid var(--border); padding: 6px 8px; text-align: left; vertical-align: middle; }
        th { background: #f0f0f0; font-weight: 700; font-size: 10px; text-align: center; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        .col-check { width: 40px; text-align: center; }
        .checkbox-square { display: inline-block; width: 15px; height: 15px; border: 1px solid #333; border-radius: 3px; }

        /* FOOTER */
        .footer { margin-top: 30px; border-top: 1px solid var(--border); padding-top: 10px; text-align: center; font-size: 9px; color: #666; }
        .propiedad-legal { font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }

        /* CONTROL DE IMPRESIÓN */
        @media print {
            body { background: white; padding: 0; }
            .selector-box { display: none !important; }
            .report-container { display: block !important; width: 100%; max-width: none; box-shadow: none; margin: 0; padding: 0; }
            .btn-print-float { display: none; }
        }

        .btn-print-float {
            position: fixed; bottom: 30px; right: 30px; background: var(--primary); color: white;
            border: none; padding: 15px 30px; border-radius: 50px; font-weight: bold;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3); cursor: pointer; font-size: 16px;
            display: flex; align-items: center; gap: 10px; z-index: 1000;
        }
    </style>
</head>
<body>

    <div class="selector-box">
        <div class="selector-title">🖨️ Configurar Impresión</div>
        <form method="GET" action="imprimir_plantilla.php">
            <p style="font-size:13px; text-align:center; color:#666; margin-bottom:15px;">Seleccione las áreas que desea incluir en la hoja manual:</p>
            
            <div class="area-grid">
                <?php foreach($areas_disponibles as $a): ?>
                    <label class="check-label">
                        <input type="checkbox" name="areas[]" value="<?php echo $a['id']; ?>" checked>
                        <span><?php echo $a['nombre']; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <button type="submit" name="generar" value="1" class="btn-gen">GENERAR VISTA PREVIA</button>
        </form>
    </div>

    <div class="report-container <?php echo $mostrar_reporte ? 'visible' : ''; ?>">
        
        <?php if($mostrar_reporte): ?>
            <button onclick="window.print()" class="btn-print-float">🖨️ Imprimir Ahora</button>
        <?php endif; ?>

        <div class="header">
            <div class="logos">
                <img src="imagenes/logoIntegra.png" alt="Integra" class="logo-img">
                <img src="imagenes/logoLosAngeles.png" alt="Los Angeles" class="logo-img">
            </div>
            <div class="doc-info">
                <h1 class="doc-title">Checklist Clínico Diario</h1>
                <div class="doc-meta">Formato de Control Manual</div>
                <div class="doc-meta">Generado: <?php echo $generado_el; ?></div>
            </div>
        </div>

        <div class="manual-fields">
            <div class="field"><label>Fecha:</label><div class="field-line"></div></div>
            <div class="field"><label>Sede:</label><div class="field-line"></div></div>
            <div class="field"><label>Turno (M/T/N):</label><div class="field-line"></div></div>
            <div class="field"><label>Licenciada (Firma):</label><div class="field-line"></div></div>
        </div>

        <?php if (empty($data)): ?>
            <p style="text-align:center; padding: 50px; border:1px dashed #ccc;">
                ⚠️ No hay actividades para las áreas seleccionadas.
            </p>
        <?php else: ?>
            <?php foreach($data as $area): ?>
                <div class="area-section">
                    <div class="area-header">
                        <span><?php echo $area['info']['emoji']; ?></span>
                        <span><?php echo $area['info']['nombre']; ?></span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40%">Actividad</th>
                                <th style="width: 30%">Criterio / Estándar</th>
                                <th class="col-check">SI</th>
                                <th class="col-check">NO</th>
                                <th style="width: 20%">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($area['items'] as $item): ?>
                            <tr>
                                <td><strong><?php echo $item['actividad']; ?></strong></td>
                                <td style="font-size: 10px; color: #444;"><?php echo $item['criterio']; ?></td>
                                <td class="col-check"><div class="checkbox-square"></div></td>
                                <td class="col-check"><div class="checkbox-square"></div></td>
                                <td></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="footer">
            <p class="propiedad-legal">© Propiedad de Elam Medical del Norte SAC</p>
            <p>Este documento es de uso interno y exclusivo. Prohibida su reproducción sin autorización.</p>
        </div>
    </div>

</body>
</html>