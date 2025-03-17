<?php
session_start();
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type"); 
header("Content-Type: application/json");

require_once "db.php"; 

// ✅ Debug: Verifica connessione database
file_put_contents("debug_log.txt", "✅ Connessione al database riuscita.\n", FILE_APPEND);

// ✅ Ricezione metodo della richiesta
$method = $_SERVER["REQUEST_METHOD"];

// ✅ Ricezione dati JSON
$jsonInput = trim(file_get_contents("php://input"));
$data = json_decode($jsonInput, true);
if (!is_array($data)) {
    $data = [];
}

// ✅ Recupera l'azione dall'input JSON o dai parametri GET
$action = isset($data["action"]) ? $data["action"] : (isset($_GET["action"]) ? $_GET["action"] : "");

// ✅ Debug: Scrive il metodo e l'azione ricevuta
file_put_contents("debug_log.txt", "📌 Metodo: $method | Azione ricevuta: " . json_encode($action) . "\n", FILE_APPEND);


// ✅ REGISTRAZIONE UTENTE (POST)
if ($method === "POST" && $action === "register") {
    file_put_contents("debug_log.txt", "📌 Tentativo di registrazione ricevuto.\n", FILE_APPEND);

    $name = trim($data["name"] ?? '');
    $surname = trim($data["surname"] ?? '');
    $email = trim($data["email"] ?? '');
    $password = trim($data["password"] ?? '');

    if (!$name || !$surname || !$email || !$password) {
        echo json_encode(["success" => false, "message" => "Compila tutti i campi"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(["success" => false, "message" => "Email già registrata"]);
            exit;
        }

        $role = "Client";
        $stmt = $pdo->prepare("INSERT INTO users (name, surname, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $surname, $email, $password, $role]);

        echo json_encode(["success" => true, "message" => "Registrazione completata!"]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Errore nel database"]);
        exit;
    }
}

// ✅ LOGIN UTENTE (POST)
if ($method === "POST" && $action === "login") {
    file_put_contents("debug_log.txt", "📌 Login ricevuto, elaborazione iniziata.\n", FILE_APPEND);

    $email = trim($data["email"] ?? '');
    $password = trim($data["password"] ?? '');

    if (!$email || !$password) {
        echo json_encode(["success" => false, "message" => "Compila tutti i campi"]);
        exit;
    }

    try {
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

        // ✅ Salva l'ID e il ruolo dell'utente nella sessione
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];


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
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Errore nel database"]);
        exit;
    }
}


// ✅ OTTENERE LISTA UTENTI
if ($method === "GET" && $action === "getUserTickets") {  
    file_put_contents("debug_log.txt", "📌 Chiamata a getUserTickets ricevuta per user_id: " . ($_GET["user_id"] ?? "null") . "\n", FILE_APPEND);

    $user_id = $_GET["user_id"] ?? null;

    if (!$user_id) {
        echo json_encode(["success" => false, "message" => "ID utente mancante"]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            SELECT t.id, t.description AS message, t.ticketDate AS timestamp, 
                   t.status,
                   IFNULL(a.name, 'Non Assegnato') AS admin
            FROM tickets t
            LEFT JOIN users a ON t.admin_id = a.id
            WHERE t.client_id = :user_id
            ORDER BY t.ticketDate DESC
        ");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "tickets" => $tickets], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;

    } catch (PDOException $e) {
        file_put_contents("debug_log.txt", "❌ Errore SQL: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Errore nella query"]);
        exit;
    }
}



// ✅ RECUPERA TUTTE LE CATEGORIE DI TICKET
if ($method === "GET" && $action === "get_ticket_categories") {
    file_put_contents("debug_log.txt", "📌 Richiesta get_ticket_categories ricevuta.\n", FILE_APPEND);
    
    try {
        $stmt = $pdo->query("SELECT id, name FROM ticketcategories");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$categories) {
            file_put_contents("debug_log.txt", "❌ Nessuna categoria trovata!\n", FILE_APPEND);
            echo json_encode(["success" => false, "message" => "Nessuna categoria trovata"]);
            exit();
        }

        file_put_contents("debug_log.txt", "✅ Categorie trovate: " . json_encode($categories) . "\n", FILE_APPEND);
        echo json_encode($categories);
        exit();
    } catch (PDOException $e) {
        file_put_contents("debug_log.txt", "❌ Errore SQL: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Errore nella query delle categorie"]);
        exit();
    }
}



// ✅ OTTENERE TUTTI I TICKET
if ($method === "GET" && $action === "getMessages") {
    if (ob_get_length()) ob_clean();

    file_put_contents("debug_log.txt", "📌 Chiamata a getMessages ricevuta!\n", FILE_APPEND);

    try {
        $stmt = $pdo->prepare("
            SELECT t.id, t.description AS message, t.ticketDate AS timestamp, 
                   u.name AS user, 
                   t.status,
                   IFNULL(a.name, 'Non Assegnato') AS admin
            FROM tickets t
            JOIN users u ON t.client_id = u.id
            LEFT JOIN users a ON t.admin_id = a.id
            ORDER BY t.ticketDate DESC
        ");
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "messages" => $messages], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;

    } catch (PDOException $e) {
        file_put_contents("debug_log.txt", "❌ Errore SQL: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Errore nella query"]);
        exit;
    }
}


// ✅ ASSEGNARE UN TICKET A UN ADMIN (SuperAdmin only)
if ($method === "POST" && $action === "assignTicketToAdmin") {
    file_put_contents("debug_log.txt", "📌 Assegnazione ticket ricevuta.\n", FILE_APPEND);

    $ticket_id = $data["ticket_id"] ?? null;
    $admin_id = $data["admin_id"] ?? null;

    if (!$ticket_id || !$admin_id) {
        echo json_encode(["success" => false, "message" => "Dati mancanti"]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE tickets SET admin_id = ?, status = 'In corso' WHERE id = ?");
        $stmt->execute([$admin_id, $ticket_id]);

        echo json_encode(["success" => true, "message" => "Ticket assegnato con successo!"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Errore nell'assegnazione del ticket"]);
    }
    exit();
}

// ✅ Ottenere la lista di tutti gli Admin
if ($method === "GET" && $action === "getAdmins") {
    file_put_contents("debug_log.txt", "📌 Richiesta lista Admin ricevuta.\n", FILE_APPEND);

    try {
        $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'Admin'");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "admins" => $admins], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        file_put_contents("debug_log.txt", "❌ Errore SQL: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Errore nella query"]);
    }
    exit();
}



// ✅ Ottenere tutti i ticket aperti (SuperAdmin)
if ($method === "GET" && $action === "getAllOpenTickets") {
    file_put_contents("debug_log.txt", "📌 Richiesta di tutti i ticket aperti ricevuta.\n", FILE_APPEND);

    try {
        $stmt = $pdo->query("
            SELECT t.id, t.description, t.status, u.name AS client_name 
            FROM tickets t
            JOIN users u ON t.client_id = u.id
            WHERE t.status = 'Aperto' AND t.admin_id IS NULL
            ORDER BY t.ticketDate DESC
        ");
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "tickets" => $tickets], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        file_put_contents("debug_log.txt", "❌ Errore SQL: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Errore nella query"]);
    }
    exit();
}


// ✅ PRENDERE IN CARICO UN TICKET (Admin only)
if ($method === "POST" && $action === "takeTicket") {
    file_put_contents("debug_log.txt", "📌 Prendere in carico ticket ricevuto.\n", FILE_APPEND);

    $ticket_id = $data["id"] ?? null;
    $admin_id = $_SESSION["user_id"] ?? null; // ✅ L'admin può prendere in carico solo per sé stesso

    if (!$ticket_id || !$admin_id) {
        echo json_encode(["success" => false, "message" => "Dati mancanti"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE tickets SET admin_id = ?, status = 'In corso' WHERE id = ?");
        $stmt->execute([$admin_id, $ticket_id]);

        echo json_encode(["success" => true, "message" => "Ticket preso in carico con successo!"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Errore nell'aggiornamento del ticket"]);
    }
    exit;
}

// ✅ RILASCIARE UN TICKET
if ($method === "POST" && $action === "releaseTicket") {
    file_put_contents("debug_log.txt", "📌 Rilascio ticket ricevuto.\n", FILE_APPEND);

    $ticket_id = $data["id"] ?? null;

    if (!$ticket_id) {
        echo json_encode(["success" => false, "message" => "ID ticket mancante"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE tickets SET admin_id = NULL, status = 'Aperto' WHERE id = ?");
        $stmt->execute([$ticket_id]);

        echo json_encode(["success" => true, "message" => "Ticket rilasciato con successo!"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Errore nel rilascio del ticket"]);
    }
    exit;
}

// ✅ CAMBIARE LO STATO DI UN TICKET
if ($method === "POST" && $action === "updateTicketStatus") {
    file_put_contents("debug_log.txt", "📌 Cambiare stato ticket ricevuto.\n", FILE_APPEND);

    $ticket_id = $data["id"] ?? null;
    $status = $data["status"] ?? null;

    if (!$ticket_id || !$status) {
        echo json_encode(["success" => false, "message" => "Dati mancanti"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        $stmt->execute([$status, $ticket_id]);

        echo json_encode(["success" => true, "message" => "Stato del ticket aggiornato con successo!"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Errore nell'aggiornamento del ticket"]);
    }
    exit;
}






// ✅ CREAZIONE NUOVA SEGNALAZIONE (TICKET)
if ($method === "POST" && $action === "createTicket") {
    file_put_contents("debug_log.txt", "📌 Creazione ticket ricevuta. Dati: " . json_encode($data) . "\n", FILE_APPEND);

    $user_id = $data["user_id"] ?? null;
    $description = trim($data["description"] ?? '');
    $category_id = $data["ticketCat_id"] ?? null;

    if (!$user_id || !$description || strlen($description) < 10 || !$category_id) {
        file_put_contents("debug_log.txt", "❌ Errore: Dati mancanti o descrizione troppo corta. User ID: $user_id, Desc: '$description', Cat ID: $category_id\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Dati mancanti o descrizione troppo corta"]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO tickets (ticketDate, description, client_id, ticketCat_id, status) 
                               VALUES (NOW(), ?, ?, ?, 'Aperto')");
        $stmt->execute([$description, $user_id, $category_id]);

        file_put_contents("debug_log.txt", "✅ Ticket creato con successo per utente $user_id\n", FILE_APPEND);
        echo json_encode(["success" => true, "message" => "Segnalazione inviata con successo!"]);
    } catch (PDOException $e) {
        file_put_contents("debug_log.txt", "❌ ERRORE SQL: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Errore nella creazione del ticket"]);
    }
    exit();
}



// ✅ MODIFICA TICKET
if ($method === "PUT" && $action === "updateTicket") {  
    file_put_contents("debug_log.txt", "📌 Modifica ticket ricevuta\n", FILE_APPEND);

    $jsonInput = file_get_contents("php://input");
    $data = json_decode($jsonInput, true);

    $ticket_id = $data["id"] ?? null;
    $new_text = trim($data["messageText"] ?? '');

    if (!$ticket_id || !$new_text) {
        echo json_encode(["success" => false, "message" => "Dati mancanti"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE tickets SET description = ? WHERE id = ?");
        $stmt->execute([$new_text, $ticket_id]);

        echo json_encode(["success" => true, "message" => "Ticket aggiornato con successo!"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Errore nell'aggiornamento del ticket"]);
    }
    exit;
}



// ✅ ELIMINA TICKET
if ($method === "DELETE" && $action === "deleteTicket") {  // 
    file_put_contents("debug_log.txt", "📌 Eliminazione ticket ricevuta\n", FILE_APPEND);

    $jsonInput = file_get_contents("php://input");
    $data = json_decode($jsonInput, true);

    $ticket_id = $data["id"] ?? null;

    if (!$ticket_id) {
        echo json_encode(["success" => false, "message" => "ID ticket mancante"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
        $stmt->execute([$ticket_id]);

        echo json_encode(["success" => true, "message" => "Ticket eliminato con successo!"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Errore nell'eliminazione del ticket"]);
    }
    exit;
}





// ✅ SE L'AZIONE NON È RICONOSCIUTA
echo json_encode(["success" => false, "message" => "Azione non valida"]);
exit;