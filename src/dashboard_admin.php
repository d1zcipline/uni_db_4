<?php
$title = "Панель администратора";
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
  <?php include 'includes/admin_navbar.php'; ?>

  <div class="container my-5">
    <h1 class="mb-4"><?= $title ?></h1>
    <div class="row">
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-primary text-white">Управление сотрудниками</div>
          <div class="card-body">
            <p>Управление учетными записями</p>
            <a href="admin_employees.php" class="btn btn-primary">Перейти</a>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-success text-white">Маршруты</div>
          <div class="card-body">
            <p>Управление маршрутами и расписанием</p>
            <a href="admin_routes.php" class="btn btn-success">Перейти</a>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-info text-white">Транспорт</div>
          <div class="card-body">
            <p>Учет автобусов и их техническое состояние</p>
            <a href="admin_buses.php" class="btn btn-info">Перейти</a>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-primary text-white">Автопарки</div>
          <div class="card-body">
            <p>Управление автопарками</p>
            <a href="admin_bus_parks.php" class="btn btn-primary">Перейти</a>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-success text-white">Районы</div>
          <div class="card-body">
            <p>Управление районами</p>
            <a href="admin/districts.php" class="btn btn-success">Перейти</a>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-info text-white">Адреса</div>
          <div class="card-body">
            <p>Список добавленных адресов</p>
            <a href="admin/locations.php" class="btn btn-info">Перейти</a>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-primary text-white">Типы автобусов</div>
          <div class="card-body">
            <p>Управление типами автобусов</p>
            <a href="admin/admin_bus_types.php" class="btn btn-primary">Перейти</a>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-success text-white">Статусы состояния автобусов</div>
          <div class="card-body">
            <p>Управление статусами</p>
            <a href="admin/statuses.php" class="btn btn-success">Перейти</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>