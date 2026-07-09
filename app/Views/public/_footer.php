</main>
<?php if (isset($extraScripts)): ?>
    <?php foreach ($extraScripts as $src): ?>
        <script src="<?= Security::e($src) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>