<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

// Обработка добавления типа ТО
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_type'])) {
  $typeName = trim($_POST['type_name']);

  if (empty($typeName)) {
    $_SESSION['mt_error'] = "Название типа обязательно";
  } else {
    try {
      $stmt = $pdo->prepare("INSERT INTO Maintenance_type (maintenance_type_name) VALUES (?)");
      $stmt->execute([$typeName]);
      $_SESSION['mt_success'] = "Тип обслуживания добавлен";
    } catch (PDOException $e) {
      $_SESSION['mt_error'] = "Ошибка: " . $e->getMessage();
    }
  }
  header('Location: maintenance_types.php');
  exit;
}

// Обработка удаления типа ТО
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];

  try {
    // Проверяем, есть ли связанные записи
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Maintenance_records WHERE id_maintenance_type = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
      $_SESSION['mt_error'] = "Нельзя удалить тип, к которому привязаны записи обслуживания";
    } else {
      $stmt = $pdo->prepare("DELETE FROM Maintenance_type WHERE id_maintenance_type = ?");
      $stmt->execute([$id]);
      $_SESSION['mt_success'] = "Тип обслуживания удален";
    }
  } catch (PDOException $e) {
    $_SESSION['mt_error'] = "Ошибка: " . $e->getMessage();
  }
  header('Location: maintenance_types.php');
  exit;
}

// Получаем список типов ТО
$types = $pdo->query("SELECT * FROM Maintenance_type ORDER BY maintenance_type_name")->fetchAll();

$title = "Управление типами технического обслуживания";
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
      <h1>Типы технического обслуживания</h1>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTypeModal">
        <i class="bi bi-plus-lg"></i> Добавить тип
      </button>
    </div>

    <?php if (isset($_SESSION['mt_error'])): ?>
      <div class="alert alert-danger"><?= $_SESSION['mt_error'] ?></div>
      <?php unset($_SESSION['mt_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['mt_success'])): ?>
      <div class="alert alert-success"><?= $_SESSION['mt_success'] ?></div>
      <?php unset($_SESSION['mt_success']); ?>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <?php if (empty($types)): ?>
          <div class="alert alert-info">Нет добавленных типов обслуживания</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Название типа</th>
                  <th>Кол-во записей</th>
                  <th>Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($types as $type): ?>
                  <tr>
                    <td><?= $type['id_maintenance_type'] ?></td>
                    <td><?= htmlspecialchars($type['maintenance_type_name']) ?></td>
                    <td>
                      <?php
                      $stmt = $pdo->prepare("SELECT COUNT(*) FROM Maintenance_records WHERE id_maintenance_type = ?");
                      $stmt->execute([$type['id_maintenance_type']]);
                      echo $stmt->fetchColumn();
                      ?>
                    </td>
                    <td>
                      <a href="edit_maintenance_type.php?id=<?= $type['id_maintenance_type'] ?>"
                        class="btn btn-sm btn-outline-primary me-1">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <a href="?delete=<?= $type['id_maintenance_type'] ?>"
                        class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Удалить этот тип обслуживания?')">
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

  <!-- Модальное окно добавления типа -->
  <div class="modal fade" id="addTypeModal" tabindex="-1" aria-labelledby="addTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addTypeModalLabel">Добавить тип обслуживания</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Название типа</label>
              <input type="text" name="type_name" class="form-control" required>
              <div class="form-text">Пример: "ТО-1", "ТО-2", "Капитальный ремонт"</div>
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

</html>