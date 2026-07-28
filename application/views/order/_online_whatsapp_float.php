<?php
$waUrl = trim((string)($manual_whatsapp_url ?? ''));
if ($waUrl !== ''):
?>
<style>
  .nm-wa-float {
    position: fixed;
    right: 18px;
    bottom: 86px;
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
<?php endif; ?>
