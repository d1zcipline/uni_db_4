<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$recordId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$busId = isset($_GET['bus_id']) ? (int)$_GET['bus_id'] : 0;

// Проверяем существование записи
$stmt = $pdo->prepare("
    SELECT mr.*, b.id_bus, b.last_maintenance_date 
    FROM Maintenance_records mr
    JOIN Buses b ON mr.id_bus = b.id_bus
    WHERE mr.id_maintenance = ?
");
$stmt->execute([$recordId]);
$record = $stmt->fetch();

if (!$record) {
  $_SESSION['maintenance_error'] = "Запись обслуживания не найдена";
  header('Location: ../admin_buses.php');
  exit;
}

try {
  $pdo->beginTransaction();

  // Удаляем запись о ТО
  $stmt = $pdo->prepare("DELETE FROM Maintenance_records WHERE id_maintenance = ?");
  $stmt->execute([$recordId]);

  // Если удаленная запись была последним ТО, обновляем дату последнего ТО в автобусе
  if ($record['maintenance_date'] == $record['last_maintenance_date']) {
    $stmt = $pdo->prepare("
            SELECT MAX(maintenance_date) 
            FROM Maintenance_records 
            WHERE id_bus = ? AND id_maintenance_status = 2
        ");
    $stmt->execute([$record['id_bus']]);
    $lastDate = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
            UPDATE Buses 
            SET last_maintenance_date = ? 
            WHERE id_bus = ?
        ");
    $stmt->execute([$lastDate, $record['id_bus']]);
  }

  $pdo->commit();
  $_SESSION['maintenance_success'] = "Запись обслуживания успешно удалена";
} catch (PDOException $e) {
  $pdo->rollBack();
  $_SESSION['maintenance_error'] = "Ошибка при удалении записи: " . $e->getMessage();
}

header("Location: bus_maintenance.php?id=" . ($busId ? $busId : $record['id_bus']));
exit;
