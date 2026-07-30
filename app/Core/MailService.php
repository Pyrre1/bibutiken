<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

class MailService
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/config.php';
    }

    /**
     * Send an email.
     *
     * @param string      $toEmail
     * @param string      $toName
     * @param string      $subject
     * @param string      $bodyHtml
     * @param string      $bodyPlain
     * @param array       $vars         Substitution vars: ['namn' => '...', 'vara' => '...', 'pris' => '...']
     * @param string|null $role         Role this mail targets (for unsubscribe link + log)
     * @param int|null    $customerId
     * @param int|null    $orderId
     * @return true
     * @throws RuntimeException on failure
     */
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $bodyHtml,
        string $bodyPlain,
        array  $vars = [],
        ?string $role = null,
        ?int $customerId = null,
        ?int $orderId = null
    ): true {
        // 1. Substitute placeholders in subject and body
        $subject    = $this->substitute($subject,   $vars);
        $bodyHtml   = $this->substitute($bodyHtml,  $vars);
        $bodyPlain  = $this->substitute($bodyPlain, $vars);

        // 2. Append unsubscribe footer
        if ($role !== null && $customerId !== null) {
            $footerHtml  = $this->buildFooterHtml($customerId, $role);
            $footerPlain = $this->buildFooterPlain($customerId, $role);
            $bodyHtml   .= $footerHtml;
            $bodyPlain  .= $footerPlain;
        }

        // 3. Send via PHPMailer
        $mail = new PHPMailer(true);

        try {
            $cfg = $this->config['mail'];

            $mail->isSMTP();
            $mail->Host       = $cfg['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $cfg['smtp_user'];
            $mail->Password   = $cfg['smtp_pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $cfg['smtp_port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($cfg['from_email'], $cfg['from_name']);
            $mail->addAddress($toEmail, $toName);
            $mail->addReplyTo($cfg['from_email'], $cfg['from_name']);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHtml;
            $mail->AltBody = $bodyPlain;

            $mail->send();

            $this->log($toEmail, $subject, $role, $customerId, $orderId, true, null);

            return true;

        } catch (MailerException $e) {
            $error = $mail->ErrorInfo;
            $this->log($toEmail, $subject, $role, $customerId, $orderId, false, $error);
            throw new RuntimeException('Mailfel: ' . $error);
        }
    }

    // ─────────────────────────────────────────────
    // Placeholder substitution
    // ─────────────────────────────────────────────

    private function substitute(string $text, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $text = str_replace('{' . $key . '}', (string) $value, $text);
        }
        return $text;
    }

    // ─────────────────────────────────────────────
    // Unsubscribe footer
    // ─────────────────────────────────────────────

    private function unsubscribeUrl(int $customerId, string $role): string
    {
        $token = hash_hmac('sha256', $customerId . $role, $this->config['mail']['secret_key']);
        $base  = rtrim(getenv('APP_URL') ?: 'https://strangnasbirodskap.se', '/');
        return $base . '/avregistrera?id=' . $customerId . '&roll=' . urlencode($role) . '&token=' . $token;
    }

    private function buildFooterPlain(int $customerId, string $role): string
    {
        $url = $this->unsubscribeUrl($customerId, $role);
        return "\n\n---\n"
            . "Du får det här mejlet för att du har gjort en föranmälan hos Strängnäs Biredskap.\n"
            . "Om du klickar på länken nedan tas du bort från den här typen av mejl.\n"
            . "OBS: Om du har en pågående beställning som inte hämtats kommer du inte längre\n"
            . "få påminnelser om den.\n\n"
            . "Avregistrera mig från dessa mejl:\n"
            . $url . "\n";
    }

    private function buildFooterHtml(int $customerId, string $role): string
    {
        $url = htmlspecialchars($this->unsubscribeUrl($customerId, $role));
        return '<br><br><hr style="border:none;border-top:1px solid #ccc;margin:24px 0">'
            . '<p style="font-size:13px;color:#666;">'
            . 'Du får det här mejlet för att du har gjort en föranmälan hos Strängnäs Biredskap.<br>'
            . 'Om du klickar på länken nedan tas du bort från den här typen av mejl.<br>'
            . '<strong>OBS:</strong> Om du har en pågående beställning som inte hämtats kommer du inte längre '
            . 'få påminnelser om den.'
            . '</p>'
            . '<p><a href="' . $url . '" style="color:#666;font-size:13px;">Avregistrera mig från dessa mejl</a></p>';
    }

    // ─────────────────────────────────────────────
    // Mail log
    // ─────────────────────────────────────────────

    private function log(
        string  $recipient,
        string  $subject,
        ?string $role,
        ?int    $customerId,
        ?int    $orderId,
        bool    $success,
        ?string $errorMsg
    ): void {
        try {
            $db  = Database::getConnection();
            $sql = 'INSERT INTO mail_log
                        (recipient, subject, role, customer_id, order_id, success, error_msg)
                    VALUES
                        (:recipient, :subject, :role, :customer_id, :order_id, :success, :error_msg)';

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':recipient'   => $recipient,
                ':subject'     => $subject,
                ':role'        => $role,
                ':customer_id' => $customerId,
                ':order_id'    => $orderId,
                ':success'     => $success ? 1 : 0,
                ':error_msg'   => $errorMsg,
            ]);
        } catch (PDOException $e) {
            // Log failure must never crash the app — silently ignore
            error_log('MailService::log failed: ' . $e->getMessage());
        }
    }
}