<?php
$host = 'localhost';
$dbname = 'u82306';
$username = 'u82306';
$password = '6691165';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Валидация
    $errors = [];
    if (empty($_POST['full_name'])) $errors[] = "ФИО обязательно";
    if (empty($_POST['phone'])) $errors[] = "Телефон обязателен";
    if (empty($_POST['email'])) $errors[] = "Email обязателен";
    if (empty($_POST['birth_date'])) $errors[] = "Дата рождения обязательна";
    if (empty($_POST['gender'])) $errors[] = "Пол обязателен";
    if (empty($_POST['languages'])) $errors[] = "Выберите хотя бы один язык";
    if (!isset($_POST['contract_agreed'])) $errors[] = "Необходимо согласие с контрактом";
    
    if (!empty($errors)) {
        $error_str = implode(', ', $errors);
        header("Location: index.php?error=" . urlencode($error_str) . "&" . http_build_query($_POST));
        exit;
    }
    
    // Сохранение
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, contract_agreed) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['full_name'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['birth_date'],
        $_POST['gender'],
        $_POST['biography'] ?? '',
        isset($_POST['contract_agreed']) ? 1 : 0
    ]);
    $app_id = $pdo->lastInsertId();
    
    $lang_stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($_POST['languages'] as $lang_id) {
        $lang_stmt->execute([$app_id, $lang_id]);
    }
    
    $pdo->commit();
    header("Location: index.php?success=Данные сохранены! ID заявки: $app_id");
    exit;
    
} catch (PDOException $e) {
    if (isset($pdo)) $pdo->rollBack();
    header("Location: index.php?error=Ошибка БД: " . urlencode($e->getMessage()));
    exit;
}
?>