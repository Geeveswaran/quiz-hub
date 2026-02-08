<?php
/**
 * Reset Script for Batch Feature Implementation
 * Deletes all old data and prepares for fresh start with batch feature
 * - Deletes all users
 * - Deletes all quizzes
 * - Deletes all questions
 * - Deletes all results
 * - Deletes all study materials
 */

require_once 'config/mongodb.php';

$db = getDatabase();

echo "🧹 RESETTING DATA FOR BATCH FEATURE\n";
echo "===================================\n\n";

try {
    // Delete all users
    $user_count = $db->users->countDocuments([]);
    $db->users->deleteMany([]);
    echo "✓ Deleted $user_count users\n";

    // Delete all quizzes
    $quiz_count = $db->quizzes->countDocuments([]);
    $db->quizzes->deleteMany([]);
    echo "✓ Deleted $quiz_count quizzes\n";

    // Delete all questions
    $question_count = $db->questions->countDocuments([]);
    $db->questions->deleteMany([]);
    echo "✓ Deleted $question_count questions\n";

    // Delete all results
    $result_count = $db->results->countDocuments([]);
    $db->results->deleteMany([]);
    echo "✓ Deleted $result_count results\n";

    // Delete all study materials
    $material_count = $db->study_materials->countDocuments([]);
    $db->study_materials->deleteMany([]);
    echo "✓ Deleted $material_count study materials\n";

    echo "\n✨ RESET COMPLETE - All old data cleared!\n";
    echo "===================================\n";
    echo "\n✅ Ready to test batch feature with fresh data!\n\n";
    echo "Next steps:\n";
    echo "1. Register a new user and select a batch (batch1-batch10)\n";
    echo "2. Create quizzes and questions for that batch\n";
    echo "3. Students in the same batch can see the quiz\n";
    echo "4. Students in different batches cannot see other batch quizzes\n";

} catch (Exception $e) {
    echo "❌ Error during reset: " . $e->getMessage() . "\n";
    exit(1);
}
?>
