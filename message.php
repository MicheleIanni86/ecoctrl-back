<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

$filename = "messages.json";

if (!file_exists($filename)) {
    file_put_contents($filename, json_encode([]));
}

$messages = json_decode(file_get_contents($filename), true);

// **Forziamo la risposta JSON se viene passato ?json=true nella URL**
if (isset($_GET['json']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("Content-Type: application/json");

    if ($_SERVER['REQUEST_METHOD'] == "GET") {
        echo json_encode(["success" => true, "messages" => $messages]);
        exit();
    }

    // **POST - Aggiunge un nuovo messaggio**
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

    // **DELETE - Elimina un messaggio**
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

    // **PUT - Modifica un messaggio**
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

// **Se la pagina viene aperta dal browser, mostra la tabella HTML**
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
                <tbody>
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
