<?php
declare(strict_types=1);
session_start();
function db(): PDO { static $pdo; if (!$pdo) { $host=getenv('DB_HOST') ?: '127.0.0.1'; $name=getenv('DB_NAME') ?: 'myportfolio_db'; $user=getenv('DB_USER') ?: 'root'; $pass=getenv('DB_PASS') ?: ''; $pdo=new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); } return $pdo; }
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_check(): void { if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) { http_response_code(400); exit('Invalid request token. Please go back and try again.'); } }
function require_admin(): void { if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; } }
