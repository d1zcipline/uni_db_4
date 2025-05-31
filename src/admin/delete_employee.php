<?php
session_start();
require 'db.php';

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
  header('Location: index.php');
  exit;
}

// Проверка ID сотрудника
$employeeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($employeeId === 0) {
  $_SESSION['employee_errors'] = ["Неверный ID сотрудника"];
  header('Location: admin_employees.php');
  exit;
}

// Проверка что сотрудник не администратор
$stmt = $pdo->prepare("
    SELECT p.role 
    FROM Employees e
    JOIN Employee_positions p ON e.id_position = p.id_position
    WHERE e.id_employee = ?
");
$stmt->execute([$employeeId]);
$role = $stmt->fetchColumn();

if ($role === 'администратор') {
  $_SESSION['employee_errors'] = ["Нельзя удалить администратора"];
  header('Location: admin_employees.php');
  exit;
}

// Удаление сотрудника
try {
  $stmt = $pdo->prepare("DELETE FROM Employees WHERE id_employee = ?");
  $stmt->execute([$employeeId]);

  if ($stmt->rowCount() > 0) {
    $_SESSION['employee_success'] = "Сотрудник успешно удален";
  } else {
    $_SESSION['employee_errors'] = ["Сотрудник не найден"];
  }
} catch (PDOException $e) {
  $_SESSION['employee_errors'] = ["Ошибка при удалении сотрудника: " . $e->getMessage()];
}

header('Location: admin_employees.php');
exit;
