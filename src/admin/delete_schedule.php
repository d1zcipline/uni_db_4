<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$scheduleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Проверяем существование расписания
$stmt = $pdo->prepare("SELECT * FROM Schedule WHERE id_schedule = ?");
$stmt->execute([$scheduleId]);
$schedule = $stmt->fetch();

if (!$schedule) {
  $_SESSION['schedule_error'] = "Расписание не найдено";
  header('Location: schedule.php');
  exit;
}

try {
  $stmt = $pdo->prepare("DELETE FROM Schedule WHERE id_schedule = ?");
  $stmt->execute([$scheduleId]);

  $_SESSION['schedule_success'] = "Расписание успешно удалено";
} catch (PDOException $e) {
  $_SESSION['schedule_error'] = "Ошибка при удалении расписания: " . $e->getMessage();
}

header('Location: schedule.php');
exit;
