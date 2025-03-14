<?php
// File: db.php

$host = "localhost";
$db   = "ticket_manager";  
$user = "root";           
$pass = "";           
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Abilita la gestione degli errori
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Formato array associativo
        PDO::ATTR_EMULATE_PREPARES => false, // Disabilita emulazione query per maggiore sicurezza
    ]);

    // ✅ Log della connessione
    file_put_contents("debug_log.txt", "✅ Connessione al database riuscita.\n", FILE_APPEND);
    
} catch (PDOException $e) {
    die("❌ Connessione fallita: " . $e->getMessage());
}
