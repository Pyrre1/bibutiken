<div>
    <h1>Vinterfoder</h1>
    <p>Denna tiden på året tar vi inte emot beställningar av vinterfoder. Välkommen åter när förbeställningar öppnar igen!</p>

    <?php if ($reminderMessage): ?>
        <p class="reminder-success"><?= Security::e($reminderMessage) ?></p>
    <?php else: ?>
        <p>Har du beställt via hemsidan tidigare år kommer du automatiskt få en påminnelse när nästa säsongs vinterfoder går att beställa.</p>
        <p class="reminder-note">Ny kund från 2026? Har du inte beställt via den nya hemsidan tidigare – skriv upp dig nedan för att få ett mejl när beställningen öppnar.</p>
        <div class="reminder-toggle-wrap">
            <button type="button" class="btn btn--secondary" id="show-reminder-form">Meddela mig när beställningen öppnar</button>
        </div>

        <form class="reminder-form" method="post" action="/vinterfoder" id="reminder-form" hidden>
            <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
            <?php if ($reminderError): ?>
                <p class="reminder-error"><?= Security::e($reminderError) ?></p>
            <?php endif; ?>
            <div class="reminder-form__row">
                <label for="reminder_name">Namn</label>
                <input type="text" id="reminder_name" name="reminder_name"
                    value="<?= Security::e($_POST['reminder_name'] ?? '') ?>"
                    required autocomplete="name">
            </div>
            <div class="reminder-form__row">
                <label for="reminder_email">E-postadress</label>
                <input type="email" id="reminder_email" name="reminder_email"
                    value="<?= Security::e($_POST['reminder_email'] ?? '') ?>"
                    required autocomplete="email">
            </div>
            <div class="reminder-form__actions">
                <button type="submit" name="reminder_submit" class="btn btn--primary">Meddela mig</button>
            </div>
        </form>
    <?php endif; ?>
</div>