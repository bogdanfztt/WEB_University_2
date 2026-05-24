<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Лабораторная 4 - Анкета с Cookies</title>
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
        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        .error-field { border: 2px solid red !important; background-color: #ffe6e6; }
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
        <h1>📝 Анкета участника (с Cookies)</h1>
        <p>Заполните все обязательные поля</p>
    </div>

    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $msg): ?>
            <?= $msg ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <form action="index.php" method="POST">
        <div class="form-group">
            <label class="required">ФИО</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($values['full_name'] ?? '') ?>" class="<?= $errors['full_name'] ? 'error-field' : '' ?>">
            <small>Только буквы, пробелы и дефис. Не более 150 символов.</small>
        </div>

        <div class="form-group">
            <label class="required">Телефон</label>
            <input type="tel" name="phone" value="<?= htmlspecialchars($values['phone'] ?? '') ?>" class="<?= $errors['phone'] ? 'error-field' : '' ?>">
            <small>Формат: +7XXXXXXXXXX или 8XXXXXXXXXX</small>
        </div>

        <div class="form-group">
            <label class="required">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($values['email'] ?? '') ?>" class="<?= $errors['email'] ? 'error-field' : '' ?>">
        </div>

        <div class="form-group">
            <label class="required">Дата рождения</label>
            <input type="date" name="birth_date" value="<?= htmlspecialchars($values['birth_date'] ?? '') ?>" class="<?= $errors['birth_date'] ? 'error-field' : '' ?>">
            <small>Возраст от 18 до 100 лет</small>
        </div>

        <div class="form-group">
            <label class="required">Пол</label>
            <div class="radio-group">
                <label><input type="radio" name="gender" value="male" <?= (($values['gender'] ?? '') == 'male') ? 'checked' : '' ?>> Мужской</label>
                <label><input type="radio" name="gender" value="female" <?= (($values['gender'] ?? '') == 'female') ? 'checked' : '' ?>> Женский</label>
            </div>
            <?php if ($errors['gender']): ?>
                <small style="color: red;">Выберите пол</small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="required">Любимые языки программирования</label>
            <select name="languages[]" multiple class="<?= $errors['languages'] ? 'error-field' : '' ?>">
                <option value="1" <?= in_array(1, $values['languages'] ?? []) ? 'selected' : '' ?>>Pascal</option>
                <option value="2" <?= in_array(2, $values['languages'] ?? []) ? 'selected' : '' ?>>C</option>
                <option value="3" <?= in_array(3, $values['languages'] ?? []) ? 'selected' : '' ?>>C++</option>
                <option value="4" <?= in_array(4, $values['languages'] ?? []) ? 'selected' : '' ?>>JavaScript</option>
                <option value="5" <?= in_array(5, $values['languages'] ?? []) ? 'selected' : '' ?>>PHP</option>
                <option value="6" <?= in_array(6, $values['languages'] ?? []) ? 'selected' : '' ?>>Python</option>
                <option value="7" <?= in_array(7, $values['languages'] ?? []) ? 'selected' : '' ?>>Java</option>
                <option value="8" <?= in_array(8, $values['languages'] ?? []) ? 'selected' : '' ?>>Haskell</option>
                <option value="9" <?= in_array(9, $values['languages'] ?? []) ? 'selected' : '' ?>>Clojure</option>
                <option value="10" <?= in_array(10, $values['languages'] ?? []) ? 'selected' : '' ?>>Prolog</option>
                <option value="11" <?= in_array(11, $values['languages'] ?? []) ? 'selected' : '' ?>>Scala</option>
                <option value="12" <?= in_array(12, $values['languages'] ?? []) ? 'selected' : '' ?>>Go</option>
            </select>
            <small>Зажмите Cmd (Mac) или Ctrl (Win) для выбора нескольких языков</small>
        </div>

        <div class="form-group">
            <label>Биография</label>
            <textarea name="biography" rows="4"><?= htmlspecialchars($values['biography'] ?? '') ?></textarea>
        </div>

        <div class="checkbox-group">
            <input type="checkbox" name="contract_agreed" value="1" <?= isset($values['contract_agreed']) && $values['contract_agreed'] ? 'checked' : '' ?> class="<?= $errors['contract_agreed'] ? 'error-field' : '' ?>">
            <label class="required">Я ознакомлен с контрактом и согласен</label>
        </div>

        <button type="submit">💾 Сохранить</button>
    </form>
</div>
</body>
</html>
