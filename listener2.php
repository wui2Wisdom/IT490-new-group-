<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Database connection
$host = '172.30.105.20';
$dbname = 'IT490';
$user = 'IT490';
$pass = 'password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// RabbitMQ connection
$connection = new AMQPStreamConnection('172.30.105.10', 5672, 'guest', 'guest');
$channel = $connection->channel();

// Declare queues
$channel->queue_declare('registration', false, true, false, false);
$channel->queue_declare('login', false, true, false, false);

echo "Waiting for messages...\n";

// Callback function to process messages
$callback = function ($msg) use ($pdo, $channel) {
    $data = json_decode($msg->body, true);
    $response = [];

    if ($msg->get('routing_key') === 'registration') {
        // Handle registration
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)");
            $stmt->execute([
                'username' => $data['username'],
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT)
            ]);
            $response = ['success' => true, 'message' => "User registered: " . $data['username']];
        } catch (PDOException $e) {
            $response = ['success' => false, 'message' => "Registration failed: " . $e->getMessage()];
        }
    } elseif ($msg->get('routing_key') === 'login') {
        // Handle login
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $data['username']]);
        $user = $stmt->fetch();

        if ($user && password_verify($data['password'], $user['password_hash'])) {
            $sessionKey = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("INSERT INTO sessions (user_id, session_key, expires_at) VALUES (:user_id, :session_key, :expires_at)");
            $stmt->execute([
                'user_id' => $user['id'],
                'session_key' => $sessionKey,
                'expires_at' => (new DateTime('+1 hour'))->format('Y-m-d H:i:s')
            ]);
            $response = ['success' => true, 'session_key' => $sessionKey];
        } else {
            $response = ['success' => false];
        }
    }

    // Send response back to the web server
    $replyMsg = new AMQPMessage(
        json_encode($response),
        ['correlation_id' => $msg->get('correlation_id')]
    );
    $channel->basic_publish($replyMsg, '', $msg->get('reply_to'));
    echo "Processed: " . json_encode($response) . "\n";
};

// Consume messages
$channel->basic_consume('registration', '', false, true, false, false, $callback);
$channel->basic_consume('login', '', false, true, false, false, $callback);

// Keep the script running
while ($channel->is_consuming()) {
    $channel->wait();
}

// Close connections
$channel->close();
$connection->close();
?>
