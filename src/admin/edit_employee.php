<?php
require '../includes/db.php';
require_once __DIR__ . '/functions.php';
session_start();
require_admin();

// Получаем ID сотрудника для редактирования
$employeeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные сотрудника
$stmt = $pdo->prepare("
    SELECT e.*, p.role 
    FROM Employees e
    JOIN Employee_positions p ON e.id_position = p.id_position
    WHERE e.id_employee = ?
");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch();

// Проверка существования сотрудника
if (!$employee) {
  $_SESSION['employee_errors'] = ["Сотрудник не найден"];
  header('Location: admin_employees.php');
  exit;
}

// Получаем данные для формы
$rolesStmt = $pdo->query("SELECT * FROM Employee_positions");
$roles = $rolesStmt->fetchAll();

$parksStmt = $pdo->query("SELECT * FROM Bus_parks");
$parks = $parksStmt->fetchAll();

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $errors = [];

  // Валидация данных
  $firstName = trim($_POST['first_name'] ?? '');
  $lastName = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $roleId = (int)($_POST['role'] ?? 0);
  $busParkId = !empty($_POST['bus_park']) ? (int)$_POST['bus_park'] : null;
  $active = isset($_POST['active']) ? 1 : 0;

  // Проверка обязательных полей
  if (empty($firstName)) $errors[] = "Имя обязательно для заполнения";
  if (empty($lastName)) $errors[] = "Фамилия обязательна для заполнения";
  if (empty($email)) $errors[] = "Email обязателен для заполнения";
  if ($roleId === 0) $errors[] = "Не выбрана должность";

  // Проверка email
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Некорректный формат email";
  } else {
    // Проверка уникальности email (исключая текущего сотрудника)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Employees WHERE email = ? AND id_employee != ?");
    $stmt->execute([$email, $employeeId]);
    if ($stmt->fetchColumn() > 0) {
      $errors[] = "Пользователь с таким email уже существует";
    }
  }

  // Если нет ошибок - обновляем данные
  if (empty($errors)) {
    try {
      $stmt = $pdo->prepare("
                UPDATE Employees SET
                    id_bus_park = ?,
                    id_position = ?,
                    first_name = ?,
                    middle_name = ?,
                    last_name = ?,
                    phone = ?,
                    email = ?,
                    active = ?
                WHERE id_employee = ?
            ");

      $stmt->execute([
        $busParkId,
        $roleId,
        $firstName,
        $_POST['middle_name'] ?? null,
        $lastName,
        $_POST['phone'] ?? null,
        $email,
        $active,
        $employeeId
      ]);

      $_SESSION['employee_success'] = "Данные сотрудника успешно обновлены!";
      header('Location: ../admin_employees.php');
      exit;
    } catch (PDOException $e) {
      $errors[] = "Ошибка при обновлении данных: " . $e->getMessage();
    }
  }

  if (!empty($errors)) {
    $_SESSION['employee_errors'] = $errors;
  }
}

$title = "Редактирование сотрудника";
$userName = $_SESSION['user']['name'];
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
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="../index.php">Московский транспорт</a>
      <div class="d-flex align-items-center">
        <span class="text-light me-3"><?= $userName ?> (Администратор)</span>
        <a href="../logout.php" class="btn btn-outline-light">Выйти</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <h1 class="mb-4"><?= $title ?></h1>

    <?php if (isset($_SESSION['employee_errors'])): ?>
      <div class="alert alert-danger">
        <?php foreach ($_SESSION['employee_errors'] as $error): ?>
          <p class="mb-0"><?= $error ?></p>
        <?php endforeach; ?>
        <?php unset($_SESSION['employee_errors']); ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Редактирование данных сотрудника</h5>
      </div>
      <div class="card-body">
        <form method="POST">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Имя</label>
              <input type="text" name="first_name" class="form-control"
                value="<?= htmlspecialchars($employee['first_name']) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Отчество</label>
              <input type="text" name="middle_name" class="form-control"
                value="<?= htmlspecialchars($employee['middle_name'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Фамилия</label>
              <input type="text" name="last_name" class="form-control"
                value="<?= htmlspecialchars($employee['last_name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control"
                value="<?= htmlspecialchars($employee['email']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Телефон</label>
              <input type="tel" name="phone" class="form-control"
                value="<?= htmlspecialchars($employee['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Должность</label>
              <select name="role" class="form-select" required>
                <?php foreach ($roles as $role): ?>
                  <option value="<?= $role['id_position'] ?>"
                    <?= $role['id_position'] == $employee['id_position'] ? 'selected' : '' ?>>
                    <?= $role['role'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Автопарк</label>
              <select name="bus_park" class="form-select">
                <option value="">Не назначен</option>
                <?php foreach ($parks as $park): ?>
                  <option value="<?= $park['id_bus_park'] ?>"
                    <?= $park['id_bus_park'] == $employee['id_bus_park'] ? 'selected' : '' ?>>
                    <?= $park['bus_park_name'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch"
                  id="activeSwitch" name="active" value="1"
                  <?= $employee['active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="activeSwitch">Активный сотрудник</label>
              </div>
            </div>
            <div class="col-12 mt-4">
              <button type="submit" class="btn btn-primary me-2">
                <i class="bi bi-save"></i> Сохранить изменения
              </button>
              <a href="admin_employees.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Назад к списку
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>

</html>