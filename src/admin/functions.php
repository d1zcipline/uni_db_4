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
  $firstAdmin = $stmt->fetch();

  if ($firstAdmin && strtotime($firstAdmin['created_at']) === strtotime($_SESSION['user']['created_at'])) {
    return true;
  }

  return false;
}
