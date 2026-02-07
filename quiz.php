<?php
session_start();
require_once 'config/mongodb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

$db = getDatabase();

// Get quiz time limit
$quiz = $db->quizzes->findOne(['status' => 'published']);
$time_limit_minutes = $quiz['time_limit_minutes'] ?? 30;
$time_limit_seconds = $time_limit_minutes * 60;

// Get only published questions
$all_questions = $db->questions->find(['status' => 'published'], ['sort' => ['created_at' => 1]]);

// Get student's already attempted questions
$student_results = $db->results->find(['user_id' => $_SESSION['user_id']]);
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
    echo "<!DOCTYPE html><html><head><link rel='stylesheet' href='style.css'></head><body><div class='container'><h3>No questions available or you have already answered all questions.</h3><a href='student_dashboard.php' class='btn'>Back</a></div></body></html>";
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

        <form method="POST" action="submit_quiz.php" id="quizForm">
            <input type="hidden" id="cheatingAttempts" name="cheating_attempts" value="0">
            
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
    </div>

    <script>
        let cheatingAttempts = 0;
        let warningShown = false;
        let timeRemaining = <?php echo $time_limit_seconds; ?>;
        let timerInterval;

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
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
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
        document.addEventListener('fullscreenchange', function() {
            if (!document.fullscreenElement) {
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
            // Prevent Escape key
            if (e.key === 'Escape') {
                e.preventDefault();
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
