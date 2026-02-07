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

// MongoDB REST API Connection Class
class MongoDBConnection {
    private $uri;
    private $dataApiUrl = 'https://data.mongodb-api.com/app/data-abcde/endpoint/data/v1';
    private $apiKey;
    private $database = 'quiz_system';
    
    public function __construct() {
        $this->uri = getenv('MONGODB_URI');
        if (!$this->uri) {
            die("Error: MONGODB_URI environment variable not set.");
        }
        
        // For local MongoDB, use a simpler approach
        $this->useLocalMongoDB();
    }
    
    private function useLocalMongoDB() {
        // This will use a local MongoDB or proxy through Node.js
    }
    
    public function users() {
        return new MongoDBCollection($this, 'users');
    }
}

class MongoDBCollection {
    private $connection;
    private $collectionName;
    
    public function __construct($connection, $collectionName) {
        $this->connection = $connection;
        $this->collectionName = $collectionName;
    }
    
    public function insertOne($document) {
        // Use simple file-based storage for now or HTTP endpoint
        return new InsertOneResult(true, 1);
    }
    
    public function findOne($filter) {
        // Query implementation
        return null;
    }
}

class InsertOneResult {
    private $success;
    private $count;
    
    public function __construct($success, $count) {
        $this->success = $success;
        $this->count = $count;
    }
    
    public function getInsertedCount() {
        return $this->count;
    }
}

// For now, use a file-based database wrapper
class FileBasedDB {
    private $dataDir;
    public $users;
    public $questions;
    public $results;
    public $quizzes;
    
    public function __construct() {
        $this->dataDir = __DIR__ . '/../data';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
        $this->users = new FileBasedCollection($this->dataDir . '/users.json');
        $this->questions = new FileBasedCollection($this->dataDir . '/questions.json');
        $this->results = new FileBasedCollection($this->dataDir . '/results.json');
        $this->quizzes = new FileBasedCollection($this->dataDir . '/quizzes.json');
    }
}

class FileBasedCollection {
    private $filepath;
    private $data;
    
    public function __construct($filepath) {
        $this->filepath = $filepath;
        $this->loadData();
    }
    
    private function loadData() {
        if (file_exists($this->filepath)) {
            $this->data = json_decode(file_get_contents($this->filepath), true) ?: [];
        } else {
            $this->data = [];
        }
    }
    
    private function saveData() {
        file_put_contents($this->filepath, json_encode($this->data, JSON_PRETTY_PRINT));
    }
    
    public function findOne($filter) {
        foreach ($this->data as $doc) {
            $matches = true;
            foreach ($filter as $key => $value) {
                if (!isset($doc[$key]) || $doc[$key] != $value) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                return $doc;
            }
        }
        return null;
    }
    
    public function insertOne($document) {
        $document['_id'] = uniqid();
        $document['created_at'] = date('Y-m-d H:i:s');
        $this->data[] = $document;
        $this->saveData();
        
        return new class {
            public function getInsertedCount() {
                return 1;
            }
        };
    }
    
    public function find($filter = [], $options = []) {
        $results = [];
        
        // Filter documents
        foreach ($this->data as $doc) {
            $matches = true;
            foreach ($filter as $key => $value) {
                if (!isset($doc[$key]) || $doc[$key] != $value) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
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
        $count = 0;
        foreach ($this->data as $doc) {
            $matches = true;
            foreach ($filter as $key => $value) {
                if (!isset($doc[$key]) || $doc[$key] != $value) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                $count++;
            }
        }
        return $count;
    }
    
    public function updateMany($filter, $update) {
        $updated = 0;
        foreach ($this->data as &$doc) {
            $matches = true;
            foreach ($filter as $key => $value) {
                if (!isset($doc[$key]) || $doc[$key] != $value) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                foreach ($update as $key => $value) {
                    $doc[$key] = $value;
                }
                $updated++;
            }
        }
        $this->saveData();
        return new class($updated) {
            private $count;
            public function __construct($count) {
                $this->count = $count;
            }
            public function getModifiedCount() {
                return $this->count;
            }
        };
    }
}

// Use file-based database as temporary solution
function getDatabase() {
    return new FileBasedDB();
}
?>
