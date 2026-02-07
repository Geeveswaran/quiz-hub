<?php
session_start();
require_once 'config/mongodb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

$db = getDatabase();
$questions = $db->questions->find(['status' => 'published'], ['sort' => ['created_at' => -1]]);
$results = $db->results->find([], ['sort' => ['date' => -1]]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Auto-refresh every 5 seconds for results -->
    <meta http-equiv="refresh" content="5">
    <title>Teacher Dashboard - Quiz System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-nav">
            <h2>Teacher Dashboard</h2>
            <div>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn btn-secondary" style="margin-left: 1rem;">Logout</a>
            </div>
        </div>

        <div style="margin-bottom: 2rem;">
            <h3>Actions</h3>
            <a href="add_question.php" class="btn">Add New Question</a>
        </div>

        <div style="margin-bottom: 2rem;">
            <h3>Quiz Questions</h3>
            <?php 
            $count = $db->questions->countDocuments();
            if ($count === 0): 
            ?>
                <p>No questions added yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Correct Answer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $q): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($q['text'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($q['options'][$q['correct_index'] - 1] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div>
            <h3>Student Results (Live)</h3>
            <?php 
            $resCount = $db->results->countDocuments();
            if ($resCount === 0): 
            ?>
                <p>No results yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Score</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['username'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(($r['score'] ?? 0) . ' / ' . ($r['total'] ?? 0)); ?></td>
                            <td>
                                <?php 
                                echo htmlspecialchars($r['date'] ?? 'N/A');
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
