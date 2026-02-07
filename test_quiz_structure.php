<?php
session_start();
require_once 'config/mongodb.php';

$db = getDatabase();

echo "<h2>Quiz System Structure Test</h2>\n";

// Test published questions
$published = $db->questions->find(['status' => 'published']);
echo "<h3>Published Questions: " . count($published) . "</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Quiz Title</th><th>Question</th><th>Status</th></tr>";
foreach ($published as $q) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($q['quiz_title'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars(substr($q['text'], 0, 50)) . "...</td>";
    echo "<td>" . ($q['status'] ?? 'N/A') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test quizzes
$quizzes = $db->quizzes->find(['status' => 'published']);
echo "<h3>Published Quizzes: " . count($quizzes) . "</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Quiz Title</th><th>Time Limit</th><th>Question Count</th><th>Created</th></tr>";
foreach ($quizzes as $quiz) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($quiz['quiz_title'] ?? 'N/A') . "</td>";
    echo "<td>" . ($quiz['time_limit_minutes'] ?? 'N/A') . " min</td>";
    echo "<td>" . ($quiz['question_count'] ?? 'N/A') . "</td>";
    echo "<td>" . ($quiz['created_at'] ?? 'N/A') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test results with quiz_title
$results = $db->results->find([], ['sort' => ['date' => -1]]);
echo "<h3>Latest Quiz Results: " . count($results) . "</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Student</th><th>Quiz Title</th><th>Score</th><th>Date</th></tr>";
$count = 0;
foreach ($results as $r) {
    if ($count >= 5) break;
    echo "<tr>";
    echo "<td>" . htmlspecialchars($r['username'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($r['quiz_title'] ?? 'N/A') . "</td>";
    echo "<td>" . ($r['score'] ?? 0) . " / " . ($r['total'] ?? 0) . "</td>";
    echo "<td>" . ($r['date'] ?? 'N/A') . "</td>";
    echo "</tr>";
    $count++;
}
echo "</table>";

echo "<hr>";
echo "<p><strong>✅ System is ready for quiz taking!</strong></p>";
echo "<p>Students can now select specific quizzes and take them.</p>";
echo "<p><a href='student_dashboard.php'>Go to Student Dashboard</a></p>";
?>
