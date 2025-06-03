<?php
require_once '../includes/db.php';
require_once '../admin/functions.php';
session_start();
require_admin();

// Включим отображение ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подготовим массив для ответа
$response = ['success' => false, 'message' => '', 'errors' => []];

try {
  // Валидация данных
  $requiredFields = [
    'route_number' => 'Номер маршрута',
    'route_name' => 'Название маршрута',
    'start_point' => 'Начальная остановка',
    'end_point' => 'Конечная остановка',
    'distance' => 'Протяженность маршрута'
  ];

  foreach ($requiredFields as $field => $name) {
    if (empty($_POST[$field])) {
      $response['errors'][] = "Поле '$name' обязательно для заполнения";
    }
  }

  // Проверка числовых полей
  if (!is_numeric($_POST['distance']) || $_POST['distance'] <= 0) {
    $response['errors'][] = "Протяженность должна быть положительным числом";
  }

  // Если есть ошибки - возвращаем их
  if (!empty($response['errors'])) {
    $_SESSION['route_errors'] = $response['errors'];
    header('Location: /admin_routes.php');
    exit;
  }

  // Подготовка данных
  $data = [
    'id_bus_park' => !empty($_POST['id_bus_park']) ? (int)$_POST['id_bus_park'] : null,
    'route_name' => trim($_POST['route_name']),
    'route_number' => trim($_POST['route_number']),
    'route_type' => $_POST['route_type'] ?? 'городской',
    'start_point' => trim($_POST['start_point']),
    'end_point' => trim($_POST['end_point']),
    'distance' => (float)$_POST['distance'],
    'active' => isset($_POST['active']) ? 1 : 0
  ];

  // Проверка существования автопарка
  if ($data['id_bus_park']) {
    $stmt = $pdo->prepare("SELECT 1 FROM Bus_parks WHERE id_bus_park = ?");
    $stmt->execute([$data['id_bus_park']]);
    if (!$stmt->fetch()) {
      $response['errors'][] = "Указанный автопарк не существует";
    }
  }

  // Если все проверки пройдены - вставляем данные
  if (empty($response['errors'])) {
    $stmt = $pdo->prepare("
            INSERT INTO Routes (
                id_bus_park, route_name, route_number, 
                route_type, start_point, end_point, 
                distance, active
            ) VALUES (
                :id_bus_park, :route_name, :route_number, 
                :route_type, :start_point, :end_point, 
                :distance, :active
            )
        ");

    $stmt->execute($data);

    $response['success'] = true;
    $response['message'] = "Маршрут успешно добавлен!";
    $_SESSION['route_success'] = $response['message'];
  }
} catch (PDOException $e) {
  $response['errors'][] = "Ошибка базы данных: " . $e->getMessage();
  error_log("DB Error: " . $e->getMessage());
}

// Сохраняем ошибки в сессии, если они есть
if (!empty($response['errors'])) {
  $_SESSION['route_errors'] = $response['errors'];
  $_SESSION['route_form_data'] = $_POST; // Сохраняем введенные данные
}

// Перенаправляем обратно на страницу маршрутов
header('Location: /admin_routes.php');
exit;
