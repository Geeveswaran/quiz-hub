<?php
require_once __DIR__ . '/../vendor/autoload.php';

function getDatabase() {
    $uri = getenv('MONGODB_URI');
    if (!$uri) {
        die("Error: MONGODB_URI environment variable not set.");
    }

    try {
        $client = new MongoDB\Client($uri);
        // Using a fixed database name 'quiz_system' or parsing from URI if needed
        return $client->selectDatabase('quiz_system');
    } catch (Exception $e) {
        die("Error connecting to MongoDB: " . $e->getMessage());
    }
}
?>
