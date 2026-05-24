<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Лабораторная 3 - Анкета</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        form { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .required:after { content: " *"; color: red; }
        input[type="text"], input[type="tel"], input[type="email"], input[type="date"], textarea, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        .radio-group { display: flex; gap: 20px; margin-top: 8px; }
        .radio-group label { display: flex; align-items: center; gap: 8px; font-weight: normal; }
        select[multiple] { height: 120px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin: 20px 0; }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .error-message { background: #fee; color: #c33; padding: 12px; border-radius: 8px; margin: 20px 30px 0 30px; border-left: 4px solid #c33; }
        .success-message { background: #efe; color: #3a6; padding: 12px; border-radius: 8px; margin: 20px 30px 0 30px; border-left: 4px solid #3a6; }
        small { color: #666; font-size: 12px; display: block; margin-top: 5px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📝 Анкета участника</h1>
        <p>Заполните все обязательные поля</p>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-message">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">✅ <?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>

    <form action="submit.php" method="POST">
        <div class="form-group">
            <label class="required">ФИО</label>
            <input type="text" name="full_name" required value="<?= htmlspecialchars($_GET['full_name'] ?? '') ?>">
            <small>Только буквы, пробелы и дефис. Не более 150 символов.</small>
        </div>

        <div class="form-group">
            <label class="required">Телефон</label>
            <input type="tel" name="phone" required value="<?= htmlspecialchars($_GET['phone'] ?? '') ?>">
            <small>Формат: +7XXXXXXXXXX или 8XXXXXXXXXX</small>
        </div>

        <div class="form-group">
            <label class="required">Email</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="required">Дата рождения</label>
            <input type="date" name="birth_date" required value="<?= htmlspecialchars($_GET['birth_date'] ?? '') ?>">
            <small>Возраст от 18 до 100 лет</small>
        </div>

        <div class="form-group">
            <label class="required">Пол</label>
            <div class="radio-group">
                <label><input type="radio" name="gender" value="male" <?= (($_GET['gender'] ?? '') == 'male') ? 'checked' : '' ?>> Мужской</label>
                <label><input type="radio" name="gender" value="female" <?= (($_GET['gender'] ?? '') == 'female') ? 'checked' : '' ?>> Женский</label>
            </div>
        </div>

        <div class="form-group">
            <label class="required">Любимые языки программирования</label>
            <select name="languages[]" multiple required>
                <option value="1">Pascal</option><option value="2">C</option><option value="3">C++</option>
                <option value="4">JavaScript</option><option value="5">PHP</option><option value="6">Python</option>
                <option value="7">Java</option><option value="8">Haskell</option><option value="9">Clojure</option>
                <option value="10">Prolog</option><option value="11">Scala</option><option value="12">Go</option>
            </select>
            <small>Зажмите Cmd (Mac) или Ctrl (Win) для выбора нескольких языков</small>
        </div>

        <div class="form-group">
            <label>Биография</label>
            <textarea name="biography" rows="4"><?= htmlspecialchars($_GET['biography'] ?? '') ?></textarea>
        </div>

        <div class="checkbox-group">
            <input type="checkbox" name="contract_agreed" value="1" <?= isset($_GET['contract_agreed']) ? 'checked' : '' ?>>
            <label class="required">Я ознакомлен с контрактом и согласен</label>
        </div>

        <button type="submit">💾 Сохранить</button>
    </form>
</div>
</body>
</html>