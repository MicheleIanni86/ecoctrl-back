<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

$filename = "messages.json";

if (!file_exists($filename)) {
    file_put_contents($filename, json_encode([]));
}

$messages = json_decode(file_get_contents($filename), true);

// **Forziamo sempre JSON per richieste AJAX o metodi diversi da GET**
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || isset($_GET['json'])) {
    header("Content-Type: application/json");

    if ($_SERVER['REQUEST_METHOD'] == "GET") {
        echo json_encode(["success" => true, "messages" => $messages]);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['user']) || !isset($data['message'])) {
            echo json_encode(["success" => false, "message" => "Dati non validi"]);
            exit();
        }

        $newMessage = [
            "id" => count($messages) > 0 ? max(array_column($messages, 'id')) + 1 : 1,
            "user" => htmlspecialchars($data['user']),
            "message" => htmlspecialchars($data['message']),
            "timestamp" => date('Y-m-d H:i:s')
        ];

        $messages[] = $newMessage;
        file_put_contents($filename, json_encode($messages, JSON_PRETTY_PRINT));

        echo json_encode(["success" => true]);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id'])) {
            echo json_encode(["success" => false, "message" => "ID non valido"]);
            exit();
        }

        $messages = array_values(array_filter($messages, fn($msg) => $msg['id'] != $data['id']));
        file_put_contents($filename, json_encode($messages, JSON_PRETTY_PRINT));

        echo json_encode(["success" => true]);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == "PUT") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id']) || !isset($data['message'])) {
            echo json_encode(["success" => false, "message" => "Dati non validi"]);
            exit();
        }

        foreach ($messages as &$msg) {
            if ($msg['id'] == $data['id']) {
                $msg['message'] = htmlspecialchars($data['message']);
                break;
            }
        }

        file_put_contents($filename, json_encode($messages, JSON_PRETTY_PRINT));
        echo json_encode(["success" => true]);
        exit();
    }

    echo json_encode(["success" => false, "message" => "Metodo non supportato"]);
    exit();
}

// **Se la richiesta è GET senza JSON, mostriamo la tabella HTML**
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Segnalazioni</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-secondary">
    <div class="container mt-5">
        <h1 class="text-white text-center bg-success p-3 rounded">Segnalazioni Ricevute</h1>

        <div class="table-responsive">
            <table class="table table-striped table-bordered text-center bg-light rounded">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Utente</th>
                        <th>Messaggio</th>
                        <th>Data</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($messages)) : ?>
                        <?php foreach ($messages as $msg) : ?>
                            <tr>
                                <td><?= htmlspecialchars($msg['id']) ?></td>
                                <td><?= htmlspecialchars($msg['user']) ?></td>
                                <td class="text-start"><?= nl2br(htmlspecialchars(substr($msg['message'], 0, 50))) ?>...</td>
                                <td><?= htmlspecialchars($msg['timestamp']) ?></td>
                                <td>
                                    <button class="btn btn-info btn-sm" onclick="showMessage('<?= htmlspecialchars(addslashes($msg['message'])) ?>')">Visualizza</button>
                                    <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $msg['id'] ?>)">Elimina</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">Nessuna segnalazione presente</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-4">
            <a href="index.html" class="btn btn-warning">Torna alla Home</a>
        </div>
    </div>


    <script>
    function showMessage(message) {
        document.getElementById("modalMessage").textContent = message;
        new bootstrap.Modal(document.getElementById("messageModal")).show();
    }

    function confirmDelete(messageId) {
        if (confirm("Sei sicuro di voler eliminare questo messaggio?")) {
            fetch("http://localhost/ecoctrl-back/message.php", {
                method: "DELETE",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: messageId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Messaggio eliminato con successo!");
                    location.reload();
                } else {
                    alert("Errore nell'eliminazione!");
                }
            })
            .catch(error => console.error("Errore eliminazione:", error));
        }
    }
</script>

<!-- **MODAL per Visualizzare Messaggi** -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dettaglio Messaggio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modalMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>




<!-- CREATE TABLE users(
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    surname VARCHAR(255) NOT NULL,
    mail VARCHAR(255) NOT NULL,
    pass VARCHAR(255) NOT NULL,
    role ENUM ('SuperAdmin', 'Admin', 'Client') DEFAULT 'Client'
    );
    
    CREATE TABLE ticketCategories(
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description VARCHAR(255) NOT NULL
    );
    
    CREATE TABLE ticket(
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        ticketDate TIMESTAMP,
        description VARCHAR(255) NOT NULL,
        user_id BIGINT,
        ticketCat_id BIGINT, 
        FOREIGN KEY(user_id) REFERENCES users(id),
        FOREIGN KEY(ticketCat_id) REFERENCES ticketCategories(id)
    );
    
    CREATE TABLE messages(
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        messageDate TIMESTAMP,
        messageText VARCHAR(255) NOT NULL,
        user_id BIGINT,
        ticket_id BIGINT,
        FOREIGN KEY(user_id) REFERENCES users(id),
        FOREIGN KEY(ticket_id) REFERENCES tickets(id)
   ); -->
