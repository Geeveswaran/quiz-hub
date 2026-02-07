<?php
session_start();
require_once 'config/mongodb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

$db = getDatabase();
$questions = $db->questions->find([], ['sort' => ['created_at' => 1]]);
$questionsArr = iterator_to_array($questions);

if (count($questionsArr) === 0) {
    echo "<!DOCTYPE html><html><head><link rel='stylesheet' href='style.css'></head><body><div class='container'><h3>No questions available.</h3><a href='student_dashboard.php' class='btn'>Back</a></div></body></html>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz - Quiz System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-nav">
            <h2>General Quiz</h2>
            <a href="student_dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>

        <form method="POST" action="submit_quiz.php">
            <?php foreach ($questionsArr as $index => $q): ?>
                <div class="question-card">
                    <p><strong>Q<?php echo $index + 1; ?>: <?php echo htmlspecialchars($q->text); ?></strong></p>
                    <div class="options-list">
                        <?php foreach ($q->options as $optIndex => $option): ?>
                            <label style="font-weight: normal;">
                                <input type="radio" name="answers[<?php echo (string)$q->_id; ?>]" value="<?php echo $optIndex + 1; ?>" required>
                                <?php echo htmlspecialchars($option); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn">Submit Quiz</button>
        </form>
    </div>
</body>
</html>
