-- Создание таблицы языков программирования
CREATE TABLE IF NOT EXISTS programming_languages (
    id INT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

-- Вставка языков
INSERT IGNORE INTO programming_languages (id, name) VALUES
(1, 'Pascal'), (2, 'C'), (3, 'C++'), (4, 'JavaScript'),
(5, 'PHP'), (6, 'Python'), (7, 'Java'), (8, 'Haskell'),
(9, 'Clojure'), (10, 'Prolog'), (11, 'Scala'), (12, 'Go');

-- Основная таблица заявок
CREATE TABLE IF NOT EXISTS applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    birth_date DATE NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    biography TEXT,
    contract_agreed TINYINT(1) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица связи (многие ко многим)
CREATE TABLE IF NOT EXISTS application_languages (
    application_id INT UNSIGNED NOT NULL,
    language_id INT NOT NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES programming_languages(id),
    PRIMARY KEY (application_id, language_id)
);
