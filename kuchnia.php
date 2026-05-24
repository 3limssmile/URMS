<?php
session_start();
require_once 'db_config.php';

$message = '';

// Oznacz jako w trakcie
if (isset($_POST['start_order'])) {
    $id_zamowienia = intval($_POST['id_zamowienia']);
    
    $stmt = $conn->prepare("UPDATE zamowienia SET status = 'w_trakcie' WHERE id_zamowienia = ?");
    $stmt->bind_param("i", $id_zamowienia);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Zamówienie oznaczone jako w trakcie realizacji</div>';
    }
}

// Oznacz jako gotowe
if (isset($_POST['complete_order'])) {
    $id_zamowienia = intval($_POST['id_zamowienia']);
    
    $conn->begin_transaction();
    
    try {
        // Pobierz pozycje zamówienia
        $stmt = $conn->prepare("SELECT pz.id_dania, pz.ilosc FROM pozycje_zamowienia pz WHERE pz.id_zamowienia = ?");
        $stmt->bind_param("i", $id_zamowienia);
        $stmt->execute();
        $pozycje = $stmt->get_result();
        
        // Odejmij składniki
        while ($pozycja = $pozycje->fetch_assoc()) {
            $id_dania = $pozycja['id_dania'];
            $ilosc_dan = $pozycja['ilosc'];
            
            // Pobierz składniki dania
            $stmt = $conn->prepare("SELECT id_skladnika, ilosc_wykorzystania FROM danie_skladniki WHERE id_dania = ?");
            $stmt->bind_param("i", $id_dania);
            $stmt->execute();
            $skladniki = $stmt->get_result();
            
            while ($skladnik = $skladniki->fetch_assoc()) {
                $id_skladnika = $skladnik['id_skladnika'];
                $ilosc_na_danie = $skladnik['ilosc_wykorzystania'];
                $total_wykorzystanie = $ilosc_na_danie * $ilosc_dan;
                
                // Odejmij od zapasów
                $stmt = $conn->prepare("UPDATE zapasy SET ilosc = ilosc - ? WHERE id_skladnika = ?");
                $stmt->bind_param("di", $total_wykorzystanie, $id_skladnika);
                $stmt->execute();
            }
        }
        
        // Oznacz zamówienie jako gotowe
        $stmt = $conn->prepare("UPDATE zamowienia SET status = 'gotowe' WHERE id_zamowienia = ?");
        $stmt->bind_param("i", $id_zamowienia);
        $stmt->execute();
        
        $conn->commit();
        $message = '<div class="alert alert-success">Zamówienie gotowe! Składniki zostały odjęte z zapasów.</div>';
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = '<div class="alert alert-error">Błąd: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Oznacz jako odebrane
if (isset($_POST['pickup_order'])) {
    $id_zamowienia = intval($_POST['id_zamowienia']);
    
    $stmt = $conn->prepare("UPDATE zamowienia SET status = 'odebrane' WHERE id_zamowienia = ?");
    $stmt->bind_param("i", $id_zamowienia);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Zamówienie oznaczone jako odebrane</div>';
    }
}

// Anuluj zamówienie
if (isset($_POST['cancel_order'])) {
    $id_zamowienia = intval($_POST['id_zamowienia']);
    
    $stmt = $conn->prepare("UPDATE zamowienia SET status = 'anulowane' WHERE id_zamowienia = ?");
    $stmt->bind_param("i", $id_zamowienia);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Zamówienie anulowane</div>';
    }
}

// Pobierz zamówienia
$zamowienia_result = $conn->query("
    SELECT z.*, 
           k.imie as kelner_imie, k.nazwisko as kelner_nazwisko,
           kl.imie as klient_imie, kl.nazwisko as klient_nazwisko
    FROM zamowienia z
    LEFT JOIN kelnerzy k ON z.id_kelnera = k.id_kelnera
    LEFT JOIN klienci kl ON z.id_klienta = kl.id_klienta
    WHERE z.status IN ('nowe', 'w_trakcie', 'gotowe')
    ORDER BY 
        CASE z.status 
            WHEN 'w_trakcie' THEN 1
            WHEN 'nowe' THEN 2
            WHEN 'gotowe' THEN 3
        END,
        z.data_zamowienia ASC
");
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Kuchni</title>
    <link rel="stylesheet" href="style.css">
    <meta http-equiv="refresh" content="30">
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <ul>
                <li><a href="index.php">Menu Główne</a></li>
                <li><a href="kolejka_display.php">Zobacz Kolejkę</a></li>
            </ul>
        </div>

        <h1>Panel realizacji zamówień</h1>
        
        <div class="alert alert-info">
            Strona odświeża się automatycznie co 30 sekund
        </div>
        
        <?php echo $message; ?>

        <div class="order-grid">
            <?php while ($zamowienie = $zamowienia_result->fetch_assoc()): ?>
                <div class="order-card" style="border-color: <?php 
                    echo $zamowienie['status'] === 'nowe' ? '#007bff' : 
                         ($zamowienie['status'] === 'w_trakcie' ? '#ffc107' : '#28a745'); 
                ?>;">
                    <div class="order-number">#<?php echo $zamowienie['numer_kolejkowy']; ?></div>
                    
                    <div style="text-align: center; margin-bottom: 15px;">
                        <span class="badge badge-<?php echo $zamowienie['status']; ?>">
                            <?php echo strtoupper($zamowienie['status']); ?>
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong>Metoda:</strong> <?php echo $zamowienie['metoda'] === 'kelner' ? '👨‍🍳 Kelner' : '💻 Online'; ?><br>
                        
                        <?php if ($zamowienie['kelner_imie']): ?>
                            <strong>Kelner:</strong> <?php echo htmlspecialchars($zamowienie['kelner_imie'] . ' ' . $zamowienie['kelner_nazwisko']); ?><br>
                        <?php endif; ?>
                        
                        <?php if ($zamowienie['klient_imie']): ?>
                            <strong>Klient:</strong> <?php echo htmlspecialchars($zamowienie['klient_imie'] . ' ' . $zamowienie['klient_nazwisko']); ?><br>
                        <?php endif; ?>
                        
                        <strong>Czas:</strong> <?php echo date('H:i', strtotime($zamowienie['data_zamowienia'])); ?><br>
                        <strong>Cena:</strong> <?php echo number_format($zamowienie['laczna_cena'], 2); ?> zł
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                        <strong>Pozycje:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <?php
                            $stmt = $conn->prepare("
                                SELECT d.nazwa, pz.ilosc 
                                FROM pozycje_zamowienia pz
                                JOIN dania d ON pz.id_dania = d.id_dania
                                WHERE pz.id_zamowienia = ?
                            ");
                            $stmt->bind_param("i", $zamowienie['id_zamowienia']);
                            $stmt->execute();
                            $pozycje = $stmt->get_result();
                            
                            while ($pozycja = $pozycje->fetch_assoc()):
                            ?>
                                <li><?php echo $pozycja['ilosc']; ?>× <?php echo htmlspecialchars($pozycja['nazwa']); ?></li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="id_zamowienia" value="<?php echo $zamowienie['id_zamowienia']; ?>">
                        
                        <?php if ($zamowienie['status'] === 'nowe'): ?>
                            <button type="submit" name="start_order" class="btn btn-warning" style="width: 100%; margin-bottom: 5px;">
                                Rozpocznij Przygotowanie
                            </button>
                        <?php elseif ($zamowienie['status'] === 'w_trakcie'): ?>
                            <button type="submit" name="complete_order" class="btn btn-success" style="width: 100%; margin-bottom: 5px;">
                                Oznacz jako Gotowe
                            </button>
                        <?php elseif ($zamowienie['status'] === 'gotowe'): ?>
                            <button type="submit" name="pickup_order" class="btn btn-info" style="width: 100%; margin-bottom: 5px;">
                                Wydano Klientowi
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($zamowienie['status'] !== 'gotowe'): ?>
                            <button type="submit" name="cancel_order" class="btn btn-danger" style="width: 100%;" 
                                    onclick="return confirm('Czy na pewno anulować zamówienie?')">
                                Anuluj
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php if ($zamowienia_result->num_rows === 0): ?>
            <div class="alert alert-info" style="text-align: center; margin-top: 50px;">
                <h2>Brak zamówień do realizacji</h2>
                <p>Wszystkie zamówienia zostały zrealizowane!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>