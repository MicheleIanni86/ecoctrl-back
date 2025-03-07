<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

$filename = "messages.json";

// **Se il file JSON non esiste, crealo vuoto**
if (!file_exists($filename)) {
    file_put_contents($filename, json_encode([]));
}

// **Leggi i messaggi dal file JSON**
$messages = json_decode(file_get_contents($filename), true);

// **Gestione delle richieste POST (aggiunta di un messaggio)**
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $inputJSON = file_get_contents("php://input");
    $data = json_decode($inputJSON, true);

    if (!isset($data['user']) || !isset($data['message'])) {
        echo json_encode(["success" => false, "message" => "Dati non validi"]);
        exit();
    }

    $newMessage = [
        "id" => count($messages) > 0 ? max(array_column($messages, 'id')) + 1 : 1,
        "user" => $data['user'],
        "message" => $data['message'],
        "timestamp" => date('Y-m-d H:i:s')
    ];

    $messages[] = $newMessage;
    file_put_contents($filename, json_encode($messages, JSON_PRETTY_PRINT));

    echo json_encode(["success" => true, "message" => "Messaggio salvato"]);
    exit();
}

// **Gestione delle richieste DELETE (eliminazione di un messaggio)**
if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
    $inputJSON = file_get_contents("php://input");
    $data = json_decode($inputJSON, true);

    if (!isset($data['id'])) {
        echo json_encode(["success" => false, "message" => "ID non valido"]);
        exit();
    }

    $messages = array_filter($messages, fn($msg) => $msg['id'] != $data['id']);
    file_put_contents($filename, json_encode(array_values($messages), JSON_PRETTY_PRINT));

    echo json_encode(["success" => true, "message" => "Messaggio eliminato"]);
    exit();
}

// **Gestione delle richieste PUT (modifica di un messaggio)**
if ($_SERVER['REQUEST_METHOD'] == "PUT") {
    $inputJSON = file_get_contents("php://input");
    $data = json_decode($inputJSON, true);

    if (!isset($data['id']) || !isset($data['message'])) {
        echo json_encode(["success" => false, "message" => "Dati non validi"]);
        exit();
    }

    foreach ($messages as &$msg) {
        if ($msg['id'] == $data['id']) {
            $msg['message'] = $data['message'];
            break;
        }
    }

    file_put_contents($filename, json_encode($messages, JSON_PRETTY_PRINT));
    echo json_encode(["success" => true, "message" => "Messaggio modificato"]);
    exit();
}

// **Se la richiesta è GET, mostra la tabella HTML**
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
                    </tr>
                </thead>
                <tbody id="messagesTableBody">
                    <?php if (!empty($messages)) : ?>
                        <?php foreach ($messages as $msg) : ?>
                            <tr>
                                <td><?= htmlspecialchars($msg['id']) ?></td>
                                <td><?= htmlspecialchars($msg['user']) ?></td>
                                <td class="text-start"><?= nl2br(htmlspecialchars($msg['message'])) ?></td>
                                <td><?= htmlspecialchars($msg['timestamp']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">Nessuna segnalazione presente</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-4">
            <a href="index.html" class="btn btn-warning">Torna alla Home</a>
        </div>
    </div>
</body>
</html>
