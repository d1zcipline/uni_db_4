<?php
function require_admin()
{
  if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Администратор') {
    header('Location: /login.php');
    exit;
  }
}

function require_super_admin($pdo)
{
  require_admin();

  // Проверка что текущий пользователь - первый администратор
  $stmt = $pdo->prepare("SELECT created_at FROM Employees 
                          WHERE id_position = (SELECT id_position FROM Employee_positions WHERE role = 'Администратор')
                          ORDER BY created_at ASC 
                          LIMIT 1");
  $stmt->execute();
  $firstAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

  // Проверяем, что данные получены и даты существуют
  if (
    $firstAdmin &&
    isset($firstAdmin['created_at'], $_SESSION['user']['created_at']) &&
    $firstAdmin['created_at'] !== null &&
    $_SESSION['user']['created_at'] !== null
  ) {

    return strtotime($firstAdmin['created_at']) === strtotime($_SESSION['user']['created_at']);
  }

  return false;
}

function validate_employee_data($pdo, $data, $currentEmployeeId = null)
{
  $errors = [];

  $firstName = trim($data['first_name'] ?? '');
  $lastName = trim($data['last_name'] ?? '');
  $email = trim($data['email'] ?? '');
  $roleId = (int)($data['role'] ?? 0);

  // Проверка обязательных полей
  if (empty($firstName)) $errors[] = "Имя обязательно для заполнения";
  if (empty($lastName)) $errors[] = "Фамилия обязательна для заполнения";
  if (empty($email)) $errors[] = "Email обязателен для заполнения";
  if ($roleId === 0) $errors[] = "Не выбрана должность";

  if (!empty($data['phone']) && !preg_match('/^\+?\d{10,15}$/', $data['phone'])) {
    $errors[] = "Некорректный формат телефона";
  }

  // Проверка формата email
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Некорректный формат email";
  } else {
    // Проверка уникальности email
    $sql = "SELECT COUNT(*) FROM Employees WHERE email = ?";
    $params = [$email];

    if ($currentEmployeeId !== null) {
      $sql .= " AND id_employee != ?";
      $params[] = $currentEmployeeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->fetchColumn() > 0) {
      $errors[] = "Пользователь с таким email уже существует";
    }
  }

  return $errors;
}
