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

$skills   = $db->prepare("SELECT * FROM student_skills WHERE student_id=? ORDER BY skill_name");
$skills->execute([$sId]);
$skills   = $skills->fetchAll();

$projects = $db->prepare("SELECT * FROM student_projects WHERE student_id=? ORDER BY id DESC");
$projects->execute([$sId]);
$projects = $projects->fetchAll();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $branch     = trim($_POST['branch'] ?? '');
        $cgpa       = (float)($_POST['cgpa'] ?? 0);
        $backlogs   = (int)($_POST['backlogs'] ?? 0);
        $roll       = trim($_POST['roll_number'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $linkedin   = trim($_POST['linkedin_url'] ?? '');
        $github     = trim($_POST['github_url'] ?? '');
        $yop        = (int)($_POST['year_of_passing'] ?? date('Y') + 1);

        if (!$branch || $cgpa < 0 || $cgpa > 10) {
            $error = 'Please enter valid branch and CGPA (0-10).';
        } else {
            $db->prepare("UPDATE students SET branch=?,cgpa=?,backlogs=?,roll_number=?,phone=?,linkedin_url=?,github_url=?,year_of_passing=? WHERE id=?")
               ->execute([$branch,$cgpa,$backlogs,$roll,$phone,$linkedin,$github,$yop,$sId]);

            // Update user name if changed
            if (trim($_POST['name'] ?? '') !== '') {
                $db->prepare("UPDATE users SET name=? WHERE id=?")->execute([trim($_POST['name']),$user['id']]);
                $_SESSION['user']['name'] = trim($_POST['name']);
            }
            $success = 'Profile updated successfully!';

            // Reload
            $student = $db->prepare("SELECT * FROM students WHERE id=?");
            $student->execute([$sId]);
            $student = $student->fetch();
        }
    }

    elseif ($action === 'add_skill') {
        $skillName = trim($_POST['skill_name'] ?? '');
        $proficiency = $_POST['proficiency'] ?? 'intermediate';
        if ($skillName) {
            try {
                $db->prepare("INSERT INTO student_skills (student_id,skill_name,proficiency) VALUES (?,?,?) ON DUPLICATE KEY UPDATE proficiency=VALUES(proficiency)")
                   ->execute([$sId, $skillName, $proficiency]);
                $success = 'Skill added!';
            } catch (Exception $e) { $error = 'Skill already exists.'; }
        }
        $skills = $db->prepare("SELECT * FROM student_skills WHERE student_id=? ORDER BY skill_name");
        $skills->execute([$sId]); $skills = $skills->fetchAll();
    }

    elseif ($action === 'delete_skill') {
        $db->prepare("DELETE FROM student_skills WHERE id=? AND student_id=?")->execute([(int)$_POST['skill_id'],$sId]);
        $success = 'Skill removed.';
        $skills = $db->prepare("SELECT * FROM student_skills WHERE student_id=? ORDER BY skill_name");
        $skills->execute([$sId]); $skills = $skills->fetchAll();
    }

    elseif ($action === 'add_project') {
        $title = trim($_POST['proj_title'] ?? '');
        $desc  = trim($_POST['proj_desc'] ?? '');
        $tech  = trim($_POST['proj_tech'] ?? '');
        $url   = trim($_POST['proj_url'] ?? '');
        if ($title) {
            $db->prepare("INSERT INTO student_projects (student_id,title,description,tech_stack,project_url) VALUES (?,?,?,?,?)")
               ->execute([$sId,$title,$desc,$tech,$url]);
            $success = 'Project added!';
        }
        $projects = $db->prepare("SELECT * FROM student_projects WHERE student_id=? ORDER BY id DESC");
        $projects->execute([$sId]); $projects = $projects->fetchAll();
    }

    elseif ($action === 'delete_project') {
        $db->prepare("DELETE FROM student_projects WHERE id=? AND student_id=?")->execute([(int)$_POST['proj_id'],$sId]);
        $success = 'Project removed.';
        $projects = $db->prepare("SELECT * FROM student_projects WHERE student_id=? ORDER BY id DESC");
        $projects->execute([$sId]); $projects = $projects->fetchAll();
    }
}

$branches = ['Computer Science','Information Technology','Electronics','Electrical','Mechanical','Civil','Chemical','Biotechnology'];
$proficiencies = ['beginner','intermediate','advanced','expert'];

$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>My Profile</h1>
      <p>Keep your profile updated to maximize placement opportunities</p>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success" data-autohide><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger" data-autohide><?= e($error) ?></div>
    <?php endif; ?>

    <div class="grid-2">
      <!-- Profile Form -->
      <div>
        <div class="card mb-3">
          <div class="card-header"><h3 class="card-title">Personal Information</h3></div>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update_profile">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Roll Number</label>
              <input type="text" name="roll_number" class="form-control" value="<?= e($student['roll_number'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Branch *</label>
              <select name="branch" class="form-control" required>
                <option value="">Select Branch</option>
                <?php foreach ($branches as $b): ?>
                <option value="<?= e($b) ?>" <?= ($student['branch'] ?? '') === $b ? 'selected' : '' ?>><?= e($b) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="grid-2">
              <div class="form-group">
                <label class="form-label">CGPA *</label>
                <input type="number" name="cgpa" class="form-control" min="0" max="10" step="0.01" value="<?= e($student['cgpa'] ?? '0') ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Active Backlogs</label>
                <input type="number" name="backlogs" class="form-control" min="0" max="30" value="<?= e($student['backlogs'] ?? '0') ?>">
              </div>
            </div>
            <div class="grid-2">
              <div class="form-group">
                <label class="form-label">Year of Passing</label>
                <input type="number" name="year_of_passing" class="form-control" min="2020" max="2030" value="<?= e($student['year_of_passing'] ?? (date('Y') + 1)) ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control" value="<?= e($student['phone'] ?? '') ?>">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">LinkedIn URL</label>
              <input type="url" name="linkedin_url" class="form-control" placeholder="https://linkedin.com/in/..." value="<?= e($student['linkedin_url'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">GitHub URL</label>
              <input type="url" name="github_url" class="form-control" placeholder="https://github.com/..." value="<?= e($student['github_url'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Save Profile</button>
          </form>
        </div>
      </div>

      <!-- Skills + Projects -->
      <div>
        <!-- Skills -->
        <div class="card mb-3">
          <div class="card-header"><h3 class="card-title">Technical Skills</h3></div>
          <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1rem">
            <?php foreach ($skills as $sk): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action" value="delete_skill">
              <input type="hidden" name="skill_id" value="<?= $sk['id'] ?>">
              <button type="submit" class="badge badge-info" style="border:none;cursor:pointer;font-size:0.82rem" title="Click to remove">
                <?= e($sk['skill_name']) ?> <small>(<?= $sk['proficiency'] ?>)</small> ×
              </button>
            </form>
            <?php endforeach; ?>
            <?php if (!$skills): ?><p class="text-muted" style="font-size:0.88rem">No skills added yet.</p><?php endif; ?>
          </div>
          <form method="POST" style="display:flex;gap:0.5rem;flex-wrap:wrap">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="add_skill">
            <input type="text" name="skill_name" class="form-control" placeholder="e.g. Python" style="flex:1;min-width:120px">
            <select name="proficiency" class="form-control" style="width:auto">
              <?php foreach ($proficiencies as $p): ?>
              <option value="<?= $p ?>"><?= ucfirst($p) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Add</button>
          </form>
        </div>

        <!-- Projects -->
        <div class="card">
          <div class="card-header"><h3 class="card-title">Projects</h3></div>
          <?php foreach ($projects as $proj): ?>
          <div style="padding:0.9rem;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:0.75rem">
            <div style="display:flex;justify-content:space-between;align-items:start">
              <div>
                <strong><?= e($proj['title']) ?></strong>
                <?php if ($proj['tech_stack']): ?><div style="font-size:0.8rem;color:var(--accent);margin-top:0.2rem"><?= e($proj['tech_stack']) ?></div><?php endif; ?>
                <?php if ($proj['description']): ?><p style="font-size:0.85rem;color:var(--text-secondary);margin-top:0.3rem"><?= e($proj['description']) ?></p><?php endif; ?>
              </div>
              <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="delete_project">
                <input type="hidden" name="proj_id" value="<?= $proj['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">✕</button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="add_project">
            <div class="form-group">
              <label class="form-label">Project Title *</label>
              <input type="text" name="proj_title" class="form-control" placeholder="AI Resume Analyzer" required>
            </div>
            <div class="form-group">
              <label class="form-label">Tech Stack</label>
              <input type="text" name="proj_tech" class="form-control" placeholder="Python, Flask, React">
            </div>
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea name="proj_desc" class="form-control" rows="2" placeholder="Brief project description..."></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Project URL</label>
              <input type="url" name="proj_url" class="form-control" placeholder="https://github.com/...">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Add Project</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
