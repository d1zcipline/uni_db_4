<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

// Получаем список локаций с информацией о районах
$locations = $pdo->query("
    SELECT l.*, d.district_name 
    FROM Locations l
    JOIN Districts d ON l.id_district = d.id_district
    ORDER BY d.district_name, l.address
")->fetchAll();

$title = "Управление адресами";
$userName = $_SESSION['user']['name'];
$role = $_SESSION['user']['role'];
?>

<head>
  <meta charset="UTF-8">
  <title><?= $title ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<?php include '../includes/admin_navbar.php'; ?>

<div class="container mt-4">
  <h2>Список адресов</h2>

  <div class="card">
    <div class="card-body">
      <?php if (empty($locations)): ?>
        <div class="alert alert-info">Нет добавленных адресов</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Район</th>
                <th>Адрес</th>
                <th>Используется</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($locations as $location): ?>
                <tr>
                  <td><?= $location['id_location'] ?></td>
                  <td><?= htmlspecialchars($location['district_name']) ?></td>
                  <td><?= htmlspecialchars($location['address']) ?></td>
                  <td>
                    <?php
                    $stmt = $pdo->prepare("
                                            SELECT COUNT(*) FROM Bus_parks 
                                            WHERE id_location = ?
                                        ");
                    $stmt->execute([$location['id_location']]);
                    $count = $stmt->fetchColumn();
                    echo $count ? "Да" : "Нет";
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>