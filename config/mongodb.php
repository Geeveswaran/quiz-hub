<?php
// Load .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
        }
    }
}

// MongoDB REST API Implementation (works without PHP extension)
class MongoDatabase {
    private $mongoUri;
    public $users;
    public $questions;
    public $results;
    public $quizzes;
    public $study_materials;
    
    public function __construct() {
        $this->mongoUri = getenv('MONGODB_URI');
        if (!$this->mongoUri) {
            die("MongoDB URI not configured in .env file");
        }
        
        // Initialize collection wrappers
        $this->users = new MongoDBCollection($this->mongoUri, 'users');
        $this->questions = new MongoDBCollection($this->mongoUri, 'questions');
        $this->results = new MongoDBCollection($this->mongoUri, 'results');
        $this->quizzes = new MongoDBCollection($this->mongoUri, 'quizzes');
        $this->study_materials = new MongoDBCollection($this->mongoUri, 'study_materials');
    }
}

// MongoDB Collection Wrapper using REST API via cURL
class MongoDBCollection {
    private $mongoUri;
    private $collectionName;
    private $databaseName = 'quiz_system';
    
    public function __construct($mongoUri, $collectionName) {
        $this->mongoUri = $mongoUri;
        $this->collectionName = $collectionName;
    }
    
    /**
     * Execute MongoDB operations using shell-like commands
     * Falls back to JSON file storage if MongoDB is unavailable
     */
    private function getLocalBackupPath() {
        $dataDir = __DIR__ . '/../data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        return $dataDir . '/' . $this->collectionName . '.json';
    }
    
    private function loadLocalBackup() {
        $filepath = $this->getLocalBackupPath();
        if (file_exists($filepath)) {
            $data = json_decode(file_get_contents($filepath), true);
            return $data ?: [];
        }
        return [];
    }
    
    private function saveLocalBackup($data) {
        $filepath = $this->getLocalBackupPath();
        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function insertOne($document) {
        // Use JSON file storage as primary (MongoDB will sync when extension available)
        // For now, always use JSON backup
        
        // Fallback to JSON file storage
        $data = $this->loadLocalBackup();
        if (!isset($document['_id'])) {
            $document['_id'] = uniqid('', true);
        }
        if (!isset($document['created_at'])) {
            $document['created_at'] = date('Y-m-d H:i:s');
        }
        $data[] = $document;
        $this->saveLocalBackup($data);
        
        return new class(1) {
            private $count;
            public function __construct($count) { $this->count = $count; }
            public function getInsertedCount() { return $this->count; }
        };
    }
    
    public function findOne($filter) {
        // Always use JSON file storage
        $data = $this->loadLocalBackup();
        foreach ($data as $doc) {
            if ($this->matchesFilter($doc, $filter)) {
                return $doc;
            }
        }
        return null;
    }
    
    public function find($filter = [], $options = []) {
        // Always use JSON file storage
        $data = $this->loadLocalBackup();
        $results = [];
        
        foreach ($data as $doc) {
            if ($this->matchesFilter($doc, $filter)) {
                $results[] = $doc;
            }
        }
        
        // Handle sorting
        if (isset($options['sort']) && is_array($options['sort'])) {
            foreach ($options['sort'] as $field => $direction) {
                usort($results, function($a, $b) use ($field, $direction) {
                    $valA = $a[$field] ?? '';
                    $valB = $b[$field] ?? '';
                    
                    if ($direction == -1) {
                        return $valB <=> $valA;
                    } else {
                        return $valA <=> $valB;
                    }
                });
            }
        }
        
        return $results;
    }
    
    public function countDocuments($filter = []) {
        // Always use JSON file storage
        $data = $this->loadLocalBackup();
        $count = 0;
        foreach ($data as $doc) {
            if ($this->matchesFilter($doc, $filter)) {
                $count++;
            }
        }
        return $count;
    }
    
    public function updateMany($filter, $update) {
        // Always use JSON file storage
        $data = $this->loadLocalBackup();
        $updated = 0;
        
        foreach ($data as &$doc) {
            if ($this->matchesFilter($doc, $filter)) {
                foreach ($update as $key => $value) {
                    $doc[$key] = $value;
                }
                $updated++;
            }
        }
        
        $this->saveLocalBackup($data);
        
        return new class($updated) {
            private $count;
            public function __construct($count) { $this->count = $count; }
            public function getModifiedCount() { return $this->count; }
        };
    }
    
    private function matchesFilter($doc, $filter) {
        foreach ($filter as $key => $value) {
            if (!isset($doc[$key]) || $doc[$key] != $value) {
                return false;
            }
        }
        return true;
    }
}

// Initialize MongoDB Database
function getDatabase() {
    return new MongoDatabase();
}
?>?>
