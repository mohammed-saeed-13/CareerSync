<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/gemini.php';
requireRole('student');

$db   = getDB();
$user = currentUser();

$student = $db->prepare("SELECT s.*, u.name FROM students s JOIN users u ON u.id=s.user_id WHERE s.user_id=?");
$student->execute([$user['id']]);
$student = $student->fetch();
$sId = $student['id'];

$skills   = $db->prepare("SELECT * FROM student_skills WHERE student_id=?");
$skills->execute([$sId]); $skills = $skills->fetchAll();

$projects = $db->prepare("SELECT * FROM student_projects WHERE student_id=?");
$projects->execute([$sId]); $projects = $projects->fetchAll();

$analysis = null;
$latestLog = $db->prepare("SELECT * FROM resume_analysis_logs WHERE student_id=? ORDER BY analyzed_at DESC LIMIT 1");
$latestLog->execute([$sId]);
$latestLog = $latestLog->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyze'])) {
    verifyCsrf();

    if (!$student['branch'] || !$skills) {
        $flash_error = 'Please complete your profile and add skills before analyzing.';
    } else {
        $result = analyzeResume($student, $skills, $projects);
        $parsed = $result['parsed'];

        // Save to DB
        $missing = json_encode($parsed['missing_keywords'] ?? []);
        $suggestions = json_encode($parsed['suggestions'] ?? []);

        $db->prepare("
            INSERT INTO resume_analysis_logs (student_id, score, ats_score, placement_probability, missing_keywords, suggestions, raw_response)
            VALUES (?,?,?,?,?,?,?)
        ")->execute([
            $sId,
            $parsed['resume_score'] ?? 65,
            $parsed['ats_score'] ?? 60,
            $parsed['placement_probability'] ?? 70,
            $missing,
            $suggestions,
            $result['raw']
        ]);

        $latestLog = $db->prepare("SELECT * FROM resume_analysis_logs WHERE student_id=? ORDER BY analyzed_at DESC LIMIT 1");
        $latestLog->execute([$sId]);
        $latestLog = $latestLog->fetch();
        $analysis = $parsed;
    }
}

if ($latestLog && !$analysis) {
    $analysis = [
        'resume_score'         => $latestLog['score'],
        'ats_score'            => $latestLog['ats_score'],
        'placement_probability'=> $latestLog['placement_probability'],
        'missing_keywords'     => json_decode($latestLog['missing_keywords'] ?? '[]', true),
        'suggestions'          => json_decode($latestLog['suggestions'] ?? '[]', true),
        'summary'              => '',
    ];
}

$pageTitle = 'AI Resume Analyzer';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>🤖 AI Resume Analyzer</h1>
      <p>NLP-powered analysis to improve your placement success probability</p>
    </div>

    <?php if (!empty($flash_error)): ?>
    <div class="alert alert-warning mb-3"><?= e($flash_error) ?></div>
    <?php endif; ?>

    <div class="grid-2">
      <!-- Profile Summary -->
      <div class="card">
        <div class="card-header"><h3 class="card-title">Your Current Profile</h3></div>
        <div style="font-size:0.9rem">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.6rem;margin-bottom:1rem">
            <div><span class="text-muted">Branch:</span> <strong><?= e($student['branch'] ?: 'Not set') ?></strong></div>
            <div><span class="text-muted">CGPA:</span> <strong><?= $student['cgpa'] ?></strong></div>
            <div><span class="text-muted">Backlogs:</span> <strong><?= $student['backlogs'] ?></strong></div>
            <div><span class="text-muted">Passing:</span> <strong><?= $student['year_of_passing'] ?: 'N/A' ?></strong></div>
          </div>
          <div>
            <div class="text-muted" style="font-size:0.82rem;margin-bottom:0.4rem">SKILLS (<?= count($skills) ?>)</div>
            <div style="display:flex;flex-wrap:wrap;gap:0.4rem">
              <?php foreach ($skills as $sk): ?>
              <span class="badge badge-info"><?= e($sk['skill_name']) ?></span>
              <?php endforeach; ?>
              <?php if (!$skills): ?><span class="text-muted" style="font-size:0.85rem">No skills added</span><?php endif; ?>
            </div>
          </div>
          <div style="margin-top:0.8rem">
            <div class="text-muted" style="font-size:0.82rem;margin-bottom:0.4rem">PROJECTS (<?= count($projects) ?>)</div>
            <?php foreach ($projects as $p): ?>
            <div style="font-size:0.85rem">• <?= e($p['title']) ?> <span class="text-muted">(<?= e($p['tech_stack'] ?: 'N/A') ?>)</span></div>
            <?php endforeach; ?>
          </div>
        </div>
        <form method="POST" style="margin-top:1.5rem">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="analyze" value="1">
          <button type="submit" class="btn btn-primary btn-block btn-lg" <?= (!$student['branch'] || !$skills) ? 'disabled' : '' ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7"/></svg>
            Analyze with Gemini AI
          </button>
          <?php if (!$student['branch'] || !$skills): ?>
          <p class="text-center mt-1" style="font-size:0.82rem;color:var(--warning)">Complete profile & add skills first</p>
          <?php endif; ?>
          <?php if ($latestLog): ?>
          <p class="text-center mt-1 text-muted" style="font-size:0.8rem">
            Last analyzed: <?= date('d M Y, h:i A', strtotime($latestLog['analyzed_at'])) ?>
          </p>
          <?php endif; ?>
        </form>
      </div>

      <!-- Analysis Result -->
      <?php if ($analysis): ?>
      <div class="card">
        <div class="card-header"><h3 class="card-title">Analysis Results</h3></div>

        <!-- Score Circles -->
        <div style="display:flex;justify-content:space-around;margin-bottom:1.5rem">
          <div class="text-center">
            <div style="--pct:<?= $analysis['resume_score'] ?>;" class="score-circle" style="--pct:<?= $analysis['resume_score'] ?>">
              <div class="score-circle-value"><?= $analysis['resume_score'] ?></div>
            </div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.4rem">Resume Score</div>
          </div>
          <div class="text-center">
            <div style="--pct:<?= $analysis['ats_score'] ?>;" class="score-circle">
              <div class="score-circle-value"><?= $analysis['ats_score'] ?></div>
            </div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.4rem">ATS Score</div>
          </div>
          <div class="text-center">
            <div style="--pct:<?= $analysis['placement_probability'] ?>;" class="score-circle">
              <div class="score-circle-value"><?= $analysis['placement_probability'] ?>%</div>
            </div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.4rem">Placement %</div>
          </div>
        </div>

        <!-- Score Circle CSS trick via inline style -->
        <style>
        .score-circle { background: conic-gradient(var(--accent) calc(var(--pct) * 1%), var(--border) 0); }
        </style>

        <?php if (!empty($analysis['summary'])): ?>
        <div class="alert alert-info mb-3"><?= e($analysis['summary']) ?></div>
        <?php endif; ?>

        <?php if (!empty($analysis['missing_keywords'])): ?>
        <div style="margin-bottom:1rem">
          <div style="font-weight:600;font-size:0.85rem;color:var(--danger);margin-bottom:0.5rem">⚠️ Missing Keywords</div>
          <div style="display:flex;flex-wrap:wrap;gap:0.4rem">
            <?php foreach ($analysis['missing_keywords'] as $kw): ?>
            <span class="badge badge-danger"><?= e($kw) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($analysis['suggestions'])): ?>
        <div>
          <div style="font-weight:600;font-size:0.85rem;color:var(--success);margin-bottom:0.5rem">💡 Suggestions</div>
          <?php foreach ($analysis['suggestions'] as $i => $sug): ?>
          <div style="display:flex;gap:0.5rem;align-items:start;margin-bottom:0.5rem;font-size:0.88rem">
            <span class="badge badge-success"><?= $i+1 ?></span>
            <?= e($sug) ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="card flex-center" style="flex-direction:column;gap:1rem;min-height:300px;text-align:center">
        <div style="font-size:3rem">🤖</div>
        <p class="text-muted">Click "Analyze with Gemini AI" to get your resume score, ATS compatibility check, and improvement suggestions.</p>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
