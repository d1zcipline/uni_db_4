<?php
require_once 'includes/db.php';
require_once 'admin/functions.php';
session_start();
require_admin();

// Получаем список автобусов с дополнительной информацией
$buses = $pdo->query("
    SELECT b.*, 
           bt.bus_type_name,
           s.status_name,
           bp.bus_park_name,
           (SELECT COUNT(*) FROM Maintenance_records mr 
            WHERE mr.id_bus = b.id_bus AND mr.id_maintenance_status = 1) as pending_maintenance
    FROM Buses b
    LEFT JOIN Bus_types bt ON b.id_bus_type = bt.id_bus_type
    LEFT JOIN Statuses s ON b.id_status = s.id_status
    LEFT JOIN Bus_parks bp ON b.id_bus_park = bp.id_bus_park
    ORDER BY b.license_plate
")->fetchAll();

// Получаем данные для форм
$busTypes = $pdo->query("SELECT * FROM Bus_types ORDER BY bus_type_name")->fetchAll();
$statuses = $pdo->query("SELECT * FROM Statuses ORDER BY status_name")->fetchAll();
$parks = $pdo->query("SELECT * FROM Bus_parks ORDER BY bus_park_name")->fetchAll();

$title = "Управление автобусами";
$userName = $_SESSION['user']['name'];
$role = $_SESSION['user']['role'];
?>

<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <title><?= $title ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
  <?php include 'includes/admin_navbar.php' ?>

  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>Управление автобусами</h1>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBusModal">
        <i class="bi bi-plus-lg"></i> Добавить автобус
      </button>
    </div>

    <!-- Фильтры -->
    <div class="card mb-4">
      <div class="card-body">
        <form class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Госномер</label>
            <input type="text" class="form-control" placeholder="A123BC777">
          </div>
          <div class="col-md-3">
            <label class="form-label">Тип</label>
            <select class="form-select">
              <option value="">Все типы</option>
              <?php foreach ($busTypes as $type): ?>
                <option value="<?= $type['id_bus_type'] ?>"><?= $type['bus_type_name'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Статус</label>
            <select class="form-select">
              <option value="">Все статусы</option>
              <?php foreach ($statuses as $status): ?>
                <option value="<?= $status['id_status'] ?>"><?= $status['status_name'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Автопарк</label>
            <select class="form-select">
              <option value="">Все автопарки</option>
              <?php foreach ($parks as $park): ?>
                <option value="<?= $park['id_bus_park'] ?>"><?= $park['bus_park_name'] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>
      </div>
    </div>

    <!-- Таблица автобусов -->
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Госномер</th>
                <th>Тип</th>
                <th>Вместимость</th>
                <th>Статус</th>
                <th>Автопарк</th>
                <th>Год выпуска</th>
                <th>Тех. состояние</th>
                <th>Действия</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($buses as $bus): ?>
                <tr class="<?= $bus['pending_maintenance'] > 0 ? 'table-warning' : '' ?>">
                  <td>
                    <?= htmlspecialchars($bus['license_plate']) ?>
                    <?php if ($bus['pending_maintenance'] > 0): ?>
                      <span class="badge bg-danger ms-2" title="Требуется техобслуживание">
                        <?= $bus['pending_maintenance'] ?>
                      </span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($bus['bus_type_name']) ?></td>
                  <td><?= $bus['capacity'] ?></td>
                  <td>
                    <span class="badge bg-<?= getStatusBadgeClass($bus['status_name']) ?>">
                      <?= htmlspecialchars($bus['status_name']) ?>
                    </span>
                  </td>
                  <td><?= htmlspecialchars($bus['bus_park_name'] ?? 'Не назначен') ?></td>
                  <td><?= $bus['manufacture_year'] ?></td>
                  <td>
                    <?php if ($bus['last_maintenance_date']): ?>
                      <span title="Последнее ТО: <?= $bus['last_maintenance_date'] ?>">
                        <?= getMaintenanceStatus($bus['last_maintenance_date']) ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">Нет данных</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="d-flex">
                      <a href="admin/edit_bus.php?id=<?= $bus['id_bus'] ?>"
                        class="btn btn-sm btn-outline-primary me-2">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <a href="admin/bus_maintenance.php?id=<?= $bus['id_bus'] ?>"
                        class="btn btn-sm btn-outline-warning me-2">
                        <i class="bi bi-tools"></i>
                      </a>
                      <a href="admin/delete_bus.php?id=<?= $bus['id_bus'] ?>"
                        class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Удалить этот автобус?')">
                        <i class="bi bi-trash"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Модальное окно добавления автобуса -->
  <div class="modal fade" id="addBusModal" tabindex="-1" aria-labelledby="addBusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addBusModalLabel">Добавить новый автобус</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="admin/add_bus.php" method="POST">
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Государственный номер</label>
                <input type="text" name="license_plate" class="form-control" required
                  pattern="[А-ЯA-Z]{1}\d{3}[А-ЯA-Z]{2}\d{2,3}"
                  title="Формат: A123BC777 или X123XX123">
              </div>
              <div class="col-md-6">
                <label class="form-label">Тип автобуса</label>
                <select name="id_bus_type" class="form-select" required>
                  <option value="">Выберите тип</option>
                  <?php foreach ($busTypes as $type): ?>
                    <option value="<?= $type['id_bus_type'] ?>">
                      <?= htmlspecialchars($type['bus_type_name']) ?>
                      <?= $type['electric'] ? ' (электрический)' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Статус</label>
                <select name="id_status" class="form-select" required>
                  <option value="">Выберите статус</option>
                  <?php foreach ($statuses as $status): ?>
                    <option value="<?= $status['id_status'] ?>">
                      <?= htmlspecialchars($status['status_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Автопарк</label>
                <select name="id_bus_park" class="form-select">
                  <option value="">Не назначен</option>
                  <?php foreach ($parks as $park): ?>
                    <option value="<?= $park['id_bus_park'] ?>">
                      <?= htmlspecialchars($park['bus_park_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Год выпуска</label>
                <input type="number" name="manufacture_year" class="form-control"
                  min="1990" max="<?= date('Y') + 1 ?>"
                  value="<?= date('Y') - 5 ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Вместимость (пассажиров)</label>
                <input type="number" name="capacity" class="form-control" min="1" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Дата последнего ТО</label>
                <input type="date" name="last_maintenance_date" class="form-control">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
            <button type="submit" class="btn btn-primary">Добавить автобус</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php
  // Вспомогательные функции для отображения
  function getStatusBadgeClass($status)
  {
    switch ($status) {
      case 'В эксплуатации':
        return 'success';
      case 'На обслуживании':
        return 'warning';
      case 'Списан':
        return 'secondary';
      case 'Аварийный':
        return 'danger';
      default:
        return 'info';
    }
  }

  function getMaintenanceStatus($lastDate)
  {
    $last = new DateTime($lastDate);
    $now = new DateTime();
    $diff = $now->diff($last)->m; // Разница в месяцах

    if ($diff < 3) return '<span class="text-success">Отличное</span>';
    if ($diff < 6) return '<span class="text-primary">Хорошее</span>';
    if ($diff < 12) return '<span class="text-warning">Требует проверки</span>';
    return '<span class="text-danger">Необходимо ТО</span>';
  }
  ?>
  <?php if (!empty($_SESSION['bus_errors'])): ?>
    <div class="alert alert-danger">
      <ul>
        <?php foreach ($_SESSION['bus_errors'] as $error): ?>
          <li><?= $error ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php unset($_SESSION['bus_errors']); // Очистка после отображения 
    ?>
  <?php endif; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>