<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$scheduleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные расписания
$stmt = $pdo->prepare("
    SELECT s.*, r.route_number, r.route_name 
    FROM Schedule s
    JOIN Routes r ON s.id_route = r.id_route
    WHERE s.id_schedule = ?
");
$stmt->execute([$scheduleId]);
$schedule = $stmt->fetch();

if (!$schedule) {
  $_SESSION['schedule_error'] = "Расписание не найдено";
  header('Location: schedule.php');
  exit;
}

// Получаем данные для форм
$routes = $pdo->query("SELECT * FROM Routes WHERE active = 1 ORDER BY route_number")->fetchAll();
$dayTypes = ['будни', 'выходные', 'праздничные'];

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $errors = [];

  $required = [
    'id_route' => 'Маршрут',
    'day_type' => 'Тип дня',
    'departure_time' => 'Время отправления',
    'arrival_time' => 'Время прибытия'
  ];

  foreach ($required as $field => $name) {
    if (empty($_POST[$field])) {
      $errors[] = "Поле '$name' обязательно для заполнения";
    }
  }

  if (empty($errors)) {
    try {
      $stmt = $pdo->prepare("
                UPDATE Schedule SET
                    id_route = ?,
                    day_type = ?,
                    departure_time = ?,
                    arrival_time = ?,
                    active = ?
                WHERE id_schedule = ?
            ");

      $stmt->execute([
        $_POST['id_route'],
        $_POST['day_type'],
        $_POST['departure_time'],
        $_POST['arrival_time'],
        isset($_POST['active']) ? 1 : 0,
        $scheduleId
      ]);

      $_SESSION['schedule_success'] = "Расписание успешно обновлено";
      header('Location: schedule.php');
      exit;
    } catch (PDOException $e) {
      $errors[] = "Ошибка базы данных: " . $e->getMessage();
    }
  }

  if (!empty($errors)) {
    $_SESSION['schedule_errors'] = $errors;
  }
}

$title = "Редактирование расписания маршрута №" . $schedule['route_number'];
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

    <?php if (isset($_SESSION['schedule_errors'])): ?>
      <div class="alert alert-danger">
        <?php foreach ($_SESSION['schedule_errors'] as $error): ?>
          <p class="mb-0"><?= $error ?></p>
        <?php endforeach; ?>
      </div>
      <?php unset($_SESSION['schedule_errors']); ?>
    <?php endif; ?>

    <form method="POST">
      <div class="card">
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Маршрут*</label>
            <select name="id_route" class="form-select" required>
              <option value="">Выберите маршрут</option>
              <?php foreach ($routes as $route): ?>
                <option value="<?= $route['id_route'] ?>"
                  <?= $route['id_route'] == $schedule['id_route'] ? 'selected' : '' ?>>
                  №<?= $route['route_number'] ?> - <?= $route['route_name'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Тип дня*</label>
            <select name="day_type" class="form-select" required>
              <option value="">Выберите тип дня</option>
              <?php foreach ($dayTypes as $type): ?>
                <option value="<?= $type ?>"
                  <?= $type == $schedule['day_type'] ? 'selected' : '' ?>>
                  <?= ucfirst($type) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Время отправления*</label>
              <input type="time" name="departure_time" class="form-control"
                value="<?= substr($schedule['departure_time'], 0, 5) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Время прибытия*</label>
              <input type="time" name="arrival_time" class="form-control"
                value="<?= substr($schedule['arrival_time'], 0, 5) ?>" required>
            </div>
          </div>
          <div class="form-check form-switch mt-3">
            <input class="form-check-input" type="checkbox" role="switch"
              id="activeSwitch" name="active" value="1"
              <?= $schedule['active'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="activeSwitch">Активное расписание</label>
          </div>
        </div>
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Сохранить изменения
          </button>
          <a href="schedule.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Отмена
          </a>
        </div>
      </div>
    </form>
  </div>
</body>

</html>