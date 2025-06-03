<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$errors = [];

// Валидация данных
$required = [
  'bus_park_name' => 'Название автопарка',
  'id_district' => 'Район',
  'address' => 'Адрес',
  'capacity' => 'Вместимость'
];

foreach ($required as $field => $name) {
  if (empty($_POST[$field])) {
    $errors[] = "Поле '$name' обязательно для заполнения";
  }
}

// Проверка числовых значений
if (!is_numeric($_POST['capacity']) || $_POST['capacity'] <= 0) {
  $errors[] = "Вместимость должна быть положительным числом";
}

// Проверка существования района
if (empty($errors)) {
  $stmt = $pdo->prepare("SELECT 1 FROM Districts WHERE id_district = ?");
  $stmt->execute([$_POST['id_district']]);
  if (!$stmt->fetch()) {
    $errors[] = "Указанный район не существует";
  }
}

if (empty($errors)) {
  try {
    // Добавляем локацию
    $stmt = $pdo->prepare("
            INSERT INTO Locations (id_district, address)
            VALUES (?, ?)
        ");
    $stmt->execute([
      $_POST['id_district'],
      trim($_POST['address'])
    ]);
    $locationId = $pdo->lastInsertId();

    // Добавляем автопарк
    $stmt = $pdo->prepare("
            INSERT INTO Bus_parks (id_location, bus_park_name, capacity)
            VALUES (?, ?, ?)
        ");
    $stmt->execute([
      $locationId,
      trim($_POST['bus_park_name']),
      (int)$_POST['capacity']
    ]);

    $_SESSION['park_success'] = "Автопарк успешно добавлен!";
  } catch (PDOException $e) {
    $errors[] = "Ошибка базы данных: " . $e->getMessage();
    error_log("DB Error: " . $e->getMessage());
  }
}

if (!empty($errors)) {
  $_SESSION['park_errors'] = $errors;
  $_SESSION['park_form_data'] = $_POST;
}

header('Location: bus_parks.php');
exit;
