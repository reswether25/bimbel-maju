<?php
session_start();
define('DB_HOST','localhost'); define('DB_NAME','kursus_db'); define('DB_USER','root'); define('DB_PASS','');
try { $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }
catch (PDOException $e) { die('Koneksi database gagal. Pastikan MySQL di XAMPP aktif dan database.sql sudah diimpor.'); }
// NFR-13: sesi berakhir setelah 30 menit tidak aktif
if (isset($_SESSION['last']) && time() - $_SESSION['last'] > 1800) { session_unset(); session_destroy(); session_start(); $_SESSION['flash'] = 'Sesi berakhir karena tidak aktif. Silakan login lagi.'; }
$_SESSION['last'] = time();
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n){ return 'Rp '.number_format((int)$n, 0, ',', '.'); }
function user(){ return $_SESSION['user'] ?? null; }
function flash($m){ $_SESSION['flash'] = $m; }
function redirect($to){ header('Location: '.$to); exit; }
function require_login(array $roles = []){
  $u = user(); if (!$u) redirect('login.php');
  if ($roles && !in_array($u['role'], $roles)) { http_response_code(403); die('Akses ditolak untuk peran Anda.'); }
}
function csrf_field(){ if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return '<input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'">'; }
function csrf_check(){ if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); die('Permintaan tidak valid. Muat ulang halaman.'); } }

// Metode pembayaran. GANTI nomor rekening / akun di bawah ini dengan data asli lembaga.
$METODE_BAYAR = [
  'bca'    => ['nama'=>'Transfer Bank BCA',     'tujuan'=>'1234567890',    'atas_nama'=>'Bimbel Maju', 'info'=>'No. rekening'],
  'mandiri'=> ['nama'=>'Transfer Bank Mandiri', 'tujuan'=>'1230009876543', 'atas_nama'=>'Bimbel Maju', 'info'=>'No. rekening'],
  'bri'    => ['nama'=>'Transfer Bank BRI',     'tujuan'=>'012345678901234','atas_nama'=>'Bimbel Maju', 'info'=>'No. rekening'],
  'gopay'  => ['nama'=>'GoPay',                 'tujuan'=>'081234567890',  'atas_nama'=>'Bimbel Maju', 'info'=>'No. GoPay'],
  'dana'   => ['nama'=>'DANA',                  'tujuan'=>'081234567890',  'atas_nama'=>'Bimbel Maju', 'info'=>'No. DANA'],
];
function invoice_no($t){ return 'INV-'.date('Ym', strtotime($t['created_at'])).'-'.str_pad($t['id'],4,'0',STR_PAD_LEFT); }
