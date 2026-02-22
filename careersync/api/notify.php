<?php
// api/notify.php – Notify eligible students for a drive
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $driveId = (int)($_POST['drive_id'] ?? 0);
    if (!$driveId) { header('Location: ' . APP_URL . '/admin/criteria.php'); exit; }

    $db = getDB();
    $drive = $db->prepare("SELECT * FROM drives WHERE id=?");
    $drive->execute([$driveId]);
    $drive = $drive->fetch();

    if (!$drive) { header('Location: ' . APP_URL . '/admin/criteria.php'); exit; }

    $branches = json_decode($drive['allowed_branches'], true) ?: [];
    $placeholders = rtrim(str_repeat('?,', count($branches)), ',');

    $stmt = $db->prepare("
        SELECT s.id as student_id, u.id as user_id
        FROM students s JOIN users u ON u.id = s.user_id
        WHERE s.cgpa >= ? AND s.backlogs <= ? AND s.branch IN ($placeholders)
    ");
    $params = array_merge([$drive['min_cgpa'], $drive['max_backlogs']], $branches);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

    $notified = 0;
    foreach ($students as $st) {
        // Check if notification already sent for this drive+student
        $exists = $db->prepare("
            SELECT id FROM notifications
            WHERE user_id=? AND message LIKE ?
        ");
        $exists->execute([$st['user_id'], '%' . $drive['company_name'] . '%']);
        if (!$exists->fetch()) {
            $db->prepare("
                INSERT INTO notifications (user_id, title, message, type)
                VALUES (?,?,?,?)
            ")->execute([
                $st['user_id'],
                'New Drive: ' . $drive['company_name'],
                "You are eligible for {$drive['company_name']} – {$drive['job_role']}. Drive date: {$drive['drive_date']}. Min CGPA: {$drive['min_cgpa']}. Apply before deadline!",
                'info'
            ]);
            $notified++;
        }
    }

    $_SESSION['flash']['success'] = "Notified $notified eligible students for {$drive['company_name']}.";
}

header('Location: ' . APP_URL . '/admin/criteria.php?drive_id=' . $driveId);
exit;
