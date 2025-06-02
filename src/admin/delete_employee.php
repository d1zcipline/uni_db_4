<?php
session_start();
require '../includes/db.php';
require 'functions.php';

require_admin();

// Проверка ID сотрудника
$employeeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($employeeId === 0) {
  $_SESSION['employee_errors'] = ["Неверный ID сотрудника"];
  header('Location: ../admin_employees.php');
  exit;
}

// Запрет удаления самого себя
if ($employeeId === $_SESSION['user']['id']) {
  $_SESSION['employee_errors'] = ["Вы не можете удалить свою учетную запись"];
  header('Location: ../admin_employees.php');
  exit;
}

// Проверка что сотрудник существует
$stmt = $pdo->prepare("
    SELECT p.role 
    FROM Employees e
    JOIN Employee_positions p ON e.id_position = p.id_position
    WHERE e.id_employee = ?
");
$stmt->execute([$employeeId]);
$role = $stmt->fetchColumn();

// Проверка последнего администратора
if ($role === 'Администратор') {
  $adminCountStmt = $pdo->query("SELECT COUNT(*) FROM Employees 
                                  JOIN Employee_positions ON Employees.id_position = Employee_positions.id_position
                                  WHERE role = 'Администратор'");
  $adminCount = $adminCountStmt->fetchColumn();

  if ($adminCount <= 1) {
    $_SESSION['employee_errors'] = ["Нельзя удалить последнего администратора"];
    header('Location: ../admin_employees.php');
    exit;
  }
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

  if ($role === 'Администратор' && !require_super_admin($pdo)) {
    $_SESSION['employee_errors'] = ["Только главный администратор может удалять других администраторов"];
    header('Location: ../admin_employees.php');
    exit;
  }
  if ($role === 'Администратор') {
    $_SESSION['employee_success'] = "Администратор успешно удален";
  }
} catch (PDOException $e) {
  $_SESSION['employee_errors'] = ["Ошибка при удалении сотрудника: " . $e->getMessage()];
}

header('Location: ../admin_employees.php');
exit;
