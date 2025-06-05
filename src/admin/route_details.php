<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

// Получение ID маршрута
$routeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$routeId) {
  die("Некорректный ID маршрута");
}

// Получение данных маршрута
$routeStmt = $pdo->prepare("SELECT r.*, b.bus_park_name FROM Routes r LEFT JOIN Bus_parks b ON r.id_bus_park = b.id_bus_park WHERE r.id_route = ?");
$routeStmt->execute([$routeId]);
$route = $routeStmt->fetch();

if (!$route) {
  die("Маршрут не найден");
}

// Получение остановок маршрута
$stopsStmt = $pdo->prepare("SELECT rs.stop_order, s.stop_name, s.latitude, s.longitude FROM Routes_stops rs JOIN Stops s ON rs.id_stop = s.id_stop WHERE rs.id_route = ? ORDER BY rs.stop_order");
$stopsStmt->execute([$routeId]);
$stops = $stopsStmt->fetchAll();

$title = "Маршрут №" . htmlspecialchars($route['route_number']);
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

  <div class="container py-5">
    <h1><?= $title ?> - <?= htmlspecialchars($route['route_name']) ?></h1>
    <p>
      <strong>Тип:</strong> <?= htmlspecialchars($route['route_type']) ?><br>
      <strong>Начало:</strong> <?= htmlspecialchars($route['start_point']) ?><br>
      <strong>Конец:</strong> <?= htmlspecialchars($route['end_point']) ?><br>
      <strong>Протяженность:</strong> <?= $route['distance'] ?> км<br>
      <strong>Автопарк:</strong> <?= htmlspecialchars($route['bus_park_name'] ?? 'Не назначен') ?><br>
      <strong>Статус:</strong> <?= $route['active'] ? 'Активен' : 'Неактивен' ?>
    </p>
    <a href="manage_route_stops.php?id=<?= $route['id_route'] ?>" class="btn btn-outline-secondary btn-sm">Редактировать остановки</a>

    <h3>Остановки на маршруте</h3>
    <?php if ($stops): ?>
      <ol class="list-group list-group-numbered">
        <?php foreach ($stops as $stop): ?>
          <li class="list-group-item">
            <?= htmlspecialchars($stop['stop_name']) ?>
            <small class="text-muted">(<?= $stop['latitude'] ?>, <?= $stop['longitude'] ?>)</small>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php else: ?>
      <div class="alert alert-warning">Для этого маршрута ещё не назначены остановки.</div>
    <?php endif; ?>

    <a href="../admin_routes.php" class="btn btn-secondary mt-4">Назад к маршрутам</a>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>