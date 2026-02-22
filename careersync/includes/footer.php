<?php // includes/footer.php ?>
<script>
  window.CSRF_TOKEN = '<?= csrfToken() ?>';
</script>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
<?php if (!empty($extraJs)): ?>
  <?= $extraJs ?>
<?php endif; ?>
</body>
</html>
