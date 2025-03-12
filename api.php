<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

if ($method == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// GET (Leggi messaggi)
if ($method == 'GET') {
    $stmt = $pdo->query("
        SELECT m.id, m.messageText AS message, m.messageDate AS timestamp, u.name AS user
        FROM messages m JOIN users u ON m.user_id = u.id
        ORDER BY m.messageDate DESC
    ");
    echo json_encode(["success" => true, "messages" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit();
}

// POST (Inserisci messaggio)
if ($method == 'POST') {
    if (!isset($data['messageText'], $data['user_id'], $data['ticket_id'])) {
        echo json_encode(["success" => false, "message" => "Dati incompleti"]);
        exit();
    }
    $stmt = $pdo->prepare("INSERT INTO messages (messageText, messageDate, user_id, ticket_id) VALUES (:messageText, NOW(), :user_id, :ticket_id)");
    $stmt->execute([
        ':messageText' => $data['messageText'],
        ':user_id' => $data['user_id'],
        ':ticket_id' => $data['ticket_id']
    ]);
    echo json_encode(["success" => true]);
    exit();
}

// PUT (Modifica messaggio)
if ($method == 'PUT') {
    if (!isset($data['id'], $data['messageText'])) {
        echo json_encode(["success" => false, "message" => "Dati incompleti"]);
        exit();
    }
    $stmt = $pdo->prepare("UPDATE messages SET messageText = :messageText WHERE id = :id");
    $stmt->execute([':messageText' => $data['messageText'], ':id' => $data['id']]);
    echo json_encode(["success" => true]);
    exit();
}

// DELETE (Elimina messaggio)
if ($method == 'DELETE') {
    if (!isset($data['id'])) {
        echo json_encode(["success" => false, "message" => "Dati incompleti"]);
        exit();
    }
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = :id");
    $stmt->execute([':id' => $data['id']]);
    echo json_encode(["success" => true]);
    exit();
}
