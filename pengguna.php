<?php require 'config.php'; require_login(['admin']); $title='Pengguna';
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check();
  if (isset($_POST['toggle'])) { $pdo->prepare("UPDATE users SET aktif=1-aktif WHERE id=? AND role<>'admin'")->execute([(int)$_POST['toggle']]); flash('Status akun diubah.'); }
  else { $n=trim($_POST['nama']??''); $m=trim($_POST['email']??''); $p=$_POST['password']??'';
    $c=$pdo->prepare('SELECT id FROM users WHERE email=?'); $c->execute([$m]);
    if ($n===''||!filter_var($m,FILTER_VALIDATE_EMAIL)||strlen($p)<6) flash('Isi nama, email valid, dan password minimal 6 karakter.');
    elseif ($c->fetch()) flash('Email sudah terdaftar.');
    else { $pdo->prepare("INSERT INTO users (nama,email,password,role) VALUES (?,?,?,'pengajar')")->execute([$n,$m,password_hash($p,PASSWORD_DEFAULT)]); flash('Akun pengajar dibuat.'); } }
  redirect('pengguna.php'); }
$pengajar=$pdo->query("SELECT * FROM users WHERE role='pengajar' ORDER BY nama")->fetchAll();
$siswa=$pdo->query("SELECT * FROM users WHERE role='siswa' ORDER BY nama")->fetchAll();
include 'includes/header.php';
function baris_status($x){ ?>
<td><span class="badge <?= $x['aktif']?'aktif':'nonaktif' ?>"><?= $x['aktif']?'aktif':'nonaktif' ?></span></td>
<td><form class="inline" method="post"><?= csrf_field() ?><button name="toggle" value="<?= $x['id'] ?>" class="<?= $x['aktif']?'red':'' ?>"><?= $x['aktif']?'Nonaktifkan':'Aktifkan' ?></button></form></td>
<?php } ?>
<h1>Pengguna</h1>

<h2 class="sec-title">Pengajar <span class="count"><?= count($pengajar) ?></span></h2>
<div class="card"><h3>Tambah akun pengajar</h3>
<form method="post" autocomplete="off" data-reset><?= csrf_field() ?>
<label>Nama</label><input name="nama" required autocomplete="off">
<label>Email</label><input type="email" name="email" required autocomplete="off">
<label>Password awal</label><input type="password" name="password" required autocomplete="new-password">
<p><button>Tambah pengajar</button></p></form></div>
<div class="tablewrap"><table><tr><th>Nama</th><th>Email</th><th>Status</th><th>Aksi</th></tr>
<?php foreach ($pengajar as $x): ?><tr><td><?= e($x['nama']) ?></td><td><?= e($x['email']) ?></td><?php baris_status($x); ?></tr><?php endforeach;
if (!$pengajar): ?><tr><td colspan="4" class="muted">Belum ada pengajar.</td></tr><?php endif; ?></table></div>

<h2 class="sec-title">Siswa <span class="count"><?= count($siswa) ?></span></h2>
<p class="muted">Siswa membuat akun sendiri lewat halaman Daftar akun.</p>
<div class="tablewrap"><table><tr><th>Nama</th><th>Email</th><th>Telepon</th><th>Status</th><th>Aksi</th></tr>
<?php foreach ($siswa as $x): ?><tr><td><?= e($x['nama']) ?></td><td><?= e($x['email']) ?></td><td><?= e($x['telepon'] ?: '-') ?></td><?php baris_status($x); ?></tr><?php endforeach;
if (!$siswa): ?><tr><td colspan="5" class="muted">Belum ada siswa terdaftar.</td></tr><?php endif; ?></table></div>
<?php include 'includes/footer.php'; ?>
