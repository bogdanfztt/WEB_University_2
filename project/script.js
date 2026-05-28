const API_BASE = '/WEB_University_2/project/api.php';

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('a[href^="#"]:not([href="#"])').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
    
    const form = document.getElementById('callbackForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            const alertDiv = document.getElementById('formAlert');
            
            const formData = {
                name: this.querySelector('input[name="name"]').value.trim(),
                phone: this.querySelector('input[name="phone"]').value.trim(),
                email: this.querySelector('input[name="email"]').value.trim(),
                message: this.querySelector('textarea[name="message"]').value.trim()
            };
            
            const errors = validateForm(formData);
            if (Object.keys(errors).length > 0) {
                showAlert(alertDiv, Object.values(errors).join('<br>'), 'danger');
                return;
            }
            
            submitBtn.innerHTML = 'Отправка...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch(API_BASE + '?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    showAlert(alertDiv, `
                        ✅ ${result.message}<br><br>
                        <strong>🔐 Ваши данные для входа:</strong><br>
                        📝 Логин: <strong>${result.login}</strong><br>
                        🔑 Пароль: <strong>${result.password}</strong><br><br>
                        <small>⚠️ Сохраните эти данные!</small>
                    `, 'success');
                    form.reset();
                    await loginUser(result.login, result.password);
                } else {
                    let errorMsg = result.error || 'Ошибка при отправке';
                    if (result.details) {
                        errorMsg += '<br>' + Object.values(result.details).join('<br>');
                    }
                    showAlert(alertDiv, '❌ ' + errorMsg, 'danger');
                }
            } catch (error) {
                showAlert(alertDiv, '❌ Ошибка соединения с сервером', 'danger');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }
    
    const loginBtn = document.getElementById('loginBtn');
    const loginInput = document.getElementById('loginInput');
    const passwordInput = document.getElementById('passwordInput');
    const loginAlert = document.getElementById('loginAlert');
    
    if (loginBtn) {
        loginBtn.addEventListener('click', async function() {
            const login = loginInput ? loginInput.value.trim() : '';
            const password = passwordInput ? passwordInput.value.trim() : '';
            
            if (!login || !password) {
                showLoginMessage('❌ Введите логин и пароль', 'danger');
                return;
            }
            
            loginBtn.disabled = true;
            loginBtn.innerHTML = 'Вход...';
            
            try {
                const response = await fetch(API_BASE + '?action=auth', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ login: login, password: password })
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    localStorage.setItem('auth_token', result.token);
                    localStorage.setItem('user_id', result.user.id);
                    showLoginMessage('✅ Вход выполнен успешно!', 'success');
                    showUserPanel(result.user);
                    
                    if (loginInput) loginInput.value = '';
                    if (passwordInput) passwordInput.value = '';
                    
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showLoginMessage('❌ ' + (result.error || 'Неверный логин или пароль'), 'danger');
                }
            } catch (error) {
                showLoginMessage('❌ Ошибка соединения с сервером', 'danger');
            } finally {
                loginBtn.disabled = false;
                loginBtn.innerHTML = 'Войти';
            }
        });
    }
    
    const navLoginBtn = document.getElementById('navLoginBtn');
    if (navLoginBtn) {
        navLoginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const loginSection = document.getElementById('loginSection');
            if (loginSection) {
                loginSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }
    
    const token = localStorage.getItem('auth_token');
    const userId = localStorage.getItem('user_id');
    if (token && userId) {
        loadUserData(userId, token);
    }
    
    const sections = document.querySelectorAll('section[id]');
    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop - 150;
            if (window.scrollY >= sectionTop) current = section.getAttribute('id');
        });
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) link.classList.add('active');
        });
    });
});

async function loginUser(login, password) {
    try {
        const response = await fetch(API_BASE + '?action=auth', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ login, password })
        });
        
        const result = await response.json();
        if (response.ok && result.success) {
            localStorage.setItem('auth_token', result.token);
            localStorage.setItem('user_id', result.user.id);
            showUserPanel(result.user);
        }
    } catch (error) {
        console.error('Auto-login failed:', error);
    }
}

async function loadUserData(userId, token) {
    try {
        const response = await fetch(API_BASE + `?action=getUser&id=${userId}`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const user = await response.json();
        if (response.ok && !user.error) {
            showUserPanel(user);
        } else {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_id');
        }
    } catch (error) {
        console.error('Load user failed:', error);
    }
}

function showUserPanel(user) {
    let panel = document.getElementById('userPanel');
    if (!panel) {
        panel = document.createElement('div');
        panel.id = 'userPanel';
        panel.style.cssText = `
            position: fixed; bottom: 20px; right: 20px; background: white;
            border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 15px; z-index: 1000; max-width: 300px;
            border-left: 4px solid #7c3aed; font-family: Inter, sans-serif;
        `;
        document.body.appendChild(panel);
    }
    
    panel.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <strong>👤 ${user.name || user.login}</strong>
            <button onclick="window.logout()" style="background: none; border: none; cursor: pointer; font-size: 18px;">✖️</button>
        </div>
        <div style="font-size: 13px; color: #666;">
            <div>📧 ${user.email}</div>
            <div>📞 ${user.phone}</div>
            <a href="#" onclick="window.showEditForm(); return false;" style="color: #7c3aed; text-decoration: none;">✏️ Редактировать</a>
            &nbsp;|&nbsp;
            <a href="#" onclick="window.viewProfile(); return false;" style="color: #7c3aed; text-decoration: none;">👁️ Профиль</a>
        </div>
    `;
}

window.logout = function() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_id');
    const panel = document.getElementById('userPanel');
    if (panel) panel.remove();
    location.reload();
};

window.viewProfile = async function() {
    const userId = localStorage.getItem('user_id');
    const token = localStorage.getItem('auth_token');
    
    if (!userId || !token) {
        alert('Необходима авторизация');
        return;
    }
    
    try {
        const response = await fetch(API_BASE + `?action=getUser&id=${userId}`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const user = await response.json();
        
        if (response.ok && !user.error) {
            alert(`📋 ВАШ ПРОФИЛЬ:\n\nID: ${user.id}\nЛогин: ${user.login}\nИмя: ${user.name}\nТелефон: ${user.phone}\nEmail: ${user.email}\nСообщение: ${user.message || '—'}\nЗарегистрирован: ${user.created_at}\nОбновлён: ${user.updated_at}`);
        } else {
            alert('Ошибка загрузки профиля');
        }
    } catch (error) {
        alert('Ошибка соединения');
    }
};

window.showEditForm = async function() {
    const userId = localStorage.getItem('user_id');
    const token = localStorage.getItem('auth_token');
    
    if (!userId || !token) {
        alert('Необходима авторизация');
        return;
    }
    
    let currentData = {};
    try {
        const response = await fetch(API_BASE + `?action=getUser&id=${userId}`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        currentData = await response.json();
    } catch (e) {}
    
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5); display: flex; align-items: center;
        justify-content: center; z-index: 1001;
    `;
    
    modal.innerHTML = `
        <div style="background: white; border-radius: 16px; padding: 24px; width: 400px; max-width: 90%;">
            <h3 style="margin-bottom: 20px;">Редактирование профиля</h3>
            <form id="editForm">
                <div class="mb-3">
                    <label class="form-label">Имя</label>
                    <input type="text" name="name" class="form-control" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 10px; width: 100%;" value="${escapeHtml(currentData.name || '')}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Телефон</label>
                    <input type="tel" name="phone" class="form-control" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 10px; width: 100%;" value="${escapeHtml(currentData.phone || '')}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 10px; width: 100%;" value="${escapeHtml(currentData.email || '')}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Сообщение</label>
                    <textarea name="message" class="form-control" rows="3" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 10px; width: 100%;">${escapeHtml(currentData.message || '')}</textarea>
                </div>
                <div id="editAlert"></div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 10px 20px; color: white; border-radius: 50px; cursor: pointer;">Сохранить</button>
                    <button type="button" onclick="this.closest('div').parentElement.remove()" style="background: transparent; border: 2px solid #7c3aed; padding: 10px 20px; border-radius: 50px; cursor: pointer;">Отмена</button>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    const editForm = modal.querySelector('#editForm');
    editForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const editAlert = modal.querySelector('#editAlert');
        
        const updateData = {
            name: this.querySelector('[name="name"]').value.trim(),
            phone: this.querySelector('[name="phone"]').value.trim(),
            email: this.querySelector('[name="email"]').value.trim(),
            message: this.querySelector('[name="message"]').value.trim()
        };
        
        try {
            const response = await fetch(API_BASE + `?action=updateUser&id=${userId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(updateData)
            });
            
            const result = await response.json();
            if (response.ok && result.success) {
                editAlert.innerHTML = '<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 8px;">✅ Данные обновлены!</div>';
                setTimeout(() => {
                    modal.remove();
                    location.reload();
                }, 1500);
            } else {
                editAlert.innerHTML = `<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px;">❌ ${result.error || 'Ошибка'}</div>`;
            }
        } catch (error) {
            editAlert.innerHTML = '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px;">❌ Ошибка соединения</div>';
        }
    });
};

function validateForm(data) {
    const errors = {};
    
    if (!data.name || data.name.trim().length < 2) {
        errors.name = 'Имя должно содержать минимум 2 символа';
    }
    
    const phoneClean = data.phone.replace(/[^0-9]/g, '');
    if (!data.phone || phoneClean.length < 10) {
        errors.phone = 'Введите корректный номер телефона (минимум 10 цифр)';
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!data.email || !emailRegex.test(data.email)) {
        errors.email = 'Введите корректный email';
    }
    
    return errors;
}

function showAlert(container, message, type) {
    if (!container) return;
    const bgColor = type === 'success' ? '#d4edda' : '#f8d7da';
    const textColor = type === 'success' ? '#155724' : '#721c24';
    container.innerHTML = `<div style="background: ${bgColor}; color: ${textColor}; padding: 15px; border-radius: 12px; margin-bottom: 20px;">${message}</div>`;
    setTimeout(() => {
        if (container.innerHTML.includes(message)) container.innerHTML = '';
    }, 15000);
}

function showLoginMessage(message, type) {
    const loginAlert = document.getElementById('loginAlert');
    if (!loginAlert) return;
    const bgColor = type === 'success' ? '#d4edda' : '#f8d7da';
    const textColor = type === 'success' ? '#155724' : '#721c24';
    loginAlert.innerHTML = `<div style="background: ${bgColor}; color: ${textColor}; padding: 12px; border-radius: 8px; margin-bottom: 15px;">${message}</div>`;
    setTimeout(() => {
        if (loginAlert.innerHTML.includes(message)) {
            loginAlert.innerHTML = '';
        }
    }, 5000);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}