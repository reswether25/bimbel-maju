<?php require 'config.php'; require_login(['admin','siswa']); $u=user(); $title='Pembayaran';
$label=['belum_bayar'=>'Belum Bayar','menunggu_verifikasi'=>'Menunggu Verifikasi','lunas'=>'Lunas','ditolak'=>'Ditolak'];
if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_check(); $tid=(int)($_POST['tagihan_id']??0);
  if ($u['role']==='siswa' && isset($_FILES['bukti'])) {
    $q=$pdo->prepare('SELECT t.id,t.status FROM tagihan t JOIN pendaftaran p ON p.id=t.pendaftaran_id WHERE t.id=? AND p.siswa_id=?'); $q->execute([$tid,$u['id']]); $t=$q->fetch(); $f=$_FILES['bukti']; $mt=$_POST['metode']??'';
    if (!$t || !in_array($t['status'],['belum_bayar','ditolak'])) flash('Tagihan ini tidak dapat diproses.');
    elseif (!isset($METODE_BAYAR[$mt])) flash('Pilih metode pembayaran terlebih dahulu.');
    elseif ($f['error']!==UPLOAD_ERR_OK || $f['size']==0 || $f['size']>2*1024*1024) flash('Unggah gagal. Pastikan file tidak kosong dan maksimal 2 MB.');
    else { $ext=['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'][(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'])] ?? null;
      if (!$ext) flash('Format file harus JPG, PNG, atau PDF.');
      else { $nm=bin2hex(random_bytes(12)).'.'.$ext;
        if (!move_uploaded_file($f['tmp_name'],__DIR__.'/uploads/'.$nm)) flash('Gagal menyimpan file. Coba lagi.');
        else { $pdo->prepare('INSERT INTO pembayaran (tagihan_id,bukti,metode) VALUES (?,?,?)')->execute([$tid,$nm,$mt]);
          $pdo->prepare("UPDATE tagihan SET status='menunggu_verifikasi' WHERE id=?")->execute([$tid]); flash('Bukti terkirim. Menunggu verifikasi admin.'); } } } }
  if ($u['role']==='admin' && isset($_POST['aksi'])) { $ok=$_POST['aksi']==='setuju'; $alasan=trim($_POST['alasan']??'');
    if (!$ok && $alasan==='') flash('Alasan penolakan wajib diisi.');
    else { $up=$pdo->prepare("UPDATE tagihan SET status=? WHERE id=? AND status='menunggu_verifikasi'"); $up->execute([$ok?'lunas':'ditolak',$tid]);
      if (!$ok && $up->rowCount()) $pdo->prepare('UPDATE pembayaran SET catatan_penolakan=? WHERE id=(SELECT id FROM (SELECT MAX(id) id FROM pembayaran WHERE tagihan_id=?) x)')->execute([$alasan,$tid]);
      flash($ok?'Pembayaran disetujui. Status: Lunas.':'Pembayaran ditolak.'); } }
  redirect('pembayaran.php'); }
include 'includes/header.php';
$sel='SELECT t.*,k.nama kursus,u.nama siswa,(SELECT MAX(id) FROM pembayaran WHERE tagihan_id=t.id) pid,(SELECT catatan_penolakan FROM pembayaran WHERE id=(SELECT MAX(id) FROM pembayaran WHERE tagihan_id=t.id)) catatan,(SELECT metode FROM pembayaran WHERE id=(SELECT MAX(id) FROM pembayaran WHERE tagihan_id=t.id)) metode FROM tagihan t JOIN pendaftaran p ON p.id=t.pendaftaran_id JOIN users u ON u.id=p.siswa_id JOIN kursus k ON k.id=p.kursus_id ';
if ($u['role']==='siswa'): $s=$pdo->prepare($sel.'WHERE p.siswa_id=? ORDER BY t.created_at DESC'); $s->execute([$u['id']]); $rows=$s->fetchAll(); ?>
<h1>Tagihan dan pembayaran</h1><p class="muted">Pilih metode pembayaran, transfer sesuai jumlah tagihan ke tujuan yang tertera, lalu unggah bukti (JPG, PNG, atau PDF, maksimal 2 MB).</p>
<?php foreach ($rows as $t): ?><div class="card"><h3><?= e($t['kursus']) ?> <span class="badge <?= e($t['status']) ?>"><?= $label[$t['status']] ?></span></h3>
<p class="muted">No. invoice: <b><?= e(invoice_no($t)) ?></b> &middot; <a href="invoice.php?id=<?= $t['id'] ?>" target="_blank">Lihat / cetak invoice</a></p>
<p><b><?= rupiah($t['jumlah']) ?></b></p>
<?php if ($t['metode'] && isset($METODE_BAYAR[$t['metode']])): ?><p class="muted">Metode terakhir: <?= e($METODE_BAYAR[$t['metode']]['nama']) ?></p><?php endif; ?>
<?php if ($t['status']==='ditolak' && $t['catatan']): ?><p class="flash">Ditolak: <?= e($t['catatan']) ?></p><?php endif; ?>
<?php if ($t['pid']): ?><p><a href="bukti.php?id=<?= $t['pid'] ?>" target="_blank">Lihat bukti terakhir</a></p><?php endif; ?>
<?php if (in_array($t['status'],['belum_bayar','ditolak'])): ?><form method="post" enctype="multipart/form-data" autocomplete="off" data-reset><?= csrf_field() ?><input type="hidden" name="tagihan_id" value="<?= $t['id'] ?>">
<label>Metode pembayaran dan tujuan bayar</label>
<?php foreach ($METODE_BAYAR as $kode=>$m): ?><label class="metode"><input type="radio" name="metode" value="<?= e($kode) ?>" required><b><?= e($m['nama']) ?></b><small><?= e($m['info']) ?>: <b><?= e($m['tujuan']) ?></b> a.n. <?= e($m['atas_nama']) ?></small></label><?php endforeach; ?>
<label>Bukti pembayaran</label><input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf" required><p><button>Kirim bukti</button></p></form><?php endif; ?></div>
<?php endforeach; if (!$rows): ?><div class="card"><p>Belum ada tagihan. Tagihan muncul setelah admin menerima pendaftaran Anda.</p></div><?php endif;
else: $d1=$_GET['dari']??''; $d2=$_GET['sampai']??''; $s=$pdo->prepare($sel."WHERE DATE(t.created_at) BETWEEN ? AND ? ORDER BY t.status='menunggu_verifikasi' DESC,t.created_at DESC"); $s->execute([$d1?:'2000-01-01',$d2?:'2999-12-31']); $rows=$s->fetchAll();
  $tot=array_sum(array_map(fn($r)=>$r['status']==='lunas'?$r['jumlah']:0,$rows)); ?>
<h1>Verifikasi pembayaran dan laporan</h1>
<form method="get" class="card"><div class="grid"><div><label>Dari tanggal</label><input type="date" name="dari" value="<?= e($d1) ?>"></div><div><label>Sampai tanggal</label><input type="date" name="sampai" value="<?= e($d2) ?>"></div></div><p><button>Tampilkan</button></p></form>
<div class="card stat"><b><?= rupiah($tot) ?></b>Total pemasukan (lunas) pada periode ini</div>
<div class="tablewrap"><table><tr><th>Siswa</th><th>Kursus</th><th>Invoice</th><th>Jumlah</th><th>Metode</th><th>Status</th><th>Bukti</th><th>Aksi</th></tr>
<?php foreach ($rows as $t): ?><tr><td><?= e($t['siswa']) ?></td><td><?= e($t['kursus']) ?></td><td><a href="invoice.php?id=<?= $t['id'] ?>" target="_blank"><?= e(invoice_no($t)) ?></a></td><td><?= rupiah($t['jumlah']) ?></td><td><?= e(($t['metode'] && isset($METODE_BAYAR[$t['metode']])) ? $METODE_BAYAR[$t['metode']]['nama'] : '-') ?></td><td><span class="badge <?= e($t['status']) ?>"><?= $label[$t['status']] ?></span></td>
<td><?= $t['pid'] ? '<a href="bukti.php?id='.$t['pid'].'" target="_blank">Lihat</a>' : '-' ?></td>
<td><?php if ($t['status']==='menunggu_verifikasi'): ?><form method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="tagihan_id" value="<?= $t['id'] ?>">
<input name="alasan" placeholder="Alasan jika ditolak"><p><button name="aksi" value="setuju">Setujui</button> <button name="aksi" value="tolak" class="red">Tolak</button></p></form><?php endif; ?></td></tr><?php endforeach; ?></table></div>
<?php endif; include 'includes/footer.php'; ?>
