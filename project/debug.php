<!DOCTYPE html>
<html>
<head>
    <title>Отладка API</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: green; background: #e0ffe0; padding: 10px; margin: 10px 0; }
        .error { color: red; background: #ffe0e0; padding: 10px; margin: 10px 0; }
        pre { background: #333; color: #0f0; padding: 15px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        input, textarea { display: block; margin: 10px 0; padding: 8px; width: 300px; }
    </style>
</head>
<body>
    <h1>🔧 Отладка API WebCore</h1>
    
    <div style="border: 1px solid #ccc; padding: 20px; margin: 20px 0; background: white;">
        <h2>1. Список всех пользователей</h2>
        <button onclick="showUsers()">Показать пользователей</button>
        <div id="usersList"></div>
    </div>

    <div style="border: 1px solid #ccc; padding: 20px; margin: 20px 0; background: white;">
        <h2>2. Регистрация нового пользователя</h2>
        <input type="text" id="regName" placeholder="Имя" value="Тестовый Пользователь">
        <input type="text" id="regPhone" placeholder="Телефон" value="9991234567">
        <input type="email" id="regEmail" placeholder="Email" value="test@test.ru">
        <textarea id="regMessage" placeholder="Сообщение (опционально)"></textarea>
        <button onclick="registerUser()">Зарегистрироваться</button>
        <div id="regResult"></div>
    </div>

    <div style="border: 1px solid #ccc; padding: 20px; margin: 20px 0; background: white;">
        <h2>3. Вход в систему</h2>
        <input type="text" id="login" placeholder="Логин">
        <input type="password" id="password" placeholder="Пароль">
        <button onclick="loginUser()">Войти</button>
        <div id="loginResult"></div>
    </div>

    <div style="border: 1px solid #ccc; padding: 20px; margin: 20px 0; background: white;">
        <h2>4. Просмотр профиля (нужен вход)</h2>
        <button onclick="viewProfile()">Показать мой профиль</button>
        <div id="profileResult"></div>
    </div>

    <script>
        const API_BASE = '/project/api.php';
        
        async function showUsers() {
            try {
                const response = await fetch('/project/users.json?_=' + Date.now());
                const data = await response.json();
                if (data && data.users) {
                    let html = '<div class="success">✅ Найдено пользователей: ' + data.users.length + '</div>';
                    html += '<pre>';
                    data.users.forEach(u => {
                        html += `ID: ${u.id} | Логин: ${u.login} | Имя: ${u.name} | Email: ${u.email}\n`;
                    });
                    html += '</pre>';
                    document.getElementById('usersList').innerHTML = html;
                } else {
                    document.getElementById('usersList').innerHTML = '<div class="error">❌ Пользователей нет</div>';
                }
            } catch(e) {
                document.getElementById('usersList').innerHTML = '<div class="error">❌ Ошибка: ' + e.message + '</div>';
            }
        }
        
        async function registerUser() {
            const data = {
                name: document.getElementById('regName').value,
                phone: document.getElementById('regPhone').value,
                email: document.getElementById('regEmail').value,
                message: document.getElementById('regMessage').value
            };
            
            try {
                const response = await fetch(API_BASE + '?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    document.getElementById('regResult').innerHTML = `
                        <div class="success">
                            ✅ Успешно!<br>
                            <strong>Логин:</strong> ${result.login}<br>
                            <strong>Пароль:</strong> ${result.password}<br>
                            <strong>ID:</strong> ${result.id}<br>
                            <span style="color: red;">⚠️ Сохраните эти данные!</span>
                        </div>
                    `;
                } else {
                    document.getElementById('regResult').innerHTML = `<div class="error">❌ ${result.error}</div>`;
                }
            } catch(e) {
                document.getElementById('regResult').innerHTML = `<div class="error">❌ Ошибка: ${e.message}</div>`;
            }
        }
        
        async function loginUser() {
            const login = document.getElementById('login').value;
            const password = document.getElementById('password').value;
            
            if (!login || !password) {
                document.getElementById('loginResult').innerHTML = '<div class="error">❌ Введите логин и пароль</div>';
                return;
            }
            
            try {
                const response = await fetch(API_BASE + '?action=auth', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ login, password })
                });
                const result = await response.json();
                if (result.success) {
                    // Сохраняем токен в localStorage
                    localStorage.setItem('auth_token', result.token);
                    localStorage.setItem('user_id', result.user.id);
                    document.getElementById('loginResult').innerHTML = `
                        <div class="success">
                            ✅ Вход выполнен!<br>
                            Добро пожаловать, ${result.user.name}!<br>
                            Токен сохранён в localStorage.
                        </div>
                    `;
                } else {
                    document.getElementById('loginResult').innerHTML = `<div class="error">❌ ${result.error}</div>`;
                }
            } catch(e) {
                document.getElementById('loginResult').innerHTML = `<div class="error">❌ Ошибка: ${e.message}</div>`;
            }
        }
        
        async function viewProfile() {
            const userId = localStorage.getItem('user_id');
            const token = localStorage.getItem('auth_token');
            
            if (!userId || !token) {
                document.getElementById('profileResult').innerHTML = '<div class="error">❌ Сначала войдите в систему</div>';
                return;
            }
            
            try {
                const response = await fetch(API_BASE + `?action=getUser&id=${userId}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const user = await response.json();
                if (!user.error) {
                    document.getElementById('profileResult').innerHTML = `
                        <div class="success">
                            ✅ Профиль пользователя:<br>
                            <pre>${JSON.stringify(user, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    document.getElementById('profileResult').innerHTML = `<div class="error">❌ ${user.error}</div>`;
                }
            } catch(e) {
                document.getElementById('profileResult').innerHTML = `<div class="error">❌ Ошибка: ${e.message}</div>`;
            }
        }
    </script>
</body>
</html>