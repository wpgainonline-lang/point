(function () {
  var t = document.querySelector('.nav-toggle'), n = document.getElementById('main-nav');
  if (t && n) t.addEventListener('click', function () { n.classList.toggle('open'); });

  document.querySelectorAll('[data-mark-all]').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('input[type=radio][value="' + b.getAttribute('data-mark-all') + '"]').forEach(function (r) { r.checked = true; });
    });
  });
})();
