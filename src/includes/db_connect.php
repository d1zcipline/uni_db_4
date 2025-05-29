<?php
$host = 'db';
$dbname = 'moscow_transport';
$user = 'user';
$pass = 'pass';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
  die("Ошибка подключения к базе данных: " . $e->getMessage());
}
