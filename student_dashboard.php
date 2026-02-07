<?php
session_start();
require_once 'config/mongodb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

$db = getDatabase();
$my_results = $db->results->find(
    ['username' => $_SESSION['username']],
    ['sort' => ['date' => -1]]
);

// Get quiz info with time limit
$quiz = $db->quizzes->findOne(['status' => 'published']);
$quiz_time_limit = $quiz['time_limit_minutes'] ?? 30;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Quiz System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-nav">
            <h2>Student Dashboard</h2>
            <div>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn btn-secondary" style="margin-left: 1rem;">Logout</a>
            </div>
        </div>

        <div style="margin-bottom: 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2rem; border-radius: 12px; color: white;">
            <h3 style="color: white; margin-top: 0; border-left: none;">Available Quizzes</h3>
            <p style="margin: 0.5rem 0;">Test your knowledge with the General Knowledge Quiz.</p>
            <div style="display: flex; gap: 1rem; align-items: center; margin-top: 1rem;">
                <div style="flex: 1;">
                    <p style="margin: 0.5rem 0;"><strong>⏱️ Time Limit:</strong> <?php echo $quiz_time_limit; ?> minutes</p>
                    <p style="margin: 0.5rem 0; font-size: 0.9rem; opacity: 0.9;">Complete the quiz within the time limit to get your score.</p>
                </div>
                <a href="quiz.php" class="btn" style="background: white; color: #667eea; font-weight: bold; white-space: nowrap;">Start Quiz →</a>
            </div>
        </div>

        <div>
            <h3>My Past Results</h3>
            <?php 
            $count = $db->results->countDocuments(['username' => $_SESSION['username']]);
            if ($count === 0): 
            ?>
                <p>No past attempts.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Score</th>
                            <th>Total Questions</th>
                            <th>Percentage</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_results as $r): 
                            $percentage = (($r['total'] ?? 0) > 0) ? round((($r['score'] ?? 0) / ($r['total'] ?? 0)) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['score'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($r['total'] ?? 0); ?></td>
                            <td><?php echo $percentage; ?>%</td>
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
