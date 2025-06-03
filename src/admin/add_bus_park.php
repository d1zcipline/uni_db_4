<?php
require_once 'includes/db.php';
require_once 'admin/functions.php';
session_start();
require_admin();

$errors = [];

// Валидация данных
$parkName = trim($_POST['bus_park_name'] ?? '');
$districtId = (int)($_POST['id_district'] ?? 0);
$address = trim($_POST['address'] ?? '');
$capacity = (int)($_POST['capacity'] ?? 0);

if (empty($parkName)) $errors[] = "Название автопарка обязательно";
if ($districtId === 0) $errors[] = "Необходимо выбрать район";
if (empty($address)) $errors[] = "Адрес обязателен";
if ($capacity <= 0) $errors[] = "Вместимость должна быть больше 0";

if (empty($errors)) {
  try {
    // Сначала добавляем локацию
    $stmt = $pdo->prepare("
            INSERT INTO Locations (id_district, address)
            VALUES (?, ?)
        ");
    $stmt->execute([$districtId, $address]);
    $locationId = $pdo->lastInsertId();

    // Затем добавляем автопарк
    $stmt = $pdo->prepare("
            INSERT INTO Bus_parks (id_location, bus_park_name, capacity)
            VALUES (?, ?, ?)
        ");
    $stmt->execute([$locationId, $parkName, $capacity]);

    $_SESSION['success'] = "Автопарк успешно добавлен!";
    header('Location: bus_parks.php');
    exit;
  } catch (PDOException $e) {
    $errors[] = "Ошибка при добавлении автопарка: " . $e->getMessage();
  }
}

$_SESSION['errors'] = $errors;
header('Location: bus_parks.php');
exit;
