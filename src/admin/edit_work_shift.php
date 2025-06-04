<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$shiftId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные смены
$stmt = $pdo->prepare("
    SELECT ws.*, 
           e.first_name, e.last_name, 
           wst.shift_name, wst.start_time, wst.end_time,
           r.route_number, r.route_name
    FROM Work_shifts ws
    JOIN Employees e ON ws.id_employee = e.id_employee
    JOIN Work_shift_types wst ON ws.id_work_shift_type = wst.id_work_shift_type
    LEFT JOIN Routes r ON ws.id_route = r.id_route
    WHERE ws.id_work_shift = ?
");
$stmt->execute([$shiftId]);
$shift = $stmt->fetch();

if (!$shift) {
  $_SESSION['shift_error'] = "Смена не найдена";
  header('Location: work_shifts.php');
  exit;
}

// Получаем данные для форм
$employees = $pdo->query("
    SELECT e.*, p.role 
    FROM Employees e
    JOIN Employee_positions p ON e.id_position = p.id_position
    WHERE p.role = 'водитель' AND e.active = 1
    ORDER BY e.last_name, e.first_name
")->fetchAll();

$shiftTypes = $pdo->query("SELECT * FROM Work_shift_types ORDER BY shift_name")->fetchAll();
$routes = $pdo->query("SELECT * FROM Routes WHERE active = 1 ORDER BY route_number")->fetchAll();

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $errors = [];

  $required = [
    'id_employee' => 'Водитель',
    'id_work_shift_type' => 'Тип смены',
    'shift_date' => 'Дата смены'
  ];

  foreach ($required as $field => $name) {
    if (empty($_POST[$field])) {
      $errors[] = "Поле '$name' обязательно для заполнения";
    }
  }

  // Проверка, что сотрудник - водитель
  if (empty($errors)) {
    $stmt = $pdo->prepare("
            SELECT p.role 
            FROM Employees e
            JOIN Employee_positions p ON e.id_position = p.id_position
            WHERE e.id_employee = ? AND p.role = 'водитель'
        ");
    $stmt->execute([$_POST['id_employee']]);
    if (!$stmt->fetch()) {
      $errors[] = "Выбранный сотрудник не является водителем";
    }
  }

  // Проверка на дублирование смены (исключая текущую)
  if (empty($errors)) {
    $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM Work_shifts 
            WHERE id_employee = ? 
              AND shift_date = ? 
              AND id_work_shift != ?
        ");
    $stmt->execute([$_POST['id_employee'], $_POST['shift_date'], $shiftId]);
    if ($stmt->fetchColumn() > 0) {
      $errors[] = "У этого водителя уже есть смена на указанную дату";
    }
  }

  if (empty($errors)) {
    try {
      $stmt = $pdo->prepare("
                UPDATE Work_shifts SET
                    id_work_shift_type = ?,
                    id_employee = ?,
                    shift_date = ?,
                    id_route = ?
                WHERE id_work_shift = ?
            ");

      $stmt->execute([
        $_POST['id_work_shift_type'],
        $_POST['id_employee'],
        $_POST['shift_date'],
        !empty($_POST['id_route']) ? $_POST['id_route'] : null,
        $shiftId
      ]);

      $_SESSION['shift_success'] = "Смена успешно обновлена";
      header('Location: work_shifts.php');
      exit;
    } catch (PDOException $e) {
      $errors[] = "Ошибка базы данных: " . $e->getMessage();
    }
  }

  if (!empty($errors)) {
    $_SESSION['shift_errors'] = $errors;
  }
}

$title = "Редактирование рабочей смены";
$userName = $_SESSION['user']['name'];
$role = $_SESSION['user']['role'];
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <title><?= $title ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
  <?php include '../includes/admin_navbar.php'; ?>

  <div class="container py-4">
    <h1 class="mb-4"><?= $title ?></h1>

    <?php if (isset($_SESSION['shift_errors'])): ?>
      <div class="alert alert-danger">
        <?php foreach ($_SESSION['shift_errors'] as $error): ?>
          <p class="mb-0"><?= $error ?></p>
        <?php endforeach; ?>
      </div>
      <?php unset($_SESSION['shift_errors']); ?>
    <?php endif; ?>

    <form method="POST">
      <div class="card">
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Водитель*</label>
            <select name="id_employee" class="form-select" required>
              <option value="">Выберите водителя</option>
              <?php foreach ($employees as $employee): ?>
                <option value="<?= $employee['id_employee'] ?>"
                  <?= $employee['id_employee'] == $shift['id_employee'] ? 'selected' : '' ?>>
                  <?= $employee['last_name'] ?> <?= $employee['first_name'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Тип смены*</label>
            <select name="id_work_shift_type" class="form-select" required>
              <option value="">Выберите тип смены</option>
              <?php foreach ($shiftTypes as $type): ?>
                <option value="<?= $type['id_work_shift_type'] ?>"
                  <?= $type['id_work_shift_type'] == $shift['id_work_shift_type'] ? 'selected' : '' ?>>
                  <?= $type['shift_name'] ?> (<?= substr($type['start_time'], 0, 5) ?>-<?= substr($type['end_time'], 0, 5) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Дата смены*</label>
            <input type="date" name="shift_date" class="form-control" required
              value="<?= $shift['shift_date'] ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Маршрут (если назначен)</label>
            <select name="id_route" class="form-select">
              <option value="">Не назначен</option>
              <?php foreach ($routes as $route): ?>
                <option value="<?= $route['id_route'] ?>"
                  <?= $route['id_route'] == $shift['id_route'] ? 'selected' : '' ?>>
                  №<?= $route['route_number'] ?> - <?= $route['route_name'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Сохранить изменения
          </button>
          <a href="work_shifts.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Отмена
          </a>
        </div>
      </div>
    </form>
  </div>
</body>

</html>