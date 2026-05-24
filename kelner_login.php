<?php
session_start();
require_once 'db_config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kelnera = intval($_POST['id_kelnera']);
    
    // Sprawdź czy kelner istnieje
    $stmt = $conn->prepare("SELECT * FROM kelnerzy WHERE id_kelnera = ?");
    $stmt->bind_param("i", $id_kelnera);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['kelner'] = $result->fetch_assoc();
        header('Location: kelner_panel.php');
        exit;
    } else {
        $error = 'Nieprawidłowe ID kelnera';
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie Kelnera</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 500px; margin-top: 100px;">
        <h1>Zaloguj się do Panelu</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>ID Kelnera</label>
                <input type="number" name="id_kelnera" required autofocus>
            
                <small>Hasło nie jest wymagane w tej wersji.</small>
                <!-- <label>Hasło (do zrobienia)</label> -->
                <!-- <input type="password" name="password" required> -->
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Zaloguj się</button>
        </form>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="index.php">Powrót do menu głównego</a>
        </div>
        
        <div class="alert alert-info" style="margin-top: 30px;">
            <strong>Informacja:</strong> Aby utworzyć konto kelnera, należy dodać rekord do tabeli 'kelnerzy' w bazie danych MySQL.
        </div>
    </div>
</body>
</html>