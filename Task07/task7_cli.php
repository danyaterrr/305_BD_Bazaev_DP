<?php
// Подключение к БД через PDO
// Автоматическое создание БД при первом запуске
if (!file_exists('sto.db')) {
    exec('sqlite3 sto.db ".read db_init.sql"');
}
try {
    $pdo = new PDO('sqlite:sto.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Вывод списка мастеров
echo "Доступные мастера:\n";
echo str_repeat("-", 60) . "\n";
$stmt = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name");
$masters = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($masters as $master) {
    echo sprintf("%-5d | %s\n", $master['id'], $master['full_name']);
}
echo str_repeat("-", 60) . "\n\n";

// Запрос номера мастера
echo "Введите номер мастера для фильтрации (Enter для всех): ";
$input = trim(fgets(STDIN));

// Валидация ввода
$employee_id = null;
if ($input !== '') {
    if (!ctype_digit($input)) {
        die("Ошибка: номер должен быть целым числом\n");
    }
    $employee_id = (int)$input;
    
    // Проверка существования мастера
    $check = $pdo->prepare("SELECT id FROM employees WHERE id = ?");
    $check->execute([$employee_id]);
    if (!$check->fetch()) {
        die("Ошибка: мастер с номером $employee_id не найден\n");
    }
}

// Формирование запроса
$sql = "SELECT 
    e.id AS employee_id,
    e.full_name,
    DATE(a.appointment_datetime) AS work_date,
    s.name AS service_name,
    aps.actual_price
FROM appointment_services aps
JOIN appointments a ON aps.appointment_id = a.id
JOIN employees e ON a.employee_id = e.id
JOIN services s ON aps.service_id = s.id
WHERE aps.is_completed = 1";

if ($employee_id !== null) {
    $sql .= " AND e.id = :employee_id";
}

$sql .= " ORDER BY e.full_name, a.appointment_datetime";

// Выполнение запроса
$stmt = $pdo->prepare($sql);
if ($employee_id !== null) {
    $stmt->execute([':employee_id' => $employee_id]);
} else {
    $stmt->execute();
}

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Вывод таблицы
echo "\n";
if (empty($results)) {
    echo "Нет данных для отображения\n";
} else {
    // Заголовок таблицы
    echo "+-----+----------------------------+------------+---------------------------+--------+\n";
    echo "| ID  | ФИО мастера              | Дата       | Услуга                    | Цена   |\n";
    echo "+-----+----------------------------+------------+---------------------------+--------+\n";
    
    // Данные
    foreach ($results as $row) {
        echo sprintf(
            "| %-3d | %-26s | %-10s | %-25s | %6.2f |\n",
            $row['employee_id'],
            mb_substr($row['full_name'], 0, 26),
            $row['work_date'],
            mb_substr($row['service_name'], 0, 25),
            $row['actual_price']
        );
    }
    
    echo "+-----+----------------------------+------------+---------------------------+--------+\n";
    echo "Всего записей: " . count($results) . "\n";
}