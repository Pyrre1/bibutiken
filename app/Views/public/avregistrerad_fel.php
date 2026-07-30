<?php require_once __DIR__ . '/_header.php'; ?>

<main class="main-content">
    <div class="avregistrerad-wrap">
        <h1>Något gick fel</h1>
        <p><?= htmlspecialchars($errorMessage ?? 'Ogiltig länk.') ?></p>
        <br>
        <p>Testa igen med länken i mejlet, eller kontakta oss på mejl så gör vi en manuell avregistrering.</p>
        <p><a href="/">Gå till startsidan</a></p>
    </div>
</main>

<?php require_once __DIR__ . '/_footer.php'; ?>