<?php require 'config.php'; require_login(); $u=user(); $title='Dashboard';
function n($pdo,$q,$p=[]){ $s=$pdo->prepare($q); $s->execute($p); return (int)$s->fetchColumn(); }
$stats=[]; $notes=[]; $uid=$u['id']; $w=''; $par=[];
if ($u['role']==='admin') {
  $stats=[['Total siswa',n($pdo,"SELECT COUNT(*) FROM users WHERE role='siswa'")],['Total pengajar',n($pdo,"SELECT COUNT(*) FROM users WHERE role='pengajar'")],['Kursus aktif',n($pdo,"SELECT COUNT(*) FROM kursus WHERE status='aktif'")],['Kelas berjalan',n($pdo,'SELECT COUNT(*) FROM kelas')]];
  if ($x=n($pdo,"SELECT COUNT(*) FROM pendaftaran WHERE status='menunggu'")) $notes[]=["$x pendaftaran menunggu konfirmasi",'pendaftaran.php'];
  if ($x=n($pdo,"SELECT COUNT(*) FROM tagihan WHERE status='menunggu_verifikasi'")) $notes[]=["$x bukti pembayaran perlu diverifikasi",'pembayaran.php'];
} elseif ($u['role']==='siswa') { $w=' AND k.id IN (SELECT kelas_id FROM kelas_siswa WHERE siswa_id=?)'; $par=[$uid];
  $bb=n($pdo,"SELECT COUNT(*) FROM tagihan t JOIN pendaftaran p ON p.id=t.pendaftaran_id WHERE p.siswa_id=? AND t.status IN ('belum_bayar','ditolak')",[$uid]);
  $stats=[['Kursus diterima',n($pdo,"SELECT COUNT(*) FROM pendaftaran WHERE siswa_id=? AND status='diterima'",[$uid])],['Menunggu konfirmasi',n($pdo,"SELECT COUNT(*) FROM pendaftaran WHERE siswa_id=? AND status='menunggu'",[$uid])],['Tagihan perlu dibayar',$bb]];
  if ($bb) $notes[]=["$bb tagihan perlu dibayar atau dikirim ulang buktinya",'pembayaran.php'];
} else { $w=' AND k.pengajar_id=?'; $par=[$uid];
  $stats=[['Kelas diampu',n($pdo,'SELECT COUNT(*) FROM kelas WHERE pengajar_id=?',[$uid])],['Siswa di kelas saya',n($pdo,'SELECT COUNT(*) FROM kelas_siswa ks JOIN kelas k ON k.id=ks.kelas_id WHERE k.pengajar_id=?',[$uid])]]; }
$a=$pdo->prepare('SELECT j.jam_mulai,j.jam_selesai,j.ruang,k.nama kelas,c.nama kursus FROM jadwal j JOIN kelas k ON k.id=j.kelas_id JOIN kursus c ON c.id=k.kursus_id WHERE j.tanggal=CURDATE()'.$w.' ORDER BY j.jam_mulai'); $a->execute($par); $ag=$a->fetchAll();
include 'includes/header.php'; ?>
<div class="hero"><div><p class="eyebrow">Selamat datang kembali</p><h1>Halo, <?= e($u['nama']) ?></h1></div>
<div class="datecard" aria-live="off"><span id="bln"></span><b id="hari"></b><span id="jam"></span></div></div>
<div class="grid"><?php foreach ($stats as $i=>[$l,$v]): ?><div class="card stat c<?= $i%4+1 ?>"><b><?= $v ?></b><?= e($l) ?></div><?php endforeach; ?></div>
<div class="cols">
<section class="card"><h3>Perlu perhatian</h3><ul class="notes"><?php foreach ($notes as [$t,$l]): ?><li><a href="<?= $l ?>"><?= e($t) ?></a></li><?php endforeach; if (!$notes): ?><li>Tidak ada hal mendesak saat ini.</li><?php endif; ?></ul></section>
<section class="card"><h3>Agenda hari ini</h3><ol class="agenda"><?php foreach ($ag as $j): ?><li><span class="t"><?= e(substr($j['jam_mulai'],0,5)) ?> - <?= e(substr($j['jam_selesai'],0,5)) ?></span><span><?= e($j['kursus']) ?><small><?= e($j['kelas']) ?> · Ruang <?= e($j['ruang']) ?></small></span></li><?php endforeach; if (!$ag): ?><li><span></span><span class="muted">Tidak ada kelas hari ini.</span></li><?php endif; ?></ol></section>
</div>
<?php include 'includes/footer.php'; ?>
