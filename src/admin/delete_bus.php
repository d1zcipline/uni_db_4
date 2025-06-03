<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$busId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Проверяем существование автобуса
$stmt = $pdo->prepare("SELECT * FROM Buses WHERE id_bus = ?");
$stmt->execute([$busId]);
$bus = $stmt->fetch();

if (!$bus) {
  $_SESSION['bus_error'] = "Автобус не найден";
  header('Location: buses.php');
  exit;
}

// Проверяем связанные записи
$checks = [
  'Work_shifts' => "SELECT COUNT(*) FROM Work_shifts WHERE id_bus = ?",
  'Maintenance_records' => "SELECT COUNT(*) FROM Maintenance_records WHERE id_bus = ?"
];

$canDelete = true;
foreach ($checks as $table => $sql) {
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$busId]);
  if ($stmt->fetchColumn() > 0) {
    $canDelete = false;
    $_SESSION['bus_error'] = "Невозможно удалить автобус: есть связанные записи в таблице $table";
    break;
  }
}

if ($canDelete) {
  try {
    $stmt = $pdo->prepare("DELETE FROM Buses WHERE id_bus = ?");
    $stmt->execute([$busId]);
    $_SESSION['bus_success'] = "Автобус успешно удален";
  } catch (PDOException $e) {
    $_SESSION['bus_error'] = "Ошибка при удалении автобуса: " . $e->getMessage();
  }
}

header('Location: ../admin_buses.php');
exit;
