<?php require 'config.php'; $title='Beranda';
$kursus = $pdo->query("SELECT * FROM kursus WHERE status='aktif' ORDER BY nama")->fetchAll();
include 'includes/header.php'; ?>
<h1>Pilih kursus, daftar online, belajar terjadwal</h1>
<p class="muted">Bimbel Maju membuka program berikut. Buat akun siswa untuk mendaftar.</p>
<div class="grid"><?php foreach ($kursus as $k): ?>
  <div class="card"><h3><?= e($k['nama']) ?></h3>
  <p class="muted">Tingkat: <?= e($k['tingkat']) ?></p><p><b><?= rupiah($k['biaya']) ?></b></p>
  <a class="btn" href="<?= user() ? 'pendaftaran.php' : 'register.php' ?>">Daftar kursus</a></div>
<?php endforeach; if (!$kursus): ?><p>Belum ada kursus aktif.</p><?php endif; ?></div>
<?php include 'includes/footer.php'; ?>
