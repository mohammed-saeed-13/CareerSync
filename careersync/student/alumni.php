<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('student');

$db   = getDB();
$user = currentUser();
$student = $db->prepare("SELECT * FROM students WHERE user_id=?");
$student->execute([$user['id']]);
$student = $student->fetch();
$sId = $student ? $student['id'] : null;

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_slot']) && $sId) {
    verifyCsrf();
    $slotId = (int)$_POST['slot_id'];

    $slot = $db->prepare("SELECT * FROM mentorship_slots WHERE id=? AND status='available'");
    $slot->execute([$slotId]);
    $slot = $slot->fetch();

    if (!$slot) {
        $error = 'This slot is no longer available.';
    } else {
        $exists = $db->prepare("
            SELECT id FROM mentorship_slots
            WHERE booked_by=? AND alumni_id=? AND slot_date=? AND status='booked'
        ");
        $exists->execute([$sId, $slot['alumni_id'], $slot['slot_date']]);
        if ($exists->fetch()) {
            $error = 'You already have a slot booked with this mentor on the same day.';
        } else {
            $db->prepare("UPDATE mentorship_slots SET status='booked', booked_by=? WHERE id=?")->execute([$sId, $slotId]);
            $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)")
               ->execute([$user['id'], 'Mentor Session Booked', 'Your mentorship slot has been confirmed!', 'success']);
            $success = 'Slot booked successfully!';
        }
    }
}

// Get alumni mentors with available slots
$mentors = $db->query("
    SELECT a.*, u.name, u.email,
           COUNT(ms.id) AS available_slots
    FROM alumni a
    JOIN users u ON u.id = a.user_id
    LEFT JOIN mentorship_slots ms ON ms.alumni_id = a.id AND ms.status = 'available' AND ms.slot_date >= CURDATE()
    WHERE a.is_mentor = 1
    GROUP BY a.id
    ORDER BY available_slots DESC
")->fetchAll();

// Job referral board
$referrals = $db->query("
    SELECT r.*, u.name AS alumni_name, a.current_role, a.current_company
    FROM referrals r
    JOIN alumni a ON a.id = r.alumni_id
    JOIN users u ON u.id = a.user_id
    WHERE r.is_active = 1 AND (r.expiry_date IS NULL OR r.expiry_date >= CURDATE())
    ORDER BY r.created_at DESC
")->fetchAll();

$pageTitle = 'Alumni Connect';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-header">
      <h1>Alumni Connect</h1>
      <p>Book mentorship sessions and explore job referrals from seniors</p>
    </div>

    <?php if ($success): ?><div class="alert alert-success" data-autohide><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" data-autohide><?= e($error) ?></div><?php endif; ?>

    <!-- Mentors Section -->
    <h2 style="font-size:1.1rem;font-family:var(--font-display);margin-bottom:1rem">
      👨‍🏫 Available Mentors
    </h2>

    <?php if ($mentors): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem;margin-bottom:2.5rem">
      <?php foreach ($mentors as $m): ?>
      <div class="card">
        <div style="display:flex;align-items:center;gap:0.9rem;margin-bottom:0.9rem">
          <div class="avatar-circle" style="width:46px;height:46px;font-size:1rem;flex-shrink:0">
            <?= strtoupper(substr($m['name'], 0, 2)) ?>
          </div>
          <div>
            <strong><?= e($m['name']) ?></strong>
            <div style="font-size:0.82rem;color:var(--accent)"><?= e($m['current_role'] ?? 'Alumni') ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted)"><?= e($m['current_company'] ?? '') ?></div>
          </div>
        </div>

        <div style="display:flex;gap:0.4rem;flex-wrap:wrap;margin-bottom:0.9rem">
          <span class="badge badge-<?= $m['available_slots'] > 0 ? 'success' : 'secondary' ?>">
            <?= $m['available_slots'] ?> slot<?= $m['available_slots'] != 1 ? 's' : '' ?> available
          </span>
          <?php if ($m['branch']): ?>
          <span class="badge badge-info"><?= e($m['branch']) ?></span>
          <?php endif; ?>
          <?php if ($m['graduation_year']): ?>
          <span class="badge badge-secondary">Batch <?= $m['graduation_year'] ?></span>
          <?php endif; ?>
        </div>

        <?php if ($m['bio']): ?>
        <p style="font-size:0.84rem;color:var(--text-secondary);margin-bottom:0.9rem;line-height:1.5">
          <?= e(mb_substr($m['bio'], 0, 110)) . (mb_strlen($m['bio']) > 110 ? '...' : '') ?>
        </p>
        <?php endif; ?>

        <button
          class="btn btn-primary btn-block btn-sm"
          onclick="toggleSlots('slots-<?= $m['id'] ?>')"
          <?= $m['available_slots'] == 0 ? 'disabled' : '' ?>>
          <?= $m['available_slots'] > 0 ? '📅 View & Book Slots' : 'No Slots Available' ?>
        </button>

        <!-- Slots dropdown -->
        <div id="slots-<?= $m['id'] ?>" class="hidden" style="margin-top:0.9rem;border-top:1px solid var(--border);padding-top:0.75rem">
          <?php
          $mSlots = $db->prepare("
              SELECT * FROM mentorship_slots
              WHERE alumni_id=? AND status='available' AND slot_date >= CURDATE()
              ORDER BY slot_date ASC, start_time ASC
          ");
          $mSlots->execute([$m['id']]);
          $mSlots = $mSlots->fetchAll();
          ?>
          <?php if ($mSlots): ?>
            <?php foreach ($mSlots as $sl): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.45rem 0;border-bottom:1px solid var(--border);font-size:0.83rem">
              <div>
                <strong><?= date('D, d M', strtotime($sl['slot_date'])) ?></strong>
                <span class="text-muted"> &nbsp;<?= substr($sl['start_time'],0,5) ?>–<?= substr($sl['end_time'],0,5) ?></span>
                <?php if ($sl['topic']): ?>
                <div class="text-muted" style="font-size:0.78rem">📌 <?= e($sl['topic']) ?></div>
                <?php endif; ?>
              </div>
              <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="book_slot" value="1">
                <input type="hidden" name="slot_id" value="<?= $sl['id'] ?>">
                <button type="submit" class="btn btn-success btn-sm">Book</button>
              </form>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted" style="font-size:0.85rem">No upcoming slots.</p>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card mb-3" style="text-align:center;padding:2.5rem">
      <p class="text-muted">No mentors available right now. Check back soon!</p>
    </div>
    <?php endif; ?>

    <!-- Referrals Section -->
    <h2 style="font-size:1.1rem;font-family:var(--font-display);margin-bottom:1rem">
      💼 Job Referral Board
    </h2>

    <?php if ($referrals): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem">
      <?php foreach ($referrals as $r): ?>
      <div class="card" style="border-top:3px solid var(--accent)">
        <div style="margin-bottom:0.75rem">
          <strong style="font-size:1rem"><?= e($r['job_title']) ?></strong>
          <div style="font-size:0.88rem;color:var(--accent);font-weight:500"><?= e($r['company_name']) ?></div>
          <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.2rem">
            Referred by <?= e($r['alumni_name']) ?>
            <?php if ($r['current_role']): ?> · <?= e($r['current_role']) ?><?php endif; ?>
          </div>
        </div>

        <?php if ($r['description']): ?>
        <p style="font-size:0.84rem;color:var(--text-secondary);margin-bottom:0.75rem;line-height:1.5">
          <?= e(mb_substr($r['description'], 0, 130)) . (mb_strlen($r['description']) > 130 ? '...' : '') ?>
        </p>
        <?php endif; ?>

        <?php if ($r['required_skills']): ?>
        <div style="margin-bottom:0.6rem;font-size:0.8rem">
          <span class="text-muted">Skills:</span> <?= e($r['required_skills']) ?>
        </div>
        <?php endif; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.5rem">
          <div>
            <span class="badge badge-secondary"><?= e($r['experience_required'] ?: 'Fresher') ?></span>
            <?php if ($r['expiry_date']): ?>
            <span class="badge badge-warning" style="margin-left:0.3rem">Expires <?= date('d M', strtotime($r['expiry_date'])) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($r['apply_link']): ?>
          <a href="<?= e($r['apply_link']) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
            Apply →
          </a>
          <?php else: ?>
          <span class="badge badge-info">Contact Alumni</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card" style="text-align:center;padding:2.5rem">
      <p class="text-muted">No referrals posted yet. Check back soon!</p>
    </div>
    <?php endif; ?>
  </main>
</div>

<?php
$extraJs = <<<JS
<script>
function toggleSlots(id) {
  const el = document.getElementById(id);
  if (el) el.classList.toggle('hidden');
}
</script>
JS;
include __DIR__ . '/../includes/footer.php';
?>
