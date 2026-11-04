<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/reviews.php';
$sp = one('SELECT s.*, l.TenLoaiSP FROM SanPham s LEFT JOIN LoaiSP l ON l.MaLoaiSP = s.MaLoaiSP WHERE s.MaSP = ? AND s.HienThi = 1', [$_GET['id'] ?? '']);
if (!$sp) { http_response_code(404); $title = 'Không tìm thấy'; include __DIR__ . '/includes/header.php'; echo '<div class="empty"><p>Sản phẩm không tồn tại hoặc đã ngừng bán.</p><a class="btn" href="' . url('products.php') . '">Về cửa hàng</a></div>'; include __DIR__ . '/includes/footer.php'; exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review') {
  if (!me()) {
    $_SESSION['back'] = $_SERVER['REQUEST_URI'];
    flash('Vui lòng đăng nhập để đánh giá sản phẩm.', 'err');
    redirect('login.php');
  }
  $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
  $comment = trim((string)($_POST['comment'] ?? ''));
  $commentLength = function_exists('mb_strlen') ? mb_strlen($comment, 'UTF-8') : strlen($comment);
  if ($rating === false || $rating < 1 || $rating > 5) {
    flash('Vui lòng chọn số sao từ 1 đến 5.', 'err');
  } elseif ($commentLength > 1000) {
    flash('Nội dung đánh giá không được vượt quá 1.000 ký tự.', 'err');
  } else {
    saveProductReview($sp['MaSP'], me()['UserName'], $rating, $comment);
    flash('Đã lưu đánh giá sản phẩm.');
    redirect('product.php?id=' . urlencode($sp['MaSP']) . '#reviews');
  }
}
$sizes = csv($sp['KichCo']); $colors = csv($sp['MauSac']);
$stock = (int)$sp['SoLuongTon'];
$reviewSummary = productReviewSummary($sp['MaSP']);
$reviewDistribution = productReviewDistribution($sp['MaSP']);
$reviews = productReviews($sp['MaSP']);
$myReview = me() ? one('SELECT SoSao, NoiDung FROM DanhGia WHERE MaSP = ? AND UserName = ?', [$sp['MaSP'], me()['UserName']]) : null;
$selectedRating = $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review'
  ? (int)($_POST['rating'] ?? 0) : (int)($myReview['SoSao'] ?? 0);
$reviewComment = $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review'
  ? (string)($_POST['comment'] ?? '') : (string)($myReview['NoiDung'] ?? '');
if (!me()) $_SESSION['back'] = $_SERVER['REQUEST_URI'];
$rel = all(topN('SELECT * FROM SanPham WHERE HienThi = 1 AND MaLoaiSP = ? AND MaSP <> ? ORDER BY MaSP', 4), [$sp['MaLoaiSP'], $sp['MaSP']]);
$title = $sp['TenSP'];
include __DIR__ . '/includes/header.php';
?>
<nav class="crumbs"><a href="<?= url() ?>">Trang chủ</a> / <a href="<?= url('products.php?c=' . (int)$sp['MaLoaiSP']) ?>"><?= h($sp['TenLoaiSP']) ?></a> / <?= h($sp['TenSP']) ?></nav>
<div class="detail">
  <div class="detail-img"><img src="<?= h(img($sp)) ?>" alt="<?= h($sp['TenSP']) ?>"></div>
  <form class="detail-info" method="post" action="<?= url('cart.php') ?>">
    <?= csrf() ?>
    <input type="hidden" name="action" value="add"><input type="hidden" name="ma" value="<?= h($sp['MaSP']) ?>">
    <h1><?= h($sp['TenSP']) ?></h1>
    <p class="muted">Mã: <?= h($sp['MaSP']) ?></p>
    <p class="price big"><b><?= money(effPrice($sp)) ?></b>
      <?php if ((int)$sp['PhanTramGiam'] > 0): ?><s><?= money($sp['DonGia']) ?></s> <span class="tag sale inline">-<?= (int)$sp['PhanTramGiam'] ?>%</span><?php endif; ?></p>
    <p><?= nl2br(h($sp['MoTa'])) ?></p>
    <?php if ($sizes): ?>
      <fieldset class="opts"><legend>Kích cỡ</legend>
        <?php foreach ($sizes as $i => $s): ?><label class="opt"><input type="radio" name="size" value="<?= h($s) ?>" <?= $i === 0 ? 'checked' : '' ?>><span><?= h($s) ?></span></label><?php endforeach; ?>
      </fieldset>
    <?php endif; ?>
    <?php if ($colors): ?>
      <fieldset class="opts"><legend>Màu sắc</legend>
        <?php foreach ($colors as $i => $s): ?><label class="opt"><input type="radio" name="mau" value="<?= h($s) ?>" <?= $i === 0 ? 'checked' : '' ?>><span><?= h($s) ?></span></label><?php endforeach; ?>
      </fieldset>
    <?php endif; ?>
    <?php if ($stock > 0): ?>
      <p class="muted">Còn <?= $stock ?> sản phẩm</p>
      <div class="buy-row">
        <input type="number" name="qty" value="1" min="1" max="<?= $stock ?>" aria-label="Số lượng">
        <button class="btn" name="go" value="0">Thêm vào giỏ</button>
        <button class="btn dark" name="go" value="1">Mua ngay</button>
      </div>
    <?php else: ?>
      <p class="flash err">Sản phẩm tạm hết hàng.</p>
    <?php endif; ?>
  </form>
</div>
<section class="reviews block" id="reviews">
  <div class="block-head"><h2>Đánh giá sản phẩm</h2><span class="muted"><?= (int)$reviewSummary['SoLuot'] ?> lượt đánh giá</span></div>
  <div class="reviews-layout">
    <div class="review-score">
      <strong><?= number_format((float)$reviewSummary['DiemTB'], 1) ?></strong>
      <span class="stars" aria-label="<?= number_format((float)$reviewSummary['DiemTB'], 1) ?> trên 5 sao"><?= str_repeat('&#9733;', (int)round((float)$reviewSummary['DiemTB'])) ?><span class="empty-stars"><?= str_repeat('&#9734;', 5 - (int)round((float)$reviewSummary['DiemTB'])) ?></span></span>
      <?php for ($star = 5; $star >= 1; $star--): ?>
        <div class="rating-row"><span><?= $star ?> sao</span><span class="rating-track"><span style="width:<?= $reviewSummary['SoLuot'] ? round($reviewDistribution[$star] / $reviewSummary['SoLuot'] * 100) : 0 ?>%"></span></span><span><?= $reviewDistribution[$star] ?></span></div>
      <?php endfor; ?>
    </div>
    <div class="review-content">
      <?php if (me()): ?>
        <form class="review-form" method="post" action="<?= url('product.php?id=' . urlencode($sp['MaSP']) . '#reviews') ?>">
          <?= csrf() ?><input type="hidden" name="action" value="review">
          <h3><?= $myReview ? 'Sửa đánh giá của bạn' : 'Viết đánh giá' ?></h3>
          <fieldset class="review-stars"><legend>Chọn số sao</legend><div class="star-picker">
            <?php for ($star = 5; $star >= 1; $star--): ?><label><input type="radio" name="rating" value="<?= $star ?>" aria-label="<?= $star ?> sao" <?= $selectedRating === $star ? 'checked' : '' ?> required><span>&#9733;</span></label><?php endfor; ?>
          </div></fieldset>
          <label for="review-comment">Nhận xét <span class="muted">(không bắt buộc)</span></label>
          <textarea id="review-comment" name="comment" rows="3" maxlength="1000" placeholder="Chia sẻ cảm nhận của bạn về sản phẩm"><?= h($reviewComment) ?></textarea>
          <button class="btn" type="submit">Gửi đánh giá</button>
        </form>
      <?php else: ?>
        <p><a href="<?= url('login.php') ?>">Đăng nhập</a> hoặc <a href="<?= url('register.php') ?>">đăng ký</a> để đánh giá sản phẩm.</p>
      <?php endif; ?>
      <div class="review-list">
        <?php foreach ($reviews as $review): ?>
          <article class="review-item">
            <div class="review-item-head"><b><?= h($review['HoTen'] ?: $review['UserName']) ?></b><time class="muted" datetime="<?= h(date('c', strtotime($review['NgayTao']))) ?>"><?= fdate($review['NgayTao']) ?></time></div>
            <div class="stars" aria-label="<?= (int)$review['SoSao'] ?> trên 5 sao"><?= str_repeat('&#9733;', (int)$review['SoSao']) ?><span class="empty-stars"><?= str_repeat('&#9734;', 5 - (int)$review['SoSao']) ?></span></div>
            <?php if ($review['NoiDung'] !== ''): ?><p><?= nl2br(h($review['NoiDung'])) ?></p><?php endif; ?>
          </article>
        <?php endforeach; ?>
        <?php if (!$reviews): ?><p class="muted">Sản phẩm chưa có đánh giá.</p><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php if ($rel): ?>
<section class="block"><h2>Sản phẩm cùng nhóm</h2><div class="grid"><?php foreach ($rel as $r) echo productCard($r); ?></div></section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
