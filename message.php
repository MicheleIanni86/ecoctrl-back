<?php
require_once 'db.php';

$stmt = $pdo->query("
    SELECT m.id, m.messageText, m.messageDate, u.name AS username
    FROM messages m JOIN users u ON m.user_id = u.id
    ORDER BY m.messageDate DESC
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
<body class="bg-secondary">
<div class="container mt-5">
    <h1 class="bg-success text-white p-3 rounded">Gestione Messaggi</h1>
    <table class="table table-striped bg-light">
        <thead class="table-dark">
            <tr>
                <th>ID</th><th>Utente</th><th>Messaggio</th><th>Data</th><th>Azioni</th>
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
    <a href="../ecoctrl-front/index.html" class="btn btn-warning">Home</a>
</div>

<script>
document.querySelectorAll(".edit-btn").forEach(btn => {
    btn.onclick = function() {
        const id = this.dataset.id;
        const newMessage = prompt("Nuovo messaggio:", this.dataset.message);
        if (newMessage && newMessage.length >= 10) {
            fetch('api.php', {
                method: "PUT",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({id, messageText: newMessage})
            })
            .then(r => r.json()).then(d => d.success ? location.reload() : alert(d.message));
        }
    };
});

document.querySelectorAll(".delete-btn").forEach(btn => {
    btn.onclick = function() {
        if (confirm("Eliminare il messaggio?")) {
            fetch('api.php', {
                method: "DELETE",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({id: this.dataset.id})
            })
            .then(r => r.json()).then(d => d.success ? location.reload() : alert(d.message));
        }
    };
});
</script>
</body>
</html>
