<?php
session_start();
require 'db.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
  $_SESSION['error'] = 'Все поля обязательны для заполнения';
  header('Location: login.php');
  exit;
}

$stmt = $pdo->prepare("
    SELECT e.id_employee, e.password, e.first_name, p.role 
    FROM Employees e
    JOIN Employee_positions p ON e.id_position = p.id_position
    WHERE e.email = ? AND e.active = TRUE
");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
  $_SESSION['user'] = [
    'id' => $user['id_employee'],
    'name' => $user['first_name'],
    'role' => $user['role']
  ];
  header('Location: ../index.php');
} else {
  $_SESSION['error'] = 'Неверные учетные данные';
  header('Location: ../login.php');
}
exit;
