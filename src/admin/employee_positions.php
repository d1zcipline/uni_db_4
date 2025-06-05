<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

// Удаление роли
if (isset($_GET['delete'])) {
  $id_position = (int)$_GET['delete'];
  try {
    $stmt = $pdo->prepare("DELETE FROM Employee_positions WHERE id_position = ?");
    $stmt->execute([$id_position]);
    header("Location: employee_positions.php");
    exit;
  } catch (PDOException $e) {
    $error_message = "Невозможно удалить роль, так как она используется сотрудниками.";
  }
}

// Добавление или обновление роли
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $position_name = trim($_POST['role']);
  if ($position_name !== '') {
    if (isset($_POST['id_position'])) {
      $id_position = (int)$_POST['id_position'];
      $stmt = $pdo->prepare("UPDATE Employee_positions SET role = ? WHERE id_position = ?");
      $stmt->execute([$position_name, $id_position]);
    } else {
      $stmt = $pdo->prepare("INSERT INTO Employee_positions (role) VALUES (?)");
      $stmt->execute([$position_name]);
    }
    header("Location: employee_positions.php");
    exit;
  }
}

// Получение всех ролей
$positions = $pdo->query("SELECT * FROM Employee_positions ORDER BY role")->fetchAll();
// Для редактирования
$edit_position = null;
if (isset($_GET['edit'])) {
  $id_position = (int)$_GET['edit'];
  $stmt = $pdo->prepare("SELECT * FROM Employee_positions WHERE id_position = ?");
  $stmt->execute([$id_position]);
  $edit_position = $stmt->fetch();
}
$userName = $_SESSION['user']['name'];
$role = $_SESSION['user']['role'];
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <title>Управление ролями</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
  <?php include '../includes/admin_navbar.php'; ?>
  <div class="container py-4">
    <h1>Роли сотрудников</h1>

    <?php if (!empty($error_message)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form method="post" class="mb-4">
      <div class="mb-2">
        <label class="form-label">Название роли</label>
        <input type="text" name="role" class="form-control" value="<?= htmlspecialchars($edit_position['role'] ?? '') ?>" required>
      </div>
      <?php if ($edit_position): ?>
        <input type="hidden" name="id_position" value="<?= $edit_position['id_position'] ?>">
      <?php endif; ?>
      <button class="btn btn-primary"><?= $edit_position ? 'Обновить' : 'Добавить' ?> роль</button>
      <?php if ($edit_position): ?>
        <a href="employee_positions.php" class="btn btn-secondary">Отмена</a>
      <?php endif; ?>
    </form>

    <table class="table table-bordered">
      <thead>
        <tr>
          <th>ID</th>
          <th>Название</th>
          <th>Действия</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($positions as $pos): ?>
          <tr>
            <td><?= $pos['id_position'] ?></td>
            <td><?= htmlspecialchars($pos['role']) ?></td>
            <td>
              <a href="?edit=<?= $pos['id_position'] ?>" class="btn btn-sm btn-warning">Редактировать</a>
              <a href="?delete=<?= $pos['id_position'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить эту роль?')">Удалить</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>