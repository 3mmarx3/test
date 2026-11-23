<?php
if (!isset($showToast) || $showToast) {
    [$msg, $msgt] = pull_flash();
    if ($msg): ?>
<div class="toast <?= $msgt === 'err' ? 'err' : 'ok' ?>"><?= e($msg) ?></div>
<?php endif;
}
?>
<script src="<?= asset_url('js/app.js') ?>"></script>
</body>
</html>
