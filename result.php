<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_quiz_score'])) {
    header("Location: student_dashboard.php");
    exit;
}

$score = $_SESSION['last_quiz_score'];
$total = $_SESSION['last_quiz_total'];
$percentage = ($total > 0) ? round(($score / $total) * 100, 2) : 0;

// Clear the session variables so refreshing doesn't show old result? 
// Or keep them for a moment. Let's keep them, it's fine.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result - Quiz System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="text-align: center;">
        <h1>Quiz Result</h1>
        
        <div style="font-size: 1.2rem; margin: 2rem 0;">
            <p>You scored</p>
            <h2 style="font-size: 3rem; color: #3498db; margin: 1rem 0;"><?php echo $score; ?> / <?php echo $total; ?></h2>
            <p>Percentage: <strong><?php echo $percentage; ?>%</strong></p>
        </div>

        <?php if ($percentage >= 50): ?>
            <div class="alert alert-success">Great job! You passed.</div>
        <?php else: ?>
            <div class="alert alert-error">Keep practicing! You can do better.</div>
        <?php endif; ?>

        <div style="margin-top: 2rem;">
            <a href="student_dashboard.php" class="btn">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
