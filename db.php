<?php
// File: db.php

// Parametri di connessione al database MySQL 
$host = "localhost";       
$db   = "ticket_manager";  
$user = "root";           
$pass = "";           
$charset = "utf8mb4";

// Connessione tramite PDO
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    // Connessione PDO
    $pdo = new PDO($dsn, $user, $pass);
    
    // Gestione degli errori
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // In caso di errore, mostra il messaggio
    die("Connessione fallita: " . $e->getMessage());
}
