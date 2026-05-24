<?php
require_once 'db_config.php';

$numer = intval($_GET['numer'] ?? 0);

if ($numer === 0) {
    header('Location: index.php');
    exit;
}

// Pobierz szczegóły zamówienia
$stmt = $conn->prepare("
    SELECT z.*, k.imie, k.nazwisko, k.telefon
    FROM zamowienia z
    LEFT JOIN klienci k ON z.id_klienta = k.id_klienta
    WHERE z.numer_kolejkowy = ? AND DATE(z.data_zamowienia) = CURDATE()
");
$stmt->bind_param("i", $numer);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$zamowienie = $result->fetch_assoc();

// Pobierz pozycje zamówienia
$stmt = $conn->prepare("
    SELECT d.nazwa, pz.ilosc, d.cena
    FROM pozycje_zamowienia pz
    JOIN dania d ON pz.id_dania = d.id_dania
    WHERE pz.id_zamowienia = ?
");
$stmt->bind_param("i", $zamowienie['id_zamowienia']);
$stmt->execute();
$pozycje = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potwierdzenie Zamówienia</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .confirmation-box {
            max-width: 600px;
            margin: 50px auto;
            text-align: center;
            background: #f8f9fa;
            padding: 40px;
            border-radius: 15px;
            border: 3px solid #28a745;
        }
        
        .order-number-big {
            font-size: 80px;
            font-weight: bold;
            color: #28a745;
            margin: 20px 0;
        }
        
        .success-icon {
            font-size: 100px;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirmation-box">
            <div class="success-icon">✓</div>
            <h1>Zamówienie Przyjęte!</h1>
            
            <p style="font-size: 1.3em; margin: 20px 0;">Twój numer zamówienia:</p>
            
            <div class="order-number-big"><?php echo $numer; ?></div>
            
            <div class="alert alert-success" style="margin: 30px 0;">
                <strong>Zapamiętaj swój numer!</strong><br>
                Będzie wyświetlony na tablicy, gdy zamówienie będzie gotowe.
            </div>
            
            <div style="text-align: left; background: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <h3>Szczegóły zamówienia:</h3>
                <ul style="list-style: none; padding: 0;">
                    <?php while ($poz = $pozycje->fetch_assoc()): ?>
                        <li style="padding: 5px 0; border-bottom: 1px solid #eee;">
                            <strong><?php echo $poz['ilosc']; ?>×</strong> 
                            <?php echo htmlspecialchars($poz['nazwa']); ?>
                            <span style="float: right;"><?php echo number_format($poz['cena'] * $poz['ilosc'], 2); ?> zł</span>
                        </li>
                    <?php endwhile; ?>
                </ul>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #333; font-size: 1.3em;">
                    <strong>Razem: <?php echo number_format($zamowienie['laczna_cena'], 2); ?> zł</strong>
                </div>
            </div>
            
            <?php if ($zamowienie['imie']): ?>
                <p style="margin-top: 20px;">
                    <strong>Zamówienie na:</strong><br>
                    <?php echo htmlspecialchars($zamowienie['imie'] . ' ' . $zamowienie['nazwisko']); ?><br>
                    <?php echo htmlspecialchars($zamowienie['telefon']); ?>
                </p>
            <?php endif; ?>
            
            <div style="margin-top: 30px;">
                <a href="kolejka_display.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.2em;">
                    Zobacz Kolejkę Zamówień
                </a>
            </div>
            
            <div style="margin-top: 20px;">
                <a href="kiosk.php" class="btn btn-secondary">Złóż kolejne zamówienie</a>
                <a href="index.php" class="btn btn-secondary">Menu główne</a>
            </div>
        </div>
    </div>
</body>
</html>