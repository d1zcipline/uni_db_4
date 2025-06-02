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
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="#">Московский транспорт</a>
      <div class="d-flex align-items-center">
        <span class="text-light me-3"><?= $userName ?> (<?= $role ?>)</span>
        <a href="logout.php" class="btn btn-outline-light">Выйти</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <h1 class="mb-4"><?= $title ?></h1>
    <div class="row">
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-primary text-white">Управление сотрудниками</div>
          <div class="card-body">
            <p>Добавление, редактирование и блокировка учетных записей</p>
            <a href="admin_employees.php" class="btn btn-primary">Перейти</a>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-success text-white">Маршруты</div>
          <div class="card-body">
            <p>Управление маршрутами и расписанием</p>
            <a href="#" class="btn btn-success">Перейти</a>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-header bg-info text-white">Транспорт</div>
          <div class="card-body">
            <p>Учет автобусов и их техническое состояние</p>
            <a href="#" class="btn btn-info">Перейти</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>