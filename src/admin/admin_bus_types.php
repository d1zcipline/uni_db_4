<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

// Обработка добавления типа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_type'])) {
  $typeName = trim($_POST['type_name']);
  $isElectric = isset($_POST['electric']) ? 1 : 0;

  if (empty($typeName)) {
    $_SESSION['type_error'] = "Название типа обязательно";
  } else {
    try {
      $stmt = $pdo->prepare("INSERT INTO Bus_types (bus_type_name, electric) VALUES (?, ?)");
      $stmt->execute([$typeName, $isElectric]);
      $_SESSION['type_success'] = "Тип автобуса добавлен";
    } catch (PDOException $e) {
      $_SESSION['type_error'] = "Ошибка: " . $e->getMessage();
    }
  }
  header('Location: admin_bus_types.php');
  exit;
}

// Обработка удаления типа
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];

  try {
    // Проверяем, есть ли связанные автобусы
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Buses WHERE id_bus_type = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
      $_SESSION['type_error'] = "Нельзя удалить тип, к которому привязаны автобусы";
    } else {
      $stmt = $pdo->prepare("DELETE FROM Bus_types WHERE id_bus_type = ?");
      $stmt->execute([$id]);
      $_SESSION['type_success'] = "Тип автобуса удален";
    }
  } catch (PDOException $e) {
    $_SESSION['type_error'] = "Ошибка: " . $e->getMessage();
  }
  header('Location: admin_bus_types.php');
  exit;
}

// Получаем список типов
$types = $pdo->query("SELECT * FROM Bus_types ORDER BY bus_type_name")->fetchAll();

$title = "Управление типами автобусов";
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
      <h1>Типы автобусов</h1>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTypeModal">
        <i class="bi bi-plus-lg"></i> Добавить тип
      </button>
    </div>

    <?php if (isset($_SESSION['type_error'])): ?>
      <div class="alert alert-danger"><?= $_SESSION['type_error'] ?></div>
      <?php unset($_SESSION['type_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['type_success'])): ?>
      <div class="alert alert-success"><?= $_SESSION['type_success'] ?></div>
      <?php unset($_SESSION['type_success']); ?>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <?php if (empty($types)): ?>
          <div class="alert alert-info">Нет добавленных типов автобусов</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Название типа</th>
                  <th>Электрический</th>
                  <th>Кол-во автобусов</th>
                  <th>Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($types as $type): ?>
                  <tr>
                    <td><?= $type['id_bus_type'] ?></td>
                    <td><?= htmlspecialchars($type['bus_type_name']) ?></td>
                    <td>
                      <?php if ($type['electric']): ?>
                        <span class="badge bg-success">Да</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Нет</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php
                      $stmt = $pdo->prepare("SELECT COUNT(*) FROM Buses WHERE id_bus_type = ?");
                      $stmt->execute([$type['id_bus_type']]);
                      echo $stmt->fetchColumn();
                      ?>
                    </td>
                    <td>
                      <a href="?delete=<?= $type['id_bus_type'] ?>"
                        class="btn btn-sm btn-danger"
                        onclick="return confirm('Удалить этот тип?')">
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

  <!-- Модальное окно добавления типа -->
  <div class="modal fade" id="addTypeModal" tabindex="-1" aria-labelledby="addTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addTypeModalLabel">Добавить тип автобуса</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Название типа</label>
              <input type="text" name="type_name" class="form-control" required>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch"
                id="electricSwitch" name="electric" value="1">
              <label class="form-check-label" for="electricSwitch">Электрический автобус</label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
            <button type="submit" name="add_type" class="btn btn-primary">Добавить</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>