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
        // Assign quiz with specific title
        $quiz_title = trim($_POST['quiz_title'] ?? '');
        $time_limit = (int)($_POST['time_limit'] ?? 30);
        
        if (empty($quiz_title)) {
            $error = "Please provide a quiz title.";
        } else {
            $questions = $db->questions->find(['status' => 'draft', 'quiz_title' => $quiz_title]);
            $draft_questions_count = count($questions);
            
            if ($draft_questions_count > 0) {
                // Update questions to published status with quiz_title
                $db->questions->updateMany(
                    ['status' => 'draft', 'quiz_title' => $quiz_title],
                    ['status' => 'published']
                );
                
                // Create quiz record with title and time limit
                $quiz_result = $db->quizzes->insertOne([
                    'teacher_id' => $_SESSION['user_id'],
                    'quiz_title' => $quiz_title,
                    'question_count' => $draft_questions_count,
                    'status' => 'published',
                    'time_limit_minutes' => $time_limit,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                if ($quiz_result->getInsertedCount() === 1) {
                    $success = "Quiz '$quiz_title' published successfully! " . $draft_questions_count . " questions are now available to students.";
                    header("refresh:2;url=teacher_dashboard.php");
                }
            } else {
                $error = "No questions found for the quiz title: $quiz_title. Add questions first.";
            }
        }
    } else {
        // Add question
        $quiz_title = trim($_POST['quiz_title'] ?? '');
        $question_text = trim($_POST['question_text'] ?? '');
        $option1 = trim($_POST['option1'] ?? '');
        $option2 = trim($_POST['option2'] ?? '');
        $option3 = trim($_POST['option3'] ?? '');
        $option4 = trim($_POST['option4'] ?? '');
        $correct_index = (int)($_POST['correct_index'] ?? 0);

        if (empty($quiz_title) || empty($question_text) || empty($option1) || empty($option2) || empty($option3) || empty($option4)) {
            $error = "Please fill in all fields, including quiz title.";
        } else {
            $result = $db->questions->insertOne([
                'quiz_title' => $quiz_title,
                'text' => $question_text,
                'options' => [$option1, $option2, $option3, $option4],
                'correct_index' => $correct_index,
                'status' => 'draft',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if ($result->getInsertedCount() === 1) {
                $success = "Question added to '$quiz_title'! Form cleared for next question.";
            } else {
                $error = "Failed to add question.";
            }
        }
    }
}

// Get draft questions grouped by quiz title
$draft_questions_all = $db->questions->find(['status' => 'draft'], ['sort' => ['quiz_title' => 1]]);
$quizzes_draft = [];
foreach ($draft_questions_all as $q) {
    $title = $q['quiz_title'] ?? 'Untitled';
    if (!isset($quizzes_draft[$title])) {
        $quizzes_draft[$title] = 0;
    }
    $quizzes_draft[$title]++;
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
            <h2>Add Questions to Quiz</h2>
            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="teacher_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <?php if (count($quizzes_draft) > 0): ?>
                    <form method="POST" action="" style="display: flex; gap: 10px; align-items: center;">
                        <input type="hidden" name="action" value="assign_quiz">
                        <select name="quiz_title" required style="padding: 8px; border-radius: 4px; border: none;">
                            <option value="">Select Quiz to Publish</option>
                            <?php foreach ($quizzes_draft as $title => $count): ?>
                                <option value="<?php echo htmlspecialchars($title); ?>">
                                    <?php echo htmlspecialchars($title) . " ($count questions)"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="time_limit" style="margin: 0; color: white; font-weight: bold;">Time (min):</label>
                        <input type="number" id="time_limit" name="time_limit" value="30" min="1" max="180" required style="padding: 8px; border-radius: 4px; border: none; width: 70px;">
                        <button type="submit" class="btn" style="background-color: #27ae60; cursor: pointer;">
                            Publish Quiz
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
            <p><strong>Quizzes in Progress:</strong> <?php echo count($quizzes_draft); ?></p>
            <?php if (count($quizzes_draft) > 0): ?>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <?php foreach ($quizzes_draft as $title => $count): ?>
                        <li><?php echo htmlspecialchars($title); ?> - <strong><?php echo $count; ?> questions</strong></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <p style="color: #666; font-size: 0.9rem;">Add questions to a quiz title below, then publish when ready.</p>
        </div>

        <form method="POST" action="" id="questionForm">
            <div class="form-group">
                <label for="quiz_title">Quiz Title *</label>
                <input type="text" id="quiz_title" name="quiz_title" placeholder="e.g., Python Basics, Chapter 1, Mid-term Exam" required>
                <small style="color: #666;">Enter the quiz title. Questions with the same title will be grouped together.</small>
            </div>

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
                // Keep quiz title, clear question fields
                setTimeout(() => {
                    document.getElementById('question_text').value = '';
                    document.getElementById('question_text').focus();
                }, 100);
            });
        </script>
    </div>
</body>
</html>
