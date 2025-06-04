<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$statusId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные статуса ТО
$stmt = $pdo->prepare("SELECT * FROM Maintenance_statuses WHERE id_maintenance_status = ?");
$stmt->execute([$statusId]);
$status = $stmt->fetch();

if (!$status) {
  $_SESSION['ms_error'] = "Статус обслуживания не найден";
  header('Location: maintenance_statuses.php');
  exit;
}

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $errors = [];
  $statusName = trim($_POST['status_name']);

  if (empty($statusName)) {
    $errors[] = "Название статуса обязательно";
  } else {
    try {
      $stmt = $pdo->prepare("UPDATE Maintenance_statuses SET maintenance_status = ? WHERE id_maintenance_status = ?");
      $stmt->execute([$statusName, $statusId]);
      $_SESSION['ms_success'] = "Статус обслуживания успешно обновлен";
      header('Location: maintenance_statuses.php');
      exit;
    } catch (PDOException $e) {
      $errors[] = "Ошибка: " . $e->getMessage();
    }
  }

  if (!empty($errors)) {
    $_SESSION['ms_errors'] = $errors;
  }
}

$title = "Редактирование статуса обслуживания";
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

    <?php if (isset($_SESSION['ms_errors'])): ?>
      <div class="alert alert-danger">
        <?php foreach ($_SESSION['ms_errors'] as $error): ?>
          <p class="mb-0"><?= $error ?></p>
        <?php endforeach; ?>
      </div>
      <?php unset($_SESSION['ms_errors']); ?>
    <?php endif; ?>

    <form method="POST">
      <div class="card">
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Название статуса обслуживания</label>
            <input type="text" name="status_name" class="form-control"
              value="<?= htmlspecialchars($status['maintenance_status']) ?>" required>
          </div>
        </div>
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Сохранить изменения
          </button>
          <a href="maintenance_statuses.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Отмена
          </a>
        </div>
      </div>
    </form>
  </div>
</body>

</html>