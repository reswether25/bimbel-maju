<?php require 'config.php'; $title='Login'; $err='';
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check();
  $s = $pdo->prepare('SELECT * FROM users WHERE email=?'); $s->execute([trim($_POST['email'] ?? '')]); $u = $s->fetch();
  if ($u && $u['aktif'] && password_verify($_POST['password'] ?? '', $u['password'])) {
    session_regenerate_id(true); $_SESSION['user'] = ['id'=>$u['id'],'nama'=>$u['nama'],'role'=>$u['role']]; redirect('dashboard.php');
  } else $err = 'Email atau password salah, atau akun tidak aktif.';
}
include 'includes/header.php'; ?>
<div class="card narrow"><h1 class="center">Login</h1><?php if ($err): ?><p class="flash"><?= e($err) ?></p><?php endif; ?>
<form method="post" autocomplete="off"><?= csrf_field() ?><label>Email</label><input type="email" name="email" required autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
<label>Password</label><input type="password" name="password" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')"><p class="center"><button>Login</button></p></form>
<p class="muted center">Belum buat akun? <a href="register.php">Buat akun baru</a></p></div>
<?php include 'includes/footer.php'; ?>
