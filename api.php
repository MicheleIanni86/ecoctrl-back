<?php
header("Access-Control-Allow-Origin: *"); // Permetti richieste da qualsiasi dominio
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Accetta i metodi GET, POST, OPTIONS
header("Access-Control-Allow-Headers: Content-Type"); // Permetti header per JSON
header("Content-Type: application/json");

require_once "db.php"; // Assicura che il file di connessione sia incluso

// ✅ Rispondere alle richieste preflight CORS
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// ✅ Controllo che il metodo sia POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Metodo non consentito"]);
    exit;
}

// ✅ Ricezione dati JSON corretta
$jsonInput = trim(file_get_contents("php://input"));
$data = json_decode($jsonInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["success" => false, "message" => "Errore nella decodifica JSON", "jsonError" => json_last_error_msg()]);
    exit;
}

$action = isset($data["action"]) ? trim($data["action"]) : '';

if (!$action) {
    echo json_encode(["success" => false, "message" => "Azione non valida"]);
    exit;
}

// ✅ DEBUG: Log dettagliato
file_put_contents("debug_log.txt", "Azione ricevuta: $action\n", FILE_APPEND);

// ✅ REGISTRAZIONE UTENTE ✅
if ($action === "register") {
    $name = trim($data["name"] ?? '');
    $surname = trim($data["surname"] ?? '');
    $email = trim($data["email"] ?? '');
    $password = trim($data["password"] ?? '');

    if (!$name || !$surname || !$email || !$password) {
        echo json_encode(["success" => false, "message" => "Compila tutti i campi"]);
        exit;
    }

    // ✅ Controlla se l'email è già registrata
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Email già registrata"]);
        exit;
    }

    // ✅ Inserisci l'utente nel database con nome e cognome
    $role = "Client"; // Ruolo di default
    $stmt = $pdo->prepare("INSERT INTO users (name, surname, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $surname, $email, $password, $role]);

    echo json_encode(["success" => true, "message" => "Registrazione completata!"]);
    exit;
}

// ✅ LOGIN UTENTE ✅
if ($action === "login") {
    $email = trim($data["email"] ?? '');
    $password = trim($data["password"] ?? '');

    if (!$email || !$password) {
        echo json_encode(["success" => false, "message" => "Compila tutti i campi"]);
        exit;
    }

    // Controllo credenziali
    $stmt = $pdo->prepare("SELECT id, name, surname, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "Utente non trovato"]);
        exit;
    }

    if ($user["password"] !== $password) {
        echo json_encode(["success" => false, "message" => "Password errata"]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "user" => [
            "id" => $user["id"],
            "name" => $user["name"],
            "surname" => $user["surname"],
            "email" => $email,
            "role" => $user["role"]
        ]
    ]);
    exit;
}

// ✅ SE L'AZIONE NON È RICONOSCIUTA
echo json_encode([
    "success" => false,
    "message" => "Azione non valida",
    "received_action" => $action
]);
exit;
?>
