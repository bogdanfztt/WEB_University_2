<?php
header('Content-Type: text/html; charset=UTF-8');

// Подключение к БД
$host = 'localhost';
$dbname = 'u82306';
$user = 'u82306';
$pass = '6691165';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = [];
    $errors = [];
    $values = [];

    // Успешное сохранение
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        $messages[] = '<div class="success-message">✅ Данные успешно сохранены!</div>';
    }

    // Чтение ошибок и значений из Cookies
    $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_agreed'];
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        $values[$field] = $_COOKIE[$field . '_value'] ?? '';
        if ($errors[$field]) {
            setcookie($field . '_error', '', 100000);
            setcookie($field . '_value', '', 100000);
        }
    }

    // Ошибки для языков
    $errors['languages'] = !empty($_COOKIE['languages_error']);
    if ($errors['languages']) {
        setcookie('languages_error', '', 100000);
        $messages[] = '<div class="error-message">⚠️ Выберите хотя бы один язык программирования.</div>';
    }

    // Заполнение языков из Cookie
    $values['languages'] = [];
    for ($i = 1; $i <= 12; $i++) {
        if (!empty($_COOKIE["lang_{$i}_value"])) {
            $values['languages'][] = $i;
        }
    }

    // Подключение формы
    include('form.php');
    exit;
}

// ---------- POST ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = false;

    // Валидация Full Name
    if (empty($_POST['full_name']) || !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]{1,150}$/u', $_POST['full_name'])) {
        setcookie('full_name_error', '1', time() + 86400);
        $errors = true;
    } else {
        setcookie('full_name_value', $_POST['full_name'], time() + 365 * 86400);
    }

    // Валидация телефона
    if (empty($_POST['phone']) || !preg_match('/^(\+7|8)\d{10}$/', $_POST['phone'])) {
        setcookie('phone_error', '1', time() + 86400);
        $errors = true;
    } else {
        setcookie('phone_value', $_POST['phone'], time() + 365 * 86400);
    }

    // Валидация email
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 86400);
        $errors = true;
    } else {
        setcookie('email_value', $_POST['email'], time() + 365 * 86400);
    }

    // Валидация даты рождения (возраст 18-100 лет)
    $birth_date = $_POST['birth_date'] ?? '';
    $age = date('Y') - date('Y', strtotime($birth_date));
    if (date('md') < date('md', strtotime($birth_date))) $age--;
    if (empty($birth_date) || $age < 18 || $age > 100) {
        setcookie('birth_date_error', '1', time() + 86400);
        $errors = true;
    } else {
        setcookie('birth_date_value', $birth_date, time() + 365 * 86400);
    }

    // Валидация пола
    $gender = $_POST['gender'] ?? '';
    if (!in_array($gender, ['male', 'female'])) {
        setcookie('gender_error', '1', time() + 86400);
        $errors = true;
    } else {
        setcookie('gender_value', $gender, time() + 365 * 86400);
    }

    // Валидация языков
    $languages = $_POST['languages'] ?? [];
    if (empty($languages)) {
        setcookie('languages_error', '1', time() + 86400);
        $errors = true;
    } else {
        foreach ($languages as $lang_id) {
            setcookie("lang_{$lang_id}_value", $lang_id, time() + 365 * 86400);
        }
    }

    // Биография (необязательное)
    setcookie('biography_value', $_POST['biography'] ?? '', time() + 365 * 86400);

    // Контракт
    if (empty($_POST['contract_agreed'])) {
        setcookie('contract_agreed_error', '1', time() + 86400);
        $errors = true;
    } else {
        setcookie('contract_agreed_value', '1', time() + 365 * 86400);
    }

    if ($errors) {
        header('Location: index.php');
        exit;
    }

    // --- Сохранение в БД ---
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, contract_agreed) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['full_name'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['birth_date'],
        $_POST['gender'],
        $_POST['biography'] ?? '',
        1
    ]);
    $app_id = $pdo->lastInsertId();

    $lang_stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($languages as $lang_id) {
        $lang_stmt->execute([$app_id, $lang_id]);
    }
    $pdo->commit();

    // Очистка Cookies ошибок
    $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'contract_agreed'];
    foreach ($fields as $field) {
        setcookie($field . '_error', '', 100000);
        setcookie($field . '_value', '', 100000);
    }
    setcookie('languages_error', '', 100000);

    setcookie('save', '1', time() + 30);
    header('Location: index.php');
    exit;
}
?>
