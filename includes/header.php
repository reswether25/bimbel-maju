<?php $u = user(); $cur = basename($_SERVER['PHP_SELF']);
$I = ['home'=>'<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>','book'=>'<path d="M4 4h12a3 3 0 013 3v13H7a3 3 0 01-3-3z"/><path d="M8 4v13"/>','users'=>'<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.5 3-6 6-6s6 2.5 6 6"/><path d="M16 5a3 3 0 010 6M18 14c2 .8 3 3 3 6"/>','clip'=>'<rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4h6v3H9zM9 12h6M9 16h4"/>','cal'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>','wallet'=>'<rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18M16 15h2"/>','check'=>'<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 12l3 3 5-6"/>','out'=>'<path d="M10 4H5v16h5M15 8l4 4-4 4M19 12H9"/>'];
$ico = fn($k)=>'<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$I[$k].'</svg>';
$nav = [];
if ($u) { $nav[] = ['dashboard.php','Dashboard','home'];
  if ($u['role']=='admin') { $nav[] = ['kursus.php','Kursus','book']; $nav[] = ['pengguna.php','Pengguna','users']; }
  if ($u['role']!='pengajar') $nav[] = ['pendaftaran.php','Pendaftaran','clip'];
  $nav[] = ['jadwal.php', $u['role']=='admin' ? 'Kelas & Jadwal' : 'Jadwal','cal'];
  $nav[] = ['presensi.php','Presensi','check'];
  if ($u['role']!='pengajar') $nav[] = ['pembayaran.php','Pembayaran','wallet']; } ?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Beranda') ?> - Bimbel Maju</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,700;0,800;1,700&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css"></head><body class="<?= $u ? 'is-app' : 'is-guest' ?>">
<?php if ($u): ?><div class="app"><aside class="side"><a class="brand" href="dashboard.php">Bimbel <em>Maju</em></a><p class="tag">manajemen kursus</p>
<nav><?php foreach ($nav as [$f,$l,$i]): ?><a href="<?= $f ?>" class="<?= $cur==$f?'on':'' ?>"><?= $ico($i) ?><span><?= e($l) ?></span></a><?php endforeach; ?></nav>
<div class="me"><span class="av"><?= e(mb_strtoupper(mb_substr($u['nama'],0,1))) ?></span><div><b><?= e($u['nama']) ?></b><small><?= e($u['role']) ?></small></div><a href="logout.php" title="Keluar" aria-label="Keluar"><?= $ico('out') ?></a></div></aside>
<div class="content"><main>
<?php else: ?><header class="gbar"><a class="brand" href="index.php">Bimbel <em>Maju</em></a><nav><a href="index.php">Kursus</a><a href="login.php">Login</a><a class="btn" href="register.php">Daftar akun</a></nav></header><main class="gmain">
<?php endif;
if (!empty($_SESSION['flash'])): ?><p class="flash"><?= e($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif; ?>
