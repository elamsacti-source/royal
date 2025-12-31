<?php
include_once 'session.php';
include_once 'db_config.php';

// Seguridad: Solo Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php'); exit;
}

// 1. Cargar Sedes
$sedes_list = []; 
$res = $conn->query("SELECT id, nombre FROM sedes WHERE activo=1 ORDER BY nombre");
if($res) while($r=$res->fetch_assoc()) $sedes_list[] = $r;

// 2. Cargar TODAS las personas (Supervisores + Colaboradores) en una sola lista única
$staff_list = [];
$sql_staff = "SELECT DISTINCT nombre FROM (
                SELECT supervisor_nombre AS nombre FROM checklist_sessions
                UNION
                SELECT colab_1 AS nombre FROM checklist_sessions
                UNION
                SELECT colab_2 AS nombre FROM checklist_sessions
                UNION
                SELECT colab_3 AS nombre FROM checklist_sessions
              ) AS unificado 
              WHERE nombre IS NOT NULL AND nombre != '' 
              ORDER BY nombre ASC";

$res_staff = $conn->query($sql_staff);
if($res_staff) {
    while($r = $res_staff->fetch_assoc()) {
        $staff_list[] = $r['nombre'];
    }
}

// Filtros actuales (por si recarga la página)
$f_ini = $_GET['fecha_inicio'] ?? date('Y-m-d');
$f_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Cumplimiento</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #f8fafc; --card: #ffffff; --primary: #2563eb; --text: #1e293b; --border: #e2e8f0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); padding: 20px; margin: 0; }
        .container { max-width: 1400px; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        h1 { margin: 0; font-size: 1.5rem; }
        .btn-back { text-decoration: none; background: var(--card); border: 1px solid var(--border); color: var(--text); padding: 8px 16px; border-radius: 8px; font-weight: 600; }

        /* Filtros */
        .filter-bar {
            background: var(--card); padding: 20px; border-radius: 12px; border: 1px solid var(--border);
            display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); 
            gap: 15px; margin-bottom: 20px; align-items: end;
        }
        .form-group label { font-size: 0.75rem; font-weight: 700; display: block; margin-bottom: 6px; text-transform: uppercase; color: #64748b; }
        select, input { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); outline: none; }
        
        .btn-filter { background: var(--primary); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; width: 100%; }
        .btn-excel { background: #16a34a; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; width: 100%; }

        /* Gráfico y Tabla */
        .chart-container { background: var(--card); padding: 20px; border-radius: 12px; border: 1px solid var(--border); height: 300px; margin-bottom: 20px; }
        .table-responsive { overflow-x: auto; background: var(--card); border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; white-space: nowrap; }
        th, td { padding: 12px 15px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: #f1f5f9; font-weight: 700; color: #475569; text-transform: uppercase; font-size: 0.7rem; }
        
        .badge { padding: 4px 10px; border-radius: 99px; font-size: 0.7rem; font-weight: 700; }
        .badge.REALIZADO { background: #dcfce7; color: #166534; }
        .badge.NO { background: #fee2e2; color: #991b1b; } 
        .badge.PENDIENTE { background: #f1f5f9; color: #64748b; }
        .text-wrap { white-space: normal; min-width: 150px; }
        .text-small { font-size: 0.8rem; color: #64748b; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📊 Reporte de Cumplimiento</h1>
        <a href="admin.php" class="btn-back">← Volver</a>
    </div>

    <form id="filterForm" class="filter-bar">
        <div class="form-group"><label>Desde</label><input type="date" id="f_ini" value="<?php echo $f_ini; ?>"></div>
        <div class="form-group"><label>Hasta</label><input type="date" id="f_fin" value="<?php echo $f_fin; ?>"></div>
        
        <div class="form-group">
            <label>Sede</label>
            <select id="f_sede">
                <option value="">Todas</option>
                <?php foreach($sedes_list as $s): ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo $s['nombre']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Personal (Sup. o Colab.)</label>
            <select id="f_colab">
                <option value="">-- Todos --</option>
                <?php foreach($staff_list as $persona): ?>
                    <option value="<?php echo $persona; ?>"><?php echo $persona; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Estado</label>
            <select id="f_est">
                <option value="">Todos</option>
                <option value="REALIZADO">Realizado</option>
                <option value="NO REALIZADO">No Realizado</option>
                <option value="PENDIENTE">Pendiente</option>
            </select>
        </div>
        
        <div><button type="submit" class="btn-filter">Filtrar</button></div>
        <div><button type="button" class="btn-excel" onclick="exportar()">Excel</button></div>
    </form>

    <div class="chart-container"><canvas id="myChart"></canvas></div>

    <div class="table-responsive">
        <table id="reportTable">
            <thead>
                <tr>
                    <th>Fecha / Turno</th>
                    <th>Sede</th>
                    <th>Responsable</th>
                    <th>Equipo (Staff)</th>
                    <th>Actividad</th>
                    <th>Estado</th>
                    <th>Obs / Cant.</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
    let chartInstance = null;
    document.addEventListener('DOMContentLoaded', loadData);
    document.getElementById('filterForm').addEventListener('submit', (e) => { e.preventDefault(); loadData(); });

    async function loadData() {
        const p = new URLSearchParams({
            json: 1,
            fecha_inicio: document.getElementById('f_ini').value,
            fecha_fin: document.getElementById('f_fin').value,
            sede_id: document.getElementById('f_sede').value,
            // Enviamos el valor seleccionado en el combo como 'colaborador'
            // Nota: Ya no enviamos supervisor_nombre por separado, este combo cubre ambos
            colaborador: document.getElementById('f_colab').value, 
            estado: document.getElementById('f_est').value
        });

        const tbody = document.querySelector('#reportTable tbody');
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px;">Cargando datos...</td></tr>';

        try {
            const res = await fetch(`api_export.php?${p.toString()}`);
            const data = await res.json();
            renderTable(data);
            renderChart(data);
        } catch (e) {
            console.error(e);
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:red;">Error al cargar</td></tr>';
        }
    }

    function renderTable(data) {
        const tbody = document.querySelector('#reportTable tbody');
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px;">Sin registros.</td></tr>';
            return;
        }

        data.forEach(row => {
            const statusKey = row.estado.split(' ')[0];
            const staff = [row.colab_1, row.colab_2, row.colab_3].filter(Boolean).join(', ');
            
            // Resaltar si coincide con la búsqueda
            const search = document.getElementById('f_colab').value;
            let styleStaff = "color:#2563eb;";
            let styleSup = "";

            if(search) {
                if(staff.includes(search)) styleStaff = "font-weight:bold; background:#eff6ff; color:#2563eb;";
                if(row.supervisor_nombre === search) styleSup = "font-weight:bold; background:#eff6ff; padding:2px 5px; border-radius:4px;";
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><b>${row.fecha}</b><br><span class="text-small">${row.turno}</span></td>
                <td>${row.sede_nombre}</td>
                <td><span style="${styleSup}">${row.supervisor_nombre}</span></td>
                <td class="text-wrap text-small" style="${styleStaff}">${staff || '-'}</td>
                <td class="text-wrap">${row.actividad_nombre}</td>
                <td><span class="badge ${statusKey}">${row.estado}</span></td>
                <td class="text-wrap text-small">
                    ${row.observacion ? '📝 '+row.observacion : ''}
                    ${row.quantity ? '<br>🔢 Cant: '+row.quantity : ''}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function renderChart(data) {
        const ctx = document.getElementById('myChart').getContext('2d');
        if (chartInstance) chartInstance.destroy();

        const stats = { 'REALIZADO': 0, 'NO REALIZADO': 0, 'PENDIENTE': 0, 'EN PROCESO': 0 };
        data.forEach(d => { if (stats[d.estado] !== undefined) stats[d.estado]++; });

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Realizado', 'No Realizado', 'En Proceso'],
                datasets: [{
                    label: 'Registros',
                    data: [stats['REALIZADO'], stats['NO REALIZADO'], stats['EN PROCESO']],
                    backgroundColor: ['#22c55e', '#ef4444', '#f97316'],
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }

    function exportar() {
        const p = new URLSearchParams({
            fecha_inicio: document.getElementById('f_ini').value,
            fecha_fin: document.getElementById('f_fin').value,
            sede_id: document.getElementById('f_sede').value,
            colaborador: document.getElementById('f_colab').value,
            estado: document.getElementById('f_est').value
        });
        window.location.href = `api_export.php?${p.toString()}`;
    }
</script>
</body>
</html>