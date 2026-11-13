<?php require 'config.php'; require_login(['admin','siswa']); $u=user();
$s=$pdo->prepare('SELECT b.bukti,p.siswa_id FROM pembayaran b JOIN tagihan t ON t.id=b.tagihan_id JOIN pendaftaran p ON p.id=t.pendaftaran_id WHERE b.id=?'); $s->execute([(int)($_GET['id']??0)]); $r=$s->fetch();
if (!$r || ($u['role']!=='admin' && $r['siswa_id']!=$u['id'])) { http_response_code(404); die('Berkas tidak ditemukan.'); }
$p=__DIR__.'/uploads/'.basename($r['bukti']); if (!is_file($p)) { http_response_code(404); die('Berkas tidak ditemukan.'); }
header('Content-Type: '.(new finfo(FILEINFO_MIME_TYPE))->file($p)); header('X-Content-Type-Options: nosniff'); readfile($p);
