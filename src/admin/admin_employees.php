<?php
session_start();
require '../includes/db.php';

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
  header('Location: index.php');
  exit;
}

// Получение списка пользователей (кроме администраторов)
$stmt = $pdo->prepare("
    SELECT e.id_employee, e.first_name, e.middle_name, e.last_name, e.email, e.phone, p.role, b.bus_park_name
    FROM Employees e
    JOIN Employee_positions p ON e.id_position = p.id_position
    LEFT JOIN Bus_parks b ON e.id_bus_park = b.id_bus_park
    WHERE p.role IN ('водитель', 'диспетчер')
    ORDER BY e.created_at DESC
");
$stmt->execute();
$employees = $stmt->fetchAll();

// Получение данных для формы
$rolesStmt = $pdo->query("SELECT * FROM Employee_positions WHERE role IN ('водитель', 'диспетчер')");
$roles = $rolesStmt->fetchAll();

$parksStmt = $pdo->query("SELECT * FROM Bus_parks");
$parks = $parksStmt->fetchAll();

$title = "Управление сотрудниками";
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
      <a class="navbar-brand" href="index.php">Московский транспорт</a>
      <div class="d-flex align-items-center">
        <span class="text-light me-3"><?= $userName ?> (администратор)</span>
        <a href="logout.php" class="btn btn-outline-light">Выйти</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <h1 class="mb-4"><?= $title ?></h1>

    <!-- Форма добавления нового сотрудника -->
    <div class="card mb-5">
      <div class="card-header bg-success text-white">
        <h5 class="mb-0">Добавить нового сотрудника</h5>
      </div>
      <div class="card-body">
        <form action="add_employee.php" method="POST">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Имя</label>
              <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Отчество</label>
              <input type="text" name="middle_name" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Фамилия</label>
              <input type="text" name="last_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Телефон</label>
              <input type="tel" name="phone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Должность</label>
              <select name="role" class="form-select" required>
                <?php foreach ($roles as $role): ?>
                  <option value="<?= $role['id_position'] ?>"><?= $role['role'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Автопарк</label>
              <select name="bus_park" class="form-select">
                <option value="">Не назначен</option>
                <?php foreach ($parks as $park): ?>
                  <option value="<?= $park['id_bus_park'] ?>"><?= $park['bus_park_name'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Пароль</label>
              <input type="password" name="password" class="form-control" required>
              <div class="form-text">Минимум 8 символов</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Подтверждение пароля</label>
              <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <div class="col-12 mt-3">
              <button type="submit" class="btn btn-success">
                <i class="bi bi-person-plus"></i> Добавить сотрудника
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Таблица существующих сотрудников -->
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Список сотрудников</h5>
      </div>
      <div class="card-body">
        <?php if (empty($employees)): ?>
          <div class="alert alert-info">Нет зарегистрированных сотрудников</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>ФИО</th>
                  <th>Email</th>
                  <th>Телефон</th>
                  <th>Должность</th>
                  <th>Автопарк</th>
                  <th>Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($employees as $employee): ?>
                  <tr>
                    <td><?= htmlspecialchars($employee['last_name']) ?> <?= htmlspecialchars($employee['first_name']) ?> <?= htmlspecialchars($employee['middle_name']) ?></td>
                    <td><?= htmlspecialchars($employee['email']) ?></td>
                    <td><?= htmlspecialchars($employee['phone']) ?></td>
                    <td><?= htmlspecialchars($employee['role']) ?></td>
                    <td><?= htmlspecialchars($employee['bus_park_name'] ?? 'Не назначен') ?></td>
                    <td>
                      <a href="#" class="btn btn-sm btn-outline-primary me-2">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <a href="delete_employee.php?id=<?= $employee['id_employee'] ?>" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>

</html>