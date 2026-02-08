<?php
session_start();
require_once 'config/mongodb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

$db = getDatabase();

// Get quiz selection from URL parameter
$selected_quiz_id = $_GET['quiz_id'] ?? null;

// Get all available quizzes for this college and batch
$all_quizzes = $db->quizzes->find(['status' => 'published', 'college' => $_SESSION['college'], 'batch' => $_SESSION['batch']], ['sort' => ['quiz_title' => 1]]);
$available_quizzes = [];
foreach ($all_quizzes as $q) {
    $available_quizzes[] = $q;
}

// If no quiz selected, show quiz selection page
if (!$selected_quiz_id) {
    if (count($available_quizzes) > 0) {
        // Show quiz selection
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Select Quiz - Quiz System</title>
            <link rel="stylesheet" href="style.css">
            <style>
                .quiz-card {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 1.5rem;
                    border-radius: 8px;
                    margin-bottom: 1rem;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                .quiz-card h3 {
                    margin: 0 0 0.5rem 0;
                    color: white;
                }
                .quiz-card p {
                    margin: 0.25rem 0;
                    font-size: 0.95rem;
                }
                .quiz-card .btn {
                    background: white;
                    color: #667eea;
                    font-weight: bold;
                    padding: 0.75rem 1.5rem;
                }
                .quiz-card .btn:hover {
                    background: #f0f0f0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header-nav">
                    <h2>Select a Quiz to Take</h2>
                    <div>
                        <span><?php echo htmlspecialchars($_SESSION['college_name']); ?></span>
                        <a href="student_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </div>

                <div>
                    <?php foreach ($available_quizzes as $quiz): 
                        $quiz_questions = $db->questions->find(['status' => 'published', 'quiz_title' => $quiz['quiz_title']]);
                        $q_count = count($quiz_questions);
                    ?>
                        <div class="quiz-card">
                            <div>
                                <h3><?php echo htmlspecialchars($quiz['quiz_title'] ?? 'Untitled Quiz'); ?></h3>
                                <p><strong>Questions:</strong> <?php echo $q_count; ?> | <strong>Time Limit:</strong> <?php echo htmlspecialchars($quiz['time_limit_minutes']); ?> minutes</p>
                                <p style="font-size: 0.85rem; opacity: 0.9;">Posted by: <?php echo htmlspecialchars($quiz['teacher_name'] ?? 'Unknown'); ?></p>
                                <p style="font-size: 0.85rem; opacity: 0.9;">Created: <?php echo htmlspecialchars($quiz['created_at']); ?></p>
                            </div>
                            <a href="?quiz_id=<?php echo htmlspecialchars($quiz['_id']); ?>" class="btn">Start Quiz</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        // No quizzes available for this college
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>No Quizzes Available - Quiz System</title>
            <link rel="stylesheet" href="style.css">
            <style>
                .info-container {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 80vh;
                }
                .info-box {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 3rem;
                    border-radius: 12px;
                    text-align: center;
                    max-width: 500px;
                    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
                }
                .info-box h2 {
                    margin-top: 0;
                    font-size: 1.8rem;
                    margin-bottom: 1rem;
                    border: none;
                    padding: 0;
                    color: white;
                }
                .info-box p {
                    margin: 1rem 0;
                    font-size: 1.1rem;
                    opacity: 0.95;
                }
                .info-box a.btn {
                    margin-top: 1.5rem;
                    display: inline-block;
                    background: white !important;
                    color: #667eea !important;
                    font-weight: bold;
                    padding: 0.75rem 2rem !important;
                    text-decoration: none !important;
                    box-shadow: none !important;
                    cursor: pointer;
                }
                .info-box a.btn:hover {
                    background: #f0f0f0 !important;
                    opacity: 1;
                    text-decoration: none !important;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="info-container">
                    <div class="info-box">
                        <h2>📚 No Quizzes Available</h2>
                        <p>There are currently no quizzes available for your college.</p>
                        <p>Please check back later or contact your teacher.</p>
                        <a href="student_dashboard.php" class="btn">← Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Find the selected quiz
$quiz = null;
if ($selected_quiz_id) {
    foreach ($available_quizzes as $q) {
        if ($q['_id'] === $selected_quiz_id) {
            $quiz = $q;
            break;
        }
    }
}

// Check if quiz has expired
$quiz_expired = false;
if ($quiz) {
    $due_datetime = $quiz['due_datetime'] ?? '';
    if ($due_datetime) {
        $due_timestamp = strtotime($due_datetime);
        $now_timestamp = time();
        if ($due_timestamp < $now_timestamp) {
            $quiz_expired = true;
        }
    }
}

if (!$quiz || $quiz_expired) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Quiz Not Found - Quiz System</title>
        <link rel="stylesheet" href="style.css">
        <style>
            .error-container {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 80vh;
            }
            .error-box {
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                color: white;
                padding: 3rem;
                border-radius: 12px;
                text-align: center;
                max-width: 500px;
                box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            }
            .error-box h2 {
                margin-top: 0;
                font-size: 1.8rem;
                margin-bottom: 1rem;
                color: white;
                border: none;
                padding: 0;
            }
            .error-box p {
                margin: 1rem 0;
                font-size: 1.1rem;
                opacity: 0.95;
            }
            .error-box a.btn {
                margin-top: 1.5rem;
                display: inline-block;
                background: white !important;
                color: #f5576c !important;
                font-weight: bold;
                padding: 0.75rem 2rem !important;
                text-decoration: none !important;
                box-shadow: none !important;
                cursor: pointer;
                border: 2px solid white;
            }
            .error-box a.btn:hover {
                background: transparent !important;
                color: white !important;
                text-decoration: none !important;
                border: 2px solid white;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="error-container">
                <div class="error-box">
                    <?php if ($quiz_expired): ?>
                        <h2>⏰ Quiz Expired</h2>
                        <p>This quiz ended on <strong><?php echo date('M d, Y H:i', strtotime($quiz['due_datetime'])); ?></strong></p>
                        <p>You can no longer take this quiz. However, you can still view your previous results.</p>
                    <?php else: ?>
                        <h2>❌ Quiz Not Found</h2>
                        <p>This quiz is no longer available or has been deleted.</p>
                        <p>Please select another quiz to continue.</p>
                    <?php endif; ?>
                    <a href="quiz.php" class="btn" onclick="window.location.href='quiz.php'; return false;">← Back to Quiz Selection</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$time_limit_minutes = $quiz['time_limit_minutes'] ?? 30;
$time_limit_seconds = $time_limit_minutes * 60;
$quiz_title = $quiz['quiz_title'] ?? 'Untitled Quiz';

// Get study materials for reference during quiz
$study_materials = $db->study_materials->find(['status' => 'active'], ['sort' => ['created_at' => -1]]);

// Get only published questions from THIS QUIZ
$all_questions = $db->questions->find(['status' => 'published', 'quiz_title' => $quiz_title], ['sort' => ['created_at' => 1]]);

// Get student's already attempted questions in this quiz
$student_results = $db->results->find(['user_id' => $_SESSION['user_id'], 'quiz_title' => $quiz_title]);
$attempted_question_ids = [];
foreach ($student_results as $result) {
    if (isset($result['attempted_questions'])) {
        $attempted_question_ids = array_merge($attempted_question_ids, $result['attempted_questions']);
    }
}

// Filter out already attempted questions
$questionsArr = [];
foreach ($all_questions as $q) {
    if (!in_array($q['_id'], $attempted_question_ids)) {
        $questionsArr[] = $q;
    }
}

if (count($questionsArr) === 0) {
    echo "<!DOCTYPE html><html><head><link rel='stylesheet' href='style.css'></head><body><div class='container'><h3>No questions available in this quiz or you have already answered all questions.</h3><a href='quiz.php' class='btn'>Select Another Quiz</a></div></body></html>";
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
    <style>
        .materials-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
        }
        .materials-modal.active {
            display: flex;
        }
        .materials-modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 600px;
            width: 95%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .materials-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 1rem;
        }
        .materials-modal-header h3 {
            margin: 0;
            color: #333;
        }
        .close-modal-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .close-modal-btn:hover {
            color: #333;
        }
        .material-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }
        .material-item h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }
        .material-item p {
            margin: 0.25rem 0;
            color: #666;
            font-size: 0.9rem;
        }
        .material-item-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        .material-item-actions a, .material-item-actions button {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            text-align: center;
            background: #667eea;
            color: white;
        }
        .material-item-actions a:hover, .material-item-actions button:hover {
            background: #5568d3;
        }
        .materials-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #27ae60 !important;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.4);
            z-index: 1500;
            transition: all 0.3s ease;
        }
        .materials-btn:hover {
            background: #229954 !important;
            box-shadow: 0 6px 16px rgba(39, 174, 96, 0.6);
        }
        .no-materials-msg {
            text-align: center;
            color: #999;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-nav">
            <h2><?php echo htmlspecialchars($quiz_title); ?></h2>
            <div>
                <a href="quiz.php" class="btn btn-secondary">Cancel</a>
                <?php if (!empty($study_materials)): ?>
                    <button type="button" class="btn" style="background: #27ae60; margin-left: 0.5rem;" onclick="openMaterialsModal()">📚 Study Materials</button>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" action="submit_quiz.php" id="quizForm">
            <input type="hidden" id="cheatingAttempts" name="cheating_attempts" value="0">
            <input type="hidden" name="quiz_title" value="<?php echo htmlspecialchars($quiz_title); ?>">
            
            <!-- Timer Display -->\n            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center;\">\n                <h3 style=\"margin: 0 0 10px 0;\">⏱️ Time Remaining</h3>\n                <div id="timer" style=\"font-size: 32px; font-weight: bold; font-family: monospace;\"><?php echo sprintf('%02d:%02d', intdiv($time_limit_seconds, 60), $time_limit_seconds % 60); ?></div>\n                <input type="hidden" id="timeRemaining" name="time_remaining" value="<?php echo $time_limit_seconds; ?>">\n            </div>
            
            <?php foreach ($questionsArr as $index => $q): ?>
                <div class="question-card">
                    <p><strong>Q<?php echo $index + 1; ?>: <?php echo htmlspecialchars($q['text'] ?? ''); ?></strong></p>
                    <div class="options-list">
                        <?php foreach ($q['options'] as $optIndex => $option): ?>
                            <label style="font-weight: normal;">
                                <input type="radio" name="answers[<?php echo htmlspecialchars($q['_id'] ?? ''); ?>]" value="<?php echo $optIndex + 1; ?>" required>
                                <?php echo htmlspecialchars($option); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn">Submit Quiz</button>
        </form>

        <!-- Study Materials Modal -->
        <div id="materialsModal" class="materials-modal">
            <div class="materials-modal-content">
                <div class="materials-modal-header">
                    <h3>📚 Study Materials</h3>
                    <button type="button" class="close-modal-btn" onclick="closeMaterialsModal()">&times;</button>
                </div>
                
                <div id="materialsContainer">
                    <?php if (empty($study_materials)): ?>
                        <div class="no-materials-msg">
                            <p>No study materials available at this time.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($study_materials as $material): ?>
                            <div class="material-item">
                                <h4><?php echo htmlspecialchars($material['title']); ?></h4>
                                <?php if (!empty($material['description'])): ?>
                                    <p><?php echo htmlspecialchars($material['description']); ?></p>
                                <?php endif; ?>
                                <p>📁 File: <?php echo htmlspecialchars($material['original_filename']); ?></p>
                                <p>📊 Size: <?php echo round($material['file_size'] / 1024 / 1024, 2); ?> MB</p>
                                <p>⏱️ Study Period: <?php echo $material['study_hours']; ?>h <?php echo $material['study_minutes']; ?>m</p>
                                
                                <div class="material-item-actions">
                                    <a href="uploads/<?php echo htmlspecialchars($material['filename']); ?>" download target="_blank">⬇️ Download</a>
                                    <a href="uploads/<?php echo htmlspecialchars($material['filename']); ?>" target="_blank">👁️ View</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cheatingAttempts = 0;
        let warningShown = false;
        let timeRemaining = <?php echo $time_limit_seconds; ?>;
        let timerInterval;
        let materialsModalOpen = false;

        // Materials Modal Functions
        function openMaterialsModal() {
            materialsModalOpen = true;
            document.getElementById('materialsModal').classList.add('active');
        }

        function closeMaterialsModal() {
            materialsModalOpen = false;
            document.getElementById('materialsModal').classList.remove('active');
        }

        // Close modal when clicking outside of it
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('materialsModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeMaterialsModal();
                    }
                });
            }
        });

        // Timer countdown
        function startTimer() {
            timerInterval = setInterval(function() {
                timeRemaining--;
                document.getElementById('timeRemaining').value = timeRemaining;
                
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                document.getElementById('timer').textContent = 
                    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                
                // Timer colors based on remaining time
                const timerDiv = document.getElementById('timer').parentElement;
                if (timeRemaining <= 60) {
                    timerDiv.style.background = 'linear-gradient(135deg, #f93b1d 0%, #ea1e63 100%)';
                    document.getElementById('timer').style.color = '#fff';
                } else if (timeRemaining <= 300) {
                    timerDiv.style.background = 'linear-gradient(135deg, #fa7921 0%, #fbb034 100%)';
                }
                
                // Auto-submit when time runs out
                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    alert('⏰ TIME\'S UP! Your quiz will be submitted now.');
                    document.getElementById('quizForm').submit();
                }
            }, 1000);
        }

        // Request fullscreen mode on page load
        function enterFullscreen() {
            const elem = document.documentElement;
            if (elem.requestFullscreen) {
                elem.requestFullscreen().catch(err => {
                    console.log('Fullscreen request failed:', err);
                });
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (elem.mozRequestFullScreen) {
                elem.mozRequestFullScreen();
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            }
        }

        // Detect tab/window switching using visibilitychange event
        // Modified to ignore when materials modal is open
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && !materialsModalOpen) {
                cheatingAttempts++;
                document.getElementById('cheatingAttempts').value = cheatingAttempts;

                if (cheatingAttempts === 1) {
                    // First offense - show warning
                    warningShown = true;
                    alert('⚠️ WARNING: You left the quiz window! This is considered cheating.\n\nIf you do this again, your quiz will be submitted with a score of 0 and marked as cheating.');
                } else if (cheatingAttempts >= 2) {
                    // Second offense - quit quiz with zero score
                    alert('❌ CHEATING DETECTED! Your quiz has been submitted with a score of 0.\nYour teacher will be notified of this cheating attempt.');
                    
                    // Submit with all empty answers
                    document.getElementById('quizForm').innerHTML += '<input type="hidden" name="cheated" value="true">';
                    document.getElementById('quizForm').submit();
                }
            }
        });

        // Prevent exiting fullscreen
        // Modified to allow fullscreen exit when materials modal is intentionally opened
        document.addEventListener('fullscreenchange', function() {
            if (!document.fullscreenElement && !materialsModalOpen) {
                cheatingAttempts++;
                document.getElementById('cheatingAttempts').value = cheatingAttempts;

                if (cheatingAttempts === 1) {
                    warningShown = true;
                    alert('⚠️ WARNING: You exited fullscreen mode! This is considered cheating.\n\nIf you do this again, your quiz will be submitted with a score of 0 and marked as cheating.');
                    // Re-enter fullscreen
                    enterFullscreen();
                } else if (cheatingAttempts >= 2) {
                    alert('❌ CHEATING DETECTED! Your quiz has been submitted with a score of 0.\nYour teacher will be notified of this cheating attempt.');
                    
                    document.getElementById('quizForm').innerHTML += '<input type="hidden" name="cheated" value="true">';
                    document.getElementById('quizForm').submit();
                }
            }
        });

        // Prevent keyboard shortcuts to quit fullscreen
        document.addEventListener('keydown', function(e) {
            // Prevent F11 (fullscreen toggle)
            if (e.key === 'F11' || e.keyCode === 122) {
                e.preventDefault();
            }
            // Prevent Escape key when modal is not open
            if (e.key === 'Escape' && !materialsModalOpen) {
                e.preventDefault();
            }
            // Allow Escape to close modal
            if (e.key === 'Escape' && materialsModalOpen) {
                closeMaterialsModal();
            }
            // Prevent Alt+Tab (browser dependent)
            if (e.altKey && e.key === 'Tab') {
                e.preventDefault();
            }
        });

        // Request fullscreen and start timer when page loads
        window.addEventListener('load', function() {
            setTimeout(enterFullscreen, 500);
            startTimer();
        });
    </script>
</body>
</html>
