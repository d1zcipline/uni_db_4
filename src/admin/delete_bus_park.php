<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$parkId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Проверяем существование автопарка
$stmt = $pdo->prepare("SELECT * FROM Bus_parks WHERE id_bus_park = ?");
$stmt->execute([$parkId]);
$park = $stmt->fetch();

if (!$park) {
  $_SESSION['park_error'] = "Автопарк не найден";
  header('Location: ../admin_bus_parks.php');
  exit;
}

// Проверяем связанные сущности
$checks = [
  'Routes' => "SELECT COUNT(*) FROM Routes WHERE id_bus_park = ?",
  'Buses' => "SELECT COUNT(*) FROM Buses WHERE id_bus_park = ?",
  'Employees' => "SELECT COUNT(*) FROM Employees WHERE id_bus_park = ?"
];

$hasDependencies = false;
foreach ($checks as $entity => $sql) {
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$parkId]);
  $count = $stmt->fetchColumn();

  if ($count > 0) {
    $hasDependencies = true;
    $_SESSION['park_error'] = "Невозможно удалить автопарк: есть связанные записи в таблице $entity";
    break;
  }
}

if (!$hasDependencies) {
  try {
    $pdo->beginTransaction();

    // Удаляем автопарк
    $stmt = $pdo->prepare("DELETE FROM Bus_parks WHERE id_bus_park = ?");
    $stmt->execute([$parkId]);

    // Удаляем связанную локацию
    $stmt = $pdo->prepare("DELETE FROM Locations WHERE id_location = ?");
    $stmt->execute([$park['id_location']]);

    $pdo->commit();
    $_SESSION['park_success'] = "Автопарк успешно удален";
  } catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['park_error'] = "Ошибка при удалении автопарка: " . $e->getMessage();
  }
}

header('Location: ../admin_bus_parks.php');
exit;
