<?php require 'config.php'; require_login(); $u=user(); $title='Presensi';
$STATUS=['Hadir','Izin','Sakit','Alpa'];
// ambil jadwal + cek akses (pengajar hanya kelas miliknya, admin boleh lihat semua, siswa tidak punya halaman detail)
function ambil_jadwal($pdo,$id,$u){
  $s=$pdo->prepare('SELECT j.*,(j.tanggal<=CURDATE()) boleh,k.id kid,k.nama kelas,k.pengajar_id,c.nama kursus,p.nama pengajar FROM jadwal j JOIN kelas k ON k.id=j.kelas_id JOIN kursus c ON c.id=k.kursus_id JOIN users p ON p.id=k.pengajar_id WHERE j.id=?');
  $s->execute([$id]); $j=$s->fetch();
  if (!$j || $u['role']==='siswa' || ($u['role']==='pengajar' && $j['pengajar_id']!=$u['id'])) return null;
  return $j;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && $u['role']==='pengajar') { csrf_check();
  $jid=(int)($_POST['jadwal_id']??0); $j=ambil_jadwal($pdo,$jid,$u);
  if (!$j) { flash('Jadwal tidak ditemukan.'); redirect('presensi.php'); }
  if (!$j['boleh']) { flash('Presensi baru bisa diisi pada hari kelas atau setelahnya.'); redirect('presensi.php?jadwal='.$jid); }
  $sw=$pdo->prepare('SELECT siswa_id FROM kelas_siswa WHERE kelas_id=?'); $sw->execute([$j['kid']]); $ids=$sw->fetchAll(PDO::FETCH_COLUMN);
  $in=$_POST['status']??[]; $ok=true; foreach ($ids as $sid) if (!in_array($in[$sid]??'',$STATUS,true)) $ok=false;
  if (!$ids) flash('Belum ada siswa di kelas ini.');
  elseif (!$ok) flash('Pilih status untuk semua siswa.');
  else { $pdo->beginTransaction();
    $pt=$pdo->prepare('SELECT id FROM pertemuan WHERE jadwal_id=?'); $pt->execute([$jid]); $ptid=$pt->fetchColumn();
    if (!$ptid) { $pdo->prepare('INSERT INTO pertemuan (jadwal_id,tanggal) VALUES (?,?)')->execute([$jid,$j['tanggal']]); $ptid=$pdo->lastInsertId(); }
    foreach ($ids as $sid) { $ex=$pdo->prepare('SELECT id FROM presensi WHERE pertemuan_id=? AND siswa_id=?'); $ex->execute([$ptid,$sid]); $pid=$ex->fetchColumn();
      if ($pid) $pdo->prepare('UPDATE presensi SET status=?,pengajar_id=?,waktu_catat=NOW() WHERE id=?')->execute([$in[$sid],$u['id'],$pid]);
      else $pdo->prepare('INSERT INTO presensi (pertemuan_id,siswa_id,status,pengajar_id) VALUES (?,?,?,?)')->execute([$ptid,$sid,$in[$sid],$u['id']]); }
    $pdo->commit(); flash('Presensi disimpan.'); }
  redirect('presensi.php?jadwal='.$jid); }
include 'includes/header.php';

if ($u['role']==='siswa'):
  $s=$pdo->prepare('SELECT j.tanggal,j.jam_mulai,j.jam_selesai,k.nama kelas,c.nama kursus,(SELECT pr.status FROM presensi pr JOIN pertemuan pt ON pt.id=pr.pertemuan_id WHERE pt.jadwal_id=j.id AND pr.siswa_id=? ORDER BY pr.id DESC LIMIT 1) status FROM jadwal j JOIN kelas k ON k.id=j.kelas_id JOIN kursus c ON c.id=k.kursus_id WHERE k.id IN (SELECT kelas_id FROM kelas_siswa WHERE siswa_id=?) AND j.tanggal<=CURDATE() ORDER BY j.tanggal DESC,j.jam_mulai DESC');
  $s->execute([$u['id'],$u['id']]); $rows=$s->fetchAll(); $tot=array_fill_keys($STATUS,0); foreach ($rows as $r) if ($r['status']) $tot[$r['status']]++; ?>
<h1>Presensi saya</h1>
<div class="grid"><?php foreach ($STATUS as $i=>$st): ?><div class="card stat c<?= $i+1 ?>"><b><?= $tot[$st] ?></b><?= $st ?></div><?php endforeach; ?></div>
<div class="tablewrap"><table><tr><th>Tanggal</th><th>Jam</th><th>Kursus / Kelas</th><th>Status</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['tanggal']) ?></td><td><?= e(substr($r['jam_mulai'],0,5)) ?> - <?= e(substr($r['jam_selesai'],0,5)) ?></td><td><?= e($r['kursus']) ?> / <?= e($r['kelas']) ?></td>
<td><?= $r['status'] ? '<span class="badge '.e($r['status']).'">'.e($r['status']).'</span>' : '<span class="muted">Belum dicatat</span>' ?></td></tr><?php endforeach;
if (!$rows): ?><tr><td colspan="4" class="muted">Belum ada pertemuan yang berlangsung.</td></tr><?php endif; ?></table></div>

<?php elseif (isset($_GET['jadwal'])):
  $j=ambil_jadwal($pdo,(int)$_GET['jadwal'],$u);
  if (!$j) { echo '<h1>Presensi</h1><div class="card"><p>Jadwal tidak ditemukan.</p><a class="btn sec" href="presensi.php">Kembali</a></div>'; include 'includes/footer.php'; exit; }
  $s=$pdo->prepare('SELECT u.id,u.nama,(SELECT pr.status FROM presensi pr JOIN pertemuan pt ON pt.id=pr.pertemuan_id WHERE pt.jadwal_id=? AND pr.siswa_id=u.id ORDER BY pr.id DESC LIMIT 1) status FROM kelas_siswa ks JOIN users u ON u.id=ks.siswa_id WHERE ks.kelas_id=? ORDER BY u.nama');
  $s->execute([$j['id'],$j['kid']]); $sw=$s->fetchAll(); $edit=($u['role']==='pengajar' && $j['boleh']); ?>
<h1>Presensi</h1>
<div class="card"><b><?= e($j['kursus']) ?> / <?= e($j['kelas']) ?></b><br><span class="muted"><?= e($j['tanggal']) ?>, <?= e(substr($j['jam_mulai'],0,5)) ?> - <?= e(substr($j['jam_selesai'],0,5)) ?>, Ruang <?= e($j['ruang']) ?> &middot; Pengajar: <?= e($j['pengajar']) ?></span></div>
<?php if ($u['role']==='pengajar' && !$j['boleh']): ?><p class="flash">Presensi baru bisa diisi pada hari kelas (<?= e($j['tanggal']) ?>) atau setelahnya.</p><?php endif; ?>
<?php if (!$sw): ?><div class="card"><p>Belum ada siswa di kelas ini.</p></div>
<?php elseif ($edit): ?>
<form method="post" class="tablewrap" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="jadwal_id" value="<?= $j['id'] ?>">
<table><tr><th>Siswa</th><th>Status kehadiran</th></tr>
<?php foreach ($sw as $x): ?><tr><td><?= e($x['nama']) ?></td><td><?php foreach ($STATUS as $st): ?><label class="rad"><input type="radio" name="status[<?= $x['id'] ?>]" value="<?= $st ?>" <?= ($x['status'] ?: 'Hadir')===$st?'checked':'' ?>> <?= $st ?></label><?php endforeach; ?></td></tr><?php endforeach; ?></table>
<p style="padding:0 .8rem"><button>Simpan presensi</button> <a class="btn sec" href="presensi.php">Kembali</a></p></form>
<?php else: ?>
<div class="tablewrap"><table><tr><th>Siswa</th><th>Status</th></tr><?php foreach ($sw as $x): ?><tr><td><?= e($x['nama']) ?></td><td><?= $x['status'] ? '<span class="badge '.e($x['status']).'">'.e($x['status']).'</span>' : '<span class="muted">Belum dicatat</span>' ?></td></tr><?php endforeach; ?></table></div>
<p><a class="btn sec" href="presensi.php">Kembali</a></p>
<?php endif; ?>

<?php else:
  $adm=$u['role']==='admin'; $w=$adm?'':'WHERE k.pengajar_id=?'; $par=$adm?[]:[$u['id']];
  $s=$pdo->prepare('SELECT j.id,j.tanggal,j.jam_mulai,j.jam_selesai,j.ruang,(j.tanggal<=CURDATE()) boleh,k.nama kelas,c.nama kursus,p.nama pengajar,(SELECT COUNT(*) FROM kelas_siswa WHERE kelas_id=k.id) jml,(SELECT COUNT(*) FROM pertemuan pt JOIN presensi pr ON pr.pertemuan_id=pt.id WHERE pt.jadwal_id=j.id) tercatat FROM jadwal j JOIN kelas k ON k.id=j.kelas_id JOIN kursus c ON c.id=k.kursus_id JOIN users p ON p.id=k.pengajar_id '.$w.' ORDER BY j.tanggal DESC,j.jam_mulai');
  $s->execute($par); $jd=$s->fetchAll();
  $r=$pdo->prepare('SELECT k.id,c.nama kursus,k.nama kelas,u.nama siswa,COALESCE(SUM(pr.status="Hadir"),0) h,COALESCE(SUM(pr.status="Izin"),0) i,COALESCE(SUM(pr.status="Sakit"),0) s,COALESCE(SUM(pr.status="Alpa"),0) a FROM kelas k JOIN kursus c ON c.id=k.kursus_id JOIN kelas_siswa ks ON ks.kelas_id=k.id JOIN users u ON u.id=ks.siswa_id LEFT JOIN jadwal j ON j.kelas_id=k.id LEFT JOIN pertemuan pt ON pt.jadwal_id=j.id LEFT JOIN presensi pr ON pr.pertemuan_id=pt.id AND pr.siswa_id=u.id '.$w.' GROUP BY k.id,u.id ORDER BY c.nama,k.nama,u.nama');
  $r->execute($par); $rekap=$r->fetchAll(); ?>
<h1><?= $adm ? 'Rekap presensi' : 'Presensi kelas saya' ?></h1>
<h2 class="sec-title">Daftar pertemuan</h2>
<div class="tablewrap"><table><tr><th>Tanggal</th><th>Jam</th><th>Kursus / Kelas</th><?php if ($adm): ?><th>Pengajar</th><?php endif; ?><th>Presensi</th><th>Aksi</th></tr>
<?php foreach ($jd as $x): ?><tr><td><?= e($x['tanggal']) ?></td><td><?= e(substr($x['jam_mulai'],0,5)) ?> - <?= e(substr($x['jam_selesai'],0,5)) ?></td><td><?= e($x['kursus']) ?> / <?= e($x['kelas']) ?></td><?php if ($adm): ?><td><?= e($x['pengajar']) ?></td><?php endif; ?>
<td><?= $x['tercatat'] ? e($x['tercatat'].' dari '.$x['jml'].' siswa tercatat') : '<span class="muted">Belum dicatat</span>' ?></td>
<td><a class="btn sec" href="?jadwal=<?= $x['id'] ?>"><?= (!$adm && $x['boleh']) ? ($x['tercatat'] ? 'Ubah' : 'Isi presensi') : 'Lihat' ?></a></td></tr><?php endforeach;
if (!$jd): ?><tr><td colspan="<?= $adm?6:5 ?>" class="muted">Belum ada jadwal.</td></tr><?php endif; ?></table></div>
<h2 class="sec-title">Rekap kehadiran per siswa</h2>
<div class="tablewrap"><table><tr><th>Kursus / Kelas</th><th>Siswa</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpa</th></tr>
<?php foreach ($rekap as $x): ?><tr><td><?= e($x['kursus']) ?> / <?= e($x['kelas']) ?></td><td><?= e($x['siswa']) ?></td><td><?= (int)$x['h'] ?></td><td><?= (int)$x['i'] ?></td><td><?= (int)$x['s'] ?></td><td><?= (int)$x['a'] ?></td></tr><?php endforeach;
if (!$rekap): ?><tr><td colspan="6" class="muted">Belum ada siswa di kelas.</td></tr><?php endif; ?></table></div>
<?php endif; include 'includes/footer.php'; ?>
