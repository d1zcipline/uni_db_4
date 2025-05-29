<?php
session_start();

function isAuthenticated()
{
  return isset($_SESSION['user_id']);
}

function hasRole($role)
{
  return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function requireAuth()
{
  if (!isAuthenticated()) {
    header("Location: /login.php");
    exit;
  }
}

function requireAdmin()
{
  requireAuth();
  if (!hasRole('admin')) {
    $_SESSION['error'] = "Доступ запрещен";
    header("Location: /dashboard.php");
    exit;
  }
}
