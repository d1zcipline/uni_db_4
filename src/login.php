<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <title>Авторизация в системе управления общественным транспортом</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-7">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Вход в систему</h4>
          </div>
          <div class="card-body">
            <!-- Блок сообщений об ошибках -->
            <?php if (isset($_SESSION['error'])): ?>
              <div class="alert alert-danger">
                <?= $_SESSION['error'];
                unset($_SESSION['error']); ?>
              </div>
            <?php endif; ?>

            <!-- Описание системы и ролей -->
            <div class="mb-4">
              <p class="mb-2"><strong>Добро пожаловать в Систему Управления Общественным Транспортом</strong></p>
              <p class="small text-muted mb-1">
                Пожалуйста, войдите под одной из трех ролей:
              </p>
              <ul class="small text-muted">
                <li><strong>Администратор</strong> — полный доступ к настройкам системы: создание новых пользователей, формирование расписаний маршрутов, управление тарифами и просмотр всех статистических отчетов.</li>
                <li><strong>Диспетчер</strong> — управление текущими рейсами, мониторинг положения транспорта, распределение заданий водителям и оперативное внесение изменений в расписание.</li>
                <li><strong>Водитель</strong> — доступ к своему личному кабинету: просмотр назначенных рейсов, расписания, инструкции по маршруту и отметка о начале/окончании работы.</li>
              </ul>
            </div>

            <!-- Форма авторизации -->
            <form action="includes/auth.php" method="POST">
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input
                  type="email"
                  class="form-control"
                  id="email"
                  name="email"
                  placeholder="Введите ваш корпоративный email"
                  required>
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">Пароль</label>
                <input
                  type="password"
                  class="form-control"
                  id="password"
                  name="password"
                  placeholder="Введите ваш пароль"
                  required>
              </div>
              <button type="submit" class="btn btn-primary w-100">Войти</button>
            </form>
          </div>
          <div class="card-footer text-center">
            <small class="text-muted">
              Если у вас нет учетной записи или возникли проблемы с доступом,
              свяжитесь со службой поддержки по телефону <a href="tel:+74951234567">+7 (495) 123-45-67</a> или отправьте письмо на <a href="mailto:support@transport.example.ru">support@transport.example.ru</a>.
            </small>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>