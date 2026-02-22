<?php
// ============================================================
// includes/gemini.php – Google Gemini API Integration
// ============================================================

require_once __DIR__ . '/../config.php';

function callGemini(string $prompt, string $systemInstruction = ''): string {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . GEMINI_API_KEY;

    $payload = [
        'contents' => [
            ['role' => 'user', 'parts' => [['text' => $prompt]]]
        ],
        'generationConfig' => [
            'temperature'     => 0.7,
            'maxOutputTokens' => 2048,
        ],
    ];

    if ($systemInstruction) {
        $payload['system_instruction'] = ['parts' => [['text' => $systemInstruction]]];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return "AI service temporarily unavailable. Please try again later.";
    }

    $data = json_decode($response, true);
    return $data['candidates'][0]['content']['parts'][0]['text'] ?? "No response generated.";
}

// ---- Resume Analyzer -------------------------------------------
function analyzeResume(array $studentData, array $skills, array $projects): array {
    $skillList    = implode(', ', array_column($skills, 'skill_name'));
    $projectCount = count($projects);
    $projectTitles = implode(', ', array_column($projects, 'title'));

    $prompt = <<<PROMPT
Analyze this student's placement profile and provide a JSON response ONLY (no markdown):

Student Profile:
- Name: {$studentData['name']}
- Branch: {$studentData['branch']}
- CGPA: {$studentData['cgpa']}
- Backlogs: {$studentData['backlogs']}
- Skills: {$skillList}
- Projects: {$projectCount} projects ({$projectTitles})
- Year of Passing: {$studentData['year_of_passing']}

Return ONLY this JSON structure:
{
  "resume_score": <0-100>,
  "ats_score": <0-100>,
  "placement_probability": <0-100>,
  "missing_keywords": ["skill1","skill2","skill3"],
  "strengths": ["strength1","strength2"],
  "suggestions": ["suggestion1","suggestion2","suggestion3"],
  "summary": "2-sentence overall assessment"
}
PROMPT;

    $raw = callGemini($prompt, "You are an expert ATS resume analyzer and placement counselor for engineering students.");

    // Strip markdown code blocks if present
    $raw = preg_replace('/```json\s*/i', '', $raw);
    $raw = preg_replace('/```\s*/', '', $raw);
    $raw = trim($raw);

    $parsed = json_decode($raw, true);
    if (!$parsed) {
        $parsed = [
            'resume_score'         => 65,
            'ats_score'            => 60,
            'placement_probability'=> 70,
            'missing_keywords'     => ['Communication Skills', 'Problem Solving', 'Agile'],
            'strengths'            => ['Technical foundation', 'Academic record'],
            'suggestions'          => ['Add more projects', 'Improve skill diversity', 'Add certifications'],
            'summary'              => "Profile shows potential. Enhance technical skills and project depth.",
        ];
    }

    return ['parsed' => $parsed, 'raw' => $raw];
}

// ---- Smart Chatbot ---------------------------------------------
function chatbotResponse(string $userMessage, array $studentProfile, array $drives, array $studentSkills): string {
    $skillList  = implode(', ', array_column($studentSkills, 'skill_name'));
    $driveList  = '';
    foreach ($drives as $d) {
        $driveList .= "- {$d['company_name']} ({$d['job_role']}): CGPA≥{$d['min_cgpa']}, Backlogs≤{$d['max_backlogs']}, Package: {$d['package_lpa']} LPA, Date: {$d['drive_date']}\n";
    }

    $systemPrompt = <<<SYS
You are CareerSync AI, a smart campus placement assistant. Answer concisely and helpfully.

Student Context:
- Name: {$studentProfile['name']}
- CGPA: {$studentProfile['cgpa']}
- Branch: {$studentProfile['branch']}
- Backlogs: {$studentProfile['backlogs']}
- Skills: {$skillList}

Active Drives:
{$driveList}

Rules:
1. For eligibility queries, check student's CGPA vs drive min_cgpa, backlogs vs max_backlogs, branch vs allowed branches
2. Be specific with numbers
3. Always mention drive date if relevant
4. Keep responses under 150 words
5. Use friendly, professional tone
SYS;

    return callGemini($userMessage, $systemPrompt);
}
