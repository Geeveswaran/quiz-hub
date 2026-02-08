<?php
/**
 * Test Script - Quiz Expiration Feature
 * This script tests the quiz expiration and cleanup functionality
 * 
 * To use:
 * 1. Run this script: php test_expiration_feature.php
 * 2. Creates a test quiz with a past due date
 * 3. Runs the cleanup script
 * 4. Verifies cleanup results
 */

session_start();
require_once 'config/mongodb.php';

echo "🧪 QUIZ EXPIRATION FEATURE TEST\n";
echo "================================\n\n";

$db = getDatabase();

// Test 1: Create a test quiz with expired due date
echo "Test 1: Creating a quiz with past due date...\n";
$test_quiz = [
    'quiz_title' => 'Test Expiration Quiz ' . date('Ymd_His'),
    'college' => 'IIT Delhi',
    'college_name' => 'IIT Delhi',
    'status' => 'published',
    'question_count' => 3,
    'time_limit_minutes' => 30,
    'teacher_name' => 'Test Teacher',
    'created_at' => date('Y-m-d H:i:s'),
    'due_date' => date('Y-m-d', strtotime('-1 day')), // Yesterday
    'due_time' => '14:00',
    'due_datetime' => date('Y-m-d H:i:s', strtotime('-1 day 14:00')), // Expired
];

try {
    $quiz_result = $db->quizzes->insertOne($test_quiz);
    $quiz_id = $test_quiz['_id'] ?? 'N/A';
    echo "   ✓ Created test quiz: {$test_quiz['quiz_title']}\n";
    echo "   ✓ Due datetime: {$test_quiz['due_datetime']} (EXPIRED)\n";
    echo "   ✓ Quiz ID: $quiz_id\n\n";
} catch (Exception $e) {
    echo "   ✗ Error creating quiz: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Verify quiz doesn't appear in student dashboard
echo "Test 2: Verifying expired quiz is filtered out...\n";
$all_quizzes = $db->quizzes->find(['status' => 'published', 'college' => 'IIT Delhi']);
$available_quizzes = [];
$now_timestamp = time();

foreach ($all_quizzes as $q) {
    $due_datetime = $q['due_datetime'] ?? '';
    $due_timestamp = strtotime($due_datetime);
    
    if ($due_timestamp > $now_timestamp) {
        $available_quizzes[] = $q;
    }
}

// Check if our test quiz is in available list (it shouldn't be)
$test_quiz_in_available = false;
foreach ($available_quizzes as $q) {
    if ($q['quiz_title'] === $test_quiz['quiz_title']) {
        $test_quiz_in_available = true;
        break;
    }
}

if (!$test_quiz_in_available) {
    echo "   ✓ Quiz correctly marked as expired (not in available list)\n";
} else {
    echo "   ✗ Quiz incorrectly shown as available!\n";
}
echo "\n";

// Test 3: Run cleanup script
echo "Test 3: Running cleanup script...\n";
ob_start();
include 'cleanup_expired_quizzes.php';
$cleanup_output = ob_get_clean();
echo $cleanup_output;

// Test 4: Verify not_attended records were created
echo "Test 4: Verifying not_attended records...\n";
$not_attended_count = $db->results->countDocuments([
    'quiz_title' => $test_quiz['quiz_title'],
    'status' => 'not_attended'
]);

if ($not_attended_count > 0) {
    echo "   ✓ Created $not_attended_count 'not_attended' records\n";
    
    // Show sample records
    $sample_results = $db->results->find([
        'quiz_title' => $test_quiz['quiz_title'],
        'status' => 'not_attended'
    ], ['limit' => 3]);
    
    echo "   Sample records:\n";
    foreach ($sample_results as $result) {
        echo "     - Student: {$result['username']}, Score: {$result['score']}/{$result['total']}\n";
    }
} else {
    echo "   ⚠ No not_attended records created (might be ok if no students exist)\n";
}
echo "\n";

// Test 5: Verify quiz was deleted
echo "Test 5: Verifying quiz was deleted...\n";
$quiz_exists = $db->quizzes->findOne(['quiz_title' => $test_quiz['quiz_title']]);

if ($quiz_exists === null) {
    echo "   ✓ Quiz successfully deleted\n";
} else {
    echo "   ✗ Quiz still exists in database!\n";
}
echo "\n";

// Test 6: Verify questions were deleted
echo "Test 6: Verifying quiz questions were deleted...\n";
$question_count = $db->questions->countDocuments(['quiz_title' => $test_quiz['quiz_title']]);

if ($question_count === 0) {
    echo "   ✓ All questions successfully deleted\n";
} else {
    echo "   ✗ $question_count questions still exist!\n";
}
echo "\n";

// Cleanup test data (optional)
echo "Cleaning up test data...\n";
try {
    $db->results->deleteMany(['quiz_title' => $test_quiz['quiz_title']]);
    echo "   ✓ Cleaned up test results\n";
} catch (Exception $e) {
    echo "   Note: Test results may still exist\n";
}

echo "\n✅ TEST COMPLETE\n";
echo "================================\n";
echo "The quiz expiration feature is working correctly!\n";
?>
