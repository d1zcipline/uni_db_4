<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

// Получаем список назначенных смен
$shifts = $pdo->query("
    SELECT ws.*, 
           e.first_name, e.last_name, 
           wst.shift_name, wst.start_time, wst.end_time,
           r.route_number, r.route_name
    FROM Work_shifts ws
    JOIN Employees e ON ws.id_employee = e.id_employee
    JOIN Work_shift_types wst ON ws.id_work_shift_type = wst.id_work_shift_type
    LEFT JOIN Routes r ON ws.id_route = r.id_route
    ORDER BY ws.shift_date DESC
")->fetchAll();

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

$title = "Управление рабочими сменами";
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
      <h1>Рабочие смены водителей</h1>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addShiftModal">
        <i class="bi bi-plus-lg"></i> Назначить смену
      </button>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Дата</th>
                <th>Водитель</th>
                <th>Смена</th>
                <th>Маршрут</th>
                <th>Время</th>
                <th>Действия</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($shifts as $shift): ?>
                <tr>
                  <td><?= date('d.m.Y', strtotime($shift['shift_date'])) ?></td>
                  <td><?= htmlspecialchars($shift['last_name']) ?> <?= htmlspecialchars($shift['first_name']) ?></td>
                  <td><?= htmlspecialchars($shift['shift_name']) ?></td>
                  <td>
                    <?php if ($shift['id_route']): ?>
                      №<?= $shift['route_number'] ?> - <?= $shift['route_name'] ?>
                    <?php else: ?>
                      <span class="text-muted">Не назначен</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?= substr($shift['start_time'], 0, 5) ?> - <?= substr($shift['end_time'], 0, 5) ?>
                  </td>
                  <td>
                    <a href="edit_work_shift.php?id=<?= $shift['id_work_shift'] ?>"
                      class="btn btn-sm btn-outline-primary me-1">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <a href="delete_work_shift.php?id=<?= $shift['id_work_shift'] ?>"
                      class="btn btn-sm btn-outline-danger"
                      onclick="return confirm('Удалить эту смену?')">
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

  <!-- Модальное окно назначения смены -->
  <div class="modal fade" id="addShiftModal" tabindex="-1" aria-labelledby="addShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addShiftModalLabel">Назначить рабочую смену</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="add_work_shift.php" method="POST">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Водитель*</label>
              <select name="id_employee" class="form-select" required>
                <option value="">Выберите водителя</option>
                <?php foreach ($employees as $employee): ?>
                  <option value="<?= $employee['id_employee'] ?>">
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
                  <option value="<?= $type['id_work_shift_type'] ?>">
                    <?= $type['shift_name'] ?> (<?= substr($type['start_time'], 0, 5) ?>-<?= substr($type['end_time'], 0, 5) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Дата смены*</label>
              <input type="date" name="shift_date" class="form-control" required
                value="<?= date('Y-m-d') ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Маршрут (если назначен)</label>
              <select name="id_route" class="form-select">
                <option value="">Не назначен</option>
                <?php foreach ($routes as $route): ?>
                  <option value="<?= $route['id_route'] ?>">
                    №<?= $route['route_number'] ?> - <?= $route['route_name'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
            <button type="submit" class="btn btn-primary">Назначить</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>