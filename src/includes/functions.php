<?php
// Функция для безопасного вывода
function esc($str)
{
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Получение списка таблиц
function getTables($pdo)
{
  $stmt = $pdo->query("SHOW TABLES");
  return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Генерация CRUD-интерфейса для таблицы
function renderCrudTable($pdo, $tableName)
{
  // Получение структуры таблицы
  $stmt = $pdo->query("DESCRIBE $tableName");
  $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

  // Получение данных
  $stmt = $pdo->query("SELECT * FROM $tableName");
  $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Генерация HTML
  echo '<div class="card mb-4">';
  echo '<div class="card-header d-flex justify-content-between align-items-center">';
  echo '<h5 class="mb-0">' . esc(ucfirst(str_replace('_', ' ', $tableName))) . '</h5>';
  echo '<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal-' . $tableName . '">Добавить</button>';
  echo '</div>';

  echo '<div class="card-body">';
  echo '<div class="table-responsive">';
  echo '<table class="table table-striped">';
  echo '<thead><tr>';
  foreach ($columns as $col) {
    echo '<th>' . esc($col) . '</th>';
  }
  echo '<th>Действия</th>';
  echo '</tr></thead>';

  echo '<tbody>';
  foreach ($data as $row) {
    echo '<tr>';
    foreach ($columns as $col) {
      echo '<td>' . esc($row[$col] ?? '') . '</td>';
    }
    echo '<td>';
    echo '<button class="btn btn-sm btn-warning edit-btn" data-id="' . $row['id'] . '">✏️</button> ';
    echo '<button class="btn btn-sm btn-danger delete-btn" data-id="' . $row['id'] . '">🗑️</button>';
    echo '</td>';
    echo '</tr>';
  }
  echo '</tbody>';
  echo '</table>';
  echo '</div></div></div>';
}
