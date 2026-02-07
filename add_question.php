<?php
session_start();
require_once 'config/mongodb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';
$db = getDatabase();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'assign_quiz') {
        // Assign quiz to all students
        $questions = $db->questions->find(['status' => 'draft']);
        if (count($questions) > 0) {
            // Update questions to published status
            $db->questions->updateMany(['status' => 'draft'], ['status' => 'published']);
            
            // Create quiz record
            $quiz_result = $db->quizzes->insertOne([
                'teacher_id' => $_SESSION['user_id'],
                'question_count' => count($questions),
                'status' => 'published',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($quiz_result->getInsertedCount() === 1) {
                $success = "Quiz published successfully! " . count($questions) . " questions are now available to students.";
                header("refresh:2;url=teacher_dashboard.php");
            }
        } else {
            $error = "No questions to assign. Add questions first.";
        }
    } else {
        // Add question
        $question_text = trim($_POST['question_text'] ?? '');
        $option1 = trim($_POST['option1'] ?? '');
        $option2 = trim($_POST['option2'] ?? '');
        $option3 = trim($_POST['option3'] ?? '');
        $option4 = trim($_POST['option4'] ?? '');
        $correct_index = (int)($_POST['correct_index'] ?? 0);

        if (empty($question_text) || empty($option1) || empty($option2) || empty($option3) || empty($option4)) {
            $error = "Please fill in all fields.";
        } else {
            $result = $db->questions->insertOne([
                'text' => $question_text,
                'options' => [$option1, $option2, $option3, $option4],
                'correct_index' => $correct_index,
                'status' => 'draft',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if ($result->getInsertedCount() === 1) {
                $success = "Question added! Form cleared for next question.";
            } else {
                $error = "Failed to add question.";
            }
        }
    }
}

// Get draft questions count
$draft_questions = $db->questions->find(['status' => 'draft']);
$question_count = count($draft_questions);
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
            <h2>Add Questions to Quiz</h2>
            <div style="display: flex; gap: 10px;">
                <a href="teacher_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <?php if ($question_count > 0): ?>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="assign_quiz">
                        <button type="submit" class="btn" style="background-color: #27ae60;">
                            Publish Quiz (<?php echo $question_count; ?> Questions)
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div style="background: #f0f0f0; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <p><strong>Questions Added (Draft):</strong> <?php echo $question_count; ?></p>
            <p style="color: #666; font-size: 0.9rem;">Add as many questions as needed, then click "Publish Quiz" to make them available to students.</p>
        </div>

        <form method="POST" action="" id="questionForm">
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

            <button type="submit" class="btn">Save and Add Next Question</button>
        </form>
        
        <script>
            document.getElementById('questionForm').addEventListener('submit', function(e) {
                // Clear form after submission for better UX
                const form = this;
                setTimeout(() => {
                    form.reset();
                    document.getElementById('question_text').focus();
                }, 100);
            });
        </script>
    </div>
</body>
</html>
