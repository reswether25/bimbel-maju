<?php require 'config.php'; $title='Daftar akun'; $err='';
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check();
  $nama=trim($_POST['nama']??''); $email=trim($_POST['email']??''); $tel=trim($_POST['telepon']??''); $pw=$_POST['password']??'';
  if ($nama==='' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw)<6) $err='Isi nama, email yang valid, dan password minimal 6 karakter.';
  else { $c=$pdo->prepare('SELECT id FROM users WHERE email=?'); $c->execute([$email]);
    if ($c->fetch()) $err='Email sudah terdaftar.';
    else { $pdo->prepare("INSERT INTO users (nama,email,telepon,password,role) VALUES (?,?,?,?,'siswa')")->execute([$nama,$email,$tel,password_hash($pw,PASSWORD_DEFAULT)]);
      flash('Akun dibuat. Silakan login.'); redirect('login.php'); } }
}
include 'includes/header.php'; ?>
<div class="card narrow"><h1>Daftar akun siswa</h1><?php if ($err): ?><p class="flash"><?= e($err) ?></p><?php endif; ?>
<form method="post"><?= csrf_field() ?><label>Nama lengkap</label><input name="nama" required value="<?= e($_POST['nama']??'') ?>">
<label>Email</label><input type="email" name="email" required value="<?= e($_POST['email']??'') ?>">
<label>No. telepon</label><input name="telepon" value="<?= e($_POST['telepon']??'') ?>">
<label>Password (minimal 6 karakter)</label><input type="password" name="password" required><p><button>Buat akun</button></p></form></div>
<?php include 'includes/footer.php'; ?>
