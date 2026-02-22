<?php
// api/chat.php – AI Chatbot Endpoint
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/gemini.php';

requireLogin();

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');

if (empty($message)) {
    echo json_encode(['response' => 'Please enter a message.']);
    exit;
}

$db   = getDB();
$user = currentUser();

// Get context based on role
$studentProfile = ['name' => $user['name'], 'cgpa' => 'N/A', 'branch' => 'N/A', 'backlogs' => 0];
$studentSkills  = [];

if ($user['role'] === 'student') {
    $s = $db->prepare("SELECT s.*, u.name FROM students s JOIN users u ON u.id=s.user_id WHERE s.user_id=?");
    $s->execute([$user['id']]);
    $s = $s->fetch();
    if ($s) {
        $studentProfile = $s;
        $skStmt = $db->prepare("SELECT skill_name FROM student_skills WHERE student_id=?");
        $skStmt->execute([$s['id']]);
        $studentSkills = $skStmt->fetchAll();
    }
}

// Get active drives
$drives = $db->query("
    SELECT * FROM drives WHERE status IN ('upcoming','active') AND drive_date >= CURDATE()
    ORDER BY drive_date ASC LIMIT 10
")->fetchAll();

$response = chatbotResponse($message, $studentProfile, $drives, $studentSkills);

// Log
$db->prepare("INSERT INTO chatbot_logs (user_id, message, response) VALUES (?,?,?)")
   ->execute([$user['id'], $message, $response]);

echo json_encode(['response' => $response]);
