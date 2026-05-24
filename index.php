<?php
session_start();
require_once 'db_config.php';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>System Zarządzania Restauracją</h1>
        
        <div class="menu-grid" style="">
            <div class="menu-card">
                <h2>Kiosk</h2>
                <p>Złóż zamówienie samodzielnie</p>
                <a href="kiosk.php" class="btn btn-primary">Przejdź do Kiosku</a>
            </div>

            <div class="menu-card">
                <h2>Panel dla kelnerów</h2>
                <p>Obsługa zamówień klientów</p>
                <a href="kelner_login.php" class="btn btn-primary">Zaloguj się</a>
            </div>

            <div class="menu-card">
                <h2>Kuchnia</h2>
                <p>Realizacja zamówień</p>
                <a href="kuchnia.php" class="btn btn-primary">Otwórz Panel</a>
            </div>

            <div class="menu-card">
                <h2>Zarządzanie Zapasami</h2>
                <p>Kontrola stanów magazynowych</p>
                <a href="zapasy.php" class="btn btn-primary">Zarządzaj Zapasami</a>
            </div>

            <div class="menu-card">
                <h2>Menu i Składniki</h2>
                <p>Edycja dań i przepisów</p>
                <a href="menu_manager.php" class="btn btn-primary">Zarządzaj Menu</a>
            </div>

            <div class="menu-card">
                <h2>Kolejka Zamówień</h2>
                <p>Status zamówień dla klientów</p>
                <a href="kolejka_display.php" class="btn btn-primary">Zobacz Kolejkę</a>
            </div>
        </div>
    </div>
</body>
</html>