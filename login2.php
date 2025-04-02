<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Set the response type to JSON
header('Content-Type: application/json');

// RabbitMQ connection
$connection = new AMQPStreamConnection('rabbitmq', 5672, 'myuser', 'mypassword');
$channel = $connection->channel();
$channel->queue_declare('login', false, true, false, false);

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the username and password from the POST request
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare the data to send to RabbitMQ
    $data = json_encode([
        'username' => $username,
        'password' => $password
    ]);

    // Create a persistent message
    $msg = new AMQPMessage($data, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

    // Publish the message to the 'login' queue
    $channel->basic_publish($msg, '', 'login');

    // Return a success response
    echo json_encode(['message' => 'Login request sent!']);
} else {
    // Return an error response for non-POST requests
    echo json_encode(['message' => 'Invalid request method.']);
}

// Close the RabbitMQ connection
$channel->close();
$connection->close();
