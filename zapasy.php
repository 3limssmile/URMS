<?php
session_start();
require_once 'db_config.php';

$message = '';

// Dodawanie nowego składnika
if (isset($_POST['add_skladnik'])) {
    $nazwa = $conn->real_escape_string($_POST['nazwa']);
    $ilosc = floatval($_POST['ilosc']);
    $jednostka = $conn->real_escape_string($_POST['jednostka']);
    
    $stmt = $conn->prepare("INSERT INTO zapasy (nazwa, ilosc, jednostka) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $nazwa, $ilosc, $jednostka);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Składnik został dodany!</div>';
    } else {
        $message = '<div class="alert alert-error">Błąd dodawania składnika</div>';
    }
}

// Aktualizacja ilości
if (isset($_POST['update_ilosc'])) {
    $id_skladnika = intval($_POST['id_skladnika']);
    $ilosc = floatval($_POST['ilosc']);
    
    $stmt = $conn->prepare("UPDATE zapasy SET ilosc = ? WHERE id_skladnika = ?");
    $stmt->bind_param("di", $ilosc, $id_skladnika);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Ilość zaktualizowana!</div>';
    }
}

// Uzupełnianie zapasów
if (isset($_POST['uzupelnij'])) {
    $id_skladnika = intval($_POST['id_skladnika']);
    $dodaj_ilosc = floatval($_POST['dodaj_ilosc']);
    
    $stmt = $conn->prepare("UPDATE zapasy SET ilosc = ilosc + ? WHERE id_skladnika = ?");
    $stmt->bind_param("di", $dodaj_ilosc, $id_skladnika);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Zapasy uzupełnione!</div>';
    }
}

// Zmniejszanie zapasów
if (isset($_POST['zmniejsz'])) {
    $id_skladnika = intval($_POST['id_skladnika']);
    $odejmij_ilosc = floatval($_POST['odejmij_ilosc']);
    
    $stmt = $conn->prepare("UPDATE zapasy SET ilosc = GREATEST(0, ilosc - ?) WHERE id_skladnika = ?");
    $stmt->bind_param("di", $odejmij_ilosc, $id_skladnika);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Zapasy zmniejszone!</div>';
    }
}

// Usuwanie składnika
if (isset($_GET['delete'])) {
    $id_skladnika = intval($_GET['delete']);
    
    // Sprawdź czy składnik jest używany
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM danie_skladniki WHERE id_skladnika = ?");
    $stmt->bind_param("i", $id_skladnika);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['cnt'] > 0) {
        $message = '<div class="alert alert-error">Nie można usunąć składnika - jest używany w daniach!</div>';
    } else {
        $stmt = $conn->prepare("DELETE FROM zapasy WHERE id_skladnika = ?");
        $stmt->bind_param("i", $id_skladnika);
        
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Składnik został usunięty!</div>';
        }
    }
}

// Pobierz wszystkie zapasy
$zapasy = $conn->query("SELECT * FROM zapasy ORDER BY nazwa");
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie Zapasami</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <ul>
                <li><a href="index.php">Menu Główne</a></li>
            </ul>
        </div>

        <h1>Zarządzanie Zapasami</h1>
        
        <?php echo $message; ?>

        <!-- Formularz dodawania nowego składnika -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <h2>Dodaj Nowy Składnik</h2>
            <form method="POST" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label>Nazwa składnika</label>
                    <input type="text" name="nazwa" required>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>Ilość</label>
                    <input type="number" step="0.01" name="ilosc" required>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>Jednostka</label>
                    <input type="text" name="jednostka" placeholder="kg, l, szt..." required>
                </div>
                <button type="submit" name="add_skladnik" class="btn btn-success">Dodaj</button>
            </form>
        </div>

        <!-- Lista zapasów -->
        <h2>Stan Magazynu</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nazwa</th>
                    <th>Ilość</th>
                    <th>Jednostka</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($skladnik = $zapasy->fetch_assoc()): ?>
                    <tr style="<?php echo $skladnik['ilosc'] < 10 ? 'background: #ff404045;' : 'background: #80ff4045' ; ?>">
                        <td><?php echo $skladnik['id_skladnika']; ?></td>
                        <td><strong><?php echo htmlspecialchars($skladnik['nazwa']); ?></strong></td>
                        <td>
                            <span style="font-size: 1.2em; font-weight: bold; <?php echo $skladnik['ilosc'] < 10 ? 'color: #dc3545;' : 'color: #28a745;'; ?>">
                                <?php echo number_format($skladnik['ilosc'], 2); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($skladnik['jednostka']); ?></td>
                        <td>
                            <!-- Formularz uzupełniania -->
                            <form method="POST" style="display: inline-block; margin-right: 5px;">
                                <input type="hidden" name="id_skladnika" value="<?php echo $skladnik['id_skladnika']; ?>">
                                <input type="number" step="0.01" name="dodaj_ilosc" placeholder="+" style="width: 70px;" required>
                                <button type="submit" name="uzupelnij" class="btn btn-success" style="padding: 5px 10px;">
                                    Dodaj
                                </button>
                            </form>
                            
                            <!-- Formularz zmniejszania -->
                            <form method="POST" style="display: inline-block; margin-right: 5px;">
                                <input type="hidden" name="id_skladnika" value="<?php echo $skladnik['id_skladnika']; ?>">
                                <input type="number" step="0.01" name="odejmij_ilosc" placeholder="-" style="width: 70px;" required>
                                <button type="submit" name="zmniejsz" class="btn btn-warning" style="padding: 5px 10px;">
                                    Odejmij
                                </button>
                            </form>
                            
                            <!-- Formularz ustawiania -->
                            <form method="POST" style="display: inline-block; margin-right: 5px;">
                                <input type="hidden" name="id_skladnika" value="<?php echo $skladnik['id_skladnika']; ?>">
                                <input type="number" step="0.01" name="ilosc" placeholder="Ustaw" style="width: 70px;" required>
                                <button type="submit" name="update_ilosc" class="btn btn-info" style="padding: 5px 10px;">
                                    Ustaw
                                </button>
                            </form>
                            
                            <!-- Usuwanie -->
                            <a href="?delete=<?php echo $skladnik['id_skladnika']; ?>" 
                               class="btn btn-danger" 
                               style="padding: 5px 10px;"
                               onclick="return confirm('Czy na pewno usunąć ten składnik?')">
                                Usuń
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <?php if ($zapasy->num_rows === 0): ?>
            <div class="alert alert-info">Brak składników w magazynie. Dodaj pierwszy składnik powyżej.</div>
        <?php endif; ?>
        
        <div class="alert alert-info" style="margin-top: 30px;">
            <span style="color: #dc3545;">● Czerwony</span> - bardzo niski stan (poniżej 10)<br>
            <!-- TODO <span style="color: #dc9435ff;">● Pomarańczowy</span> - niski stan (poniżej 50)<br> -->
            <span style="color: #28a745;">● Zielony</span> - wystarczający stan
        </div>
    </div>
</body>
</html>