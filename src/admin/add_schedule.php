<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$errors = [];

// Валидация данных
$required = [
  'id_route' => 'Маршрут',
  'day_type' => 'Тип дня',
  'departure_time' => 'Время отправления',
  'arrival_time' => 'Время прибытия'
];

foreach ($required as $field => $name) {
  if (empty($_POST[$field])) {
    $errors[] = "Поле '$name' обязательно для заполнения";
  }
}

if (empty($errors)) {
  try {
    $stmt = $pdo->prepare("
            INSERT INTO Schedule (
                id_route, day_type, departure_time, 
                arrival_time, active
            ) VALUES (?, ?, ?, ?, ?)
        ");

    $stmt->execute([
      $_POST['id_route'],
      $_POST['day_type'],
      $_POST['departure_time'],
      $_POST['arrival_time'],
      isset($_POST['active']) ? 1 : 0
    ]);

    $_SESSION['schedule_success'] = "Расписание успешно добавлено";
  } catch (PDOException $e) {
    $errors[] = "Ошибка базы данных: " . $e->getMessage();
  }
}

if (!empty($errors)) {
  $_SESSION['schedule_errors'] = $errors;
  $_SESSION['schedule_form_data'] = $_POST;
}

header('Location: schedule.php');
exit;
