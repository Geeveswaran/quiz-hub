<?php
session_start();
require_once 'config/mongodb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

$db = getDatabase();
$questions = $db->questions->find(['status' => 'published', 'college' => $_SESSION['college'], 'batch' => $_SESSION['batch']], ['sort' => ['created_at' => -1]]);
$results = $db->results->find(['college' => $_SESSION['college'], 'batch' => $_SESSION['batch']], ['sort' => ['date' => -1]]);
$quiz = $db->quizzes->findOne(['status' => 'published', 'college' => $_SESSION['college'], 'batch' => $_SESSION['batch']]);
$quiz_time_limit = $quiz['time_limit_minutes'] ?? 30;
$study_materials = $db->study_materials->find(['status' => 'active', 'college' => $_SESSION['college'], 'batch' => $_SESSION['batch']], ['sort' => ['created_at' => -1]]);
$published_quizzes = $db->quizzes->find(['status' => 'published', 'college' => $_SESSION['college'], 'batch' => $_SESSION['batch']], ['sort' => ['created_at' => -1]]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Auto-refresh every 5 seconds for results -->
    <meta http-equiv="refresh" content="5">
    <title>Teacher Dashboard - Quiz System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header-nav">
            <h2>Teacher Dashboard</h2>
            <div>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> | <strong><?php echo htmlspecialchars($_SESSION['college_name']); ?></strong></span>
                <a href="logout.php" class="btn btn-secondary" style="margin-left: 1rem;">Logout</a>
            </div>
        </div>

        <div style="margin-bottom: 2rem;">
            <h3>Actions</h3>
            <a href="add_question.php" class="btn">Add New Question</a>
            <a href="upload_study_material.php" class="btn" style="background: #27ae60; margin-left: 0.5rem;">📤 Upload Study Material</a>
        </div>

        <div style="margin-bottom: 2rem; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 1.5rem; border-radius: 12px; color: white;">
            <h3 style="color: white; margin-top: 0; border-left: none;">📋 Current Quiz Info</h3>
            <p style="margin: 0.5rem 0;"><strong>Time Limit:</strong> <?php echo $quiz_time_limit; ?> minutes | <strong>Total Questions:</strong> <?php echo $db->questions->countDocuments(['status' => 'published']); ?></p>
        </div>

        <div style="margin-bottom: 2rem;">
            <h3>📚 Study Materials</h3>
            <?php 
            $mat_count = count($study_materials);
            if ($mat_count === 0): 
            ?>
                <p>No study materials uploaded yet. <a href="upload_study_material.php">Upload one now</a></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>File Size</th>
                            <th>Study Period</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($study_materials as $mat): 
                            $end_time = strtotime($mat['end_time']);
                            $now = time();
                            $is_expired = $end_time < $now;
                        ?>
                        <tr style="<?php echo $is_expired ? 'opacity: 0.6;' : ''; ?>">
                            <td><?php echo htmlspecialchars($mat['title']); ?></td>
                            <td><?php echo round($mat['file_size'] / 1024 / 1024, 2); ?> MB</td>
                            <td><?php echo $mat['study_hours']; ?>h <?php echo $mat['study_minutes']; ?>m</td>
                            <td><?php echo $is_expired ? '✓ Expired' : '⏳ Active'; ?></td>
                            <td>
                                <a href="delete_material.php?id=<?php echo htmlspecialchars($mat['_id']); ?>" class="btn btn-danger" style="font-size: 0.85rem; padding: 0.5rem 1rem;" onclick="return confirm('Delete this material?');">🗑️ Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3>📋 Published Quizzes</h3>
                <a href="cleanup_expired_quizzes.php" class="btn" style="background: #e74c3c; font-size: 0.9rem;" onclick="alert('Cleanup process started. Expired quizzes will be deleted and non-attendees will be marked.');">🔄 Run Cleanup Now</a>
            </div>
            <?php 
            $pub_count = count($published_quizzes);
            if ($pub_count === 0): 
            ?>
                <p>No quizzes published yet. <a href="add_question.php">Create and publish a quiz</a></p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Quiz Title</th>
                            <th>Questions</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($published_quizzes as $pub_quiz): 
                            $due_datetime = $pub_quiz['due_datetime'] ?? '';
                            $due_timestamp = strtotime($due_datetime);
                            $now_timestamp = time();
                            $is_expired = $due_timestamp < $now_timestamp;
                            $days_remaining = (int)(($due_timestamp - $now_timestamp) / 86400);
                        ?>
                        <tr style="<?php echo $is_expired ? 'opacity: 0.6; background: #ffe0e0;' : ''; ?>">
                            <td><?php echo htmlspecialchars($pub_quiz['quiz_title']); ?></td>
                            <td><?php echo htmlspecialchars($pub_quiz['question_count']); ?></td>
                            <td><?php echo htmlspecialchars(date('M d, Y H:i', $due_timestamp)); ?></td>
                            <td>
                                <?php if ($is_expired): ?>
                                    <span style="color: #e74c3c; font-weight: bold;">❌ Expired</span>
                                <?php else: ?>
                                    <span style="color: #27ae60; font-weight: bold;">✓ Active</span>
                                    <br><small style="color: #666;"><?php echo $days_remaining >= 0 ? "$days_remaining days left" : "Expired"; ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div>
            <h3>Student Results (Live)</h3>
            <?php 
            $resCount = $db->results->countDocuments();
            if ($resCount === 0): 
            ?>
                <p>No results yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Quiz</th>
                            <th>Score</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $r): ?>
                        <tr style="<?php echo (isset($r['cheated']) && $r['cheated']) ? 'background-color: #ffcccc; color: #cc0000; font-weight: bold;' : ''; ?>">
                            <td><?php echo htmlspecialchars($r['username'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($r['quiz_title'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(($r['score'] ?? 0) . ' / ' . ($r['total'] ?? 0)); ?></td>
                            <td>
                                <?php 
                                echo htmlspecialchars($r['date'] ?? 'N/A');
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (isset($r['status']) && $r['status'] === 'not_attended') {
                                    echo '⚠️ NOT ATTENDED';
                                } elseif (isset($r['cheated']) && $r['cheated']) {
                                    echo '⚠️ CHEATED';
                                } else {
                                    echo 'Completed';
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
