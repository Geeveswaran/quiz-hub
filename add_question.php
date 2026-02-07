<?php
session_start();
require_once 'config/mongodb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = trim($_POST['question_text']);
    $option1 = trim($_POST['option1']);
    $option2 = trim($_POST['option2']);
    $option3 = trim($_POST['option3']);
    $option4 = trim($_POST['option4']);
    $correct_index = (int)$_POST['correct_index'];

    if (empty($question_text) || empty($option1) || empty($option2) || empty($option3) || empty($option4)) {
        $error = "Please fill in all fields.";
    } else {
        $db = getDatabase();
        $result = $db->questions->insertOne([
            'text' => $question_text,
            'options' => [$option1, $option2, $option3, $option4],
            'correct_index' => $correct_index,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);

        if ($result->getInsertedCount() === 1) {
            $success = "Question added successfully!";
        } else {
            $error = "Failed to add question.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question - Quiz System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-nav">
            <h2>Add Question</h2>
            <a href="teacher_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="question_text">Question Text</label>
                <input type="text" id="question_text" name="question_text" required>
            </div>
            
            <div class="form-group">
                <label>Options</label>
                <input type="text" name="option1" placeholder="Option 1" required style="margin-bottom: 0.5rem;">
                <input type="text" name="option2" placeholder="Option 2" required style="margin-bottom: 0.5rem;">
                <input type="text" name="option3" placeholder="Option 3" required style="margin-bottom: 0.5rem;">
                <input type="text" name="option4" placeholder="Option 4" required style="margin-bottom: 0.5rem;">
            </div>

            <div class="form-group">
                <label for="correct_index">Correct Answer</label>
                <select id="correct_index" name="correct_index" required>
                    <option value="1">Option 1</option>
                    <option value="2">Option 2</option>
                    <option value="3">Option 3</option>
                    <option value="4">Option 4</option>
                </select>
            </div>

            <button type="submit" class="btn">Save Question</button>
        </form>
    </div>
</body>
</html>
