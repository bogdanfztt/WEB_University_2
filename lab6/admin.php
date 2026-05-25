<?php
session_start();
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

// HTTP Basic Authentication (логин admin, пароль admin123)
$admin_login = 'admin';
$admin_password = 'admin123';

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] !== $admin_login ||
    !password_verify($_SERVER['PHP_AUTH_PW'], password_hash($admin_password, PASSWORD_DEFAULT))) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    echo '<h1>401 Требуется авторизация</h1>';
    echo '<p>Доступ разрешён только администратору.</p>';
    exit;
}

// Обработка редактирования заявки
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    $id = $_POST['edit_id'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $birth_date = $_POST['birth_date'];
    $gender = $_POST['gender'];
    $biography = trim($_POST['biography']);
    $contract_agreed = isset($_POST['contract_agreed']) ? 1 : 0;
    $languages = $_POST['languages'] ?? [];

    $stmt = $pdo->prepare("UPDATE applications SET full_name=?, phone=?, email=?, birth_date=?, gender=?, biography=?, contract_agreed=? WHERE id=?");
    $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, $contract_agreed, $id]);

    // Обновляем языки
    $pdo->prepare("DELETE FROM application_languages WHERE application_id=?")->execute([$id]);
    $lang_stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
    foreach ($languages as $lang_id) {
        $lang_stmt->execute([$id, $lang_id]);
    }

    header('Location: admin.php?msg=edited');
    exit;
}

// Обработка удаления заявки
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM application_languages WHERE application_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM applications WHERE id=?")->execute([$id]);
    header('Location: admin.php?msg=deleted');
    exit;
}

// Получаем статистику по языкам программирования
$lang_stats = $pdo->query("
    SELECT pl.id, pl.name, COUNT(al.language_id) as count
    FROM programming_languages pl
    LEFT JOIN application_languages al ON pl.id = al.language_id
    GROUP BY pl.id
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Получаем все заявки
$applications = $pdo->query("
    SELECT a.*, u.login as user_login
    FROM applications a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Загрузка данных для редактирования
$edit_application = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$id]);
    $edit_application = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_application) {
        $lang_stmt = $pdo->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
        $lang_stmt->execute([$edit_application['id']]);
        $edit_application['languages'] = $lang_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Список языков для формы
$languages_list = [
    1 => 'Pascal', 2 => 'C', 3 => 'C++', 4 => 'JavaScript',
    5 => 'PHP', 6 => 'Python', 7 => 'Java', 8 => 'Haskell',
    9 => 'Clojure', 10 => 'Prolog', 11 => 'Scala', 12 => 'Go'
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель - Лабораторная 6</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        h1, h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .stat-item {
            background: #667eea;
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-item strong {
            font-size: 24px;
            display: block;
        }
        table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #2c3e50;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            margin: 0 3px;
            border: none;
            cursor: pointer;
        }
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-back {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            margin-top: 20px;
            display: inline-block;
        }
        .edit-form {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .edit-form input, .edit-form select, .edit-form textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        .edit-form select[multiple] {
            height: 120px;
        }
        .edit-form button {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            cursor: pointer;
        }
        .form-group {
            margin-bottom: 10px;
        }
        .form-group label {
            font-weight: 600;
        }
        .admin-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }
        .badge {
            background: #e74c3c;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>👑 Панель администратора</h1>

    <?php if (isset($_GET['msg'])): ?>
        <div class="message">
            <?php if ($_GET['msg'] == 'edited'): ?>✅ Данные успешно обновлены.<?php endif; ?>
            <?php if ($_GET['msg'] == 'deleted'): ?>🗑️ Заявка удалена.<?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Статистика по языкам -->
    <div class="stats-card">
        <h2>📊 Статистика по языкам программирования</h2>
        <div class="stats-grid">
            <?php foreach ($lang_stats as $stat): ?>
                <div class="stat-item">
                    <strong><?= htmlspecialchars($stat['name']) ?></strong>
                    <?= $stat['count'] ?> пользователей
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Форма редактирования -->
    <?php if ($edit_application): ?>
        <div class="edit-form">
            <h2>✏️ Редактирование заявки #<?= $edit_application['id'] ?></h2>
            <form method="POST">
                <input type="hidden" name="edit_id" value="<?= $edit_application['id'] ?>">
                <div class="form-group">
                    <label>ФИО</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($edit_application['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($edit_application['phone']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($edit_application['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Дата рождения</label>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars($edit_application['birth_date']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Пол</label>
                    <select name="gender" required>
                        <option value="male" <?= $edit_application['gender'] == 'male' ? 'selected' : '' ?>>Мужской</option>
                        <option value="female" <?= $edit_application['gender'] == 'female' ? 'selected' : '' ?>>Женский</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Языки программирования (Ctrl/Cmd + клик для выбора нескольких)</label>
                    <select name="languages[]" multiple required>
                        <?php foreach ($languages_list as $id => $name): ?>
                            <option value="<?= $id ?>" <?= in_array($id, $edit_application['languages'] ?? []) ? 'selected' : ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Биография</label>
                    <textarea name="biography" rows="4"><?= htmlspecialchars($edit_application['biography'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="contract_agreed" value="1" <?= $edit_application['contract_agreed'] ? 'checked' : '' ?>>
                        Согласен с контрактом
                    </label>
                </div>
                <button type="submit">💾 Сохранить изменения</button>
                <a href="admin.php" style="margin-left: 15px;">❌ Отмена</a>
            </form>
        </div>
    <?php endif; ?>

    <!-- Таблица всех заявок -->
    <h2>📋 Все заявки пользователей</h2>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Пользователь</th><th>ФИО</th><th>Телефон</th><th>Email</th>
                    <th>Дата рождения</th><th>Пол</th><th>Биография</th><th>Контракт</th><th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?= $app['id'] ?></td>
                    <td><?= htmlspecialchars($app['user_login'] ?? 'Гость') ?></td>
                    <td><?= htmlspecialchars($app['full_name']) ?></td>
                    <td><?= htmlspecialchars($app['phone']) ?></td>
                    <td><?= htmlspecialchars($app['email']) ?></td>
                    <td><?= $app['birth_date'] ?></td>
                    <td><?= $app['gender'] == 'male' ? 'Мужской' : 'Женский' ?></td>
                    <td><?= htmlspecialchars(substr($app['biography'] ?? '', 0, 50)) ?>...</td>
                    <td><?= $app['contract_agreed'] ? '✅ Да' : '❌ Нет' ?></td>
                    <td>
                        <a href="admin.php?edit=<?= $app['id'] ?>" class="btn btn-edit">✏️</a>
                        <a href="admin.php?delete=<?= $app['id'] ?>" class="btn btn-delete" onclick="return confirm('Удалить заявку?')">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($applications)): ?>
                <tr><td colspan="10" style="text-align: center;">Нет заявок</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="../lab5/index.php" class="admin-link">← На главную (форма)</a>
</div>
</body>
</html>
