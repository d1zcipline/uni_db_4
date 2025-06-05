<?php
require_once 'includes/db.php';
require_once 'admin/functions.php';
session_start();
require_admin();

// Удаление маршрута
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  // Удалим сначала связи с остановками
  $pdo->prepare("DELETE FROM Routes_stops WHERE id_route = ?")->execute([$id]);
  // Затем сам маршрут
  $pdo->prepare("DELETE FROM Routes WHERE id_route = ?")->execute([$id]);
  header("Location: admin_routes.php");
  exit;
}

// Обновление маршрута
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_route'])) {
  $id = (int)$_POST['id_route'];
  $name = trim($_POST['route_name']);
  $number = trim($_POST['route_number']);

  $stmt = $pdo->prepare("UPDATE Routes SET route_name = ?, route_number = ? WHERE id_route = ?");
  $stmt->execute([$name, $number, $id]);
  header("Location: admin_routes.php");
  exit;
}

// Получение списка маршрутов
$routes = $pdo->query("SELECT * FROM Routes ORDER BY route_number")->fetchAll();
$userName = $_SESSION['user']['name'];
$role = $_SESSION['user']['role'];
?>

<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <title>Маршруты</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
  <?php include 'includes/admin_navbar.php'; ?>
  <div class="container py-4">
    <h1>Список маршрутов</h1>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>#</th>
          <th>Номер</th>
          <th>Название</th>
          <th>Действия</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($routes as $route): ?>
          <tr>
            <form method="post">
              <input type="hidden" name="update_route" value="1">
              <input type="hidden" name="id_route" value="<?= $route['id_route'] ?>">
              <td><?= $route['id_route'] ?></td>
              <td><input type="text" name="route_number" class="form-control" value="<?= htmlspecialchars($route['route_number']) ?>"></td>
              <td><input type="text" name="route_name" class="form-control" value="<?= htmlspecialchars($route['route_name']) ?>"></td>
              <td>
                <button class="btn btn-success btn-sm">Сохранить</button>
                <a href="admin/manage_route_stops.php?id=<?= $route['id_route'] ?>" class="btn btn-primary btn-sm">Остановки</a>
                <a href="?delete=<?= $route['id_route'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Удалить маршрут вместе с его остановками?')">Удалить</a>
              </td>
            </form>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>