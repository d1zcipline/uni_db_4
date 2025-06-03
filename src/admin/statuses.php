<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

// Обработка добавления статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_status'])) {
  $statusName = trim($_POST['status_name']);

  if (empty($statusName)) {
    $_SESSION['status_error'] = "Название статуса обязательно";
  } else {
    try {
      $stmt = $pdo->prepare("INSERT INTO Statuses (status_name) VALUES (?)");
      $stmt->execute([$statusName]);
      $_SESSION['status_success'] = "Статус добавлен";
    } catch (PDOException $e) {
      $_SESSION['status_error'] = "Ошибка: " . $e->getMessage();
    }
  }
  header('Location: statuses.php');
  exit;
}

// Обработка удаления статуса
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];

  try {
    // Проверяем, есть ли связанные автобусы
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Buses WHERE id_status = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
      $_SESSION['status_error'] = "Нельзя удалить статус, который используется автобусами";
    } else {
      $stmt = $pdo->prepare("DELETE FROM Statuses WHERE id_status = ?");
      $stmt->execute([$id]);
      $_SESSION['status_success'] = "Статус удален";
    }
  } catch (PDOException $e) {
    $_SESSION['status_error'] = "Ошибка: " . $e->getMessage();
  }
  header('Location: statuses.php');
  exit;
}

// Получаем список статусов
$statuses = $pdo->query("SELECT * FROM Statuses ORDER BY status_name")->fetchAll();

$title = "Управление статусами автобусов";
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
      <h1>Статусы автобусов</h1>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStatusModal">
        <i class="bi bi-plus-lg"></i> Добавить статус
      </button>
    </div>

    <?php if (isset($_SESSION['status_error'])): ?>
      <div class="alert alert-danger"><?= $_SESSION['status_error'] ?></div>
      <?php unset($_SESSION['status_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['status_success'])): ?>
      <div class="alert alert-success"><?= $_SESSION['status_success'] ?></div>
      <?php unset($_SESSION['status_success']); ?>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <?php if (empty($statuses)): ?>
          <div class="alert alert-info">Нет добавленных статусов</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Название статуса</th>
                  <th>Кол-во автобусов</th>
                  <th>Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($statuses as $status): ?>
                  <tr>
                    <td><?= $status['id_status'] ?></td>
                    <td><?= htmlspecialchars($status['status_name']) ?></td>
                    <td>
                      <?php
                      $stmt = $pdo->prepare("SELECT COUNT(*) FROM Buses WHERE id_status = ?");
                      $stmt->execute([$status['id_status']]);
                      echo $stmt->fetchColumn();
                      ?>
                    </td>
                    <td>
                      <a href="?delete=<?= $status['id_status'] ?>"
                        class="btn btn-sm btn-danger"
                        onclick="return confirm('Удалить этот статус?')">
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

  <!-- Модальное окно добавления статуса -->
  <div class="modal fade" id="addStatusModal" tabindex="-1" aria-labelledby="addStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addStatusModalLabel">Добавить статус</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Название статуса</label>
              <input type="text" name="status_name" class="form-control" required>
              <div class="form-text">Примеры: "В эксплуатации", "На обслуживании", "Списан"</div>
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