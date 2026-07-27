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
    font-size: 15px;
    line-height: 1;
  }
</style>
<a class="nm-wa-float" href="<?= html_escape($waUrl) ?>" target="_blank" rel="noopener" title="Chat admin">
  <span>WA</span>
</a>
<?php endif; ?>
