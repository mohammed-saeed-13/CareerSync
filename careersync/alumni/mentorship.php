<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('alumni');

$db   = getDB();
$user = currentUser();

$alumni = $db->prepare("SELECT * FROM alumni WHERE user_id=?");
$alumni->execute([$user['id']]);
$alumni = $alumni->fetch();
$aId = $alumni['id'];

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_slot') {
        $date      = $_POST['slot_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime   = $_POST['end_time'] ?? '';
        $topic     = trim($_POST['topic'] ?? '');
        $meetLink  = trim($_POST['meeting_link'] ?? '');

        if (!$date || !$startTime || !$endTime) {
            $error = 'Date and time are required.';
        } elseif ($startTime >= $endTime) {
            $error = 'End time must be after start time.';
        } else {
            // Check overlap
            $overlap = $db->prepare("
                SELECT id FROM mentorship_slots
                WHERE alumni_id=? AND slot_date=?
                  AND status NOT IN ('cancelled')
                  AND (
                    (start_time < ? AND end_time > ?) OR
                    (start_time >= ? AND start_time < ?)
                  )
            ");
            $overlap->execute([$aId, $date, $endTime, $startTime, $startTime, $endTime]);
            if ($overlap->fetch()) {
                $error = 'Time slot overlaps with an existing slot.';
            } else {
                try {
                    $db->prepare("
                        INSERT INTO mentorship_slots (alumni_id, slot_date, start_time, end_time, topic, meeting_link)
                        VALUES (?,?,?,?,?,?)
                    ")->execute([$aId, $date, $startTime, $endTime, $topic, $meetLink]);
                    $success = 'Mentorship slot created!';
                } catch (Exception $e) {
                    $error = 'Failed to create slot.';
                }
            }
        }
    }

    if ($action === 'cancel_slot') {
        $db->prepare("UPDATE mentorship_slots SET status='cancelled' WHERE id=? AND alumni_id=?")
           ->execute([(int)$_POST['slot_id'], $aId]);
        $success = 'Slot cancelled.';
    }
}

$slots = $db->query("
    SELECT ms.*, u.name AS student_name, u.email AS student_email
    FROM mentorship_slots ms
    LEFT JOIN students s ON s.id = ms.booked_by
    LEFT JOIN users u ON u.id = s.user_id
    WHERE ms.alumni_id = $aId
    ORDER BY ms.slot_date DESC, ms.start_time ASC
")->fetchAll();

$pageTitle = 'Mentorship Slots';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Mentorship Slots</h1>
      <p>Create and manage your availability to mentor students</p>
    </div>

    <?php if ($success): ?><div class="alert alert-success" data-autohide><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" data-autohide><?= e($error) ?></div><?php endif; ?>

    <div class="grid-2">
      <!-- Create Slot -->
      <div class="card">
        <div class="card-header"><h3 class="card-title">Create New Slot</h3></div>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="create_slot">
          <div class="form-group">
            <label class="form-label">Date *</label>
            <input type="date" name="slot_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Start Time *</label>
              <input type="time" name="start_time" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">End Time *</label>
              <input type="time" name="end_time" class="form-control" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Topic / Focus Area</label>
            <input type="text" name="topic" class="form-control" placeholder="e.g. Resume Review, Interview Prep">
          </div>
          <div class="form-group">
            <label class="form-label">Meeting Link</label>
            <input type="url" name="meeting_link" class="form-control" placeholder="https://meet.google.com/...">
          </div>
          <button type="submit" class="btn btn-primary btn-block">Create Slot</button>
        </form>
      </div>

      <!-- Slot List -->
      <div class="card">
        <div class="card-header"><h3 class="card-title">Your Slots</h3></div>
        <?php if ($slots): ?>
        <div style="max-height:480px;overflow-y:auto">
          <?php foreach ($slots as $sl): ?>
          <div style="padding:0.9rem;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:0.75rem">
            <div style="display:flex;justify-content:space-between;align-items:start">
              <div>
                <strong><?= date('D, d M Y', strtotime($sl['slot_date'])) ?></strong>
                <div style="font-size:0.85rem;color:var(--accent)">
                  <?= substr($sl['start_time'],0,5) ?> – <?= substr($sl['end_time'],0,5) ?>
                </div>
                <?php if ($sl['topic']): ?>
                <div style="font-size:0.82rem;color:var(--text-muted);margin-top:0.2rem">📌 <?= e($sl['topic']) ?></div>
                <?php endif; ?>
                <?php if ($sl['status'] === 'booked' && $sl['student_name']): ?>
                <div style="font-size:0.82rem;color:var(--success);margin-top:0.3rem">
                  👤 Booked by <?= e($sl['student_name']) ?> (<?= e($sl['student_email']) ?>)
                </div>
                <?php endif; ?>
                <?php if ($sl['meeting_link'] && $sl['status'] === 'booked'): ?>
                <a href="<?= e($sl['meeting_link']) ?>" target="_blank" class="btn btn-sm btn-success" style="margin-top:0.4rem">Join Meeting</a>
                <?php endif; ?>
              </div>
              <div style="display:flex;flex-direction:column;gap:0.4rem;align-items:flex-end">
                <span class="badge badge-<?= ['available'=>'success','booked'=>'warning','completed'=>'info','cancelled'=>'danger'][$sl['status']] ?>">
                  <?= ucfirst($sl['status']) ?>
                </span>
                <?php if ($sl['status'] === 'available'): ?>
                <form method="POST">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="action" value="cancel_slot">
                  <input type="hidden" name="slot_id" value="<?= $sl['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted">No slots created yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
