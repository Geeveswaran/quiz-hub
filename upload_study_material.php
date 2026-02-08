<?php
session_start();
require_once 'config/mongodb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

$db = getDatabase();
$message = '';
$error = '';

// Handle success/error from delete
if (isset($_GET['success'])) {
    $message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $study_hours = intval($_POST['study_hours'] ?? 0);
    $study_minutes = intval($_POST['study_minutes'] ?? 0);
    
    // Validate inputs
    if (empty($title)) {
        $error = "Title is required.";
    } elseif ($study_hours < 0 || $study_minutes < 0 || ($study_hours === 0 && $study_minutes === 0)) {
        $error = "Study period must be at least 1 minute.";
    } elseif ($study_hours > 168 || ($study_hours === 168 && $study_minutes > 0)) {
        $error = "Study period must not exceed 168 hours.";
    } elseif (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please upload a valid file.";
    } else {
        $file = $_FILES['document'];
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
        $max_size = 20 * 1024 * 1024; // 20MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $error = "Invalid file type. Allowed: PDF, Word, Text, PowerPoint";
        } elseif ($file['size'] > $max_size) {
            $error = "File size must not exceed 20MB.";
        } else {
            // Generate unique filename
            $uploads_dir = __DIR__ . '/uploads';
            if (!is_dir($uploads_dir)) {
                mkdir($uploads_dir, 0755, true);
            }
            
            $filename = uniqid() . '_' . basename($file['name']);
            $filepath = $uploads_dir . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Calculate end time with hours and minutes
                $start_time = date('Y-m-d H:i:s');
                $total_minutes = ($study_hours * 60) + $study_minutes;
                $end_time = date('Y-m-d H:i:s', strtotime("+{$total_minutes} minutes"));
                
                // Save to database
                $material = [
                    'teacher_id' => $_SESSION['user_id'],
                    'teacher_name' => $_SESSION['username'],
                    'college' => $_SESSION['college'],
                    'college_name' => $_SESSION['college_name'],
                    'batch' => $_SESSION['batch'],
                    'title' => $title,
                    'description' => $description,
                    'filename' => $filename,
                    'original_filename' => $file['name'],
                    'file_size' => $file['size'],
                    'study_hours' => $study_hours,
                    'study_minutes' => $study_minutes,
                    'total_minutes' => $total_minutes,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'created_at' => $start_time,
                    'status' => 'active'
                ];
                
                $db->study_materials->insertOne($material);
                $message = "Study material uploaded successfully! Students can start studying now.";
            } else {
                $error = "Failed to upload file. Please try again.";
            }
        }
    }
}

// Get all active study materials for this college and batch
$study_materials = $db->study_materials->find(['status' => 'active', 'college' => $_SESSION['college'], 'batch' => $_SESSION['batch']], ['sort' => ['created_at' => -1]]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Study Material - Teacher Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 1rem;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .file-input-label {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #667eea;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 0.5rem;
        }
        .file-input-label:hover {
            background: #5568d3;
        }
        #filename-display {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .material-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }
        .material-info {
            margin: 0.5rem 0;
        }
        .timer {
            font-weight: bold;
            color: #e74c3c;
        }
        .expired {
            opacity: 0.6;
            border-left-color: #999;
        }
        .expired .timer {
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-nav">
            <h2>Upload Study Material</h2>
            <div>
                <a href="teacher_dashboard.php" class="btn btn-secondary">Back</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; border-left: 4px solid #28a745;">
                ✓ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; border-left: 4px solid #f5c6cb;">
                ✗ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem;">
            <h3>📤 Upload New Study Material</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Document Title *</label>
                    <input type="text" id="title" name="title" required placeholder="e.g., Chapter 5 - Biology Basics">
                </div>

                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <textarea id="description" name="description" placeholder="Provide details about the study material..."></textarea>
                </div>

                <div class="form-group">
                    <label>Study Period *</label>
                    <div style="display: flex; gap: 1rem; align-items: flex-end;">
                        <div style="flex: 1;">
                            <label for="study_hours" style="margin-bottom: 0.25rem; display: block; font-size: 0.9rem;">Hours</label>
                            <input type="number" id="study_hours" name="study_hours" min="0" max="168" value="0" required style="width: 100%;">
                        </div>
                        <div style="flex: 1;">
                            <label for="study_minutes" style="margin-bottom: 0.25rem; display: block; font-size: 0.9rem;">Minutes</label>
                            <input type="number" id="study_minutes" name="study_minutes" min="0" max="59" value="30" required style="width: 100%;">
                        </div>
                    </div>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">How long students can study before the quiz becomes available (e.g., 1 hour 30 minutes)</small>
                </div>

                <div class="form-group">
                    <label for="document">Upload File *</label>
                    <label for="document" class="file-input-label">Choose File</label>
                    <input type="file" id="document" name="document" required style="display: none;" accept=".pdf,.doc,.docx,.txt,.ppt,.pptx">
                    <div id="filename-display">No file chosen</div>
                </div>

                <button type="submit" class="btn" style="width: 100%; background: #667eea;">Upload Material</button>
            </form>
        </div>

        <div>
            <h3>📚 Active Study Materials</h3>
            <?php if (empty($study_materials)): ?>
                <p style="color: #666;">No study materials uploaded yet.</p>
            <?php else: ?>
                <?php foreach ($study_materials as $material): 
                    $end_time = strtotime($material['end_time']);
                    $now = time();
                    $is_expired = $end_time < $now;
                    $time_remaining = $end_time - $now;
                    
                    // Calculate human readable time
                    $hours = intdiv($time_remaining, 3600);
                    $minutes = intdiv($time_remaining % 3600, 60);
                ?>
                <div class="material-card <?php echo $is_expired ? 'expired' : ''; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($material['title']); ?></h4>
                            <?php if (!empty($material['description'])): ?>
                                <p style="margin: 0.5rem 0; color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($material['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="material-info">
                                <small>📁 File: <?php echo htmlspecialchars($material['original_filename']); ?></small>
                            </div>
                            <div class="material-info">
                                <small>📊 Size: <?php echo round($material['file_size'] / 1024 / 1024, 2); ?> MB</small>
                            </div>
                            <div class="material-info">
                                <small>⏱️ Study Period: <?php echo $material['study_hours']; ?>h <?php echo $material['study_minutes']; ?>m</small>
                            </div>
                            <div class="material-info">
                                <small>📅 Uploaded: <?php echo date('M d, Y H:i', strtotime($material['created_at'])); ?></small>
                            </div>
                            
                            <?php if (!$is_expired): ?>
                                <div class="material-info" style="margin-top: 0.5rem;">
                                    <span class="timer">⏳ Quiz starts in: <?php echo $hours; ?>h <?php echo $minutes; ?>m</span>
                                </div>
                            <?php else: ?>
                                <div class="material-info" style="margin-top: 0.5rem; color: #999;">
                                    <span>✓ Study period ended - Quiz is now active</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="uploads/<?php echo htmlspecialchars($material['filename']); ?>" download class="btn" style="margin-left: 1rem;">Download</a>
                            <a href="delete_material.php?id=<?php echo htmlspecialchars($material['_id']); ?>" class="btn btn-danger" style="margin-left: 0.5rem; background: linear-gradient(135deg, #fa7921 0%, #f5576c 100%);" onclick="return confirm('Are you sure you want to delete this material? This action cannot be undone and students will no longer have access to it.');">Delete</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById('document').addEventListener('change', function(e) {
            const filename = e.target.files[0]?.name || 'No file chosen';
            document.getElementById('filename-display').textContent = filename;
        });
    </script>
</body>
</html>
