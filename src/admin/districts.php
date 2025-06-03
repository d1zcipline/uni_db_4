<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

// Обработка добавления района
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_district'])) {
  $districtName = trim($_POST['district_name']);

  if (empty($districtName)) {
    $_SESSION['district_error'] = "Название района обязательно для заполнения";
  } else {
    try {
      $stmt = $pdo->prepare("INSERT INTO Districts (district_name) VALUES (?)");
      $stmt->execute([$districtName]);
      $_SESSION['district_success'] = "Район успешно добавлен";
    } catch (PDOException $e) {
      $_SESSION['district_error'] = "Ошибка при добавлении района: " . $e->getMessage();
    }
  }
  header('Location: districts.php');
  exit;
}

// Обработка удаления района
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];

  try {
    // Проверяем, есть ли связанные локации
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Locations WHERE id_district = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
      $_SESSION['district_error'] = "Нельзя удалить район, к которому привязаны локации";
    } else {
      $stmt = $pdo->prepare("DELETE FROM Districts WHERE id_district = ?");
      $stmt->execute([$id]);
      $_SESSION['district_success'] = "Район успешно удален";
    }
  } catch (PDOException $e) {
    $_SESSION['district_error'] = "Ошибка при удалении района: " . $e->getMessage();
  }
  header('Location: districts.php');
  exit;
}

// Получаем список районов
$districts = $pdo->query("SELECT * FROM Districts ORDER BY district_name")->fetchAll();

$userName = $_SESSION['user']['name'];
$role = $_SESSION['user']['role'];
$title = "Управление районами";
?>

<head>
  <meta charset="UTF-8">
  <title><?= $title ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<?php include '../includes/admin_navbar.php'; ?>

<div class="container mt-4">
  <h2>Управление районами</h2>

  <?php if (isset($_SESSION['district_error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['district_error'] ?></div>
    <?php unset($_SESSION['district_error']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['district_success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['district_success'] ?></div>
    <?php unset($_SESSION['district_success']); ?>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-header">
      <h5>Добавить новый район</h5>
    </div>
    <div class="card-body">
      <form method="POST">
        <div class="row">
          <div class="col-md-8">
            <div class="form-group">
              <label for="district_name">Название района</label>
              <input type="text" class="form-control" id="district_name" name="district_name" required>
            </div>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button type="submit" name="add_district" class="btn btn-primary">
              <i class="bi bi-plus-circle"></i> Добавить
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h5>Список районов</h5>
    </div>
    <div class="card-body">
      <?php if (empty($districts)): ?>
        <div class="alert alert-info">Нет добавленных районов</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Действия</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($districts as $district): ?>
                <tr>
                  <td><?= $district['id_district'] ?></td>
                  <td><?= htmlspecialchars($district['district_name']) ?></td>
                  <td>
                    <a href="?delete=<?= $district['id_district'] ?>"
                      class="btn btn-sm btn-danger"
                      onclick="return confirm('Удалить этот район?')">
                      <i class="bi bi-trash"></i> Удалить
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