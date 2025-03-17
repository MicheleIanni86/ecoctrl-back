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
</body>
</html>
