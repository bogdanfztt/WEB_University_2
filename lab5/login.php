<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

$host = 'localhost';
$dbname = 'u82306';
$user = 'u82306';
$pass = '6691165';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}

// Если уже авторизован
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Вход в систему</title>
        <style>
            body { font-family: Arial; padding: 50px; }
            input { padding: 10px; margin: 5px; width: 250px; }
            button { padding: 10px 20px; }
        </style>
    </head>
    <body>
        <h2>Вход для редактирования данных</h2>
        <?php if (!empty($_GET['error'])): ?>
            <p style="color: red;">Неверный логин или пароль</p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="login" placeholder="Логин" required><br>
            <input type="password" name="password" placeholder="Пароль" required><br>
            <button type="submit">Войти</button>
        </form>
        <p><a href="index.php">← На главную</a></p>
    </body>
    </html>
    <?php
    exit;
}

// POST — проверка логина
$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE login = ?");
$stmt->execute([$login]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['login'] = $login;
    header('Location: index.php');
    exit;
} else {
    header('Location: login.php?error=1');
    exit;
}
?>
