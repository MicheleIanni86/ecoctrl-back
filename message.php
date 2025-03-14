<?php
require_once 'db.php';
session_start();

// ✅ Recupera i messaggi dal database
$stmt = $pdo->query("
    SELECT t.id, t.description AS messageText, t.ticketDate AS messageDate, u.name AS username
    FROM tickets t 
    JOIN users u ON t.client_id = u.id
    ORDER BY t.ticketDate DESC
");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Messaggi Backend</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container bg-secondary">
<div class="mt-5">
    <h1 class="bg-success text-white p-3 rounded">Gestione Messaggi</h1>
    <table class="table table-striped bg-light">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Utente</th>
                <th>Messaggio</th>
                <th>Data</th>
                <th>Azioni</th> <!-- ✅ Corretto il titolo della colonna -->
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $msg): ?>
            <tr>
                <td><?= htmlspecialchars($msg['id']) ?></td>
                <td><?= htmlspecialchars($msg['username']) ?></td>
                <td><?= nl2br(htmlspecialchars($msg['messageText'])) ?></td>
                <td><?= htmlspecialchars($msg['messageDate']) ?></td>
                <td>
                    <button class="btn btn-warning edit-btn" data-id="<?= $msg['id'] ?>" data-message="<?= htmlspecialchars($msg['messageText']) ?>">Modifica</button>
                    <button class="btn btn-danger delete-btn" data-id="<?= $msg['id'] ?>">Elimina</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>

// ✅ Funzione per modificare un ticket
document.querySelectorAll(".edit-btn").forEach(btn => {
    btn.onclick = function() {
        const id = this.dataset.id;
        const newMessage = prompt("Modifica ticket:", this.dataset.message);
        if (newMessage && newMessage.length >= 10) {
            fetch('api.php?action=updateTicket', {  // ✅ Cambiato da updateMessage a updateTicket
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id, messageText: newMessage })  // ✅ Il campo è corretto?
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Ticket aggiornato con successo!");
                    location.reload();
                } else {
                    alert("Errore: " + data.message);
                }
            })
            .catch(error => console.error("Errore nella richiesta:", error));
        }
    };
});

// ✅ Funzione per eliminare un ticket
document.querySelectorAll(".delete-btn").forEach(btn => {
    btn.onclick = function() {
        if (confirm("Eliminare il ticket?")) {
            fetch('api.php?action=deleteTicket', {  // ✅ Cambiato da deleteMessage a deleteTicket
                method: "DELETE",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: this.dataset.id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Ticket eliminato con successo!");
                    location.reload();
                } else {
                    alert("Errore: " + data.message);
                }
            })
            .catch(error => console.error("Errore nella richiesta:", error));
        }
    };
});

</script>
</body>
</html>
