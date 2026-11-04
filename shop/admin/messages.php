<?php
$title = 'Tin nhắn';
require __DIR__ . '/_header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $threadId = (int)post('thread');
    $message = post('message');
    $messageLength = function_exists('mb_strlen') ? mb_strlen($message, 'UTF-8') : strlen($message);
    if (!$threadId || !one('SELECT MaHT FROM HoiThoai WHERE MaHT = ?', [$threadId])) {
        flash('Không tìm thấy cuộc trò chuyện.', 'err');
    } elseif ($message === '' || $messageLength > 2000) {
        flash('Nội dung tin nhắn cần từ 1 đến 2.000 ký tự.', 'err');
    } else {
        run('INSERT INTO TinNhan (MaHT, NguoiGui, NoiDung, NgayGui, DaDoc) VALUES (?, ?, ?, ?, 0)', [$threadId, 'admin', $message, nowStr()]);
        run('UPDATE HoiThoai SET NgayCapNhat = ? WHERE MaHT = ?', [nowStr(), $threadId]);
        flash('Đã gửi trả lời.');
    }
    redirect('admin/messages.php?id=' . $threadId);
}
$threads = all("SELECT h.MaHT, h.UserName, h.TieuDe, h.NgayCapNhat, u.HoTen, k.TenKH,
    (SELECT COUNT(*) FROM TinNhan t WHERE t.MaHT = h.MaHT AND t.NguoiGui = 'khach' AND t.DaDoc = 0) AS ChuaDoc,
    (SELECT NoiDung FROM TinNhan t WHERE t.MaHT = h.MaHT ORDER BY t.MaTN DESC " . (driver() === 'sqlsrv' ? 'OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY' : 'LIMIT 1') . ") AS TinCuoi
    FROM HoiThoai h JOIN Users u ON u.UserName = h.UserName
    LEFT JOIN KhachHang k ON k.MaKH = u.MaKH ORDER BY h.NgayCapNhat DESC");
$threadId = (int)($_GET['id'] ?? 0);
$selected = null;
if ($threadId) foreach ($threads as $thread) if ((int)$thread['MaHT'] === $threadId) $selected = $thread;
if (!$selected && $threads) $selected = $threads[0];
if ($selected) {
    run("UPDATE TinNhan SET DaDoc = 1 WHERE MaHT = ? AND NguoiGui = 'khach' AND DaDoc = 0", [$selected['MaHT']]);
    $messages = all('SELECT * FROM TinNhan WHERE MaHT = ? ORDER BY MaTN', [$selected['MaHT']]);
} else $messages = [];
?>
<h1>Tin nhắn</h1>
<div class="inbox-layout admin-inbox">
  <section class="panel inbox-list">
    <?php foreach ($threads as $thread): $customerName = $thread['TenKH'] ?: $thread['HoTen'] ?: $thread['UserName']; ?><a class="inbox-thread <?= $selected && (int)$selected['MaHT'] === (int)$thread['MaHT'] ? 'selected' : '' ?>" href="<?= url('admin/messages.php?id=' . (int)$thread['MaHT']) ?>">
      <b><?= h($customerName) ?></b><small><?= h($thread['TieuDe']) ?> · <?= h($thread['TinCuoi'] ?? '') ?></small><time><?= fdate($thread['NgayCapNhat'], 'd/m/Y H:i') ?></time><?php if ((int)$thread['ChuaDoc']): ?><i class="unread-dot" aria-label="Tin nhắn chưa đọc"></i><?php endif; ?>
    </a><?php endforeach; ?>
    <?php if (!$threads): ?><p class="muted">Chưa có tin nhắn từ khách hàng.</p><?php endif; ?>
  </section>
  <section class="panel inbox-conversation">
    <?php if ($selected): ?><header class="conversation-head"><h2><?= h($selected['TenKH'] ?: $selected['HoTen'] ?: $selected['UserName']) ?></h2><span class="muted"><?= h($selected['TieuDe']) ?> · <?= h($selected['UserName']) ?></span></header>
      <div class="message-history"><?php foreach ($messages as $message): ?><article class="message-bubble <?= $message['NguoiGui'] === 'admin' ? 'outgoing' : 'incoming' ?>"><p><?= nl2br(h($message['NoiDung'])) ?></p><time><?= $message['NguoiGui'] === 'admin' ? 'Cửa hàng · ' : h($selected['HoTen'] ?: $selected['UserName']) . ' · ' ?><?= fdate($message['NgayGui']) ?></time></article><?php endforeach; ?></div>
      <form method="post" class="message-compose"><?= csrf() ?><input type="hidden" name="thread" value="<?= (int)$selected['MaHT'] ?>"><label class="sr-only" for="reply-message">Nội dung trả lời</label><textarea id="reply-message" name="message" rows="3" maxlength="2000" placeholder="Trả lời khách hàng…" required></textarea><button class="btn">Gửi trả lời</button></form>
    <?php else: ?><p class="muted">Chọn tin nhắn để đọc và trả lời.</p><?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>