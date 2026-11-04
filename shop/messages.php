<?php
require __DIR__ . '/includes/bootstrap.php';
requireLogin();
if (isAdmin()) redirect('admin/messages.php');
$userName = me()['UserName'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    $message = post('message');
    $subject = post('subject');
    $messageLength = function_exists('mb_strlen') ? mb_strlen($message, 'UTF-8') : strlen($message);
    if ($message === '' || $messageLength > 2000) {
        flash('Nội dung tin nhắn cần từ 1 đến 2.000 ký tự.', 'err');
        redirect('messages.php' . (!empty($_POST['thread']) ? '?id=' . (int)$_POST['thread'] : '?new=1'));
    }
    if ($action === 'new') {
        if ($subject === '') $subject = 'Tin nhắn mới';
        $subject = function_exists('mb_substr') ? mb_substr($subject, 0, 120, 'UTF-8') : substr($subject, 0, 120);
        run('INSERT INTO HoiThoai (UserName, TieuDe, NgayCapNhat) VALUES (?, ?, ?)', [$userName, $subject, nowStr()]);
        $threadId = (int)lastId();
        run('INSERT INTO TinNhan (MaHT, NguoiGui, NoiDung, NgayGui, DaDoc) VALUES (?, ?, ?, ?, 0)', [$threadId, 'khach', $message, nowStr()]);
        flash('Đã gửi tin nhắn cho cửa hàng.');
        redirect('messages.php?id=' . $threadId);
    }
    $threadId = (int)post('thread');
    if ($threadId && one('SELECT MaHT FROM HoiThoai WHERE MaHT = ? AND UserName = ?', [$threadId, $userName])) {
        run('INSERT INTO TinNhan (MaHT, NguoiGui, NoiDung, NgayGui, DaDoc) VALUES (?, ?, ?, ?, 0)', [$threadId, 'khach', $message, nowStr()]);
        run('UPDATE HoiThoai SET NgayCapNhat = ? WHERE MaHT = ?', [nowStr(), $threadId]);
        flash('Đã gửi tin nhắn.');
        redirect('messages.php?id=' . $threadId);
    }
    flash('Không tìm thấy cuộc trò chuyện.', 'err');
    redirect('messages.php');
}

$threads = all("SELECT h.MaHT, h.TieuDe, h.NgayCapNhat,
    (SELECT COUNT(*) FROM TinNhan t WHERE t.MaHT = h.MaHT AND t.NguoiGui = 'admin' AND t.DaDoc = 0) AS ChuaDoc,
    (SELECT NoiDung FROM TinNhan t WHERE t.MaHT = h.MaHT ORDER BY t.MaTN DESC " . (driver() === 'sqlsrv' ? 'OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY' : 'LIMIT 1') . ") AS TinCuoi
    FROM HoiThoai h WHERE h.UserName = ? ORDER BY h.NgayCapNhat DESC", [$userName]);
$threadId = (int)($_GET['id'] ?? 0);
$selected = null;
if ($threadId) {
    foreach ($threads as $thread) if ((int)$thread['MaHT'] === $threadId) $selected = $thread;
} elseif ($threads && !isset($_GET['new'])) $selected = $threads[0];
if ($selected) {
    run("UPDATE TinNhan SET DaDoc = 1 WHERE MaHT = ? AND NguoiGui = 'admin' AND DaDoc = 0", [$selected['MaHT']]);
    $messages = all('SELECT * FROM TinNhan WHERE MaHT = ? ORDER BY MaTN', [$selected['MaHT']]);
} else $messages = [];

$title = 'Tin nhắn';
include __DIR__ . '/includes/header.php';
?>
<h1>Tin nhắn</h1>
<div class="inbox-layout">
  <section class="panel inbox-list">
    <div class="inbox-list-head"><h2>Cuộc trò chuyện</h2><a class="btn ghost" href="<?= url('messages.php?new=1') ?>">Tin mới</a></div>
    <?php foreach ($threads as $thread): ?><a class="inbox-thread <?= $selected && (int)$selected['MaHT'] === (int)$thread['MaHT'] ? 'selected' : '' ?>" href="<?= url('messages.php?id=' . (int)$thread['MaHT']) ?>">
      <b><?= h($thread['TieuDe']) ?></b><small><?= h($thread['TinCuoi'] ?? '') ?></small><time><?= fdate($thread['NgayCapNhat'], 'd/m/Y H:i') ?></time><?php if ((int)$thread['ChuaDoc']): ?><i class="unread-dot" aria-label="Có tin nhắn mới"></i><?php endif; ?>
    </a><?php endforeach; ?>
    <?php if (!$threads): ?><p class="muted">Bạn chưa có cuộc trò chuyện nào.</p><?php endif; ?>
  </section>
  <section class="panel inbox-conversation">
    <?php if ($selected): ?><header class="conversation-head"><h2><?= h($selected['TieuDe']) ?></h2><span class="muted">Cửa hàng sẽ phản hồi tại đây</span></header>
      <div class="message-history"><?php foreach ($messages as $message): ?><article class="message-bubble <?= $message['NguoiGui'] === 'khach' ? 'outgoing' : 'incoming' ?>"><p><?= nl2br(h($message['NoiDung'])) ?></p><time><?= fdate($message['NgayGui']) ?></time></article><?php endforeach; ?></div>
      <form method="post" class="message-compose"><?= csrf() ?><input type="hidden" name="action" value="reply"><input type="hidden" name="thread" value="<?= (int)$selected['MaHT'] ?>"><label class="sr-only" for="reply-message">Tin nhắn</label><textarea id="reply-message" name="message" rows="3" maxlength="2000" placeholder="Viết tin nhắn…" required></textarea><button class="btn">Gửi tin nhắn</button></form>
    <?php elseif (isset($_GET['new'])): ?><form method="post" class="new-message-form"><?= csrf() ?><input type="hidden" name="action" value="new"><h2>Gửi tin nhắn mới</h2><label>Chủ đề<input name="subject" maxlength="120" placeholder="Ví dụ: Hỏi về đơn hàng"></label><label>Nội dung<textarea name="message" rows="6" maxlength="2000" required></textarea></label><button class="btn">Gửi cho cửa hàng</button></form>
    <?php else: ?><p class="muted">Chọn một cuộc trò chuyện để xem tin nhắn.</p><?php endif; ?>
  </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>