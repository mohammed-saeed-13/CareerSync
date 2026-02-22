<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('student');

$db   = getDB();
$user = currentUser();

$student = $db->prepare("SELECT * FROM students WHERE user_id=?");
$student->execute([$user['id']]);
$student = $student->fetch();
$sId = $student['id'];

$mySkills = $db->prepare("SELECT skill_name FROM student_skills WHERE student_id=?");
$mySkills->execute([$sId]);
$mySkillList = array_column($mySkills->fetchAll(), 'skill_name');

// Top skills among PLACED students with frequency
$totalPlaced = (int)$db->query("SELECT COUNT(DISTINCT id) FROM students WHERE placement_status='placed'")->fetchColumn();

$topPlacedSkills = $db->query("
    SELECT ss.skill_name, COUNT(DISTINCT ss.student_id) cnt
    FROM student_skills ss
    JOIN students s ON s.id = ss.student_id
    WHERE s.placement_status = 'placed'
    GROUP BY ss.skill_name
    ORDER BY cnt DESC
    LIMIT 15
")->fetchAll();

// Find gaps
$gaps = [];
$hasAll = [];
foreach ($topPlacedSkills as $ts) {
    $pct = $totalPlaced > 0 ? round(($ts['cnt']/$totalPlaced)*100) : 0;
    if (in_array($ts['skill_name'], $mySkillList, true)) {
        $hasAll[] = ['skill' => $ts['skill_name'], 'pct' => $pct];
    } else {
        $gaps[] = ['skill' => $ts['skill_name'], 'pct' => $pct];
    }
}

// Learning paths
$learningPaths = [
    'Python'        => 'Python.org → Automate the Boring Stuff → Real Python',
    'SQL'           => 'W3Schools SQL → SQLBolt → LeetCode SQL',
    'PowerBI'       => 'Microsoft Learn → Guy in a Cube (YouTube)',
    'Machine Learning' => 'Andrew Ng ML Course → Kaggle Learn',
    'React'         => 'React Docs → Scrimba React Course',
    'Java'          => 'Java MOOC → Effective Java Book → LeetCode',
    'AWS'           => 'AWS Free Tier → AWS Certified Cloud Practitioner',
    'Docker'        => 'Docker Get Started → KodeKloud',
    'Data Analysis' => 'Pandas Docs → Kaggle Courses → Towards Data Science',
    'Communication' => 'Toastmasters → Coursera Business Writing',
];

$pageTitle = 'Skill Gap Analysis';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>🧠 Skill Gap Analysis</h1>
      <p>AI-powered comparison of your skills vs what placed students have</p>
    </div>

    <?php if ($totalPlaced === 0): ?>
    <div class="alert alert-info">Not enough placement data yet to generate meaningful insights. Check back later!</div>
    <?php else: ?>

    <!-- Summary -->
    <div class="stats-grid mb-3">
      <div class="stat-card">
        <div class="stat-icon green">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= count($hasAll) ?></div>
          <div class="stat-label">Skills You Have</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= count($gaps) ?></div>
          <div class="stat-label">Missing Skills</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        </div>
        <div>
          <div class="stat-value"><?= $totalPlaced ?></div>
          <div class="stat-label">Placed Students (data)</div>
        </div>
      </div>
    </div>

    <div class="grid-2">
      <!-- Gap Analysis -->
      <div class="card">
        <div class="card-header"><h3 class="card-title">⚠️ Skills You're Missing</h3></div>
        <?php if ($gaps): ?>
        <?php foreach ($gaps as $g): ?>
        <div style="margin-bottom:1.1rem">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.3rem">
            <div>
              <strong style="font-size:0.92rem"><?= e($g['skill']) ?></strong>
              <div style="font-size:0.78rem;color:var(--danger)">
                <?= $g['pct'] ?>% of placed students had this skill
              </div>
            </div>
            <span class="badge badge-danger"><?= $g['pct'] ?>%</span>
          </div>
          <div class="progress-bar-wrap">
            <div class="progress-bar" style="width:<?= $g['pct'] ?>%;background:linear-gradient(90deg,#ef4444,#f97316)"></div>
          </div>
          <?php if (isset($learningPaths[$g['skill']])): ?>
          <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.25rem">
            📚 Path: <?= e($learningPaths[$g['skill']]) ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p class="text-success">🎉 You have all the top skills! Great work.</p>
        <?php endif; ?>
      </div>

      <!-- Skills You Have -->
      <div class="card">
        <div class="card-header"><h3 class="card-title">✅ Skills You Have (vs placed)</h3></div>
        <?php if ($hasAll): ?>
        <?php foreach ($hasAll as $h): ?>
        <div style="margin-bottom:1rem">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.3rem">
            <strong style="font-size:0.92rem"><?= e($h['skill']) ?></strong>
            <span class="badge badge-success"><?= $h['pct'] ?>%</span>
          </div>
          <div class="progress-bar-wrap">
            <div class="progress-bar" style="width:<?= $h['pct'] ?>%"></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p class="text-muted">Add skills to your profile to see comparison.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Personalized Recommendation -->
    <?php if ($gaps): ?>
    <div class="card mt-3" style="border-left:4px solid var(--accent)">
      <h3 class="card-title mb-2">🚀 Personalized Learning Roadmap</h3>
      <div style="font-size:0.92rem;color:var(--text-secondary);line-height:1.8">
        <?php
        $top3gaps = array_slice($gaps, 0, 3);
        $skillNames = implode(', ', array_column($top3gaps, 'skill'));
        ?>
        Based on placement data, you should prioritize learning <strong><?= e($skillNames) ?></strong>.
        These skills appear in <?= $top3gaps[0]['pct'] ?? '—' ?>%+ of placed student profiles.
        <br><br>
        <strong>Recommended Priority:</strong>
        <?php foreach ($top3gaps as $i => $g): ?>
        <div style="margin:0.5rem 0;padding:0.6rem 0.9rem;background:var(--bg-hover);border-radius:var(--radius-sm)">
          <strong><?= $i+1 ?>. <?= e($g['skill']) ?></strong>
          <span class="badge badge-warning" style="margin-left:0.5rem"><?= $g['pct'] ?>% placement correlation</span>
          <?php if (isset($learningPaths[$g['skill']])): ?>
          <div style="font-size:0.8rem;margin-top:0.2rem;color:var(--text-muted)">📚 <?= e($learningPaths[$g['skill']]) ?></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
