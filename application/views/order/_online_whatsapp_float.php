<?php
$waUrl = trim((string)($manual_whatsapp_url ?? ''));
if ($waUrl !== ''):
?>
<style>
  .nm-wa-float {
    position: fixed;
    right: 18px;
    bottom: 154px;
    z-index: 1200;
    width: 54px;
    height: 54px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #20b15a;
    color: #fff;
    box-shadow: 0 14px 30px rgba(20, 120, 62, .28);
    text-decoration: none;
    font-weight: 900;
    letter-spacing: 0;
    touch-action: none;
    user-select: none;
  }
  .nm-wa-float span {
    width: 28px;
    height: 28px;
    display: inline-flex;
  }
  .nm-wa-float svg { width: 28px; height: 28px; display: block; fill: currentColor; }
</style>
<a class="nm-wa-float" href="<?= html_escape($waUrl) ?>" target="_blank" rel="noopener" title="Chat admin" aria-label="Chat admin WhatsApp">
  <span aria-hidden="true">
    <svg viewBox="0 0 32 32" focusable="false">
      <path d="M16.04 4C9.4 4 4 9.35 4 15.93c0 2.1.56 4.16 1.62 5.96L4 28l6.28-1.64a12.16 12.16 0 0 0 5.76 1.46C22.68 27.82 28 22.5 28 15.93 28 9.35 22.68 4 16.04 4Zm0 21.8c-1.78 0-3.52-.48-5.05-1.39l-.36-.21-3.72.98.99-3.6-.24-.37a9.74 9.74 0 0 1-1.56-5.28c0-5.45 4.46-9.9 9.94-9.9 5.47 0 9.93 4.45 9.93 9.9 0 5.44-4.46 9.87-9.93 9.87Zm5.45-7.38c-.3-.15-1.77-.87-2.04-.97-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.08-.3-.15-1.25-.46-2.38-1.46a8.84 8.84 0 0 1-1.64-2.03c-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.08-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.08-.79.37-.27.3-1.04 1.01-1.04 2.47 0 1.46 1.07 2.87 1.22 3.07.15.2 2.1 3.2 5.1 4.48.71.31 1.27.49 1.7.62.72.23 1.37.2 1.88.12.57-.08 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.13-.27-.2-.57-.35Z"/>
    </svg>
  </span>
</a>
<script>
(function () {
  const btn = document.querySelector('.nm-wa-float');
  if (!btn) return;
  const key = 'nm_wa_float_pos_v1';
  let dragging = false;
  let moved = false;
  let startX = 0;
  let startY = 0;
  let startLeft = 0;
  let startTop = 0;

  function viewport() {
    const vv = window.visualViewport;
    return {
      width: vv && vv.width ? vv.width : window.innerWidth,
      height: vv && vv.height ? vv.height : window.innerHeight,
      offsetLeft: vv && vv.offsetLeft ? vv.offsetLeft : 0,
      offsetTop: vv && vv.offsetTop ? vv.offsetTop : 0
    };
  }
  function clamp(left, top) {
    const vp = viewport();
    const size = 54;
    const margin = 10;
    return {
      left: Math.max(vp.offsetLeft + margin, Math.min(left, vp.offsetLeft + vp.width - size - margin)),
      top: Math.max(vp.offsetTop + margin, Math.min(top, vp.offsetTop + vp.height - size - margin))
    };
  }
  function apply(left, top) {
    const p = clamp(left, top);
    btn.style.left = p.left + 'px';
    btn.style.top = p.top + 'px';
    btn.style.right = 'auto';
    btn.style.bottom = 'auto';
  }
  try {
    const saved = JSON.parse(localStorage.getItem(key) || 'null');
    if (saved && Number.isFinite(Number(saved.left)) && Number.isFinite(Number(saved.top))) {
      apply(Number(saved.left), Number(saved.top));
    }
  } catch (_) {}

  btn.addEventListener('pointerdown', function (event) {
    dragging = true;
    moved = false;
    const rect = btn.getBoundingClientRect();
    startX = event.clientX;
    startY = event.clientY;
    startLeft = rect.left;
    startTop = rect.top;
    btn.setPointerCapture && btn.setPointerCapture(event.pointerId);
  });
  btn.addEventListener('pointermove', function (event) {
    if (!dragging) return;
    const dx = event.clientX - startX;
    const dy = event.clientY - startY;
    if (Math.abs(dx) + Math.abs(dy) > 4) moved = true;
    apply(startLeft + dx, startTop + dy);
  });
  btn.addEventListener('pointerup', function () {
    if (!dragging) return;
    dragging = false;
    const rect = btn.getBoundingClientRect();
    try { localStorage.setItem(key, JSON.stringify({ left: rect.left, top: rect.top })); } catch (_) {}
  });
  btn.addEventListener('click', function (event) {
    if (!moved) return;
    event.preventDefault();
    event.stopPropagation();
    setTimeout(function () { moved = false; }, 50);
  });
  window.addEventListener('resize', function () {
    const rect = btn.getBoundingClientRect();
    apply(rect.left, rect.top);
  });
})();
</script>
<?php endif; ?>
