<?php
session_start();
require 'includes/db.php';

// Редирект на логин если не авторизован
if (!isset($_SESSION['user'])) {
  header('Location: login.php');
  exit;
}

// Подключение страницы в зависимости от роли
switch ($_SESSION['user']['role']) {
  case 'Администратор':
    require 'admin/index.php';
    break;
  case 'Диспетчер':
    require 'dashboard_dispatcher.php';
    break;
  case 'Водитель':
    require 'dashboard_driver.php';
    break;
  default:
    echo "Неизвестная роль";
    break;
}
