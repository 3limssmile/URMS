<?php
require_once 'db_config.php';

// Pobierz zamówienia gotowe i w trakcie z CURRENTDATE
$gotowe = $conn->query("
    SELECT numer_kolejkowy, data_zamowienia 
    FROM zamowienia 
    WHERE status = 'gotowe' 
    AND DATE(data_zamowienia) = CURDATE()
    ORDER BY numer_kolejkowy
");

$w_trakcie = $conn->query("
    SELECT numer_kolejkowy, data_zamowienia 
    FROM zamowienia 
    WHERE status IN ('nowe', 'w_trakcie') 
    AND DATE(data_zamowienia) = CURDATE()
    ORDER BY numer_kolejkowy
");
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolejka Zamówień</title>
    <link rel="stylesheet" href="style.css">
    <meta http-equiv="refresh" content="5">
    <style>
        body {
            background: #1a1a1a;
            color: white;
        }
        
        .container {
            background: #2d2d2d;
            max-width: 100%;
            padding: 40px;
        }
        
        .section {
            margin-bottom: 50px;
        }
        
        .section h2 {
            font-size: 3em;
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 10px;
        }
        
        .ready-section h2 {
            background: #28a745;
            color: white;
        }
        
        .preparing-section h2 {
            background: #ffc107;
            color: #333;
        }
        
        .numbers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .number-box {
            background: #3d3d3d;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            font-size: 4em;
            font-weight: bold;
            border: 3px solid;
        }
        
        .ready-number {
            border-color: #28a745;
            color: #28a745;
        }
        
        .preparing-number {
            border-color: #ffc107;
            color: #ffc107;
        }
        
        .empty-message {
            text-align: center;
            font-size: 2em;
            color: #666;
            padding: 50px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .header h1 {
            font-size: 4em;
            color: white;
        }
        
        .last-update {
            text-align: center;
            color: #999;
            font-size: 1.2em;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>KOLEJKA ZAMÓWIEŃ</h1>
        </div>
        
        <!-- Zamówienia gotowe -->
        <div class="section ready-section">
            <h2>GOTOWE DO ODBIORU</h2>
            <div class="numbers-grid">
                <?php 
                $count_gotowe = 0;
                while ($z = $gotowe->fetch_assoc()): 
                    $count_gotowe++;
                ?>
                    <div class="number-box ready-number">
                        <?php echo $z['numer_kolejkowy']; ?>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <?php if ($count_gotowe === 0): ?>
                <div class="empty-message">Brak zamówień gotowych do odbioru</div>
            <?php endif; ?>
        </div>
        
        <!-- Zamówienia w przygotowaniu -->
        <div class="section preparing-section">
            <h2>W PRZYGOTOWANIU</h2>
            <div class="numbers-grid">
                <?php 
                $count_w_trakcie = 0;
                while ($z = $w_trakcie->fetch_assoc()): 
                    $count_w_trakcie++;
                ?>
                    <div class="number-box preparing-number">
                        <?php echo $z['numer_kolejkowy']; ?>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <?php if ($count_w_trakcie === 0): ?>
                <div class="empty-message">Brak zamówień w przygotowaniu</div>
            <?php endif; ?>
        </div>
        
        <div class="last-update">
            Ostatnia aktualizacja: <?php echo date('H:i:s'); ?>
        </div>
    </div>
</body>
</html>