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

        <div style="margin-bottom: 2rem;">
            <h3>Available Quizzes</h3>
            <p>Test your knowledge with the General Knowledge Quiz.</p>
            <a href="quiz.php" class="btn">Attempt Quiz</a>
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
                            $percentage = ($r->total > 0) ? round(($r->score / $r->total) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r->score); ?></td>
                            <td><?php echo htmlspecialchars($r->total); ?></td>
                            <td><?php echo $percentage; ?>%</td>
                            <td>
                                <?php 
                                if (isset($r->date) && $r->date instanceof MongoDB\BSON\UTCDateTime) {
                                    echo $r->date->toDateTime()->format('Y-m-d H:i:s');
                                } else {
                                    echo "N/A";
                                }
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
