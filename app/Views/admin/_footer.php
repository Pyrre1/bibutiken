</main>
<script src="/assets/js/admin-common.js"></script>
<?php if (isset($extraScripts)): ?>
    <?php foreach ($extraScripts as $src): ?>
        <script src="<?= Security::e($src) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>