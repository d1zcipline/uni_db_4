<!-- TODO -->
<?php
$title = "Панель водителя";
$userName = $_SESSION['user']['name'];
$role = $_SESSION['user']['role'];
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <!-- Аналогичная шапка как в dashboard_admin.php -->
</head>

<body>
  <nav>...</nav>

  <div class="container my-5">
    <h1 class="mb-4"><?= $title ?></h1>
    <div class="card mb-4">
      <div class="card-header">Мое расписание</div>
      <div class="card-body">
        <!-- Вывод расписания водителя -->
      </div>
    </div>
    <div class="card">
      <div class="card-header">Текущий маршрут</div>
      <div class="card-body">
        <!-- Информация о текущем маршруте -->
      </div>
    </div>
  </div>
</body>

</html>