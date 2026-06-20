    </div><!-- .page -->
  </div><!-- .view -->
</div><!-- #app -->

<!-- Framework7 Core JS -->
<script src="https://cdn.jsdelivr.net/npm/framework7@8/framework7-bundle.min.js"></script>
<script>
  const app = new Framework7({
    el: '#app',
    theme: 'md',
    routes: [], // kita tidak pakai router SPA
  });

  // WAJIB create view
  app.views.create('.view-main', { stackPages: false });

  // Pindahkan tabbar ke langsung di bawah body agar position:fixed
  // tidak terpengaruh transform/overflow milik .view/.page Framework7
  (function(){
    var tb = document.querySelector('.nm-tabbar');
    if (tb) document.body.appendChild(tb);
  })();

  // BIAR LINK CI3 dianggap normal (full reload)
  document.addEventListener('click', function (e) {
    const a = e.target.closest('a');
    if (!a) return;

    // Skip link yang sudah punya handler modal sendiri
    if (a.dataset.modal || a.classList.contains('redeem-trigger')) return;

    const href = a.getAttribute('href');
    if (!href) return;

    // kalau link internal (bukan #, bukan javascript:)
    if (href.startsWith('http') || href.startsWith('/') || href.includes('<?= base_url() ?>')) {
      // paksa normal navigation (CI3)
      window.location.href = href;
      e.preventDefault();
    }
  }, true);
</script>

</body>
</html>
