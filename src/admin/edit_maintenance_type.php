<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$typeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные типа ТО
$stmt = $pdo->prepare("SELECT * FROM Maintenance_type WHERE id_maintenance_type = ?");
$stmt->execute([$typeId]);
$type = $stmt->fetch();

if (!$type) {
  $_SESSION['mt_error'] = "Тип обслуживания не найден";
  header('Location: maintenance_types.php');
  exit;
}

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $errors = [];
  $typeName = trim($_POST['type_name']);

  if (empty($typeName)) {
    $errors[] = "Название типа обязательно";
  } else {
    try {
      $stmt = $pdo->prepare("UPDATE Maintenance_type SET maintenance_type_name = ? WHERE id_maintenance_type = ?");
      $stmt->execute([$typeName, $typeId]);
      $_SESSION['mt_success'] = "Тип обслуживания успешно обновлен";
      header('Location: maintenance_types.php');
      exit;
    } catch (PDOException $e) {
      $errors[] = "Ошибка: " . $e->getMessage();
    }
  }

  if (!empty($errors)) {
    $_SESSION['mt_errors'] = $errors;
  }
}

$title = "Редактирование типа обслуживания";
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

    <?php if (isset($_SESSION['mt_errors'])): ?>
      <div class="alert alert-danger">
        <?php foreach ($_SESSION['mt_errors'] as $error): ?>
          <p class="mb-0"><?= $error ?></p>
        <?php endforeach; ?>
      </div>
      <?php unset($_SESSION['mt_errors']); ?>
    <?php endif; ?>

    <form method="POST">
      <div class="card">
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Название типа обслуживания</label>
            <input type="text" name="type_name" class="form-control"
              value="<?= htmlspecialchars($type['maintenance_type_name']) ?>" required>
          </div>
        </div>
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Сохранить изменения
          </button>
          <a href="maintenance_types.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Отмена
          </a>
        </div>
      </div>
    </form>
  </div>
</body>

</html>