<?php
header("Access-Control-Allow-Origin: *"); // Permetti richieste da qualsiasi dominio
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Accetta i metodi GET, POST, OPTIONS
header("Access-Control-Allow-Headers: Content-Type"); // Permetti header per JSON
header("Content-Type: application/json");

require_once "db.php";

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
$jsonInput = trim(file_get_contents("php://input")); // Rimuove spazi extra
$data = json_decode($jsonInput, true);

// ✅ DEBUG: Log dettagliato per capire cosa sta arrivando al backend
file_put_contents("debug_log.txt", "METODO: " . $_SERVER["REQUEST_METHOD"] . "\n", FILE_APPEND);
file_put_contents("debug_log.txt", "DATI GREZZI: " . $jsonInput . "\n", FILE_APPEND);
file_put_contents("debug_log.txt", "DECODED JSON: " . json_encode($data) . "\n", FILE_APPEND);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["success" => false, "message" => "Errore nella decodifica JSON", "jsonError" => json_last_error_msg(), "rawData" => $jsonInput]);
    exit;
}

$action = isset($data["action"]) ? trim($data["action"]) : '';

file_put_contents("debug_log.txt", "AZIONE RICEVUTA: '" . $action . "'\n", FILE_APPEND);
file_put_contents("debug_log.txt", "RAW JSON: " . json_encode($data) . "\n", FILE_APPEND);



// ✅ REGISTRAZIONE UTENTE ✅ 
if ($action === "register") {
    $email = $data["email"] ?? '';
    $password = $data["password"] ?? '';

    if (!$email || !$password) {
        echo json_encode(["success" => false, "message" => "Compila tutti i campi"]);
        exit;
    }

    // Controlla se l'email è già registrata
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Email già registrata"]);
        exit;
    }

    // ✅ Imposta il ruolo "Client" di default
    $role = "Client";

    // ✅ Inserisci l'utente con password NON criptata
    $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$email, $password, $role]);

    echo json_encode(["success" => true, "message" => "Registrazione completata!"]);
    exit;
}

// ✅ LOGIN UTENTE ✅  
if ($action === "login") {
    $email = $data["email"] ?? '';
    $password = $data["password"] ?? '';

    file_put_contents("debug_log.txt", "LOGIN: Email: " . $email . " - Password: " . $password . "\n", FILE_APPEND);

    if (!$email || !$password) {
        echo json_encode(["success" => false, "message" => "Compila tutti i campi"]);
        exit;
    }

    // Controllo credenziali
    $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

file_put_contents("debug_log.txt", "LOGIN VERIFICA: " . json_encode($user) . "\n", FILE_APPEND);

if (!$user) {
    file_put_contents("debug_log.txt", "LOGIN FALLITO: Utente non trovato\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "Utente non trovato"]);
    exit;
}

if ($user["password"] !== $password) {
    file_put_contents("debug_log.txt", "LOGIN FALLITO: Password errata - Password salvata: " . $user["password"] . "\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "Password errata"]);
    exit;
}


    if (!$user) {
        file_put_contents("debug_log.txt", "LOGIN FALLITO: Utente non trovato\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Utente non trovato"]);
        exit;
    }

    if ($user["password"] !== $password) {
        file_put_contents("debug_log.txt", "LOGIN FALLITO: Password errata\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Password errata"]);
        exit;
    }

    file_put_contents("debug_log.txt", "LOGIN RIUSCITO: " . json_encode($user) . "\n", FILE_APPEND);

    echo json_encode([
        "success" => true,
        "user" => [
            "id" => $user["id"],
            "email" => $email,
            "role" => $user["role"]
        ]
    ]);
    exit;
}

// ✅ OTTENERE I TICKET DELL'UTENTE LOGGATO ✅ 
if ($action === "getTickets") {
    $user_id = $data["user_id"] ?? null;

    if (!$user_id) {
        echo json_encode(["success" => false, "message" => "Utente non autenticato"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT t.*, 
           u.name AS admin_name 
    FROM tickets t
    LEFT JOIN users u ON t.admin_id = u.id
    WHERE t.client_id = ?
");

    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "tickets" => $tickets]);
    exit;
}

// ✅ CREAZIONE NUOVA SEGNALAZIONE (TICKET) ✅
if ($action === "createTicket") {
    $user_id = $data["user_id"] ?? null;
    $description = $data["description"] ?? '';

    if (!$user_id || !$description) {
        echo json_encode(["success" => false, "message" => "Dati mancanti"]);
        exit;
    }

    // ✅ Salva la segnalazione nel database
    $ticketCat_id = $data["ticketCat_id"] ?? null;

    if (!$ticketCat_id) {
        echo json_encode(["success" => false, "message" => "Categoria ticket mancante"]);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO tickets (ticketDate, description, client_id, ticketCat_id, status) 
                           VALUES (NOW(), ?, ?, ?, 'Aperto')");
    $stmt->execute([$description, $user_id, $ticketCat_id]);
    

    echo json_encode(["success" => true, "message" => "Ticket creato con successo!"]);
    exit;
}

// ✅ ASSEGNA UN ADMIN A UN TICKET ✅
if ($action === "assignAdminToTicket") {
    $ticket_id = $data["ticket_id"] ?? null;
    $admin_id = $data["admin_id"] ?? null;

    if (!$ticket_id || !$admin_id) {
        echo json_encode(["success" => false, "message" => "Dati mancanti"]);
        exit;
    }

    // ✅ Controlliamo se l'admin esiste e ha il ruolo corretto
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'Admin'");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo json_encode(["success" => false, "message" => "L'utente selezionato non è un admin"]);
        exit;
    }

    // ✅ Aggiorniamo il ticket con l'admin assegnato
    $stmt = $pdo->prepare("UPDATE tickets SET admin_id = ? WHERE id = ?");
    $stmt->execute([$admin_id, $ticket_id]);

    echo json_encode(["success" => true, "message" => "Admin assegnato con successo al ticket"]);
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
