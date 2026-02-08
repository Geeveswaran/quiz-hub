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

// Get current quiz from session or POST
$current_quiz = $_POST['selected_quiz'] ?? $_SESSION['current_quiz'] ?? null;
if ($current_quiz) {
    $_SESSION['current_quiz'] = $current_quiz;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'publish_quiz') {
        // Publish quiz
        $quiz_title = trim($_POST['quiz_title_publish'] ?? '');
        $time_hours = (int)($_POST['time_hours'] ?? 0);
        $time_minutes = (int)($_POST['time_minutes'] ?? 30);
        $time_limit = ($time_hours * 60) + $time_minutes;
        
        $due_date = $_POST['due_date'] ?? '';
        $due_time = $_POST['due_time'] ?? '23:59';
        
        if (empty($quiz_title)) {
            $error = "Please provide a quiz title.";
        } elseif ($time_limit < 1) {
            $error = "Please set a valid time limit (at least 1 minute).";
        } elseif (empty($due_date)) {
            $error = "Please set a due date for the quiz.";
        } else {
            $questions = $db->questions->find(['status' => 'draft', 'quiz_title' => $quiz_title, 'college' => $_SESSION['college'], 'batch' => $_SESSION['batch']]);
            $draft_questions_count = count($questions);
            
            if ($draft_questions_count > 0) {
                // Update questions to published status
                $db->questions->updateMany(
                    ['status' => 'draft', 'quiz_title' => $quiz_title, 'college' => $_SESSION['college'], 'batch' => $_SESSION['batch']],
                    ['status' => 'published']
                );
                
                // Create quiz record
                $quiz_result = $db->quizzes->insertOne([
                    'teacher_id' => $_SESSION['user_id'],
                    'teacher_name' => $_SESSION['username'],
                    'quiz_title' => $quiz_title,
                    'college' => $_SESSION['college'],
                    'college_name' => $_SESSION['college_name'],
                    'batch' => $_SESSION['batch'],
                    'question_count' => $draft_questions_count,
                    'status' => 'published',
                    'time_limit_minutes' => $time_limit,
                    'due_date' => $due_date,
                    'due_time' => $due_time,
                    'due_datetime' => date('Y-m-d H:i:s', strtotime("$due_date $due_time")),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                if ($quiz_result->getInsertedCount() === 1) {
                    $success = "✓ Quiz '$quiz_title' published successfully! $draft_questions_count questions available to students.";
                    unset($_SESSION['current_quiz']);
                    $current_quiz = null;
                    header("refresh:2;url=teacher_dashboard.php");
                }
            } else {
                $error = "No questions found for this quiz. Add questions first.";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'change_quiz') {
        // User is selecting or creating a new quiz
        $current_quiz = trim($_POST['selected_quiz'] ?? '');
        if ($current_quiz) {
            $_SESSION['current_quiz'] = $current_quiz;
        }
    } else {
        // Add question to current quiz
        if (empty($current_quiz)) {
            $error = "Please select a quiz title first.";
        } else {
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
                    'quiz_title' => $current_quiz,
                    'text' => $question_text,
                    'options' => [$option1, $option2, $option3, $option4],
                    'correct_index' => $correct_index,
                    'status' => 'draft',
                    'college' => $_SESSION['college'],
                    'college_name' => $_SESSION['college_name'],
                    'batch' => $_SESSION['batch'],
                    'teacher_id' => $_SESSION['user_id'],
                    'teacher_name' => $_SESSION['username'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                if ($result->getInsertedCount() === 1) {
                    $success = "✓ Question added! Ready to add another.";
                } else {
                    $error = "Failed to add question.";
                }
            }
        }
    }
}

// Get all draft quizzes for this teacher (filtered by college and batch)
$draft_questions_all = $db->questions->find(['status' => 'draft', 'college' => $_SESSION['college'], 'batch' => $_SESSION['batch']], ['sort' => ['quiz_title' => 1]]);
$quizzes_draft = [];
foreach ($draft_questions_all as $q) {
    $title = $q['quiz_title'] ?? 'Untitled';
    if (!isset($quizzes_draft[$title])) {
        $quizzes_draft[$title] = [];
    }
    $quizzes_draft[$title][] = $q;
}

// Get questions for current quiz
$current_quiz_questions = [];
if ($current_quiz && isset($quizzes_draft[$current_quiz])) {
    $current_quiz_questions = $quizzes_draft[$current_quiz];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question - Quiz System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .quiz-selector {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1.5rem;
            border-radius: 8px;
            color: white;
            margin-bottom: 2rem;
        }
        .quiz-selector h3 {
            margin-top: 0;
            color: white;
            border: none;
            padding: 0;
        }
        .selector-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .selector-form .form-group {
            flex: 1;
            min-width: 250px;
            margin: 0;
        }
        .selector-form label {
            color: white;
            font-weight: 600;
        }
        .selector-form input,
        .selector-form select {
            background: white;
            border: none;
            padding: 0.75rem;
            border-radius: 4px;
        }
        .selector-form button {
            padding: 0.75rem 1.5rem;
            background: white;
            color: #667eea;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
        }
        .selector-form button:hover {
            opacity: 0.9;
        }
        .current-quiz-info {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .current-quiz-info h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .current-quiz-info p {
            margin: 0.5rem 0;
            color: #555;
        }
        .quiz-questions-preview {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            max-height: 300px;
            overflow-y: auto;
        }
        .question-item {
            background: white;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            border-left: 3px solid #667eea;
            font-size: 0.95rem;
        }
        .question-item strong {
            color: #667eea;
        }
        .empty-state {
            color: #999;
            font-style: italic;
            padding: 1rem;
        }
        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .publish-section {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            padding: 2rem;
            border-radius: 8px;
            color: white;
        }
        .publish-section h3 {
            color: white;
            border: none;
            margin-top: 0;
        }
        .publish-form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .publish-form .form-group {
            flex: 1;
            min-width: 150px;
            margin: 0;
        }
        .publish-form label {
            color: white;
            font-weight: 600;
        }
        .publish-form input,
        .publish-form select {
            background: white;
            border: none;
            padding: 0.75rem;
            border-radius: 4px;
        }
        .publish-form button {
            padding: 0.75rem 1.5rem;
            background: white;
            color: #27ae60;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
        }
        .publish-form button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-nav">
            <h2>📝 Add Questions to Quiz</h2>
            <a href="teacher_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Step 1: Select or Create Quiz -->
        <div class="quiz-selector">
            <h3>📋 Step 1: Select or Create Quiz</h3>
            <form method="POST" action="" class="selector-form">
                <input type="hidden" name="action" value="change_quiz">
                <div class="form-group">
                    <label for="selected_quiz">Quiz Title:</label>
                    <input type="text" id="selected_quiz" name="selected_quiz" 
                           value="<?php echo htmlspecialchars($current_quiz ?? ''); ?>"
                           placeholder="e.g., Python Basics, Chapter 1, Mid-term Exam"
                           list="quiz_suggestions" required>
                    <datalist id="quiz_suggestions">
                        <?php foreach (array_keys($quizzes_draft) as $title): ?>
                            <option value="<?php echo htmlspecialchars($title); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <button type="submit">Select/Create Quiz</button>
            </form>
        </div>

        <!-- Step 2: Current Quiz Info -->
        <?php if ($current_quiz): ?>
            <div class="current-quiz-info">
                <h3>✓ Current Quiz: <strong><?php echo htmlspecialchars($current_quiz); ?></strong></h3>
                <p><strong>Questions Added:</strong> <?php echo count($current_quiz_questions); ?></p>
                
                <?php if (count($current_quiz_questions) > 0): ?>
                    <div class="quiz-questions-preview">
                        <?php foreach ($current_quiz_questions as $index => $q): ?>
                            <div class="question-item">
                                <strong>Q<?php echo $index + 1; ?>:</strong> <?php echo htmlspecialchars(substr($q['text'], 0, 60)) . (strlen($q['text']) > 60 ? '...' : ''); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">No questions added yet. Add your first question below.</div>
                <?php endif; ?>
            </div>

            <!-- Step 3: Add Questions -->
            <div class="form-section">
                <h3>📌 Step 2: Add Questions</h3>
                <form method="POST" action="" id="questionForm">
                    <div class="form-group">
                        <label for="question_text">Question Text *</label>
                        <input type="text" id="question_text" name="question_text" placeholder="Enter your question here" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Options *</label>
                        <input type="text" name="option1" placeholder="Option 1" required style="margin-bottom: 0.5rem;">
                        <input type="text" name="option2" placeholder="Option 2" required style="margin-bottom: 0.5rem;">
                        <input type="text" name="option3" placeholder="Option 3" required style="margin-bottom: 0.5rem;">
                        <input type="text" name="option4" placeholder="Option 4" required style="margin-bottom: 0.5rem;">
                    </div>

                    <div class="form-group">
                        <label for="correct_index">Correct Answer *</label>
                        <select id="correct_index" name="correct_index" required>
                            <option value="">-- Select Correct Answer --</option>
                            <option value="1">Option 1</option>
                            <option value="2">Option 2</option>
                            <option value="3">Option 3</option>
                            <option value="4">Option 4</option>
                        </select>
                    </div>

                    <button type="submit" class="btn" style="width: 100%; background: #667eea;">Add Question & Continue</button>
                </form>
            </div>

            <!-- Step 4: Publish Quiz -->
            <?php if (count($current_quiz_questions) > 0): ?>
                <div class="publish-section">
                    <h3>🚀 Step 3: Ready to Publish?</h3>
                    <p>You have <strong><?php echo count($current_quiz_questions); ?> question(s)</strong> ready. Set the time limit, due date, and publish.</p>
                    
                    <form method="POST" action="" class="publish-form">
                        <input type="hidden" name="action" value="publish_quiz">
                        <input type="hidden" name="quiz_title_publish" value="<?php echo htmlspecialchars($current_quiz); ?>">
                        
                        <div class="form-group">
                            <label for="pub_time_hours">Time Limit:</label>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="number" id="pub_time_hours" name="time_hours" value="0" min="0" max="10" required style="width: 70px;">
                                <span style="color: white; font-weight: bold;">h</span>
                                <input type="number" id="pub_time_minutes" name="time_minutes" value="30" min="0" max="59" required style="width: 70px;">
                                <span style="color: white; font-weight: bold;">min</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="due_date">Due Date *:</label>
                            <input type="date" id="due_date" name="due_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="due_time">Due Time:</label>
                            <input type="time" id="due_time" name="due_time" value="23:59" required>
                        </div>
                        
                        <button type="submit" onclick="return confirm('Publish this quiz? Students will see it immediately. The quiz will auto-delete after the due date.');">✓ Publish Quiz</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="form-section" style="text-align: center; padding: 3rem;">
                <p style="font-size: 1.1rem; color: #666;">👆 Select or create a quiz title above to get started</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('questionForm').addEventListener('submit', function(e) {
            setTimeout(() => {
                document.getElementById('question_text').value = '';
                document.getElementById('question_text').focus();
            }, 100);
        });
    </script>
</body>
</html>
