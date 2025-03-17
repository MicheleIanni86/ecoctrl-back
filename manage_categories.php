<?php
session_start();
require_once 'db.php';

// ✅ Debug: Testiamo la connessione al database
if (!$pdo) {
    die("Errore nella connessione al database.");
}

// ✅ Debug: Controlliamo se la tabella users ha Admin
$admins_stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'Admin'");
$admins = $admins_stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$admins) {
    die("Errore: Nessun Admin trovato nel database.");
}

// ✅ Debug: Controlliamo se la tabella ticketcategories ha categorie
$categories_stmt = $pdo->query("SELECT id, name FROM ticketcategories");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$categories) {
    die("Errore: Nessuna categoria trovata nel database.");
}




// ✅ Controllo: Solo il SuperAdmin può accedere
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'SuperAdmin') {
    die("Accesso negato. Solo il SuperAdmin può assegnare le categorie.");
}

// ✅ Recupera gli Admin
$admins_stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'Admin'");
$admins = $admins_stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Recupera le categorie
$categories_stmt = $pdo->query("SELECT id, name FROM ticketcategories");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Se viene inviato il modulo, assegna la categoria
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $admin_id = $_POST["admin_id"] ?? null;
    $category_id = $_POST["category_id"] ?? null;

    if ($admin_id && $category_id) {
        $stmt = $pdo->prepare("INSERT INTO user_ticketcategory (user_id, ticketcategories_id) VALUES (?, ?)");
        $stmt->execute([$admin_id, $category_id]);
        echo "<p class='alert alert-success'>Categoria assegnata con successo!</p>";
    } else {
        echo "<p class='alert alert-danger'>Seleziona un Admin e una Categoria.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Assegna Categorie agli Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container">
    <h1 class="mt-5">Assegna Categorie agli Admin</h1>
    <form method="POST" class="mt-4">
        <label for="admin">Seleziona Admin:</label>
        <select name="admin_id" class="form-select" required>
            <option value="">-- Seleziona Admin --</option>
            <?php 
            if (empty($admins)) {
                echo "<option value=''>⚠ Nessun Admin disponibile</option>";
            } else {
                foreach ($admins as $admin) {
                    echo "<option value='{$admin['id']}'>" . htmlspecialchars($admin['name']) . "</option>";
                }
            }
            ?>
        </select>


        <label for="category" class="mt-3">Seleziona Categoria:</label>
        <select name="category_id" class="form-select" required>
            <option value="">-- Seleziona Categoria --</option>
            <?php 
            if (empty($categories)) {
                echo "<option value=''>⚠ Nessuna Categoria disponibile</option>";
            } else {
                foreach ($categories as $category) {
                    echo "<option value='{$category['id']}'>" . htmlspecialchars($category['name']) . "</option>";
                }
            }
            ?>
        </select>


        <button type="submit" class="btn btn-success mt-3">Assegna Categoria</button>
    </form>

 
    
    
    <h2 class="mt-5">Admin con le loro Categorie</h2>
    <table class="table table-striped mt-3">
        <thead class="table-dark">
            <tr>
                <th>Admin</th>
                <th>Categoria Assegnata</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $assigned_stmt = $pdo->query("
            SELECT u.name AS admin_name, c.name AS category_name 
            FROM user_ticketcategory utc
            JOIN users u ON utc.user_id = u.id
            JOIN ticketcategories c ON utc.ticketcategories_id = c.id
            ");
            while ($row = $assigned_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['admin_name']) ?></td>
                    <td><?= htmlspecialchars($row['category_name']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

   <button id="showTicketsBtn" class="btn btn-primary my-3">Mostra tutti i ticket aperti</button>

        <div id="ticketsContainer" class="mt-4" style="display: none;">
            <h3 class="bg-dark text-white p-3 rounded">Tutti i Ticket Aperti</h3>
            <table class="table table-striped bg-light">
                <thead class="table-dark text-center">
                    <tr>
                        <th>ID</th>
                        <th>Utente</th>
                        <th>Descrizione</th>
                        <th>Stato</th>
                        <th>Assegna a Admin</th>
                    </tr>
                </thead>
                <tbody id="ticketsTableBody">
                    <tr><td colspan="5" class="text-center">Nessun ticket trovato.</td></tr>
                </tbody>
            </table>
        </div>
    </body>
    </html>


    <!-- quando il SuperAdmin clicca il pulsante "Mostra tutti i ticket aperti", la tabella viene popolata con tutti i ticket aperti. -->
    <script>
document.getElementById("showTicketsBtn").addEventListener("click", async function () {
    const container = document.getElementById("ticketsContainer");
    container.style.display = "block"; // Mostra la sezione ticket

    const tableBody = document.getElementById("ticketsTableBody");
    tableBody.innerHTML = "<tr><td colspan='5' class='text-center'>Caricamento...</td></tr>";

    try {
        const response = await fetch("../ecoctrl-back/api.php?action=getAllOpenTickets");
        const data = await response.json();

        if (!data.success || !data.tickets.length) {
            tableBody.innerHTML = "<tr><td colspan='5' class='text-center'>Nessun ticket aperto trovato.</td></tr>";
            return;
        }

        tableBody.innerHTML = ""; // Svuota la tabella e popola con i dati

        data.tickets.forEach(ticket => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${ticket.id}</td>
                <td>${ticket.client_name}</td>
                <td>${ticket.description}</td>
                <td>${ticket.status}</td>
                <td>
                    <select class="form-select assign-ticket" data-ticket-id="${ticket.id}">
                        <option value="">Seleziona Admin</option>
                    </select>
                    <button class="btn btn-success btn-assign" data-ticket-id="${ticket.id}">Assegna</button>
                </td>
            `;
            tableBody.appendChild(row);
        });

        // ✅ Carica la lista degli Admin per la selezione
        try {
            const responseAdmins = await fetch("../ecoctrl-back/api.php?action=getAdmins");
            const textResponse = await responseAdmins.text(); // ✅ Debug per verificare la risposta
            console.log("📥 Risposta API Admins (testo):", textResponse);

            const adminData = JSON.parse(textResponse);
            console.log("📥 Risposta API Admins (JSON):", adminData);

            if (adminData.success && adminData.admins.length) {
                document.querySelectorAll(".assign-ticket").forEach(select => {
                    adminData.admins.forEach(admin => {
                        const option = document.createElement("option");
                        option.value = admin.id;
                        option.textContent = admin.name;
                        select.appendChild(option);
                    });
                });
            } else {
                console.warn("⚠️ Nessun Admin trovato.");
            }
        } catch (error) {
            console.error("❌ Errore nel caricamento degli Admin:", error);
        }


        // ✅ Assegna evento ai pulsanti di assegnazione
        document.querySelectorAll(".btn-assign").forEach(button => {
            button.addEventListener("click", async function () {
                const ticketId = this.dataset.ticketId;
                const adminId = document.querySelector(`.assign-ticket[data-ticket-id="${ticketId}"]`).value;

                if (!adminId) {
                    alert("Seleziona un Admin per assegnare il ticket!");
                    return;
                }

                const response = await fetch("../ecoctrl-back/api.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ action: "assignTicketToAdmin", ticket_id: ticketId, admin_id: adminId })
                });

                const result = await response.json();
                if (result.success) {
                    alert("Ticket assegnato con successo!");
                    location.reload(); // Ricarica la pagina per aggiornare i ticket
                } else {
                    alert("Errore: " + result.message);
                }
            });
        });

    } catch (error) {
        console.error("❌ Errore nel caricamento dei ticket:", error);
    }
});
</script>

    