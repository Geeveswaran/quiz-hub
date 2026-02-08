<?php
/**
 * Quiz Cleanup Script - Handles expired quizzes
 * - Marks non-attending students
 * - Deletes expired quizzes
 * This script should be called periodically (via cron or manual trigger)
 */

require_once 'config/mongodb.php';

$db = getDatabase();
$now = date('Y-m-d H:i:s');

echo "🔄 Starting quiz cleanup process...\n\n";

try {
    // Find all expired quizzes
    $expired_quizzes = $db->quizzes->find(['status' => 'published']);
    $deleted_count = 0;
    $marked_not_attended = 0;

    foreach ($expired_quizzes as $quiz) {
        $due_datetime = $quiz['due_datetime'] ?? '';
        
        if (empty($due_datetime)) continue;
        
        $due_timestamp = strtotime($due_datetime);
        $now_timestamp = strtotime($now);
        
        // Check if quiz is expired
        if ($now_timestamp > $due_timestamp) {
            echo "⏰ Processing expired quiz: {$quiz['quiz_title']}\n";
            
            $college = $quiz['college'] ?? '';
            $batch = $quiz['batch'] ?? '';
            $quiz_title = $quiz['quiz_title'] ?? '';
            
            // Get all students from this college and batch
            $all_students = $db->users->find(['role' => 'student', 'college' => $college, 'batch' => $batch]);
            
            foreach ($all_students as $student) {
                // Check if student attended this quiz
                $student_result = $db->results->findOne([
                    'username' => $student['username'],
                    'quiz_title' => $quiz_title
                ]);
                
                // If no result, mark as not attended
                if (!$student_result) {
                    $db->results->insertOne([
                        'user_id' => $student['_id'] ?? uniqid(),
                        'username' => $student['username'],
                        'quiz_title' => $quiz_title,
                        'college' => $college,
                        'college_name' => $quiz['college_name'] ?? '',
                        'batch' => $batch,
                        'score' => 0,
                        'total' => $quiz['question_count'] ?? 0,
                        'attempted_questions' => [],
                        'status' => 'not_attended',
                        'cheated' => false,
                        'date' => date('Y-m-d H:i:s')
                    ]);
                    $marked_not_attended++;
                }
            }
            
            // Delete the expired quiz
            $db->quizzes->deleteMany(['_id' => $quiz['_id']]);
            
            // Delete associated questions
            $db->questions->deleteMany(['quiz_title' => $quiz_title, 'status' => 'published']);
            
            echo "   ✓ Marked {$marked_not_attended} students as not attended\n";
            echo "   ✓ Deleted quiz and questions\n\n";
            $deleted_count++;
            $marked_not_attended = 0;
        }
    }
    
    echo "✨ Cleanup complete!\n";
    echo "📊 Results: $deleted_count Quiz(zes) expired and processed\n";
    
} catch (Exception $e) {
    echo "❌ Error during cleanup: " . $e->getMessage() . "\n";
    exit(1);
}
?>
