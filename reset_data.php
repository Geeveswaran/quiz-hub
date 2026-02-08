<?php
/**
 * Data Reset Script - Clears collections before college feature implementation
 * This script removes all users, questions, quizzes, and results
 */

require_once 'config/mongodb.php';

$db = getDatabase();

echo "🔄 Starting data reset...\n\n";

try {
    // Delete all users
    $usersResult = $db->users->deleteMany([]);
    $deletedUsers = $usersResult->getDeletedCount();
    echo "✅ Users deleted: " . $deletedUsers . " documents removed\n";
    
    // Delete all questions
    $questionsResult = $db->questions->deleteMany([]);
    $deletedQuestions = $questionsResult->getDeletedCount();
    echo "✅ Questions deleted: " . $deletedQuestions . " documents removed\n";
    
    // Delete all quizzes
    $quizzesResult = $db->quizzes->deleteMany([]);
    $deletedQuizzes = $quizzesResult->getDeletedCount();
    echo "✅ Quizzes deleted: " . $deletedQuizzes . " documents removed\n";
    
    // Delete all results
    $resultsResult = $db->results->deleteMany([]);
    $deletedResults = $resultsResult->getDeletedCount();
    echo "✅ Results deleted: " . $deletedResults . " documents removed\n";
    
    echo "\n✨ Data reset completed successfully!\n";
    echo "📌 You can now register new users with college selection.\n";
    
} catch (Exception $e) {
    echo "❌ Error during reset: " . $e->getMessage() . "\n";
    exit(1);
}
?>
