<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="/index.php">Московский транспорт</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="#">
          </a>
        </li>
      </ul>
      <div class="d-flex align-items-center">
        <span class="text-light me-3"><?= $userName ?> (Диспетчер)</span>
        <a href="/logout.php" class="btn btn-outline-light">
          <i class="bi bi-box-arrow-right"></i> Выйти
        </a>
      </div>
    </div>
  </div>
</nav>