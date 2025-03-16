<?php
// ✅ Debug della sessione
session_start(); // ✅ Avvia la sessione
file_put_contents("debug_log.txt", "📌 Sessione in message.php: " . print_r($_SESSION, true) . "\n", FILE_APPEND);
// ✅ Debug della sessione
require_once 'db.php';

// ✅ Verifica che l'utente sia loggato e sia un Admin o SuperAdmin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'Admin' && $_SESSION['user_role'] !== 'SuperAdmin')) {
    die("Accesso negato. Effettua il login come Admin o SuperAdmin.");
}

// ✅ Recupera i messaggi dal database
$stmt = $pdo->query("
    SELECT t.id, t.description AS messageText, t.ticketDate AS messageDate, 
           u.name AS username, t.status
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
        <thead class="table-dark text-center">
            <tr>
                <th>ID</th>
                <th>Utente</th>
                <th>Messaggio</th>
                <th>Data</th>
                <th>Stato</th>
                <th></th>
                <th>Azioni</th> 
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $msg): ?>
            <tr>
                <td><?= htmlspecialchars($msg['id']) ?></td>
                <td><?= htmlspecialchars($msg['username']) ?></td>
                <td><?= nl2br(htmlspecialchars($msg['messageText'])) ?></td>
                <td><?= htmlspecialchars($msg['messageDate']) ?></td>
                <td><?= htmlspecialchars($msg['status']) ?></td>
                <td>
                    <?php if ($_SESSION['user_role'] === 'SuperAdmin'): ?>
                    <!-- ✅ Menu a tendina per assegnare il ticket (solo per SuperAdmin) -->
                    <select class="form-select assign-select" data-id="<?= $msg['id'] ?>">
                        <option value="">Assegna a...</option>
                        <?php
                        // ✅ Recupera la lista degli admin
                        $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'Admin'");
                        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($admins as $admin): ?>
                            <option value="<?= $admin['id'] ?>"><?= htmlspecialchars($admin['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                </td>
                <td>
                <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                    <!-- ✅ Pulsante "Prendi in carico" (solo per Admin) -->
                    <button class="btn btn-primary take-btn" data-id="<?= $msg['id'] ?>" data-status="take">Prendi in carico</button>
                <?php endif; ?>
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
            fetch('../ecoctrl-back/api.php?action=updateTicket', {  // ✅ Cambiato da updateMessage a updateTicket
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
            fetch('../ecoctrl-back/api.php?action=deleteTicket', {  // ✅ Cambiato da deleteMessage a deleteTicket
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

// ✅ Funzione per assegnare un ticket a un altro admin (SuperAdmin only)
document.querySelectorAll(".assign-select").forEach(select => {
    select.onchange = function() {
        const ticketId = this.dataset.id;
        const adminId = this.value;

        if (!adminId) return; // ✅ Ignora se non è selezionato nessun admin

        fetch('../ecoctrl-back/api.php', {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "assignTicket", id: ticketId, admin_id: adminId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Ticket assegnato con successo!");
                location.reload(); // ✅ Ricarica la pagina per aggiornare la tabella
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(error => console.error("Errore nella richiesta:", error));
    };
});

// ✅ Funzione per prendere in carico un ticket
document.querySelectorAll(".take-btn").forEach(btn => {
    btn.onclick = function() {
        const ticketId = this.dataset.id;
        const action = this.dataset.status === "take" ? "takeTicket" : "releaseTicket"; // ✅ Determina l'azione

        fetch('../ecoctrl-back/api.php', {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action, id: ticketId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // ✅ Cambia il testo e lo stile del pulsante
                if (action === "takeTicket") {
                    this.textContent = "Annulla incarico";
                    this.classList.remove("btn-primary");
                    this.classList.add("btn-secondary");
                    this.dataset.status = "release";
                } else {
                    this.textContent = "Prendi in carico";
                    this.classList.remove("btn-secondary");
                    this.classList.add("btn-primary");
                    this.dataset.status = "take";
                }
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(error => console.error("Errore nella richiesta:", error));
    };
});

// ✅ Funzione per cambiare lo stato di un ticket
document.querySelectorAll(".status-select").forEach(select => {
    select.onchange = function() {
        const ticketId = this.dataset.id;
        const newStatus = this.value;

        fetch('../ecoctrl-back/api.php', {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "updateTicketStatus", id: ticketId, status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Stato del ticket aggiornato con successo!");
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(error => console.error("Errore nella richiesta:", error));
    };
});

</script>





</body>
</html>
