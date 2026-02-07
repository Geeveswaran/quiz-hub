<?php
session_start();
require_once 'config/mongodb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'] ?? [];
    $score = 0;
    $total = 0;

    $db = getDatabase();
    
    // We need to fetch questions to verify answers
    // Efficiently, we could use an $in query if we had IDs, 
    // but here iterating over submitted answers is okay for small scale.
    
    foreach ($answers as $qId => $userAnswer) {
        $total++;
        try {
            $question = $db->questions->findOne(['_id' => new MongoDB\BSON\ObjectId($qId)]);
            if ($question && (int)$question->correct_index === (int)$userAnswer) {
                $score++;
            }
        } catch (Exception $e) {
            // Invalid ID or error, ignore
        }
    }

    // Double check total against actual total questions if needed, 
    // but relying on submitted answers ensures we grade what was seen.
    // For better security, we should fetch ALL questions and compare.
    $allQuestionsCount = $db->questions->countDocuments();
    
    // If the student missed some questions (didn't select), they aren't in $_POST['answers']
    // So 'total' should ideally be the total count of questions available.
    $total = $allQuestionsCount; 

    // Store result
    $result = $db->results->insertOne([
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'score' => $score,
        'total' => $total,
        'date' => new MongoDB\BSON\UTCDateTime()
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
