<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$shiftId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Проверяем существование смены
$stmt = $pdo->prepare("SELECT * FROM Work_shifts WHERE id_work_shift = ?");
$stmt->execute([$shiftId]);
$shift = $stmt->fetch();

if (!$shift) {
  $_SESSION['shift_error'] = "Смена не найдена";
  header('Location: work_shifts.php');
  exit;
}

try {
  $stmt = $pdo->prepare("DELETE FROM Work_shifts WHERE id_work_shift = ?");
  $stmt->execute([$shiftId]);

  $_SESSION['shift_success'] = "Смена успешно удалена";
} catch (PDOException $e) {
  $_SESSION['shift_error'] = "Ошибка при удалении смены: " . $e->getMessage();
}

header('Location: work_shifts.php');
exit;
