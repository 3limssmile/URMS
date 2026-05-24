<?php
session_start();
require_once 'db_config.php';

// Inicjalizacja koszyka
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';

// Obsługa dodawania do koszyka
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $id_dania = intval($_POST['id_dania']);
    
    if (isset($_SESSION['cart'][$id_dania])) {
        $_SESSION['cart'][$id_dania]++;
    } else {
        $_SESSION['cart'][$id_dania] = 1;
    }
    $message = '<div class="alert alert-success">Dodano do koszyka!</div>';
}

// Obsługa usuwania z koszyka
if (isset($_GET['remove'])) {
    $id_dania = intval($_GET['remove']);
    unset($_SESSION['cart'][$id_dania]);
    header('Location: kiosk.php');
    exit;
}

// Obsługa zmiany ilości
if (isset($_POST['update_quantity'])) {
    $id_dania = intval($_POST['id_dania']);
    $quantity = intval($_POST['quantity']);
    
    if ($quantity > 0) {
        $_SESSION['cart'][$id_dania] = $quantity;
    } else {
        unset($_SESSION['cart'][$id_dania]);
    }
    header('Location: kiosk.php');
    exit;
}

// Obsługa składania zamówienia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!empty($_SESSION['cart'])) {
        $conn->begin_transaction();
        
        try {
            // Pobierz dane klienta
            $imie = $conn->real_escape_string($_POST['imie']);
            $nazwisko = $conn->real_escape_string($_POST['nazwisko']);
            $telefon = $conn->real_escape_string($_POST['telefon']);
            $email = $conn->real_escape_string($_POST['email']);
            
            // Dodaj lub znajdź klienta
            $stmt = $conn->prepare("SELECT id_klienta FROM klienci WHERE telefon = ?");
            $stmt->bind_param("s", $telefon);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $klient = $result->fetch_assoc();
                $id_klienta = $klient['id_klienta'];
            } else {
                $stmt = $conn->prepare("INSERT INTO klienci (imie, nazwisko, email, telefon) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $imie, $nazwisko, $email, $telefon);
                $stmt->execute();
                $id_klienta = $conn->insert_id;
            }
            
            // Generuj numer kolejkowy
            $result = $conn->query("SELECT MAX(numer_kolejkowy) as max_num FROM zamowienia WHERE DATE(data_zamowienia) = CURDATE()");
            $row = $result->fetch_assoc();
            $numer_kolejkowy = ($row['max_num'] ?? 0) + 1;
            
            // Oblicz łączną cenę
            $laczna_cena = 0;
            foreach ($_SESSION['cart'] as $id_dania => $ilosc) {
                $stmt = $conn->prepare("SELECT cena FROM dania WHERE id_dania = ?");
                $stmt->bind_param("i", $id_dania);
                $stmt->execute();
                $result = $stmt->get_result();
                $danie = $result->fetch_assoc();
                $laczna_cena += $danie['cena'] * $ilosc;
            }
            
            // Utwórz zamówienie
            $stmt = $conn->prepare("INSERT INTO zamowienia (id_klienta, numer_kolejkowy, metoda, status, laczna_cena) VALUES (?, ?, 'online', 'nowe', ?)");
            $stmt->bind_param("iid", $id_klienta, $numer_kolejkowy, $laczna_cena);
            $stmt->execute();
            $id_zamowienia = $conn->insert_id;
            
            // Dodaj pozycje zamówienia
            foreach ($_SESSION['cart'] as $id_dania => $ilosc) {
                $stmt = $conn->prepare("INSERT INTO pozycje_zamowienia (id_zamowienia, id_dania, ilosc) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $id_zamowienia, $id_dania, $ilosc);
                $stmt->execute();
            }
            
            $conn->commit();
            $_SESSION['cart'] = [];
            
            header("Location: zamowienie_potwierdzenie.php?numer=$numer_kolejkowy");
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = '<div class="alert alert-error">Błąd składania zamówienia: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Pobierz dostępne dania
$dania_result = $conn->query("SELECT * FROM dania WHERE dostepnosc = 1 ORDER BY nazwa");
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiosk - Zamów Online</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <ul>
                <li><a href="index.php">Menu Główne</a></li>
                <li><a href="kolejka_display.php">Zobacz Kolejkę</a></li>
            </ul>
        </div>

        <h1>Złóż Zamówienie</h1>
        
        <?php echo $message; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <!-- Menu -->
            <div>
                <h2>Menu</h2>
                <?php while ($danie = $dania_result->fetch_assoc()): ?>
                    <div class="menu-item">
                        <img src="<?php echo htmlspecialchars($danie['image'] ?? 'images/deafult.png'); ?>" alt="<?php echo htmlspecialchars($danie['nazwa']); ?>" style="width: 67px; height: 67px; float: left; border-radius: 10px; margin-right: 15px; object-fit: cover;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3><?php echo htmlspecialchars($danie['nazwa']); ?></h3>
                                <p><?php echo htmlspecialchars($danie['opis'] ?? ''); ?></p>
                                <strong><?php echo number_format($danie['cena'], 2); ?> zł</strong>
                            </div>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="id_dania" value="<?php echo $danie['id_dania']; ?>">
                                <button type="submit" name="add_to_cart" class="btn btn-primary">Dodaj</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Koszyk -->
            <div>
                <h2>Koszyk</h2>
                <?php if (empty($_SESSION['cart'])): ?>
                    <p>Koszyk jest pusty</p>
                <?php else: ?>
                    <?php
                    $total = 0;
                    foreach ($_SESSION['cart'] as $id_dania => $ilosc):
                        $stmt = $conn->prepare("SELECT * FROM dania WHERE id_dania = ?");
                        $stmt->bind_param("i", $id_dania);
                        $stmt->execute();
                        $danie = $stmt->get_result()->fetch_assoc();
                        $subtotal = $danie['cena'] * $ilosc;
                        $total += $subtotal;
                    ?>
                        <div class="cart-item">
                            <div>
                                <strong><?php echo htmlspecialchars($danie['nazwa']); ?></strong><br>
                                <small><?php echo number_format($danie['cena'], 2); ?> zł × <?php echo $ilosc; ?></small>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="id_dania" value="<?php echo $id_dania; ?>">
                                    <input type="number" name="quantity" value="<?php echo $ilosc; ?>" min="0" style="width: 60px;">
                                    <button type="submit" name="update_quantity" class="btn btn-primary" style="padding: 5px 10px;">OK</button>
                                </form>
                                <a href="?remove=<?php echo $id_dania; ?>" class="btn btn-danger" style="padding: 5px 10px;">×</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="total-price">
                        Razem: <?php echo number_format($total, 2); ?> zł
                    </div>

                    <form method="POST" style="margin-top: 20px;">
                        <h3>Dane do zamówienia</h3>
                        <div class="form-group">
                            <label>Imię *</label>
                            <input type="text" name="imie" required>
                        </div>
                        <div class="form-group">
                            <label>Nazwisko *</label>
                            <input type="text" name="nazwisko" required>
                        </div>
                        <div class="form-group">
                            <label>Telefon *</label>
                            <input type="tel" name="telefon" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email">
                        </div>
                        <button type="submit" name="place_order" class="btn btn-success" style="width: 100%;">
                            Złóż Zamówienie
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>