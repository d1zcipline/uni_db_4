<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$busId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные автобуса
$stmt = $pdo->prepare("
    SELECT * FROM Buses 
    WHERE id_bus = ?
");
$stmt->execute([$busId]);
$bus = $stmt->fetch();

if (!$bus) {
  $_SESSION['bus_error'] = "Автобус не найден";
  header('Location: ../admin_buses.php');
  exit;
}

// Получаем данные для форм
$busTypes = $pdo->query("SELECT * FROM Bus_types ORDER BY bus_type_name")->fetchAll();
$statuses = $pdo->query("SELECT * FROM Statuses ORDER BY status_name")->fetchAll();
$parks = $pdo->query("SELECT * FROM Bus_parks ORDER BY bus_park_name")->fetchAll();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $errors = [];

  // Валидация
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
                UPDATE Buses SET
                    id_bus_type = ?,
                    id_status = ?,
                    id_bus_park = ?,
                    license_plate = ?,
                    capacity = ?,
                    manufacture_year = ?,
                    last_maintenance_date = ?
                WHERE id_bus = ?
            ");

      $stmt->execute([
        $_POST['id_bus_type'],
        $_POST['id_status'],
        !empty($_POST['id_bus_park']) ? $_POST['id_bus_park'] : null,
        $_POST['license_plate'],
        $_POST['capacity'],
        !empty($_POST['manufacture_year']) ? $_POST['manufacture_year'] : null,
        !empty($_POST['last_maintenance_date']) ? $_POST['last_maintenance_date'] : null,
        $busId
      ]);

      $_SESSION['bus_success'] = "Данные автобуса обновлены";
      header('Location: ../admin_buses.php');
      exit;
    } catch (PDOException $e) {
      $errors[] = "Ошибка базы данных: " . $e->getMessage();
    }
  }

  if (!empty($errors)) {
    $_SESSION['bus_errors'] = $errors;
  }
}

$title = "Редактирование автобуса";
include '_header.php';
?>

<!-- Форма редактирования (аналогична форме добавления, но с предзаполненными значениями) -->
<!-- ... -->