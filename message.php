<?php
// ✅ Debug della sessione
session_start(); // ✅ Avvia la sessione
file_put_contents("debug_log.txt", "📌 Sessione in message.php: " . print_r($_SESSION, true) . "\n", FILE_APPEND);
require_once 'db.php';

// ✅ Verifica che l'utente sia loggato e sia un Admin o SuperAdmin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'Admin' && $_SESSION['user_role'] !== 'SuperAdmin')) {
    die("Accesso negato. Effettua il login come Admin o SuperAdmin.");
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// ✅ Recupera i ticket
if ($user_role === 'Admin') {
    file_put_contents("debug_log.txt", "📌 Sono dentro la sezione Admin di message.php!\n", FILE_APPEND);

    // Ticket già assegnati all'Admin
    $stmt = $pdo->prepare("SELECT t.id, t.description, t.status, u.name AS client_name FROM tickets t JOIN users u ON t.client_id = u.id WHERE t.admin_id = ?");
    $stmt->execute([$user_id]);
    $assigned_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Verifica se ci sono ticket disponibili della categoria dell'Admin
    file_put_contents("debug_log.txt", "📌 Debug Query Ticket Disponibili (Admin ID: $user_id)\n", FILE_APPEND);

    // Ticket disponibili della sua categoria
    $query = "SELECT t.id, t.description, t.status, u.name AS client_name 
    FROM tickets t 
    JOIN users u ON t.client_id = u.id 
    WHERE t.admin_id IS NULL 
    AND t.ticketCat_id IN (SELECT ticketcategories_id FROM user_ticketcategory WHERE user_id = ?)";

// ✅ Scriviamo la query nel file di debug
file_put_contents("debug_log.txt", "📌 Query eseguita: " . $query . "\n", FILE_APPEND);

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);

$available_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Scriviamo quanti ticket sono stati trovati
file_put_contents("debug_log.txt", "📌 Ticket disponibili trovati: " . count($available_tickets) . "\n", FILE_APPEND);


    // Debug: Scriviamo il numero di ticket trovati
    file_put_contents("debug_log.txt", "📌 Ticket Disponibili trovati: " . count($available_tickets) . "\n", FILE_APPEND);
} elseif ($user_role === 'SuperAdmin') {
    // Il SuperAdmin vede tutti i ticket non assegnati
    $stmt = $pdo->query("SELECT t.id, t.description, t.status, u.name AS client_name FROM tickets t JOIN users u ON t.client_id = u.id WHERE t.admin_id IS NULL");
    $available_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    file_put_contents("debug_log.txt", "📌 POST ricevuto: " . print_r($_POST, true) . "\n", FILE_APPEND);

    if (isset($_POST["take_ticket"])) {
        $ticket_id = $_POST["ticket_id"];
        file_put_contents("debug_log.txt", "📌 Admin sta prendendo in carico il ticket ID: $ticket_id\n", FILE_APPEND);

        $stmt = $pdo->prepare("UPDATE tickets SET admin_id = ?, status = 'In corso' WHERE id = ?");
        $stmt->execute([$user_id, $ticket_id]);

        header("Location: message.php");
        exit();
    }

    if (isset($_POST["release_ticket"])) {
        $ticket_id = $_POST["ticket_id"];
        file_put_contents("debug_log.txt", "📌 Admin sta rilasciando il ticket ID: $ticket_id\n", FILE_APPEND);

        $stmt = $pdo->prepare("UPDATE tickets SET admin_id = NULL, status = 'Aperto' WHERE id = ?");
        $stmt->execute([$ticket_id]);

        header("Location: message.php");
        exit();
    }
}


?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container bg-secondary">
<div class="mt-5">
    <h1 class="bg-success text-white p-3 rounded">
        <?php echo $user_role === 'Admin' ? "I tuoi Ticket" : "Ticket da Assegnare"; ?>
    </h1>
    
    <?php if ($user_role === 'Admin'): ?>
        <h3 class="text-white">Ticket Assegnati a Te</h3>
        <table class="table table-striped bg-light">
            <thead class="table-dark text-center">
                <tr>
                    <th>ID</th>
                    <th>Utente</th>
                    <th>Descrizione</th>
                    <th>Stato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assigned_tickets as $ticket): ?>
                <tr>
                    <td><?= htmlspecialchars($ticket['id']) ?></td>
                    <td><?= htmlspecialchars($ticket['client_name']) ?></td>
                    <td><?= nl2br(htmlspecialchars($ticket['description'])) ?></td>
                    <td><?= htmlspecialchars($ticket['status']) ?></td>
                    <td>
                        <form method="POST" action="message.php">
                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                            <button type="submit" name="release_ticket" class="btn btn-warning">Rilascia</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    
        <h3 class="text-white">Ticket Disponibili</h3>
        <table class="table table-striped bg-light">
            <thead class="table-dark text-center">
                <tr>
                    <th>ID</th>
                    <th>Utente</th>
                    <th>Descrizione</th>
                    <th>Stato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($available_tickets as $ticket): ?>
                <tr>
                    <td><?= htmlspecialchars($ticket['id']) ?></td>
                    <td><?= htmlspecialchars($ticket['client_name']) ?></td>
                    <td><?= nl2br(htmlspecialchars($ticket['description'])) ?></td>
                    <td><?= htmlspecialchars($ticket['status']) ?></td>
                    <td>
                        <form method="POST" action="message.php">
                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                            <button type="submit" name="take_ticket" class="btn btn-primary">Prendi in Carico</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
