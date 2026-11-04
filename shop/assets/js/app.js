// Tự ẩn thông báo sau vài giây
document.querySelectorAll('.flash.ok').forEach(function (el) {
  setTimeout(function () { el.style.transition = 'opacity .5s'; el.style.opacity = '0'; setTimeout(function () { el.remove(); }, 500); }, 4500);
});
