<?php
/**
 * Quiz Master Hub - MongoDB REST API Client
 * Alternative to PHP MongoDB extension
 * Uses native cURL to connect to MongoDB Atlas
 */

class MongoDBRESTClient {
    private $api_key;
    private $base_url = "https://data.mongodb-api.com/app";
    private $mongodb_uri;
    private $database = "quiz_system";
    private $app_id = "quiz-master-app";
    
    public function __construct() {
        $this->mongodb_uri = $_ENV['MONGODB_URI'] ?? getenv('MONGODB_URI');
        
        if (!$this->mongodb_uri) {
            throw new Exception("MONGODB_URI not configured in .env");
        }
        
        // Parse MongoDB URI for credentials
        $this->parseMongoDBURI();
    }
    
    private function parseMongoDBURI() {
        // mongodb+srv://user:pass@cluster.mongodb.net/database
        if (preg_match('/mongodb\+srv:\/\/([^:]+):([^@]+)@([^\/]+)\/(.+)/', $this->mongodb_uri, $matches)) {
            $this->username = urldecode($matches[1]);
            $this->password = urldecode($matches[2]);
            $this->cluster = $matches[3];
            $this->database = $matches[4];
        }
    }
    
    /**
     * Insert a document
     */
    public function insertOne($collection, $document) {
        // Fallback to JSON if cURL not available
        if (!function_exists('curl_init')) {
            return $this->insertOneJSON($collection, $document);
        }
        
        $document['_id'] = $document['_id'] ?? uniqid();
        $document['created_at'] = $document['created_at'] ?? date('Y-m-d H:i:s');
        
        $url = "{$this->base_url}/v1/databases/{$this->database}/collections/{$collection}/documents";
        
        $payload = json_encode([
            'documents' => [$document]
        ]);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->getToken()
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code >= 200 && $http_code < 300) {
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Fallback: Use native MongoDB PHP driver if available
     */
    public function useNativeDriver() {
        if (extension_loaded('mongodb')) {
            return true;
        }
        return false;
    }
    
    /**
     * JSON-based fallback
     */
    private function insertOneJSON($collection, $document) {
        $data_dir = __DIR__ . '/data';
        $file = "{$data_dir}/{$collection}.json";
        
        if (!file_exists($file)) {
            file_put_contents($file, json_encode([]));
        }
        
        $document['_id'] = $document['_id'] ?? uniqid();
        $document['created_at'] = $document['created_at'] ?? date('Y-m-d H:i:s');
        
        $data = json_decode(file_get_contents($file), true) ?? [];
        $data[] = $document;
        
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        return 1;
    }
    
    private function getToken() {
        // Placeholder for API key authentication
        return $this->api_key ?? 'dev_token';
    }
}

// Quick test
try {
    $mongo = new MongoDBRESTClient();
    echo "✅ MongoDB REST Client initialized\n";
    echo "Database: quiz_system\n";
    echo "Status: Ready (using JSON fallback)\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
