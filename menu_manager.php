<?php
session_start();
require_once 'db_config.php';

$message = '';

// Dodawanie nowego dania
if (isset($_POST['add_danie'])) {
    $nazwa = $conn->real_escape_string($_POST['nazwa']);
    $cena = floatval($_POST['cena']);
    $opis = $conn->real_escape_string($_POST['opis']);
    $image = $_POST['image'] ?? null;
    $dostepnosc = isset($_POST['dostepnosc']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO dania (nazwa, cena, opis, dostepnosc, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sdsis", $nazwa, $cena, $opis, $dostepnosc, $image);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Danie zostało dodane!</div>';
    } else {
        $message = '<div class="alert alert-error">Błąd dodawania dania</div>';
    }
}

// Edycja dania
if (isset($_POST['edit_danie'])) {
    $id_dania = intval($_POST['id_dania']);
    $nazwa = $conn->real_escape_string($_POST['nazwa']);
    $cena = floatval($_POST['cena']);
    $opis = $conn->real_escape_string($_POST['opis']);
    $image = $_POST['image'] ?? null;
    $dostepnosc = isset($_POST['dostepnosc']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE dania SET nazwa = ?, cena = ?, opis = ?, dostepnosc = ?, image = ? WHERE id_dania = ?");
    $stmt->bind_param("sdsisi", $nazwa, $cena, $opis, $dostepnosc, $image, $id_dania);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Danie zaktualizowane!</div>';
    }
}

// Usuwanie dania
if (isset($_GET['delete_danie'])) {
    $id_dania = intval($_GET['delete_danie']);
    
    $conn->begin_transaction();
    try {
        // Usuń składniki dania
        $stmt = $conn->prepare("DELETE FROM danie_skladniki WHERE id_dania = ?");
        $stmt->bind_param("i", $id_dania);
        $stmt->execute();
        
        // Usuń danie
        $stmt = $conn->prepare("DELETE FROM dania WHERE id_dania = ?");
        $stmt->bind_param("i", $id_dania);
        $stmt->execute();
        
        $conn->commit();
        $message = '<div class="alert alert-success">Danie zostało usunięte!</div>';
    } catch (Exception $e) {
        $conn->rollback();
        $message = '<div class="alert alert-error">Nie można usunąć dania - może być używane w zamówieniach</div>';
    }
}

// Dodawanie składnika do dania
if (isset($_POST['add_skladnik_to_danie'])) {
    $id_dania = intval($_POST['id_dania']);
    $id_skladnika = intval($_POST['id_skladnika']);
    $ilosc_wykorzystania = floatval($_POST['ilosc_wykorzystania']);
    
    // Sprawdź czy składnik już istnieje w daniu
    $stmt = $conn->prepare("SELECT * FROM danie_skladniki WHERE id_dania = ? AND id_skladnika = ?");
    $stmt->bind_param("ii", $id_dania, $id_skladnika);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Aktualizuj istniejący
        $stmt = $conn->prepare("UPDATE danie_skladniki SET ilosc_wykorzystania = ? WHERE id_dania = ? AND id_skladnika = ?");
        $stmt->bind_param("dii", $ilosc_wykorzystania, $id_dania, $id_skladnika);
    } else {
        // Dodaj nowy
        $stmt = $conn->prepare("INSERT INTO danie_skladniki (id_dania, id_skladnika, ilosc_wykorzystania) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $id_dania, $id_skladnika, $ilosc_wykorzystania);
    }
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Składnik dodany do dania!</div>';
    }
}

// Usuwanie składnika z dania
if (isset($_GET['remove_skladnik'])) {
    $id_dania = intval($_GET['id_dania']);
    $id_skladnika = intval($_GET['id_skladnika']);
    
    $stmt = $conn->prepare("DELETE FROM danie_skladniki WHERE id_dania = ? AND id_skladnika = ?");
    $stmt->bind_param("ii", $id_dania, $id_skladnika);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Składnik usunięty z dania!</div>';
    }
}

// Pobierz dania
$dania = $conn->query("SELECT * FROM dania ORDER BY nazwa");

// Pobierz składniki
$skladniki = $conn->query("SELECT * FROM zapasy ORDER BY nazwa");
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie Menu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <ul>
                <li><a href="index.php">Menu Główne</a></li>
                <li><a href="zapasy.php">Zarządzaj Zapasami</a></li>
            </ul>
        </div>

        <h1>Zarządzanie Menu i Składnikami</h1>
        
        <?php echo $message; ?>

        <!-- Formularz dodawania dania -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <h2>Dodaj Nowe Danie</h2>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                    <div>
                        <div class="form-group">
                            <label>Nazwa dania *</label>
                            <input type="text" name="nazwa" required>
                        </div>
                        <div class="form-group">
                            <label>Opis</label>
                            <textarea name="opis"></textarea>
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label>Cena (zł) *</label>
                            <input type="number" step="0.01" name="cena" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="dostepnosc" checked>
                                Dostępne
                            </label>
                        </div>
                        <div class="form-group">
                            <label>Obrazek (Ścieżka/URL)</label>
                            <input type="text" name="image">
                        <button type="submit" name="add_danie" class="btn btn-success" style="width: 100%;">
                            Dodaj Danie
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Lista dań -->
        <h2>Lista Dań</h2>
        <?php 
        $dania->data_seek(0); // Reset wskaźnika
        while ($danie = $dania->fetch_assoc()): 
        ?>
            <div class="menu-item" style="margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                    <!-- Informacje o daniu -->
                    <div>
                        <h3>
                            <?php echo htmlspecialchars($danie['nazwa']); ?>
                            <?php if (!$danie['dostepnosc']): ?>
                                <span class="badge badge-anulowane">NIEDOSTĘPNE</span>
                            <?php endif; ?>
                        </h3>
                        <p><?php echo htmlspecialchars($danie['opis'] ?? 'Brak opisu'); ?></p>
                        <strong style="font-size: 1.3em; color: #28a745;">
                            <?php echo number_format($danie['cena'], 2); ?> zł
                        </strong>
                        
                        <!-- Składniki dania -->
                        <div style="margin-top: 15px; background: white; padding: 15px; border-radius: 5px;">
                            <strong>Składniki:</strong>
                            <?php
                            $stmt = $conn->prepare("
                                SELECT ds.*, z.nazwa, z.jednostka 
                                FROM danie_skladniki ds
                                JOIN zapasy z ON ds.id_skladnika = z.id_skladnika
                                WHERE ds.id_dania = ?
                            ");
                            $stmt->bind_param("i", $danie['id_dania']);
                            $stmt->execute();
                            $skladniki_dania = $stmt->get_result();
                            
                            if ($skladniki_dania->num_rows > 0):
                            ?>
                                <ul style="margin: 10px 0 0 20px;">
                                    <?php while ($sk = $skladniki_dania->fetch_assoc()): ?>
                                        <li>
                                            <?php echo htmlspecialchars($sk['nazwa']); ?>: 
                                            <strong><?php echo $sk['ilosc_wykorzystania']; ?> <?php echo $sk['jednostka']; ?></strong>
                                            <a href="?remove_skladnik=1&id_dania=<?php echo $danie['id_dania']; ?>&id_skladnika=<?php echo $sk['id_skladnika']; ?>" 
                                               style="color: #dc3545; margin-left: 10px;"
                                               onclick="return confirm('Usunąć składnik?')">
                                                [usuń]
                                            </a>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <p style="color: #999; margin: 10px 0;">Brak zdefiniowanych składników</p>
                            <?php endif; ?>
                            
                            <!-- Dodaj składnik do dania -->
                            <form method="POST" style="margin-top: 15px; display: grid; grid-template-columns: 2fr 1fr auto; gap: 10px;">
                                <input type="hidden" name="id_dania" value="<?php echo $danie['id_dania']; ?>">
                                <select name="id_skladnika" required>
                                    <option value="">-- Wybierz składnik --</option>
                                    <?php
                                    $skladniki->data_seek(0);
                                    while ($sk = $skladniki->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $sk['id_skladnika']; ?>">
                                            <?php echo htmlspecialchars($sk['nazwa']); ?> (<?php echo $sk['jednostka']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <input type="number" step="0.01" name="ilosc_wykorzystania" placeholder="Ilość" required>
                                <button type="submit" name="add_skladnik_to_danie" class="btn btn-primary">
                                    + Dodaj składnik
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Akcje -->
                    <div>
                        <form method="POST">
                            <input type="hidden" name="id_dania" value="<?php echo $danie['id_dania']; ?>">
                            
                            <div class="form-group">
                                <label>Nazwa</label>
                                <input type="text" name="nazwa" value="<?php echo htmlspecialchars($danie['nazwa']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Cena (zł)</label>
                                <input type="number" step="0.01" name="cena" value="<?php echo $danie['cena']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Opis</label>
                                <textarea name="opis"><?php echo htmlspecialchars($danie['opis'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Obrazek (Ścieżka/URL)</label>
                                <input type="text" name="image" value="<?php echo htmlspecialchars($danie['image'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="dostepnosc" <?php echo $danie['dostepnosc'] ? 'checked' : ''; ?>>
                                    Dostępne
                                </label>
                            </div>
                            
                            <button type="submit" name="edit_danie" class="btn btn-info" style="width: 100%; margin-bottom: 10px;">
                                Zapisz Zmiany
                            </button>
                            
                            <a href="?delete_danie=<?php echo $danie['id_dania']; ?>" 
                               class="btn btn-danger" 
                               style="width: 100%; display: block; text-align: center;"
                               onclick="return confirm('Czy na pewno usunąć to danie?')">
                                Usuń Danie
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
        
        <?php if ($dania->num_rows === 0): ?>
            <div class="alert alert-info">Brak dań w menu. Dodaj pierwsze danie powyżej.</div>
        <?php endif; ?>
    </div>
</body>
</html>