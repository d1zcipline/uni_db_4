<?php
require_once 'includes/db.php';
require_once 'admin/functions.php';
session_start();
require_admin();

// Получение списка автопарков с информацией о локации
$parks = $pdo->query("
    SELECT b.*, l.address, d.district_name 
    FROM Bus_parks b
    LEFT JOIN Locations l ON b.id_location = l.id_location
    LEFT JOIN Districts d ON l.id_district = d.id_district
    ORDER BY b.bus_park_name
")->fetchAll();

// Получение районов для формы
$districts = $pdo->query("SELECT * FROM Districts ORDER BY district_name")->fetchAll();

$title = "Управление автопарками";
$userName = $_SESSION['user']['name'];
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .park-card {
            transition: all 0.3s ease;
        }

        .park-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .capacity-bar {
            height: 20px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .capacity-fill {
            height: 100%;
            background-color: #198754;
        }
    </style>
</head>

<body>
    <!-- Навигация -->
    <?php include 'includes/admin_navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?= $title ?></h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addParkModal">
                <i class="bi bi-plus-lg"></i> Добавить автопарк
            </button>
        </div>

        <!-- Карточки автопарков -->
        <div class="row">
            <?php foreach ($parks as $park): ?>
                <div class="col-md-6 mb-4">
                    <div class="card park-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?= htmlspecialchars($park['bus_park_name']) ?></h5>
                            <div>
                                <a href="edit_bus_park.php?id=<?= $park['id_bus_park'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete_bus_park.php?id=<?= $park['id_bus_park'] ?>" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Удалить этот автопарк?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                <i class="bi bi-geo-alt"></i>
                                <?= htmlspecialchars($park['address'] ?? 'Адрес не указан') ?>
                                <br>
                                <i class="bi bi-pin-map"></i>
                                Район: <?= htmlspecialchars($park['district_name'] ?? 'Не указан') ?>
                                <br>
                                <i class="bi bi-car-front"></i>
                                Вместимость: <?= $park['capacity'] ?> автобусов
                            </p>
                            <div class="mb-3">
                                <small class="text-muted">Загруженность:</small>
                                <div class="capacity-bar">
                                    <div class="capacity-fill" style="width: <?= min(100, ($park['capacity'] > 0 ? (rand(30, 90)) : 0)) ?>%"></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="park_routes.php?id=<?= $park['id_bus_park'] ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-signpost"></i> Маршруты
                                </a>
                                <a href="park_buses.php?id=<?= $park['id_bus_park'] ?>" class="btn btn-outline-success btn-sm">
                                    <i class="bi bi-bus-front"></i> Автобусы
                                </a>
                                <a href="park_employees.php?id=<?= $park['id_bus_park'] ?>" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-people"></i> Сотрудники
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Модальное окно добавления автопарка -->
    <div class="modal fade" id="addParkModal" tabindex="-1" aria-labelledby="addParkModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addParkModalLabel">Добавить новый автопарк</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add_bus_park.php" method="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Название автопарка</label>
                                <input type="text" name="bus_park_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Район</label>
                                <select name="id_district" class="form-select" required>
                                    <option value="">Выберите район</option>
                                    <?php foreach ($districts as $district): ?>
                                        <option value="<?= $district['id_district'] ?>"><?= $district['district_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Адрес</label>
                                <input type="text" name="address" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Вместимость (автобусов)</label>
                                <input type="number" name="capacity" class="form-control" min="1" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Добавить автопарк</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>