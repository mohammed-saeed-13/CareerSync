<?php
// api/notifications.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$db = getDB();
$userId = currentUser()['id'];

if (isset($_GET['count'])) {
    $count = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $count->execute([$userId]);
    echo json_encode(['count' => (int)$count->fetchColumn()]);
    exit;
}

// Mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$userId]);
    echo json_encode(['ok' => true]);
    exit;
}

$notifs = $db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
$notifs->execute([$userId]);
echo json_encode($notifs->fetchAll());
