<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

// Получаем список расписаний
$schedule = $pdo->query("
    SELECT s.*, r.route_number, r.route_name
    FROM Schedule s
    JOIN Routes r ON s.id_route = r.id_route
    ORDER BY r.route_number, s.day_type, s.departure_time
")->fetchAll();

// Получаем данные для форм
$routes = $pdo->query("SELECT * FROM Routes WHERE active = 1 ORDER BY route_number")->fetchAll();
$dayTypes = ['будни', 'выходные', 'праздничные'];

$title = "Расписание маршрутов";
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
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>Расписание маршрутов</h1>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
        <i class="bi bi-plus-lg"></i> Добавить расписание
      </button>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Маршрут</th>
                <th>Тип дня</th>
                <th>Отправление</th>
                <th>Прибытие</th>
                <th>Статус</th>
                <th>Действия</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($schedule as $item): ?>
                <tr>
                  <td>
                    <div><strong>№<?= $item['route_number'] ?></strong></div>
                    <div class="text-muted"><?= $item['route_name'] ?></div>
                  </td>
                  <td><?= ucfirst($item['day_type']) ?></td>
                  <td><?= substr($item['departure_time'], 0, 5) ?></td>
                  <td><?= substr($item['arrival_time'], 0, 5) ?></td>
                  <td>
                    <span class="badge bg-<?= $item['active'] ? 'success' : 'secondary' ?>">
                      <?= $item['active'] ? 'Активно' : 'Неактивно' ?>
                    </span>
                  </td>
                  <td>
                    <a href="edit_schedule.php?id=<?= $item['id_schedule'] ?>"
                      class="btn btn-sm btn-outline-primary me-1">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <a href="delete_schedule.php?id=<?= $item['id_schedule'] ?>"
                      class="btn btn-sm btn-outline-danger"
                      onclick="return confirm('Удалить эту запись расписания?')">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Модальное окно добавления расписания -->
  <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addScheduleModalLabel">Добавить расписание</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="add_schedule.php" method="POST">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Маршрут*</label>
              <select name="id_route" class="form-select" required>
                <option value="">Выберите маршрут</option>
                <?php foreach ($routes as $route): ?>
                  <option value="<?= $route['id_route'] ?>">
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
                  <option value="<?= $type ?>"><?= ucfirst($type) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Время отправления*</label>
                <input type="time" name="departure_time" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Время прибытия*</label>
                <input type="time" name="arrival_time" class="form-control" required>
              </div>
            </div>
            <div class="form-check form-switch mt-3">
              <input class="form-check-input" type="checkbox" role="switch"
                id="activeSwitch" name="active" value="1" checked>
              <label class="form-check-label" for="activeSwitch">Активное расписание</label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
            <button type="submit" class="btn btn-primary">Добавить</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>