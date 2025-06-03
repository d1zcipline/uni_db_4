<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$errors = [];

// Валидация данных
$required = [
  'license_plate' => 'Государственный номер',
  'id_bus_type' => 'Тип автобуса',
  'id_status' => 'Статус',
  'capacity' => 'Вместимость'
];

foreach ($required as $field => $name) {
  if (empty($_POST[$field])) {
    $errors[] = "Поле '$name' обязательно для заполнения";
  }
}

// Проверка формата госномера
if (!preg_match('/^[А-ЯA-Z]{1}\d{3}[А-ЯA-Z]{2}\d{2,3}$/u', $_POST['license_plate'])) {
  $errors[] = "Неверный формат государственного номера";
}

// Проверка уникальности госномера
$stmt = $pdo->prepare("SELECT COUNT(*) FROM Buses WHERE license_plate = ?");
$stmt->execute([$_POST['license_plate']]);
if ($stmt->fetchColumn() > 0) {
  $errors[] = "Автобус с таким номером уже существует";
}

// Проверка числовых значений
if (!is_numeric($_POST['capacity']) || $_POST['capacity'] <= 0) {
  $errors[] = "Вместимость должна быть положительным числом";
}

if (empty($errors)) {
  try {
    $stmt = $pdo->prepare("
            INSERT INTO Buses (
                id_bus_type, id_status, id_bus_park, 
                license_plate, capacity, manufacture_year, 
                last_maintenance_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

    $stmt->execute([
      $_POST['id_bus_type'],
      $_POST['id_status'],
      !empty($_POST['id_bus_park']) ? $_POST['id_bus_park'] : null,
      $_POST['license_plate'],
      $_POST['capacity'],
      !empty($_POST['manufacture_year']) ? $_POST['manufacture_year'] : null,
      !empty($_POST['last_maintenance_date']) ? $_POST['last_maintenance_date'] : null
    ]);

    $_SESSION['bus_success'] = "Автобус успешно добавлен";
  } catch (PDOException $e) {
    $errors[] = "Ошибка базы данных: " . $e->getMessage();
    error_log("DB Error: " . $e->getMessage());
  }
}

if (!empty($errors)) {
  $_SESSION['bus_errors'] = $errors;
  $_SESSION['bus_form_data'] = $_POST;
}

if (!empty($errors)) {
  echo "<script>console.error('Ошибки валидации:', " . json_encode($errors) . ");</script>";
}

header('Location: ../admin_buses.php');
exit;
