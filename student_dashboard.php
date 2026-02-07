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

// Get active study materials
$study_materials = $db->study_materials->find(['status' => 'active'], ['sort' => ['created_at' => -1]]);

// Check if any active study period exists
$active_study_period = null;
$quiz_blocked = false;
$time_until_quiz = 0;

foreach ($study_materials as $material) {
    $end_time = strtotime($material['end_time']);
    $now = time();
    
    if ($end_time > $now) {
        $active_study_period = $material;
        $quiz_blocked = true;
        $time_until_quiz = $end_time - $now;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Quiz System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .study-section {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .study-material-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .timer-large {
            font-size: 24px;
            font-weight: bold;
            color: #e74c3c;
            font-family: monospace;
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .quiz-blocked {
            opacity: 0.6;
            pointer-events: none;
        }
        .blocked-overlay {
            background: rgba(0,0,0,0.05);
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            color: #666;
        }
    </style>
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

        <!-- Study Materials Section -->
        <?php if (!empty($study_materials)): ?>
            <div class="study-section">
                <h3 style="margin-top: 0;">📚 Study Materials Available</h3>
                <?php foreach ($study_materials as $material): 
                    $end_time = strtotime($material['end_time']);
                    $now = time();
                    $is_expired = $end_time < $now;
                ?>
                    <div class="study-material-card">
                        <h4 style="margin: 0 0 0.5rem 0;">📄 <?php echo htmlspecialchars($material['title']); ?></h4>
                        <?php if (!empty($material['description'])): ?>
                            <p style="margin: 0.5rem 0; color: #666;"><?php echo htmlspecialchars($material['description']); ?></p>
                        <?php endif; ?>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                            <div style="font-size: 0.9rem; color: #666;">
                                <p style="margin: 0.25rem 0;">📁 <?php echo htmlspecialchars($material['original_filename']); ?></p>
                                <p style="margin: 0.25rem 0;">📊 Size: <?php echo round($material['file_size'] / 1024 / 1024, 2); ?> MB</p>
                            </div>
                            <a href="uploads/<?php echo htmlspecialchars($material['filename']); ?>" download class="btn" style="white-space: nowrap;">⬇️ Download</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Quiz Section -->
        <?php if ($quiz_blocked): ?>
            <!-- Study Period Active - Quiz Blocked -->
            <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; text-align: center;">
                <h3 style="color: white; margin-top: 0;">📖 Study Period Active</h3>
                <p style="font-size: 1.1rem; margin: 1rem 0;">Please finish studying the provided materials before the quiz opens.</p>
                
                <div class="timer-large" id="countdown-timer">
                    <?php 
                        $hours = intdiv($time_until_quiz, 3600);
                        $minutes = intdiv($time_until_quiz % 3600, 60);
                        $seconds = $time_until_quiz % 60;
                        echo sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                    ?>
                </div>
                
                <p style="margin: 1rem 0 0 0; opacity: 0.9;">The quiz will become available when the study period ends.</p>
            </div>
        <?php else: ?>
            <!-- Quiz Available -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2rem; border-radius: 12px; color: white; margin-bottom: 2rem;">
                <h3 style="color: white; margin-top: 0; border-left: none;">🎯 Available Quizzes</h3>
                <p style="margin: 0.5rem 0;">Test your knowledge with the General Knowledge Quiz.</p>
                <div style="display: flex; gap: 1rem; align-items: center; margin-top: 1rem;">
                    <div style="flex: 1;">
                        <p style="margin: 0.5rem 0;"><strong>⏱️ Time Limit:</strong> <?php echo $quiz_time_limit; ?> minutes</p>
                        <p style="margin: 0.5rem 0; font-size: 0.9rem; opacity: 0.9;">Complete the quiz within the time limit to get your score.</p>
                    </div>
                    <a href="quiz.php" class="btn" style="background: white; color: #667eea; font-weight: bold; white-space: nowrap;">Start Quiz →</a>
                </div>
            </div>
        <?php endif; ?>

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

    <script>
        // Auto-refresh countdown timer
        <?php if ($quiz_blocked): ?>
            let timeRemaining = <?php echo $time_until_quiz; ?>;
            const countdownElement = document.getElementById('countdown-timer');

            function updateTimer() {
                timeRemaining--;

                const hours = Math.floor(timeRemaining / 3600);
                const minutes = Math.floor((timeRemaining % 3600) / 60);
                const seconds = timeRemaining % 60;

                const formatted = String(hours).padStart(2, '0') + ':' + 
                                 String(minutes).padStart(2, '0') + ':' + 
                                 String(seconds).padStart(2, '0');

                if (countdownElement) {
                    countdownElement.textContent = formatted;
                }

                if (timeRemaining <= 0) {
                    // Quiz is now available - refresh the page
                    location.reload();
                }
            }

            // Update every second
            setInterval(updateTimer, 1000);
        <?php endif; ?>
    </script>
</body>
</html>
