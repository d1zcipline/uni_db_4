<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

// Обработка добавления статуса ТО
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_status'])) {
  $statusName = trim($_POST['status_name']);

  if (empty($statusName)) {
    $_SESSION['ms_error'] = "Название статуса обязательно";
  } else {
    try {
      $stmt = $pdo->prepare("INSERT INTO Maintenance_statuses (maintenance_status) VALUES (?)");
      $stmt->execute([$statusName]);
      $_SESSION['ms_success'] = "Статус обслуживания добавлен";
    } catch (PDOException $e) {
      $_SESSION['ms_error'] = "Ошибка: " . $e->getMessage();
    }
  }
  header('Location: maintenance_statuses.php');
  exit;
}

// Обработка удаления статуса ТО
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];

  try {
    // Проверяем, есть ли связанные записи
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Maintenance_records WHERE id_maintenance_status = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
      $_SESSION['ms_error'] = "Нельзя удалить статус, который используется в записях обслуживания";
    } else {
      $stmt = $pdo->prepare("DELETE FROM Maintenance_statuses WHERE id_maintenance_status = ?");
      $stmt->execute([$id]);
      $_SESSION['ms_success'] = "Статус обслуживания удален";
    }
  } catch (PDOException $e) {
    $_SESSION['ms_error'] = "Ошибка: " . $e->getMessage();
  }
  header('Location: maintenance_statuses.php');
  exit;
}

// Получаем список статусов ТО
$statuses = $pdo->query("SELECT * FROM Maintenance_statuses ORDER BY id_maintenance_status")->fetchAll();

$title = "Управление статусами технического обслуживания";
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
      <h1>Статусы технического обслуживания</h1>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStatusModal">
        <i class="bi bi-plus-lg"></i> Добавить статус
      </button>
    </div>

    <?php if (isset($_SESSION['ms_error'])): ?>
      <div class="alert alert-danger"><?= $_SESSION['ms_error'] ?></div>
      <?php unset($_SESSION['ms_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['ms_success'])): ?>
      <div class="alert alert-success"><?= $_SESSION['ms_success'] ?></div>
      <?php unset($_SESSION['ms_success']); ?>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <?php if (empty($statuses)): ?>
          <div class="alert alert-info">Нет добавленных статусов обслуживания</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Статус</th>
                  <th>Кол-во записей</th>
                  <th>Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($statuses as $status): ?>
                  <tr>
                    <td><?= $status['id_maintenance_status'] ?></td>
                    <td><?= htmlspecialchars($status['maintenance_status']) ?></td>
                    <td>
                      <?php
                      $stmt = $pdo->prepare("SELECT COUNT(*) FROM Maintenance_records WHERE id_maintenance_status = ?");
                      $stmt->execute([$status['id_maintenance_status']]);
                      echo $stmt->fetchColumn();
                      ?>
                    </td>
                    <td>
                      <a href="edit_maintenance_status.php?id=<?= $status['id_maintenance_status'] ?>"
                        class="btn btn-sm btn-outline-primary me-1">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <a href="?delete=<?= $status['id_maintenance_status'] ?>"
                        class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Удалить этот статус обслуживания?')">
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

  <!-- Модальное окно добавления статуса -->
  <div class="modal fade" id="addStatusModal" tabindex="-1" aria-labelledby="addStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addStatusModalLabel">Добавить статус обслуживания</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Название статуса</label>
              <input type="text" name="status_name" class="form-control" required>
              <div class="form-text">Примеры: "Запланировано", "В работе", "Завершено", "Отменено"</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
            <button type="submit" name="add_status" class="btn btn-primary">Добавить</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>