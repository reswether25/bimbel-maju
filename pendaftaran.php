<?php require 'config.php'; require_login(['admin','siswa']); $u=user(); $title='Pendaftaran';
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check();
  if ($u['role']==='siswa' && isset($_POST['kursus_id'])) { $kid=(int)$_POST['kursus_id'];
    $k=$pdo->prepare("SELECT id FROM kursus WHERE id=? AND status='aktif'"); $k->execute([$kid]);
    $d=$pdo->prepare("SELECT id FROM pendaftaran WHERE siswa_id=? AND kursus_id=? AND status IN ('menunggu','diterima')"); $d->execute([$u['id'],$kid]);
    if (!$k->fetch()) flash('Kursus tidak tersedia.');
    elseif ($d->fetch()) flash('Anda sudah terdaftar pada kursus ini.');
    else { $pdo->prepare('INSERT INTO pendaftaran (siswa_id,kursus_id) VALUES (?,?)')->execute([$u['id'],$kid]); flash('Pendaftaran terkirim. Tunggu konfirmasi admin.'); } }
  if ($u['role']==='admin' && isset($_POST['aksi'])) { $id=(int)$_POST['id']; $ok=$_POST['aksi']==='terima';
    $up=$pdo->prepare("UPDATE pendaftaran SET status=? WHERE id=? AND status='menunggu'"); $up->execute([$ok?'diterima':'ditolak',$id]);
    if ($ok && $up->rowCount()) $pdo->prepare('INSERT INTO tagihan (pendaftaran_id,jumlah) SELECT p.id,k.biaya FROM pendaftaran p JOIN kursus k ON k.id=p.kursus_id WHERE p.id=?')->execute([$id]);
    flash($ok?'Pendaftaran diterima dan tagihan dibuat.':'Pendaftaran ditolak.'); }
  redirect('pendaftaran.php'); }
include 'includes/header.php';
if ($u['role']==='siswa'):
  $kursus=$pdo->query("SELECT * FROM kursus WHERE status='aktif' ORDER BY nama")->fetchAll();
  $r=$pdo->prepare('SELECT p.tanggal,p.status,k.nama FROM pendaftaran p JOIN kursus k ON k.id=p.kursus_id WHERE p.siswa_id=? ORDER BY p.tanggal DESC'); $r->execute([$u['id']]); ?>
  <h1>Daftar kursus</h1><div class="grid"><?php foreach ($kursus as $k): ?>
  <div class="card"><h3><?= e($k['nama']) ?></h3><p class="muted"><?= e($k['tingkat']) ?></p><p><b><?= rupiah($k['biaya']) ?></b></p>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="kursus_id" value="<?= $k['id'] ?>"><button>Daftar</button></form></div><?php endforeach; ?></div>
  <h2>Riwayat pendaftaran saya</h2><div class="tablewrap"><table><tr><th>Kursus</th><th>Tanggal</th><th>Status</th></tr>
  <?php foreach ($r as $x): ?><tr><td><?= e($x['nama']) ?></td><td><?= e($x['tanggal']) ?></td><td><span class="badge <?= e($x['status']) ?>"><?= e($x['status']) ?></span></td></tr><?php endforeach; ?></table></div>
<?php else:
  $kf=(int)($_GET['kursus_id']??0); $all=$pdo->query('SELECT id,nama FROM kursus ORDER BY nama')->fetchAll();
  $q='SELECT p.id,p.tanggal,p.status,u.nama siswa,u.email,k.nama kursus FROM pendaftaran p JOIN users u ON u.id=p.siswa_id JOIN kursus k ON k.id=p.kursus_id'.($kf?' WHERE k.id=?':'').' ORDER BY p.status="menunggu" DESC,p.tanggal DESC';
  $s=$pdo->prepare($q); $s->execute($kf?[$kf]:[]); ?>
  <h1>Pendaftaran siswa</h1><form method="get" class="card"><label>Filter kursus</label><select name="kursus_id" onchange="this.form.submit()"><option value="0">Semua kursus</option>
  <?php foreach ($all as $a): ?><option value="<?= $a['id'] ?>" <?= $kf==$a['id']?'selected':'' ?>><?= e($a['nama']) ?></option><?php endforeach; ?></select></form>
  <div class="tablewrap"><table><tr><th>Siswa</th><th>Kursus</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr>
  <?php foreach ($s as $x): ?><tr><td><?= e($x['siswa']) ?><br><span class="muted"><?= e($x['email']) ?></span></td><td><?= e($x['kursus']) ?></td><td><?= e($x['tanggal']) ?></td>
  <td><span class="badge <?= e($x['status']) ?>"><?= e($x['status']) ?></span></td><td><?php if ($x['status']==='menunggu'): ?>
  <form class="inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $x['id'] ?>"><button name="aksi" value="terima">Terima</button> <button name="aksi" value="tolak" class="red">Tolak</button></form><?php endif; ?></td></tr><?php endforeach; ?></table></div>
<?php endif; include 'includes/footer.php'; ?>
