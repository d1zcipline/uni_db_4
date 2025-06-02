<?php
session_start();
require '../includes/db.php';

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'Администратор') {
  header('Location: ../admin_employees.php');
  exit;
}

// Валидация данных
$errors = [];

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$roleId = (int)($_POST['role'] ?? 0);
$busParkId = !empty($_POST['bus_park']) ? (int)$_POST['bus_park'] : null;

// Дополнительная валидация для администраторов
$roleId = (int)($_POST['role'] ?? 0);
$roleStmt = $pdo->prepare("SELECT role FROM Employee_positions WHERE id_position = ?");
$roleStmt->execute([$roleId]);
$roleName = $roleStmt->fetchColumn();

// Проверка обязательных полей
if (empty($firstName)) $errors[] = "Имя обязательно для заполнения";
if (empty($lastName)) $errors[] = "Фамилия обязательна для заполнения";
if (empty($email)) $errors[] = "Email обязателен для заполнения";
if (empty($password)) $errors[] = "Пароль обязателен для заполнения";
if ($password !== $confirmPassword) $errors[] = "Пароли не совпадают";
if (strlen($password) < 8) $errors[] = "Пароль должен содержать минимум 8 символов";
if ($roleId === 0) $errors[] = "Не выбрана должность";

// Проверка email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors[] = "Некорректный формат email";
} else {
  // Проверка уникальности email
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM Employees WHERE email = ?");
  $stmt->execute([$email]);
  if ($stmt->fetchColumn() > 0) {
    $errors[] = "Пользователь с таким email уже существует";
  }
}

// Если есть ошибки - возвращаем
if (!empty($errors)) {
  $_SESSION['employee_errors'] = $errors;
  header('Location: ../admin_employees.php');
  exit;
}

// Хеширование пароля
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Добавление сотрудника в БД
try {
  $stmt = $pdo->prepare("
        INSERT INTO Employees (
            id_bus_park, 
            id_position, 
            first_name, 
            middle_name, 
            last_name, 
            phone, 
            email, 
            password
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

  $stmt->execute([
    $busParkId,
    $roleId,
    $firstName,
    $_POST['middle_name'] ?? null,
    $lastName,
    $_POST['phone'] ?? null,
    $email,
    $passwordHash
  ]);

  $_SESSION['employee_success'] = "Сотрудник успешно добавлен!";
  if ($roleName === 'Администратор') {
    $_SESSION['employee_success'] = "Новый администратор успешно добавлен!";
  }
} catch (PDOException $e) {
  $_SESSION['employee_errors'] = ["Ошибка при добавлении сотрудника: " . $e->getMessage()];
}

header('Location: ../admin_employees.php');
exit;
