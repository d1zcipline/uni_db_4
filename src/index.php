<?php require_once 'includes/header.php'; ?>
<div class="jumbotron bg-light p-5 rounded-3">
  <h1 class="display-4">Система управления общественным транспортом Москвы</h1>
  <p class="lead">Комплексное решение для учета и управления транспортом, маршрутами и персоналом</p>
  <hr class="my-4">
  <p>Для доступа к системе требуется авторизация</p>
  <div class="d-flex gap-3">
    <a class="btn btn-primary btn-lg" href="login.php" role="button">Войти в систему</a>
    <a class="btn btn-outline-secondary btn-lg" href="register.php" role="button">Зарегистрироваться</a>
  </div>
</div>
<?php
require_once 'includes/db_connect.php';
try {
  $routesCount = $pdo->query("SELECT COUNT(*) FROM Routes")->fetchColumn();
  $busesCount = $pdo->query("SELECT COUNT(*) FROM Buses")->fetchColumn();
  echo "<div class='row mt-5'>";
  echo "<div class='col-md-4'><div class='card'><div class='card-body'><h5>Маршрутов</h5><p class='display-4'>$routesCount</p></div></div></div>";
  echo "<div class='col-md-4'><div class='card'><div class='card-body'><h5>Автобусов</h5><p class='display-4'>$busesCount</p></div></div></div>";
  echo "</div>";
} catch (Exception $e) {
  echo "<div class='alert alert-warning'>База данных доступна. Войдите для управления</div>";
}
?>
<?php require_once 'includes/footer.php'; ?>