<?php
// Подключение к БД
try {
    $pdo = new PDO('sqlite:sto.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Получение списка мастеров для фильтра
$employees = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name")
                 ->fetchAll(PDO::FETCH_ASSOC);

// Обработка выбранного мастера
$selected_id = isset($_GET['employee_id']) && $_GET['employee_id'] !== '' 
    ? (int)$_GET['employee_id'] 
    : null;

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

if ($selected_id !== null) {
    $sql .= " AND e.id = :employee_id";
}

$sql .= " ORDER BY e.full_name, a.appointment_datetime";

$stmt = $pdo->prepare($sql);
if ($selected_id !== null) {
    $stmt->execute([':employee_id' => $selected_id]);
} else {
    $stmt->execute();
}

$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оказанные услуги СТО</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .filter-form { margin-bottom: 20px; }
        select { padding: 5px; margin-right: 10px; }
        button { padding: 5px 15px; }
    </style>
</head>
<body>
    <h1>Оказанные услуги СТО</h1>
    
    <div class="filter-form">
        <form method="GET">
            <label for="employee_id">Фильтр по мастеру:</label>
            <select name="employee_id" id="employee_id">
                <option value="">Все мастера</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= htmlspecialchars($emp['id']) ?>" 
                            <?= $selected_id == $emp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Применить</button>
        </form>
    </div>

    <?php if (empty($services)): ?>
        <p>Нет данных для отображения</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID мастера</th>
                    <th>ФИО мастера</th>
                    <th>Дата работы</th>
                    <th>Услуга</th>
                    <th>Стоимость (₽)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['employee_id']) ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['work_date']) ?></td>
                        <td><?= htmlspecialchars($row['service_name']) ?></td>
                        <td><?= number_format($row['actual_price'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><strong>Всего записей:</strong> <?= count($services) ?></p>
    <?php endif; ?>
</body>
</html>