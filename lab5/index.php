<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

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

// ---- GET ----
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = [];
    $errors = [];
    $values = [];

    // Успешное сохранение
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        $messages[] = '<div class="success-message">✅ Данные успешно сохранены!</div>';
        if (!empty($_COOKIE['login']) && !empty($_COOKIE['pass'])) {
            $messages[] = '<div class="success-message">🔑 Ваш логин: <strong>' . htmlspecialchars($_COOKIE['login']) . '</strong>, пароль: <strong>' . htmlspecialchars($_COOKIE['pass']) . '</strong>. <a href="login.php">Войдите</a>, чтобы редактировать данные.</div>';
            setcookie('login', '', 100000);
            setcookie('pass', '', 100000);
        }
    }

    // Если авторизован — загружаем его последнюю заявку
    if (!empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($app) {
            $values = $app;
            // Загружаем языки
            $lang_stmt = $pdo->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
            $lang_stmt->execute([$app['id']]);
            $values['languages'] = $lang_stmt->fetchAll(PDO::FETCH_COLUMN);
            $messages[] = '<div class="success-message">🔓 Вы авторизованы. Редактируйте свои данные.</div>';
        }
    } else {
        // Чтение ошибок и значений из Cookies
        $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_agreed'];
        foreach ($fields as $field) {
            $errors[$field] = !empty($_COOKIE[$field . '_error']);
            $values[$field] = $_COOKIE[$field . '_value'] ?? '';
            if ($errors[$field]) {
                setcookie($field . '_error', '', 100000);
            }
        }
        $errors['languages'] = !empty($_COOKIE['languages_error']);
        if ($errors['languages']) {
            setcookie('languages_error', '', 100000);
            $messages[] = '<div class="error-message">⚠️ Выберите хотя бы один язык программирования.</div>';
        }
        $values['languages'] = [];
        for ($i = 1; $i <= 12; $i++) {
            if (!empty($_COOKIE["lang_{$i}_value"])) {
                $values['languages'][] = $i;
            }
        }
    }

    include('form.php');
    exit;
}

// ---- POST ----
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = false;

    // Валидация (как в лабе 4)
    // Full Name
    if (empty($_POST['full_name']) || !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]{1,150}$/u', $_POST['full_name'])) {
        setcookie('full_name_error', '1', time() + 86400);
        $errors = true;
    } else {
        setcookie('full_name_value', $_POST['full_name'], time() + 365 * 86400);
    }

    // Phone
    if (empty($_POST['phone']) || !preg_match('/^(\+7|8)\d{10}$/', $_POST['phone'])) {
        setcookie('phone_error', '1', time() + 86400);
        $errors = true;
    } else {
        setcookie('phone_value', $_POST['phone'], time() + 365 * 86400);
    }

    // Email
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 86400);
        $errors = true;
    } else {
        setcookie('email_value', $_POST['email'], time() + 365 * 86400);
    }

    // Birth date
    $birth_date = $_POST['birth_date'] ?? '';
    $age = date('Y') - date('Y', strtotime($birth_date));
    if (date('md') < date('md', strtotime($birth_date))) $age--;
    if (empty($birth_date) || $age < 18 || $age > 100) {
        setcookie('birth_date_error', '1', time() + 86400);
        $errors = true;
    } else {
        setcookie('birth_date_value', $birth_date, time() + 365 * 86400);
    }

    // Gender
    $gender = $_POST['gender'] ?? '';
    if (!in_array($gender, ['male', 'female'])) {
        setcookie('gender_error', '1', time() + 86400);
        $errors = true;
    } else {
        setcookie('gender_value', $gender, time() + 365 * 86400);
    }

    // Languages
    $languages = $_POST['languages'] ?? [];
    if (empty($languages)) {
        setcookie('languages_error', '1', time() + 86400);
        $errors = true;
    } else {
        foreach ($languages as $lang_id) {
            setcookie("lang_{$lang_id}_value", $lang_id, time() + 365 * 86400);
        }
    }

    setcookie('biography_value', $_POST['biography'] ?? '', time() + 365 * 86400);

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

    // Авторизован или нет?
    $user_id = $_SESSION['user_id'] ?? null;

    // Если не авторизован — создаём нового пользователя
    if (!$user_id) {
        $login = 'user_' . bin2hex(random_bytes(4));
        $password = bin2hex(random_bytes(3));
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
        $stmt->execute([$login, $password_hash]);
        $user_id = $pdo->lastInsertId();

        setcookie('login', $login, time() + 30);
        setcookie('pass', $password, time() + 30);
    }

    // Сохранение/обновление заявки
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Обновление
        $stmt = $pdo->prepare("UPDATE applications SET full_name=?, phone=?, email=?, birth_date=?, gender=?, biography=?, contract_agreed=? WHERE user_id=?");
        $stmt->execute([$_POST['full_name'], $_POST['phone'], $_POST['email'], $_POST['birth_date'], $_POST['gender'], $_POST['biography'] ?? '', 1, $user_id]);
        $app_id = $existing['id'];
        // Удаляем старые языки
        $pdo->prepare("DELETE FROM application_languages WHERE application_id=?")->execute([$app_id]);
    } else {
        // Вставка
        $stmt = $pdo->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, contract_agreed, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['full_name'], $_POST['phone'], $_POST['email'], $_POST['birth_date'], $_POST['gender'], $_POST['biography'] ?? '', 1, $user_id]);
        $app_id = $pdo->lastInsertId();
    }

    // Вставка языков
    $lang_stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($languages as $lang_id) {
        $lang_stmt->execute([$app_id, $lang_id]);
    }

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
