<?php
// save_highscore.php

// Database configuration
$host = 'localhost';
$dbname = 'router_game';
$username = 'root';
$password = '';

// Create connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $e->getMessage()]);
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
