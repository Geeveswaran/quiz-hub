<?php
session_start();
require_once 'config/mongodb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'] ?? [];
    $cheating_attempts = (int)($_POST['cheating_attempts'] ?? 0);
    $cheated = isset($_POST['cheated']) && $_POST['cheated'] === 'true';
    
    $score = 0;
    $total = 0;

    $db = getDatabase();
    
    // Track which questions were attempted
    $attempted_questions = array_keys($answers);
    
    // If cheated or excessive cheating attempts, set score to 0
    if ($cheated || $cheating_attempts >= 2) {
        $score = 0;
        $attempted_questions = [];
    } else {
        foreach ($answers as $qId => $userAnswer) {
            $total++;
            try {
                $question = $db->questions->findOne(['_id' => $qId]);
                if ($question && (int)($question['correct_index'] ?? 0) === (int)$userAnswer) {
                    $score++;
                }
            } catch (Exception $e) {
                // Invalid ID or error, ignore
            }
        }
    }

    // Get count of published questions
    $publishedCount = $db->questions->countDocuments(['status' => 'published']);
    $total = $publishedCount;

    // Store result with attempted questions and cheating flag
    $result = $db->results->insertOne([
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'score' => $score,
        'total' => $total,
        'attempted_questions' => $attempted_questions,
        'cheated' => $cheated || $cheating_attempts >= 2 ? true : false,
        'cheating_attempts' => $cheating_attempts,
        'date' => date('Y-m-d H:i:s')
    ]);

    $_SESSION['last_quiz_score'] = $score;
    $_SESSION['last_quiz_total'] = $total;

    header("Location: result.php");
    exit;
} else {
    header("Location: student_dashboard.php");
    exit;
}
?>
