</main>
<footer class="site-foot">
  <div class="wrap foot-row">
    <div><strong><?= h(cfg('shop_name')) ?></strong><p>Quần áo dễ mặc, chất vải tốt, giá rõ ràng.</p></div>
    <div><strong>Liên hệ</strong><p><?= h(cfg('shop_address')) ?><br><?= h(cfg('shop_phone')) ?><br><?= h(cfg('shop_email')) ?></p></div>
    <div><strong>Mua hàng</strong><p><a href="<?= url('products.php') ?>">Tất cả sản phẩm</a><br><a href="<?= url('cart.php') ?>">Giỏ hàng</a><br><a href="<?= url('account.php') ?>">Đơn hàng của tôi</a></p></div>
  </div>
  <div class="wrap copy">© <?= date('Y') ?> <?= h(cfg('shop_name')) ?></div>
</footer>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
