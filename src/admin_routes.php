<?php
require_once 'includes/db.php';
require_once 'admin/functions.php';
session_start();
require_admin();

// Восстановление данных формы при ошибке
$formData = $_SESSION['route_form_data'] ?? [];
unset($_SESSION['route_form_data']);

// Получение списка маршрутов с информацией об автопарках
$routes = $pdo->query("
    SELECT r.*, b.bus_park_name 
    FROM Routes r
    LEFT JOIN Bus_parks b ON r.id_bus_park = b.id_bus_park
    ORDER BY r.route_number
")->fetchAll();

// Получение автопарков для формы
$parks = $pdo->query("SELECT * FROM Bus_parks ORDER BY bus_park_name")->fetchAll();

$title = "Управление маршрутами";
$userName = $_SESSION['user']['name'];
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <title><?= $title ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    .route-card {
      transition: all 0.3s ease;
    }

    .route-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

<body>
  <!-- Навигация -->
  <?php include 'includes/admin_navbar.php'; ?>

  <div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1><?= $title ?></h1>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRouteModal">
        <i class="bi bi-plus-lg"></i> Добавить маршрут
      </button>
    </div>

    <!-- Фильтры -->
    <div class="card mb-4">
      <div class="card-body">
        <form class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Номер маршрута</label>
            <input type="text" class="form-control" placeholder="Поиск...">
          </div>
          <div class="col-md-4">
            <label class="form-label">Автопарк</label>
            <select class="form-select">
              <option value="">Все автопарки</option>
              <?php foreach ($parks as $park): ?>
                <option value="<?= $park['id_bus_park'] ?>"><?= $park['bus_park_name'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Статус</label>
            <select class="form-select">
              <option value="">Все</option>
              <option value="1">Активные</option>
              <option value="0">Неактивные</option>
            </select>
          </div>
        </form>
      </div>
    </div>
    <?php
    if (isset($_SESSION['route_success'])) {
      echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['route_success']) . '</div>';
      unset($_SESSION['route_success']);
    }

    // Вывод сообщений об ошибках и успехе
    if (isset($_SESSION['route_errors'])) {
      foreach ($_SESSION['route_errors'] as $error) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
      }
      unset($_SESSION['route_errors']);
    }
    ?>
    <!-- Список маршрутов -->
    <div class="row">
      <?php foreach ($routes as $route): ?>
        <div class="col-md-6 mb-4">
          <div class="card route-card h-100 <?= $route['active'] ? 'border-success' : 'border-secondary' ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">
                Маршрут №<?= htmlspecialchars($route['route_number']) ?>
                <span class="badge bg-<?= $route['active'] ? 'success' : 'secondary' ?>">
                  <?= $route['active'] ? 'Активен' : 'Неактивен' ?>
                </span>
              </h5>
              <div>
                <!-- FIX -->
                <a href="edit_route.php?id=<?= $route['id_route'] ?>" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-pencil"></i>
                </a>
                <a href="delete_route.php?id=<?= $route['id_route'] ?>" class="btn btn-sm btn-outline-danger"
                  onclick="return confirm('Удалить этот маршрут?')">
                  <i class="bi bi-trash"></i>
                </a>
              </div>
            </div>
            <div class="card-body">
              <h6 class="card-subtitle mb-2 text-muted">
                <?= htmlspecialchars($route['route_name']) ?>
              </h6>
              <p class="card-text">
                <i class="bi bi-geo-alt"></i>
                <?= htmlspecialchars($route['start_point']) ?> → <?= htmlspecialchars($route['end_point']) ?>
                <br>
                <i class="bi bi-signpost"></i>
                Протяженность: <?= $route['distance'] ?> км
                <br>
                <i class="bi bi-building"></i>
                Автопарк: <?= htmlspecialchars($route['bus_park_name'] ?? 'Не назначен') ?>
              </p>
              <a href="route_details.php?id=<?= $route['id_route'] ?>" class="btn btn-outline-primary btn-sm">
                Подробнее <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Модальное окно добавления маршрута -->
  <div class="modal fade" id="addRouteModal" tabindex="-1" aria-labelledby="addRouteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addRouteModalLabel">Добавить новый маршрут</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="admin/add_route.php" method="POST">
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Номер маршрута</label>
                <input type="text" name="route_number" class="form-control"
                  value="<?= htmlspecialchars($formData['route_number'] ?? '') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Название маршрута</label>
                <input type="text" name="route_name" class="form-control"
                  value="<?= htmlspecialchars($formData['route_name'] ?? '') ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Тип маршрута</label>
                <select name="route_type" class="form-select" required>
                  <option value="городской">Городской</option>
                  <option value="пригородный">Пригородный</option>
                  <option value="экспресс">Экспресс</option>
                  <option value="кольцевой">Кольцевой</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Начальная остановка</label>
                <input type="text" name="start_point" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Конечная остановка</label>
                <input type="text" name="end_point" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Протяженность (км)</label>
                <input type="number" step="0.01" name="distance" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Автопарк</label>
                <select name="id_bus_park" class="form-select">
                  <option value="">Не назначен</option>
                  <?php foreach ($parks as $park): ?>
                    <option value="<?= $park['id_bus_park'] ?>"><?= $park['bus_park_name'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" role="switch"
                    id="activeSwitch" name="active" value="1" checked>
                  <label class="form-check-label" for="activeSwitch">Активный маршрут</label>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
            <button type="submit" class="btn btn-primary">Добавить маршрут</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>