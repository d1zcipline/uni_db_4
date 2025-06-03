<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$parkId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные автопарка
$stmt = $pdo->prepare("
    SELECT b.*, l.id_district, l.address 
    FROM Bus_parks b
    JOIN Locations l ON b.id_location = l.id_location
    WHERE b.id_bus_park = ?
");
$stmt->execute([$parkId]);
$park = $stmt->fetch();

if (!$park) {
  $_SESSION['park_error'] = "Автопарк не найден";
  header('Location: bus_parks.php');
  exit;
}

// Получаем список районов
$districts = $pdo->query("SELECT * FROM Districts ORDER BY district_name")->fetchAll();

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $errors = [];

  // Валидация
  $required = [
    'bus_park_name' => 'Название автопарка',
    'id_district' => 'Район',
    'address' => 'Адрес',
    'capacity' => 'Вместимость'
  ];

  foreach ($required as $field => $name) {
    if (empty($_POST[$field])) {
      $errors[] = "Поле '$name' обязательно для заполнения";
    }
  }

  if (!is_numeric($_POST['capacity']) || $_POST['capacity'] <= 0) {
    $errors[] = "Вместимость должна быть положительным числом";
  }

  if (empty($errors)) {
    try {
      // Обновляем локацию
      $stmt = $pdo->prepare("
                UPDATE Locations SET
                    id_district = ?,
                    address = ?
                WHERE id_location = ?
            ");
      $stmt->execute([
        $_POST['id_district'],
        trim($_POST['address']),
        $park['id_location']
      ]);

      // Обновляем автопарк
      $stmt = $pdo->prepare("
                UPDATE Bus_parks SET
                    bus_park_name = ?,
                    capacity = ?
                WHERE id_bus_park = ?
            ");
      $stmt->execute([
        trim($_POST['bus_park_name']),
        (int)$_POST['capacity'],
        $parkId
      ]);

      $_SESSION['park_success'] = "Автопарк успешно обновлен";
      header('Location: bus_parks.php');
      exit;
    } catch (PDOException $e) {
      $errors[] = "Ошибка базы данных: " . $e->getMessage();
    }
  }

  if (!empty($errors)) {
    $_SESSION['park_errors'] = $errors;
  }
}

$title = "Редактирование автопарка";
$userName = $_SESSION['user']['name'];
$role = $_SESSION['user']['role'];
?>


<head>
  <meta charset="UTF-8">
  <title><?= $title ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
  <?php include '../includes/admin_navbar.php'; ?>
  <div class="container mt-4">
    <h2>Редактирование автопарка</h2>

    <?php if (isset($_SESSION['park_errors'])): ?>
      <div class="alert alert-danger">
        <?php foreach ($_SESSION['park_errors'] as $error): ?>
          <p class="mb-0"><?= $error ?></p>
        <?php endforeach; ?>
      </div>
      <?php unset($_SESSION['park_errors']); ?>
    <?php endif; ?>

    <form method="POST">
      <div class="card">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Название автопарка</label>
              <input type="text" name="bus_park_name" class="form-control"
                value="<?= htmlspecialchars($park['bus_park_name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Вместимость (автобусов)</label>
              <input type="number" name="capacity" class="form-control"
                value="<?= htmlspecialchars($park['capacity']) ?>" min="1" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Район</label>
              <select name="id_district" class="form-select" required>
                <option value="">Выберите район</option>
                <?php foreach ($districts as $district): ?>
                  <option value="<?= $district['id_district'] ?>"
                    <?= $district['id_district'] == $park['id_district'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($district['district_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Адрес</label>
              <input type="text" name="address" class="form-control"
                value="<?= htmlspecialchars($park['address']) ?>" required>
            </div>
          </div>
        </div>
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Сохранить изменения
          </button>
          <a href="../admin_bus_parks.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Отмена
          </a>
        </div>
      </div>
    </form>
  </div>
</body>