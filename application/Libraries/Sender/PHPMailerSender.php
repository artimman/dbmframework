<?php
/**
 * Library: Universal mail sender wrapper for PHPMailer.
 * A class intended for the DbM Framework and for use in any PHP application.
 *
 * @package Lib\Sender
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 *
 * Example usage:
 * ```php
 * $params = [
 *  'subject' => 'Ważna wiadomość',
 *  'sender_name' => 'Admin',
 *  'sender_email' => 'admin@example.com',
 *  'recipient_name' => 'User',
 *  'recipient_email' => 'user@example.com',
 *  'message_content' => 'template.html', // message template or text
 *  // not required
 *  'attachment_filesecret' => 'filename_1001.zip',
 *  'attachment_filename' => 'FileName.zip',
 *  'message_type' => 'null', // options: html, text, null (default)
 *  'recipients' => [
 *   ['email' => 'user1@example.com', 'name' => 'User One'],
 *   ['email' => 'user2@example.com', 'name' => 'User Two'],
 *  ],
 *  'cc' => [
 *   ['email' => 'ccuser@example.com', 'name' => 'CC User'],
 *  ],
 *  'bcc' => [
 *   ['email' => 'bccuser@example.com', 'name' => 'BCC User'],
 *  ],
 *  'the_key' => 'The key to your heart.', // option: you can add more parameters to display in the message {the_key}
 * ];
 *
 * $mailer = new PHPMailerSender();
 * $mailer->setSMTPAuth(); // not required (default true)
 * $mailer->sendMessage($params);
 * ```
 */

declare(strict_types=1);

namespace Lib\Sender;

use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception; // as PHPMailerException; // Not used
// use PHPMailer\PHPMailer\SMTP; // Not used
use Exception;

class PHPMailerSender
{
    private PHPMailer $mailer;
    private bool $authentication = true;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true); // Passing true enables exceptions
        $this->mailer->CharSet = "UTF-8"; // Set encoding
    }

    public function sendMessage(array $params): bool
    {
        $isSend = true;

        $subject = $params['subject'] ?? getenv('MAIL_FROM_NAME');
        $senderName = $params['sender_name'] ?? getenv('MAIL_FROM_NAME');
        $senderEmail = $params['sender_email'] ?? getenv('MAIL_FROM_EMAIL');
        $recipientName = $params['recipient_name'] ?? '';
        $recipientEmail = $params['recipient_email'] ?? '';
        $messageContent = $params['message_content'] ?? '';
        $attachmentFilesecret = $params['attachment_filesecret'] ?? '';
        $attachmentFilename = $params['attachment_filename'] ?? '';
        $messageType = $params['message_type'] ?? null;
        $recipients = $params['recipients'] ?? [];
        $ccRecipients = $params['cc'] ?? [];
        $bccRecipients = $params['bcc'] ?? [];

        try {
            // Ścieżki do szablonu wiadomości i załącznika
            $pathMessage = BASE_DIRECTORY . 'data' . DS . 'mailer' . DS . $messageContent;
            $pathAttachment = BASE_DIRECTORY . 'data' . DS . 'attachment' . DS . $attachmentFilesecret;

            // Załaduj treść wiadomości
            $content = file_exists($pathMessage) ? file_get_contents($pathMessage) : nl2br($messageContent);
            $content = $this->replaceContent($content, $params);

            // Konfiguracja SMTP
            $smtpIsTrue = filter_var(getenv('MAIL_SMTP'), FILTER_VALIDATE_BOOLEAN);

            if ($smtpIsTrue) {
                $this->configureSMTP();
            }

            // Ustawienia nadawcy
            $this->mailer->setFrom($senderEmail, $senderName);

            // Dodaj odbiorców
            if (!empty($recipients)) {
                $this->addRecipients($recipients, 'to');
                $this->addRecipients($ccRecipients, 'cc');
                $this->addRecipients($bccRecipients, 'bcc');
            } elseif (!empty($recipientEmail)) {
                $this->mailer->addAddress($recipientEmail, $recipientName);
            } else {
                throw new Exception("No message recipients were specified.");
            }

            // Ustawienie wiadomości
            $this->mailer->Subject = $subject;
            $this->setMessageFormat($content, $messageType);

            // Dodaj załącznik, jeśli istnieje
            if (!empty($attachmentFilename)) {
                if (is_file($pathAttachment)) {
                    $this->mailer->addAttachment($pathAttachment, $attachmentFilename);
                } else {
                    throw new Exception("Attachment not found: {$pathAttachment}");
                }
            }

            // Wyślij wiadomość
            if (!$this->mailer->send()) {
                $errorMessage = "[PHPMailer] " . $this->mailer->ErrorInfo;
            }
        } catch (Exception $exception) {
            $errorMessage = "[Exception] " . $exception->getMessage();
        }

        // Logowanie błędów
        if (isset($errorMessage)) {
            $isSend = false;
            $this->errorLogger($errorMessage);
        }

        return $isSend;
    }

    public function setSMTPAuth(bool $authentication): void
    {
        $this->authentication = $authentication;
    }

    private function configureSMTP(): void
    {
        if (getenv('MAIL_SMTP') === 'false') {
            return;
        }

        $this->mailer->isSMTP();
        $this->mailer->SMTPAuth = $this->authentication;

        $host = getenv('MAIL_HOST');
        $port = getenv('MAIL_PORT') ?: 1025;
        $secure = getenv('MAIL_SECURE');

        if (empty($host)) {
            throw new Exception("Konfiguracja SMTP jest niekompletna: MAIL_HOST jest wymagane.");
        }

        $this->mailer->Host = $host;
        $this->mailer->Port = $port;

        if (!empty($secure)) {
            $this->mailer->SMTPSecure = $secure;
        }

        if ($this->authentication && !empty(getenv('MAIL_USERNAME')) && !empty(getenv('MAIL_PASSWORD'))) {
            $this->mailer->Username = getenv('MAIL_USERNAME');
            $this->mailer->Password = getenv('MAIL_PASSWORD');
        } else {
            $this->mailer->SMTPAuth = false;
        }
    }

    private function setMessageFormat(string $content, ?string $type = null): void
    {
        if ($type === 'html') {
            $this->mailer->isHTML(true);
            $this->mailer->Body = $content;
        } elseif ($type === 'text') {
            $this->mailer->isHTML(false);
            $this->mailer->Body = strip_tags($content);
        } else {
            $this->mailer->MsgHTML($content);
        }
    }

    private function addRecipients(array $recipients, string $type): void
    {
        foreach ($recipients as $recipient) {
            $email = $recipient['email'] ?? null;
            $name = $recipient['name'] ?? '';

            if ($email) {
                switch ($type) {
                    case 'to':
                        $this->mailer->addAddress($email, $name);
                        break;
                    case 'cc':
                        $this->mailer->addCC($email, $name);
                        break;
                    case 'bcc':
                        $this->mailer->addBCC($email, $name);
                        break;
                }
            }
        }
    }

    private function replaceContent(string $content, array $replace = []): string
    {
        $keysToRemove = ["recipients", "attachment_filesecret", "message_type"];
        $replace = array_diff_key($replace, array_flip($keysToRemove));

        $placeholders = array_map(fn ($key) => '{' . $key . '}', array_keys($replace));

        return str_replace($placeholders, $replace, $content);
    }


    private function errorLogger($error): void
    {
        $logDir = BASE_DIRECTORY . 'var' . DS . 'log' . DS . 'mailer' . DS;

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logPath = $logDir . date('Ymd') . '_error.log';

        $logData = "TIME: " . date('Y-m-d H:i:s') . PHP_EOL;
        $logData .= "    REQUEST: " . json_encode($_REQUEST) . PHP_EOL;
        $logData .= "    ERROR: " . (is_array($error) ? json_encode($error) : $error) . PHP_EOL;

        file_put_contents($logPath, $logData, FILE_APPEND);
    }
}
