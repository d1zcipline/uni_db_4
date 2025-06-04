<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$errors = [];

// Валидация данных
$required = [
  'id_employee' => 'Водитель',
  'id_work_shift_type' => 'Тип смены',
  'shift_date' => 'Дата смены'
];

foreach ($required as $field => $name) {
  if (empty($_POST[$field])) {
    $errors[] = "Поле '$name' обязательно для заполнения";
  }
}

// Проверка, что сотрудник - водитель
if (empty($errors)) {
  $stmt = $pdo->prepare("
        SELECT p.role 
        FROM Employees e
        JOIN Employee_positions p ON e.id_position = p.id_position
        WHERE e.id_employee = ? AND p.role = 'водитель'
    ");
  $stmt->execute([$_POST['id_employee']]);
  if (!$stmt->fetch()) {
    $errors[] = "Выбранный сотрудник не является водителем";
  }
}

// Проверка на дублирование смены
if (empty($errors)) {
  $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM Work_shifts 
        WHERE id_employee = ? AND shift_date = ?
    ");
  $stmt->execute([$_POST['id_employee'], $_POST['shift_date']]);
  if ($stmt->fetchColumn() > 0) {
    $errors[] = "У этого водителя уже есть смена на указанную дату";
  }
}

if (empty($errors)) {
  try {
    $stmt = $pdo->prepare("
            INSERT INTO Work_shifts (
                id_work_shift_type, id_employee, shift_date, id_route
            ) VALUES (?, ?, ?, ?)
        ");

    $stmt->execute([
      $_POST['id_work_shift_type'],
      $_POST['id_employee'],
      $_POST['shift_date'],
      !empty($_POST['id_route']) ? $_POST['id_route'] : null
    ]);

    $_SESSION['shift_success'] = "Смена успешно назначена";
  } catch (PDOException $e) {
    $errors[] = "Ошибка базы данных: " . $e->getMessage();
  }
}

if (!empty($errors)) {
  $_SESSION['shift_errors'] = $errors;
  $_SESSION['shift_form_data'] = $_POST;
}

header('Location: work_shifts.php');
exit;
