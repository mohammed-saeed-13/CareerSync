<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('admin');

$db = getDB();

// Branch-wise stats
$branchStats = $db->query("
    SELECT branch, COUNT(*) total, SUM(placement_status='placed') placed,
           ROUND(AVG(placed_package),2) avg_pkg
    FROM students GROUP BY branch ORDER BY total DESC
")->fetchAll();

// Year-wise placements (mock with placement data)
$yearStats = $db->query("
    SELECT year_of_passing yr, COUNT(*) total, SUM(placement_status='placed') placed
    FROM students WHERE year_of_passing IS NOT NULL GROUP BY year_of_passing ORDER BY yr
")->fetchAll();

// Top skills among placed students
$skillStats = $db->query("
    SELECT ss.skill_name, COUNT(*) cnt
    FROM student_skills ss
    JOIN students s ON s.id = ss.student_id
    WHERE s.placement_status = 'placed'
    GROUP BY ss.skill_name ORDER BY cnt DESC LIMIT 10
")->fetchAll();

// Company-wise placement
$companyStats = $db->query("
    SELECT placed_company, COUNT(*) total, ROUND(AVG(placed_package),2) avg_pkg
    FROM students WHERE placement_status='placed' AND placed_company IS NOT NULL
    GROUP BY placed_company ORDER BY total DESC LIMIT 8
")->fetchAll();

// Drive activity
$driveActivity = $db->query("
    SELECT d.company_name, COUNT(a.id) apps,
           SUM(a.status='selected') selected
    FROM drives d LEFT JOIN applications a ON a.drive_id=d.id
    GROUP BY d.id ORDER BY apps DESC LIMIT 6
")->fetchAll();

$pageTitle = 'Analytics';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Analytics Dashboard</h1>
      <p>Comprehensive placement insights and data visualizations</p>
    </div>

    <div class="grid-2 mb-3">
      <div class="card">
        <div class="card-header"><h3 class="card-title">Placement by Branch</h3></div>
        <canvas id="branchBar" height="280"></canvas>
      </div>
      <div class="card">
        <div class="card-header"><h3 class="card-title">Top In-Demand Skills (Placed Students)</h3></div>
        <canvas id="skillChart" height="280"></canvas>
      </div>
    </div>

    <div class="grid-2 mb-3">
      <div class="card">
        <div class="card-header"><h3 class="card-title">Year-wise Placement Growth</h3></div>
        <canvas id="yearChart" height="260"></canvas>
      </div>
      <div class="card">
        <div class="card-header"><h3 class="card-title">Drive Activity (Applications vs Selected)</h3></div>
        <canvas id="driveChart" height="260"></canvas>
      </div>
    </div>

    <!-- Branch Details Table -->
    <div class="card">
      <div class="card-header"><h3 class="card-title">Detailed Branch Statistics</h3></div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Branch</th><th>Total</th><th>Placed</th><th>Rate</th><th>Avg Package</th><th>Progress</th></tr></thead>
          <tbody>
          <?php foreach ($branchStats as $b):
            $rate = $b['total'] > 0 ? round(($b['placed']/$b['total'])*100) : 0;
          ?>
          <tr>
            <td><strong><?= e($b['branch']) ?></strong></td>
            <td><?= $b['total'] ?></td>
            <td><?= $b['placed'] ?></td>
            <td><span class="badge badge-<?= $rate>=70?'success':($rate>=50?'warning':'danger') ?>"><?= $rate ?>%</span></td>
            <td><?= $b['avg_pkg'] ? '₹'.number_format($b['avg_pkg']).' LPA' : 'N/A' ?></td>
            <td style="min-width:120px">
              <div class="progress-bar-wrap">
                <div class="progress-bar" style="width:<?= $rate ?>%"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?php
$branchLabels = json_encode(array_column($branchStats,'branch'));
$branchTotal  = json_encode(array_map('intval',array_column($branchStats,'total')));
$branchPlaced = json_encode(array_map('intval',array_column($branchStats,'placed')));

$yearLabels   = json_encode(array_column($yearStats,'yr'));
$yearTotal    = json_encode(array_map('intval',array_column($yearStats,'total')));
$yearPlaced   = json_encode(array_map('intval',array_column($yearStats,'placed')));

$skillLabels  = json_encode(array_column($skillStats,'skill_name'));
$skillCounts  = json_encode(array_map('intval',array_column($skillStats,'cnt')));

$driveLabels  = json_encode(array_column($driveActivity,'company_name'));
$driveApps    = json_encode(array_map('intval',array_column($driveActivity,'apps')));
$driveSelected= json_encode(array_map('intval',array_column($driveActivity,'selected')));

$extraJs = <<<JS
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const chartDefaults = {
  plugins: { legend: { labels: { color: getComputedStyle(document.documentElement).getPropertyValue('--text-primary') } } },
  scales: {
    y: { grid: { color: 'rgba(128,128,128,0.1)' }, ticks: { color: '#718096' } },
    x: { grid: { display: false }, ticks: { color: '#718096' } }
  }
};

new Chart('branchBar', {
  type: 'bar',
  data: { labels: {$branchLabels}, datasets: [
    { label: 'Total', data: {$branchTotal}, backgroundColor: 'rgba(14,165,233,0.25)', borderColor:'#0ea5e9', borderWidth:2, borderRadius:4 },
    { label: 'Placed', data: {$branchPlaced}, backgroundColor: 'rgba(16,185,129,0.7)', borderColor:'#10b981', borderWidth:2, borderRadius:4 }
  ]},
  options: { responsive:true, ...chartDefaults }
});

new Chart('skillChart', {
  type: 'bar',
  data: { labels: {$skillLabels}, datasets: [{
    label: 'Placed Students', data: {$skillCounts},
    backgroundColor: ['#0ea5e9','#8b5cf6','#f59e0b','#10b981','#ef4444','#06b6d4','#ec4899','#84cc16','#f97316','#6366f1'],
    borderRadius: 4
  }]},
  options: { responsive:true, indexAxis:'y',
    plugins: { legend: { display:false } },
    scales: {
      x: { grid: { color:'rgba(128,128,128,0.1)' }, ticks:{color:'#718096'} },
      y: { grid: { display:false }, ticks:{color:'#718096'} }
    }
  }
});

new Chart('yearChart', {
  type: 'line',
  data: { labels: {$yearLabels}, datasets: [
    { label:'Total', data:{$yearTotal}, borderColor:'#0ea5e9', backgroundColor:'rgba(14,165,233,0.1)', fill:true, tension:0.4 },
    { label:'Placed', data:{$yearPlaced}, borderColor:'#10b981', backgroundColor:'rgba(16,185,129,0.1)', fill:true, tension:0.4 }
  ]},
  options: { responsive:true, ...chartDefaults }
});

new Chart('driveChart', {
  type: 'bar',
  data: { labels: {$driveLabels}, datasets: [
    { label:'Applications', data:{$driveApps}, backgroundColor:'rgba(99,102,241,0.5)', borderColor:'#6366f1', borderWidth:2, borderRadius:4 },
    { label:'Selected', data:{$driveSelected}, backgroundColor:'rgba(16,185,129,0.7)', borderColor:'#10b981', borderWidth:2, borderRadius:4 }
  ]},
  options: { responsive:true, ...chartDefaults }
});
</script>
JS;

include __DIR__ . '/../includes/footer.php';
?>
