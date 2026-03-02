<?php
// save_highscore.php

// Database configuration
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'router_game';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

// Create connection
try {
    // Determine database parameters, checking $_ENV, $_SERVER and getenv()
    $host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? getenv('DB_HOST');
    if (!$host) $host = '127.0.0.1'; // Use IP to force TCP over sockets which fails on Windows sometimes

    $dbname = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? getenv('DB_NAME');
    if (!$dbname) $dbname = 'router_game';

    $username = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? getenv('DB_USER');
    if (!$username) $username = 'root';

    $password = $_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'] ?? getenv('DB_PASSWORD');
    if (!$password) $password = '';

    // First connect WITHOUT database to create it if it doesn't exist
    $pdoInit = new PDO("mysql:host=$host", $username, $password);
    $pdoInit->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database and select it
    $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdoInit->exec("USE `$dbname`");
    
    // Create the highscore table if it doesn't exist
    $pdoInit->exec("CREATE TABLE IF NOT EXISTS highscores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        player_name VARCHAR(50) NOT NULL,
        score INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Reassign PDO instance pointing to the newly guaranteed DB
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Connection or Setup failed: ' . $e->getMessage()]);
    exit;
}

// Handle POST request to save highscore
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['player_name']) && isset($data['score'])) {
        $stmt = $pdo->prepare("INSERT INTO highscores (player_name, score) VALUES (:player_name, :score)");
        $stmt->bindParam(':player_name', $data['player_name']);
        $stmt->bindParam(':score', $data['score']);

        try {
            $stmt->execute();
            echo json_encode(['status' => 'success', 'message' => 'Highscore saved successfully']);
        } catch(PDOException $e) {
             http_response_code(500);
             header('Content-Type: application/json');
             echo json_encode(['status' => 'error', 'message' => 'Error saving highscore: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    }
    exit;
}

// Handle GET request to fetch top highscores
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Fetch top 10 highscores
        $stmt = $pdo->query("SELECT player_name, score FROM highscores ORDER BY score DESC LIMIT 10");
        $highscores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($highscores);
    } catch(PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Error fetching highscores: ' . $e->getMessage()]);
    }
    exit;
}
?>
