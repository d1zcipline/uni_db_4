<?php
require_once '../includes/db.php';
require_once 'functions.php';
session_start();
require_admin();

$busId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные автобуса
$stmt = $pdo->prepare("
    SELECT b.*, bt.bus_type_name, s.status_name, bp.bus_park_name
    FROM Buses b
    LEFT JOIN Bus_types bt ON b.id_bus_type = bt.id_bus_type
    LEFT JOIN Statuses s ON b.id_status = s.id_status
    LEFT JOIN Bus_parks bp ON b.id_bus_park = bp.id_bus_park
    WHERE b.id_bus = ?
");
$stmt->execute([$busId]);
$bus = $stmt->fetch();

if (!$bus) {
  $_SESSION['bus_error'] = "Автобус не найден";
  header('Location: ../admin_buses.php');
  exit;
}

// Получаем историю ТО
$maintenanceHistory = $pdo->prepare("
    SELECT mr.*, mt.maintenance_type_name, ms.maintenance_status
    FROM Maintenance_records mr
    JOIN Maintenance_type mt ON mr.id_maintenance_type = mt.id_maintenance_type
    JOIN Maintenance_statuses ms ON mr.id_maintenance_status = ms.id_maintenance_status
    WHERE mr.id_bus = ?
    ORDER BY mr.maintenance_date DESC
");
$maintenanceHistory->execute([$busId]);

// Получаем данные для форм
$maintenanceTypes = $pdo->query("SELECT * FROM Maintenance_type ORDER BY maintenance_type_name")->fetchAll();
$maintenanceStatuses = $pdo->query("SELECT * FROM Maintenance_statuses ORDER BY id_maintenance_status")->fetchAll();

// Обработка добавления записи ТО
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_maintenance'])) {
  $errors = [];

  if (empty($_POST['id_maintenance_type'])) {
    $errors[] = "Тип обслуживания обязателен";
  }

  if (empty($_POST['maintenance_date'])) {
    $errors[] = "Дата обслуживания обязательна";
  }

  if (empty($errors)) {
    try {
      $pdo->beginTransaction();

      // Добавляем запись о ТО
      $stmt = $pdo->prepare("
                INSERT INTO Maintenance_records (
                    id_bus, id_maintenance_type, id_maintenance_status,
                    maintenance_date, completion_date, description
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");

      $stmt->execute([
        $busId,
        $_POST['id_maintenance_type'],
        $_POST['id_maintenance_status'] ?? 2, // По умолчанию "Завершено"
        $_POST['maintenance_date'],
        $_POST['completion_date'] ?? $_POST['maintenance_date'],
        $_POST['description'] ?? null
      ]);

      // Обновляем дату последнего ТО у автобуса, если обслуживание завершено
      if ($_POST['id_maintenance_status'] == 2) { // 2 = Завершено
        $stmt = $pdo->prepare("
                    UPDATE Buses 
                    SET last_maintenance_date = ?
                    WHERE id_bus = ?
                ");
        $stmt->execute([$_POST['maintenance_date'], $busId]);
      }

      $pdo->commit();
      $_SESSION['maintenance_success'] = "Запись о техническом обслуживании добавлена";
      header("Location: bus_maintenance.php?id=$busId");
      exit;
    } catch (PDOException $e) {
      $pdo->rollBack();
      $errors[] = "Ошибка базы данных: " . $e->getMessage();
    }
  }

  if (!empty($errors)) {
    $_SESSION['maintenance_errors'] = $errors;
  }
}

// Обработка обновления записи ТО
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_maintenance'])) {
  $recordId = (int)$_POST['record_id'];
  $errors = [];

  if (empty($_POST['id_maintenance_type'])) {
    $errors[] = "Тип обслуживания обязателен";
  }

  if (empty($_POST['maintenance_date'])) {
    $errors[] = "Дата обслуживания обязательна";
  }

  if (empty($errors)) {
    try {
      $pdo->beginTransaction();

      // Обновляем запись о ТО
      $stmt = $pdo->prepare("
                UPDATE Maintenance_records SET
                    id_maintenance_type = ?,
                    id_maintenance_status = ?,
                    maintenance_date = ?,
                    completion_date = ?,
                    description = ?
                WHERE id_maintenance = ?
            ");

      $stmt->execute([
        $_POST['id_maintenance_type'],
        $_POST['id_maintenance_status'],
        $_POST['maintenance_date'],
        $_POST['completion_date'] ?? null,
        $_POST['description'] ?? null,
        $recordId
      ]);

      // Обновляем дату последнего ТО у автобуса, если обслуживание завершено
      if ($_POST['id_maintenance_status'] == 2) {
        $stmt = $pdo->prepare("
                    UPDATE Buses 
                    SET last_maintenance_date = ?
                    WHERE id_bus = ?
                ");
        $stmt->execute([$_POST['maintenance_date'], $busId]);
      }

      $pdo->commit();
      $_SESSION['maintenance_success'] = "Запись о техническом обслуживании обновлена";
      header("Location: bus_maintenance.php?id=$busId");
      exit;
    } catch (PDOException $e) {
      $pdo->rollBack();
      $errors[] = "Ошибка базы данных: " . $e->getMessage();
    }
  }

  if (!empty($errors)) {
    $_SESSION['maintenance_errors'] = $errors;
  }
}

// Получение записи для редактирования
$editRecord = null;
if (isset($_GET['edit'])) {
  $recordId = (int)$_GET['edit'];
  $stmt = $pdo->prepare("
        SELECT * FROM Maintenance_records 
        WHERE id_maintenance = ?
    ");
  $stmt->execute([$recordId]);
  $editRecord = $stmt->fetch();

  if (!$editRecord || $editRecord['id_bus'] != $busId) {
    $_SESSION['maintenance_error'] = "Запись не найдена или не принадлежит этому автобусу";
    header("Location: bus_maintenance.php?id=$busId");
    exit;
  }
}

$title = "Техническое обслуживание автобуса " . $bus['license_plate'];
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
  <?php include '../includes/admin_navbar.php'; ?>

  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>
        Техническое обслуживание:
        <span class="text-primary"><?= htmlspecialchars($bus['license_plate']) ?></span>
      </h1>
      <a href="buses.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Назад к списку
      </a>
    </div>

    <!-- Информация об автобусе -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row">
          <div class="col-md-4">
            <p><strong>Тип:</strong> <?= htmlspecialchars($bus['bus_type_name']) ?></p>
            <p><strong>Вместимость:</strong> <?= $bus['capacity'] ?> пассажиров</p>
          </div>
          <div class="col-md-4">
            <p><strong>Статус:</strong>
              <span class="badge bg-<?= getStatusBadgeClass($bus['status_name']) ?>">
                <?= htmlspecialchars($bus['status_name']) ?>
              </span>
            </p>
            <p><strong>Автопарк:</strong> <?= htmlspecialchars($bus['bus_park_name'] ?? 'Не назначен') ?></p>
          </div>
          <div class="col-md-4">
            <p><strong>Год выпуска:</strong> <?= $bus['manufacture_year'] ?></p>
            <p><strong>Последнее ТО:</strong>
              <?= $bus['last_maintenance_date'] ?
                htmlspecialchars($bus['last_maintenance_date']) :
                '<span class="text-danger">Не проводилось</span>' ?>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Форма добавления/редактирования записи ТО -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">
          <?= $editRecord ? 'Редактирование записи ТО' : 'Добавить запись о техническом обслуживании' ?>
        </h5>
      </div>
      <div class="card-body">
        <?php if (isset($_SESSION['maintenance_errors'])): ?>
          <div class="alert alert-danger">
            <?php foreach ($_SESSION['maintenance_errors'] as $error): ?>
              <p class="mb-0"><?= $error ?></p>
            <?php endforeach; ?>
          </div>
          <?php unset($_SESSION['maintenance_errors']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['maintenance_success'])): ?>
          <div class="alert alert-success">
            <?= $_SESSION['maintenance_success'] ?>
          </div>
          <?php unset($_SESSION['maintenance_success']); ?>
        <?php endif; ?>

        <form method="POST">
          <?php if ($editRecord): ?>
            <input type="hidden" name="record_id" value="<?= $editRecord['id_maintenance'] ?>">
          <?php endif; ?>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Тип обслуживания*</label>
              <select name="id_maintenance_type" class="form-select" required>
                <option value="">Выберите тип</option>
                <?php foreach ($maintenanceTypes as $type): ?>
                  <option value="<?= $type['id_maintenance_type'] ?>"
                    <?= ($editRecord && $editRecord['id_maintenance_type'] == $type['id_maintenance_type']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($type['maintenance_type_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Статус*</label>
              <select name="id_maintenance_status" class="form-select" required>
                <?php foreach ($maintenanceStatuses as $status): ?>
                  <option value="<?= $status['id_maintenance_status'] ?>"
                    <?= ($editRecord && $editRecord['id_maintenance_status'] == $status['id_maintenance_status']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($status['maintenance_status']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Дата обслуживания*</label>
              <input type="date" name="maintenance_date" class="form-control"
                value="<?= $editRecord ? htmlspecialchars($editRecord['maintenance_date']) : date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Дата завершения</label>
              <input type="date" name="completion_date" class="form-control"
                value="<?= $editRecord ? htmlspecialchars($editRecord['completion_date']) : date('Y-m-d') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Описание</label>
              <input type="text" name="description" class="form-control"
                placeholder="Что было сделано или будет сделано"
                value="<?= $editRecord ? htmlspecialchars($editRecord['description']) : '' ?>">
            </div>
            <div class="col-12 mt-3">
              <?php if ($editRecord): ?>
                <button type="submit" name="update_maintenance" class="btn btn-primary">
                  <i class="bi bi-save"></i> Сохранить изменения
                </button>
                <a href="bus_maintenance.php?id=<?= $busId ?>" class="btn btn-outline-secondary">
                  <i class="bi bi-x"></i> Отмена
                </a>
              <?php else: ?>
                <button type="submit" name="add_maintenance" class="btn btn-primary">
                  <i class="bi bi-plus"></i> Добавить запись
                </button>
              <?php endif; ?>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- История технического обслуживания -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">История технического обслуживания</h5>
      </div>
      <div class="card-body">
        <?php if ($maintenanceHistory->rowCount() === 0): ?>
          <div class="alert alert-info">Нет записей о техническом обслуживании</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Дата</th>
                  <th>Тип обслуживания</th>
                  <th>Статус</th>
                  <th>Дата завершения</th>
                  <th>Описание</th>
                  <th>Действия</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($record = $maintenanceHistory->fetch()): ?>
                  <tr>
                    <td><?= htmlspecialchars($record['maintenance_date']) ?></td>
                    <td><?= htmlspecialchars($record['maintenance_type_name']) ?></td>
                    <td>
                      <span class="badge bg-<?=
                                            $record['id_maintenance_status'] == 2 ? 'success' : ($record['id_maintenance_status'] == 1 ? 'warning' : 'secondary') ?>">
                        <?= htmlspecialchars($record['maintenance_status']) ?>
                      </span>
                    </td>
                    <td>
                      <?= $record['completion_date'] ?
                        htmlspecialchars($record['completion_date']) :
                        '<span class="text-muted">-</span>' ?>
                    </td>
                    <td><?=
                        $record['description'] ?
                          htmlspecialchars($record['description']) :
                          '<span class="text-muted">Нет описания</span>' ?>
                    </td>
                    <td>
                      <div class="d-flex">
                        <a href="bus_maintenance.php?id=<?= $busId ?>&edit=<?= $record['id_maintenance'] ?>"
                          class="btn btn-sm btn-outline-primary me-2">
                          <i class="bi bi-pencil"></i>
                        </a>
                        <a href="delete_maintenance.php?id=<?= $record['id_maintenance'] ?>&bus_id=<?= $busId ?>"
                          class="btn btn-sm btn-outline-danger"
                          onclick="return confirm('Удалить эту запись обслуживания?')">
                          <i class="bi bi-trash"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>

</html>

<?php

// Вспомогательная функция для получения класса бейджа статуса
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
?>