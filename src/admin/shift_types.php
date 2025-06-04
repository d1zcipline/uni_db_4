<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

// Обработка добавления типа смены
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_shift_type'])) {
  $errors = [];

  $shiftName = trim($_POST['shift_name']);
  $startTime = $_POST['start_time'];
  $endTime = $_POST['end_time'];
  $breakDuration = (int)$_POST['break_duration'];

  if (empty($shiftName)) $errors[] = "Название смены обязательно";
  if (empty($startTime)) $errors[] = "Время начала обязательно";
  if (empty($endTime)) $errors[] = "Время окончания обязательно";
  if ($breakDuration < 0) $errors[] = "Длительность перерыва не может быть отрицательной";

  if (empty($errors)) {
    try {
      $stmt = $pdo->prepare("
                INSERT INTO Work_shift_types (shift_name, start_time, end_time, break_duration)
                VALUES (?, ?, ?, ?)
            ");
      $stmt->execute([$shiftName, $startTime, $endTime, $breakDuration]);
      $_SESSION['shift_success'] = "Тип смены успешно добавлен";
    } catch (PDOException $e) {
      $errors[] = "Ошибка базы данных: " . $e->getMessage();
    }
  }

  if (!empty($errors)) {
    $_SESSION['shift_errors'] = $errors;
  }

  header('Location: shift_types.php');
  exit;
}

// Обработка удаления типа смены
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];

  try {
    // Проверка связанных записей
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Work_shifts WHERE id_work_shift_type = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
      $_SESSION['shift_error'] = "Нельзя удалить тип смены, который используется в расписании";
    } else {
      $stmt = $pdo->prepare("DELETE FROM Work_shift_types WHERE id_work_shift_type = ?");
      $stmt->execute([$id]);
      $_SESSION['shift_success'] = "Тип смены успешно удален";
    }
  } catch (PDOException $e) {
    $_SESSION['shift_error'] = "Ошибка: " . $e->getMessage();
  }

  header('Location: shift_types.php');
  exit;
}

// Получаем список типов смен
$shiftTypes = $pdo->query("SELECT * FROM Work_shift_types ORDER BY start_time")->fetchAll();

$title = "Управление типами смен";
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
      <h1>Типы рабочих смен</h1>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addShiftTypeModal">
        <i class="bi bi-plus-lg"></i> Добавить тип смены
      </button>
    </div>

    <?php if (isset($_SESSION['shift_error'])): ?>
      <div class="alert alert-danger"><?= $_SESSION['shift_error'] ?></div>
      <?php unset($_SESSION['shift_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['shift_success'])): ?>
      <div class="alert alert-success"><?= $_SESSION['shift_success'] ?></div>
      <?php unset($_SESSION['shift_success']); ?>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <?php if (empty($shiftTypes)): ?>
          <div class="alert alert-info">Нет добавленных типов смен</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Название</th>
                  <th>Начало</th>
                  <th>Окончание</th>
                  <th>Длительность</th>
                  <th>Перерыв (мин)</th>
                  <th>Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($shiftTypes as $type): ?>
                  <tr>
                    <td><?= htmlspecialchars($type['shift_name']) ?></td>
                    <td><?= substr($type['start_time'], 0, 5) ?></td>
                    <td><?= substr($type['end_time'], 0, 5) ?></td>
                    <td>
                      <?php
                      $start = new DateTime($type['start_time']);
                      $end = new DateTime($type['end_time']);
                      $diff = $start->diff($end);
                      echo $diff->format('%h ч %i мин');
                      ?>
                    </td>
                    <td><?= $type['break_duration'] ?></td>
                    <td>
                      <a href="edit_shift_type.php?id=<?= $type['id_work_shift_type'] ?>"
                        class="btn btn-sm btn-outline-primary me-1">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <a href="?delete=<?= $type['id_work_shift_type'] ?>"
                        class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Удалить этот тип смены?')">
                        <i class="bi bi-trash"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Модальное окно добавления типа смены -->
  <div class="modal fade" id="addShiftTypeModal" tabindex="-1" aria-labelledby="addShiftTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addShiftTypeModalLabel">Добавить тип смены</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Название смены*</label>
              <input type="text" name="shift_name" class="form-control" required>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Время начала*</label>
                <input type="time" name="start_time" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Время окончания*</label>
                <input type="time" name="end_time" class="form-control" required>
              </div>
            </div>
            <div class="mb-3 mt-3">
              <label class="form-label">Длительность перерыва (минут)</label>
              <input type="number" name="break_duration" class="form-control" min="0" value="30">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
            <button type="submit" name="add_shift_type" class="btn btn-primary">Добавить</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>