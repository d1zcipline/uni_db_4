<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$routeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Создание новой остановки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_stop'])) {
  $stop_name = trim($_POST['stop_name']);
  $latitude = $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
  $longitude = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;

  if ($stop_name !== '') {
    $stmt = $pdo->prepare("INSERT INTO Stops (stop_name, latitude, longitude) VALUES (?, ?, ?)");
    $stmt->execute([$stop_name, $latitude, $longitude]);

    if ($routeId && isset($_POST['add_to_route'])) {
      $id_stop = $pdo->lastInsertId();
      $stop_order = (int)$_POST['stop_order'];
      $stmt2 = $pdo->prepare("INSERT INTO Routes_stops (id_route, id_stop, stop_order) VALUES (?, ?, ?)");
      $stmt2->execute([$routeId, $id_stop, $stop_order]);
    }

    header("Location: manage_route_stops.php" . ($routeId ? "?id=$routeId" : ""));
    exit;
  }
}

// Получаем список всех остановок
$allStops = $pdo->query("SELECT * FROM Stops ORDER BY stop_name")->fetchAll();

// Добавление существующей остановки к маршруту
if ($routeId && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stop'])) {
  $id_stop = (int)$_POST['id_stop'];
  $stop_order = (int)$_POST['stop_order'];

  $stmt = $pdo->prepare("INSERT INTO Routes_stops (id_route, id_stop, stop_order) VALUES (?, ?, ?)");
  $stmt->execute([$routeId, $id_stop, $stop_order]);
  header("Location: manage_route_stops.php?id=$routeId");
  exit;
}

// Удаление остановки из маршрута
if ($routeId && isset($_GET['delete'])) {
  $id_route_stop = (int)$_GET['delete'];
  $pdo->prepare("DELETE FROM Routes_stops WHERE id_route_stop = ?")->execute([$id_route_stop]);
  header("Location: manage_route_stops.php?id=$routeId");
  exit;
}

// Обновление порядка следования остановок
if ($routeId && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_orders'])) {
  foreach ($_POST['orders'] as $id_route_stop => $order) {
    $stmt = $pdo->prepare("UPDATE Routes_stops SET stop_order = ? WHERE id_route_stop = ?");
    $stmt->execute([(int)$order, (int)$id_route_stop]);
  }
  header("Location: manage_route_stops.php?id=$routeId");
  exit;
}

$route = null;
$routeStops = [];
if ($routeId) {
  $stmt = $pdo->prepare("SELECT route_name, route_number FROM Routes WHERE id_route = ?");
  $stmt->execute([$routeId]);
  $route = $stmt->fetch();

  $stmt2 = $pdo->prepare("SELECT rs.id_route_stop, rs.stop_order, s.stop_name FROM Routes_stops rs JOIN Stops s ON rs.id_stop = s.id_stop WHERE rs.id_route = ? ORDER BY rs.stop_order");
  $stmt2->execute([$routeId]);
  $routeStops = $stmt2->fetchAll();
}
$userName = $_SESSION['user']['name'];
$role = $_SESSION['user']['role'];
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <title><?= $routeId ? "Остановки маршрута" : "Управление остановками" ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
  <?php include '../includes/admin_navbar.php'; ?>
  <div class="container py-4">
    <h1><?= $routeId ? "Остановки маршрута №" . htmlspecialchars($route['route_number']) . " — " . htmlspecialchars($route['route_name']) : "Управление остановками" ?></h1>

    <div class="row">
      <div class="col-md-6">
        <h5>Создать остановку</h5>
        <form method="post" class="mb-4">
          <input type="hidden" name="create_stop" value="1">
          <?php if ($routeId): ?>
            <input type="hidden" name="add_to_route" value="1">
          <?php endif; ?>
          <div class="mb-2">
            <label class="form-label">Название</label>
            <input type="text" name="stop_name" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Широта</label>
            <input type="text" name="latitude" class="form-control">
          </div>
          <div class="mb-2">
            <label class="form-label">Долгота</label>
            <input type="text" name="longitude" class="form-control">
          </div>
          <?php if ($routeId): ?>
            <div class="mb-2">
              <label class="form-label">Порядок в маршруте</label>
              <input type="number" name="stop_order" class="form-control">
            </div>
          <?php endif; ?>
          <button class="btn btn-primary">Создать<?= $routeId ? ' и добавить в маршрут' : '' ?></button>
        </form>
      </div>

      <?php if ($routeId): ?>
        <div class="col-md-6">
          <h5>Добавить существующую остановку в маршрут</h5>
          <form method="post" class="mb-4">
            <input type="hidden" name="add_stop" value="1">
            <div class="mb-2">
              <label class="form-label">Остановка</label>
              <select name="id_stop" class="form-select" required>
                <?php foreach ($allStops as $stop): ?>
                  <option value="<?= $stop['id_stop'] ?>"><?= htmlspecialchars($stop['stop_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Порядок</label>
              <input type="number" name="stop_order" class="form-control" required>
            </div>
            <button class="btn btn-success">Добавить</button>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($routeId): ?>
      <form method="post">
        <input type="hidden" name="update_orders" value="1">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>Остановка</th>
              <th>Порядок</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($routeStops as $stop): ?>
              <tr>
                <td><?= $stop['id_route_stop'] ?></td>
                <td><?= htmlspecialchars($stop['stop_name']) ?></td>
                <td>
                  <input type="number" name="orders[<?= $stop['id_route_stop'] ?>]" value="<?= $stop['stop_order'] ?>" class="form-control">
                </td>
                <td>
                  <a href="?id=<?= $routeId ?>&delete=<?= $stop['id_route_stop'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить остановку из маршрута?')">Удалить</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <button class="btn btn-success">Сохранить порядок</button>
      </form>
      <a href="route_details.php?id=<?= $routeId ?>" class="btn btn-secondary mt-4">Назад</a>
    <?php else: ?>
      <h5 class="mt-5">Список всех остановок</h5>
      <ul class="list-group">
        <?php foreach ($allStops as $stop): ?>
          <li class="list-group-item"> <?= htmlspecialchars($stop['stop_name']) ?> (<?= $stop['latitude'] ?>, <?= $stop['longitude'] ?>)</li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>