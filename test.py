<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

// Database connection
$host = 'localhost';
$dbname = 'IT490';
$user = 'root';
$pass = ''; // Replace with your MySQL root password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// RabbitMQ connection
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

// Declare queues
$channel->queue_declare('registration', false, true, false, false);
$channel->queue_declare('login', false, true, false, false);

echo "Waiting for messages...\n";

// Callback function to process messages
$callback = function ($msg) use ($pdo) {
    $data = json_decode($msg->body, true);

    if ($msg->get('routing_key') === 'registration') {
        // Handle registration
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)");
        $stmt->execute([
            'username' => $data['username'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT)
        ]);
        echo "User registered: " . $data['username'] . "\n";
    } elseif ($msg->get('routing_key') === 'login') {
        // Handle login
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $data['username']]);
        $user = $stmt->fetch();

        if ($user && password_verify($data['password'], $user['password_hash'])) {
            // Insert session into the `sessions` table
            $sessionKey = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("INSERT INTO sessions (user_id, session_key, expires_at) VALUES (:user_id, :session_key, :expires_at)");
            $stmt->execute([
                'user_id' => $user['id'],
                'session_key' => $sessionKey,
                'expires_at' => (new DateTime('+1 hour'))->format('Y-m-d H:i:s')
            ]);
            echo "Login successful: " . $data['username'] . "\n";
        } else {
            echo "Login failed: " . $data['username'] . "\n";
        }
    }
};

// Consume messages from the queues
$channel->basic_consume('registration', '', false, true, false, false, $callback);
$channel->basic_consume('login', '', false, true, false, false, $callback);

// Keep the script running
while ($channel->is_consuming()) {
    $channel->wait();
}

// Close connections
$channel->close();
$connection->close();
