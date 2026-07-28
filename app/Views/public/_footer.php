</main>
<footer class="site-footer">
    <div class="site-footer__inner">
        <span>&copy; <?= date('Y') ?> Strängnäs Biredskap AB</span>
        <a href="/integritetspolicy">Integritetspolicy</a>
    </div>
</footer>
<?php if (isset($extraScripts)): ?>
    <?php foreach ($extraScripts as $src): ?>
        <script src="<?= Security::e($src) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>