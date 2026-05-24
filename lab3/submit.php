<?php
// Подключение к базе данных
$host = 'localhost';
$dbname = 'u82306';  // твой логин
$username = 'u82306'; // твой логин
$password = '6691165';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Функция валидации
$errors = [];

// 1. ФИО
$full_name = trim($_POST['full_name'] ?? '');
if (empty($full_name)) {
    $errors[] = "ФИО обязательно для заполнения";
} elseif (strlen($full_name) > 150) {
    $errors[] = "ФИО не должно превышать 150 символов";
} elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $full_name)) {
    $errors[] = "ФИО может содержать только буквы, пробелы и дефис";
}

// 2. Телефон
$phone = trim($_POST['phone'] ?? '');
if (empty($phone)) {
    $errors[] = "Телефон обязателен для заполнения";
} elseif (!preg_match('/^(\+7|8)[0-9]{10}$/', $phone)) {
    $errors[] = "Телефон должен быть в формате +7XXXXXXXXXX или 8XXXXXXXXXX";
}

// 3. Email
$email = trim($_POST['email'] ?? '');
if (empty($email)) {
    $errors[] = "Email обязателен для заполнения";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Введите корректный email адрес";
}

// 4. Дата рождения
$birth_date = $_POST['birth_date'] ?? '';
if (empty($birth_date)) {
    $errors[] = "Дата рождения обязательна";
} else {
    $birth_timestamp = strtotime($birth_date);
    $age = date('Y') - date('Y', $birth_timestamp);
    if (date('md') < date('md', $birth_timestamp)) $age--;
    if ($age < 18 || $age > 100) {
        $errors[] = "Возраст должен быть от 18 до 100 лет";
    }
}

// 5. Пол
$gender = $_POST['gender'] ?? '';
if (!in_array($gender, ['male', 'female'])) {
    $errors[] = "Выберите корректный пол";
}

// 6. Языки программирования
$languages = $_POST['languages'] ?? [];
$valid_language_ids = range(1, 12);
if (empty($languages)) {
    $errors[] = "Выберите хотя бы один язык программирования";
} else {
    foreach ($languages as $lang_id) {
        if (!in_array($lang_id, $valid_language_ids)) {
            $errors[] = "Выбран некорректный язык программирования";
            break;
        }
    }
}

// 7. Биография (необязательное поле)
$biography = trim($_POST['biography'] ?? '');

// 8. Контракт
$contract_agreed = isset($_POST['contract_agreed']) ? 1 : 0;
if (!$contract_agreed) {
    $errors[] = "Вы должны согласиться с условиями контракта";
}

// Если есть ошибки - возвращаем на форму
if (!empty($errors)) {
    $error_message = implode(", ", $errors);
    $redirect_url = "index.html?error=" . urlencode($error_message);
    
    // Передаём заполненные данные обратно
    foreach ($_POST as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $val) {
                $redirect_url .= "&{$key}[]=" . urlencode($val);
            }
        } else {
            $redirect_url .= "&" . urlencode($key) . "=" . urlencode($value);
        }
    }
    header("Location: $redirect_url");
    exit;
}

// Сохранение в базу данных
try {
    // Начинаем транзакцию
    $pdo->beginTransaction();
    
    // Вставляем основную заявку
    $sql = "INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, contract_agreed) 
            VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract_agreed)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':full_name' => $full_name,
        ':phone' => $phone,
        ':email' => $email,
        ':birth_date' => $birth_date,
        ':gender' => $gender,
        ':biography' => $biography,
        ':contract_agreed' => $contract_agreed
    ]);
    
    $application_id = $pdo->lastInsertId();
    
    // Вставляем выбранные языки в таблицу связи
    $sql_lang = "INSERT INTO application_languages (application_id, language_id) VALUES (:app_id, :lang_id)";
    $stmt_lang = $pdo->prepare($sql_lang);
    
    foreach ($languages as $lang_id) {
        $stmt_lang->execute([
            ':app_id' => $application_id,
            ':lang_id' => $lang_id
        ]);
    }
    
    // Фиксируем транзакцию
    $pdo->commit();
    
    // Перенаправляем с сообщением об успехе
    header("Location: index.html?success=Данные успешно сохранены! Спасибо за заполнение анкеты.");
    exit;
    
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: index.html?error=Ошибка базы данных: " . urlencode($e->getMessage()));
    exit;
}
?>
