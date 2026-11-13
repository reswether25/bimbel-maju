<?php require 'config.php'; require_login(['admin']); $title='Kursus';
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check();
  if (isset($_POST['toggle'])) { $pdo->prepare("UPDATE kursus SET status=IF(status='aktif','nonaktif','aktif') WHERE id=?")->execute([(int)$_POST['toggle']]); flash('Status kursus diubah.'); }
  else { $d=[trim($_POST['nama']??''),trim($_POST['tingkat']??''),max(0,(int)($_POST['biaya']??0))];
    if ($d[0]==='') flash('Nama kursus wajib diisi.');
    elseif (!empty($_POST['id'])) { $pdo->prepare('UPDATE kursus SET nama=?,tingkat=?,biaya=? WHERE id=?')->execute([...$d,(int)$_POST['id']]); flash('Kursus diperbarui.'); }
    else { $pdo->prepare('INSERT INTO kursus (nama,tingkat,biaya) VALUES (?,?,?)')->execute($d); flash('Kursus ditambahkan.'); } }
  redirect('kursus.php'); }
$edit=null; if (isset($_GET['edit'])) { $s=$pdo->prepare('SELECT * FROM kursus WHERE id=?'); $s->execute([(int)$_GET['edit']]); $edit=$s->fetch(); }
$rows=$pdo->query('SELECT * FROM kursus ORDER BY status,nama')->fetchAll();
include 'includes/header.php'; ?>
<h1>Kelola kursus</h1>
<div class="card"><form method="post" autocomplete="off" data-reset><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($edit['id']??'') ?>">
<label>Nama kursus</label><input name="nama" required value="<?= e($edit['nama']??'') ?>">
<label>Tingkat</label><input name="tingkat" value="<?= e($edit['tingkat']??'') ?>">
<label>Biaya (Rp)</label><input type="number" name="biaya" min="0" value="<?= e($edit['biaya']??0) ?>">
<p><button><?= $edit ? 'Simpan perubahan' : 'Tambah kursus' ?></button> <?php if ($edit): ?><a class="btn sec" href="kursus.php">Batal</a><?php endif; ?></p></form></div>
<div class="tablewrap"><table><tr><th>Kursus</th><th>Tingkat</th><th>Biaya</th><th>Status</th><th>Aksi</th></tr>
<?php foreach ($rows as $k): ?><tr><td><?= e($k['nama']) ?></td><td><?= e($k['tingkat']) ?></td><td><?= rupiah($k['biaya']) ?></td>
<td><span class="badge <?= e($k['status']) ?>"><?= e($k['status']) ?></span></td>
<td><a class="btn sec" href="?edit=<?= $k['id'] ?>">Ubah</a>
<form class="inline" method="post"><?= csrf_field() ?><button name="toggle" value="<?= $k['id'] ?>" class="<?= $k['status']=='aktif'?'red':'' ?>"><?= $k['status']=='aktif'?'Nonaktifkan':'Aktifkan' ?></button></form></td></tr>
<?php endforeach; ?></table></div>
<?php include 'includes/footer.php'; ?>
