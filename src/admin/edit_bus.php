<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$busId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные автобуса
$stmt = $pdo->prepare("
    SELECT * FROM Buses 
    WHERE id_bus = ?
");
$stmt->execute([$busId]);
$bus = $stmt->fetch();

if (!$bus) {
  $_SESSION['bus_error'] = "Автобус не найден";
  header('Location: ../admin_buses.php');
  exit;
}

// Получаем данные для форм
$busTypes = $pdo->query("SELECT * FROM Bus_types ORDER BY bus_type_name")->fetchAll();
$statuses = $pdo->query("SELECT * FROM Statuses ORDER BY status_name")->fetchAll();
$parks = $pdo->query("SELECT * FROM Bus_parks ORDER BY bus_park_name")->fetchAll();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $errors = [];

  // Валидация данных
  $required = [
    'license_plate' => 'Государственный номер',
    'id_bus_type' => 'Тип автобуса',
    'id_status' => 'Статус',
    'capacity' => 'Вместимость'
  ];

  foreach ($required as $field => $name) {
    if (empty($_POST[$field])) {
      $errors[] = "Поле '$name' обязательно для заполнения";
    }
  }

  // Проверка формата госномера
  if (!preg_match('/^[А-ЯA-Z]{1}\d{3}[А-ЯA-Z]{2}\d{2,3}$/u', $_POST['license_plate'])) {
    $errors[] = "Неверный формат государственного номера";
  }

  // Проверка уникальности госномера (исключая текущий автобус)
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM Buses WHERE license_plate = ? AND id_bus != ?");
  $stmt->execute([$_POST['license_plate'], $busId]);
  if ($stmt->fetchColumn() > 0) {
    $errors[] = "Автобус с таким номером уже существует";
  }

  // Проверка числовых значений
  if (!is_numeric($_POST['capacity']) || $_POST['capacity'] <= 0) {
    $errors[] = "Вместимость должна быть положительным числом";
  }

  if (empty($errors)) {
    try {
      $stmt = $pdo->prepare("
                UPDATE Buses SET
                    id_bus_type = ?,
                    id_status = ?,
                    id_bus_park = ?,
                    license_plate = ?,
                    capacity = ?,
                    manufacture_year = ?,
                    last_maintenance_date = ?
                WHERE id_bus = ?
            ");

      $stmt->execute([
        $_POST['id_bus_type'],
        $_POST['id_status'],
        !empty($_POST['id_bus_park']) ? $_POST['id_bus_park'] : null,
        $_POST['license_plate'],
        $_POST['capacity'],
        !empty($_POST['manufacture_year']) ? $_POST['manufacture_year'] : null,
        !empty($_POST['last_maintenance_date']) ? $_POST['last_maintenance_date'] : null,
        $busId
      ]);

      $_SESSION['bus_success'] = "Данные автобуса обновлены";
      header('Location: ../admin_buses.php');
      exit;
    } catch (PDOException $e) {
      $errors[] = "Ошибка базы данных: " . $e->getMessage();
    }
  }

  if (!empty($errors)) {
    $_SESSION['bus_errors'] = $errors;
  }
}

$title = "Редактирование автобуса " . $bus['license_plate'];
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

    <?php if (isset($_SESSION['bus_errors'])): ?>
      <div class="alert alert-danger">
        <?php foreach ($_SESSION['bus_errors'] as $error): ?>
          <p class="mb-0"><?= $error ?></p>
        <?php endforeach; ?>
      </div>
      <?php unset($_SESSION['bus_errors']); ?>
    <?php endif; ?>

    <form method="POST">
      <div class="card">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Государственный номер</label>
              <input type="text" name="license_plate" class="form-control" required
                pattern="[А-ЯA-Z]{1}\d{3}[А-ЯA-Z]{2}\d{2,3}"
                title="Формат: A123BC777 или X123XX123"
                value="<?= htmlspecialchars($bus['license_plate']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Тип автобуса</label>
              <select name="id_bus_type" class="form-select" required>
                <option value="">Выберите тип</option>
                <?php foreach ($busTypes as $type): ?>
                  <option value="<?= $type['id_bus_type'] ?>"
                    <?= $type['id_bus_type'] == $bus['id_bus_type'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($type['bus_type_name']) ?>
                    <?= $type['electric'] ? ' (электрический)' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Статус</label>
              <select name="id_status" class="form-select" required>
                <option value="">Выберите статус</option>
                <?php foreach ($statuses as $status): ?>
                  <option value="<?= $status['id_status'] ?>"
                    <?= $status['id_status'] == $bus['id_status'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($status['status_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Автопарк</label>
              <select name="id_bus_park" class="form-select">
                <option value="">Не назначен</option>
                <?php foreach ($parks as $park): ?>
                  <option value="<?= $park['id_bus_park'] ?>"
                    <?= $park['id_bus_park'] == $bus['id_bus_park'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($park['bus_park_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Год выпуска</label>
              <input type="number" name="manufacture_year" class="form-control"
                min="1990" max="<?= date('Y') + 1 ?>"
                value="<?= htmlspecialchars($bus['manufacture_year']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Вместимость (пассажиров)</label>
              <input type="number" name="capacity" class="form-control" min="1" required
                value="<?= htmlspecialchars($bus['capacity']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Дата последнего ТО</label>
              <input type="date" name="last_maintenance_date" class="form-control"
                value="<?= htmlspecialchars($bus['last_maintenance_date']) ?>">
            </div>
          </div>
        </div>
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Сохранить изменения
          </button>
          <a href="../admin_buses.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Отмена
          </a>
        </div>
      </div>
    </form>
  </div>
</body>