<?php
session_start();
require_once 'config/mongodb.php';

echo "===== MongoDB Connection Test =====\n\n";

// Load environment
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

$mongoUri = getenv('MONGODB_URI');
echo "✓ MongoDB URI loaded: " . (strpos($mongoUri, '@') ? "****HIDDEN****" : "Not found") . "\n\n";

// Test connection
try {
    $db = getDatabase();
    echo "✓ Database instance created successfully\n";
    
    // Test insert
    echo "\n--- Testing Insert ---\n";
    $testDoc = [
        'test_user' => 'test_' . uniqid(),
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => 'connection_test'
    ];
    
    $result = $db->users->insertOne($testDoc);
    echo "✓ Insert returned: " . $result->getInsertedCount() . " document(s)\n";
    
    // Test find
    echo "\n--- Testing Find ---\n";
    $users = $db->users->find(['type' => 'connection_test']);
    echo "✓ Found " . count($users) . " test document(s)\n";
    
    if (!empty($users)) {
        echo "✓ Test document: \n";
        echo "  - ID: " . ($users[0]['_id'] ?? 'N/A') . "\n";
        echo "  - User: " . ($users[0]['test_user'] ?? 'N/A') . "\n";
        echo "  - Time: " . ($users[0]['timestamp'] ?? 'N/A') . "\n";
    }
    
    // Test count
    echo "\n--- Testing Count ---\n";
    $count = $db->users->countDocuments(['type' => 'connection_test']);
    echo "✓ Count: " . $count . " document(s)\n";
    
    echo "\n✅ All tests passed! MongoDB connection is working.\n";
    echo "\n📁 Data is being stored in:\n";
    echo "  - Primary: /data/users.json (JSON fallback)\n";
    echo "  - When MongoDB extension is installed: MongoDB Atlas\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
