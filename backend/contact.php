<?php
/**
 * Smart Library Management System - Contact Form Controller
 * Saves incoming contact queries into data/messages.json.
 */

require_once __DIR__ . '/db.php';

$action = $_REQUEST['action'] ?? 'submit';

if ($action === 'submit') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? 'General Inquiry');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        json_response(false, 'Please fill in all required fields.', null, 400);
    }

    $messages = read_json('messages.json');
    $newId = count($messages) > 0 ? max(array_column($messages, 'id')) + 1 : 1;

    $newMessage = [
        'id' => $newId,
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
        'created_at' => date('Y-m-d'),
        'status' => 'Unread'
    ];

    $messages[] = $newMessage;
    write_json('messages.json', $messages);

    json_response(true, 'Your message has been sent successfully. The library team will respond soon.', $newMessage);
} elseif ($action === 'list') {
    $messages = read_json('messages.json');
    json_response(true, 'Contact messages loaded.', $messages);
} else {
    json_response(false, 'Invalid action specified.', null, 400);
}
?>
