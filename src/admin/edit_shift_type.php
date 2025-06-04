<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$typeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные типа смены
$stmt = $pdo->prepare("SELECT * FROM Work_shift_types WHERE id_work_shift_type = ?");
$stmt->execute([$typeId]);
$shiftType = $stmt->fetch();

if (!$shiftType) {
  $_SESSION['shift_error'] = "Тип смены не найден";
  header('Location: shift_types.php');
  exit;
}

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                UPDATE Work_shift_types SET
                    shift_name = ?,
                    start_time = ?,
                    end_time = ?,
                    break_duration = ?
                WHERE id_work_shift_type = ?
            ");
      $stmt->execute([$shiftName, $startTime, $endTime, $breakDuration, $typeId]);
      $_SESSION['shift_success'] = "Тип смены успешно обновлен";
      header('Location: shift_types.php');
      exit;
    } catch (PDOException $e) {
      $errors[] = "Ошибка базы данных: " . $e->getMessage();
    }
  }

  if (!empty($errors)) {
    $_SESSION['shift_errors'] = $errors;
  }
}

$title = "Редактирование типа смены";
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
            <label class="form-label">Название смены*</label>
            <input type="text" name="shift_name" class="form-control"
              value="<?= htmlspecialchars($shiftType['shift_name']) ?>" required>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Время начала*</label>
              <input type="time" name="start_time" class="form-control"
                value="<?= substr($shiftType['start_time'], 0, 5) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Время окончания*</label>
              <input type="time" name="end_time" class="form-control"
                value="<?= substr($shiftType['end_time'], 0, 5) ?>" required>
            </div>
          </div>
          <div class="mb-3 mt-3">
            <label class="form-label">Длительность перерыва (минут)</label>
            <input type="number" name="break_duration" class="form-control" min="0"
              value="<?= $shiftType['break_duration'] ?>">
          </div>
        </div>
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Сохранить изменения
          </button>
          <a href="shift_types.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Отмена
          </a>
        </div>
      </div>
    </form>
  </div>
</body>

</html>