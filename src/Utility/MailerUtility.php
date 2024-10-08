<?php
/*
 * DbM Framework
 * All code copyright Design by Malina
 * DbM: www.dbm.org.pl
 */

declare(strict_types=1);

namespace App\Utility;

use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception; // as PHPMailerException; // Not used
// use PHPMailer\PHPMailer\SMTP; // Not used
use Exception;

class MailerUtility
{
    private bool $authentication = true;

    public function sendMessage(array $params): bool
    {
        // Variables
        $isSend = true;

        $subject = !empty($params['subject']) ? $params['subject'] : getenv('MAIL_FROM_NAME');
        $senderName = !empty($params['sender_name']) ? $params['sender_name'] : getenv('MAIL_FROM_NAME');
        $senderEmail = !empty($params['sender_email']) ? $params['sender_email'] : getenv('MAIL_FROM_EMAIL');
        $recipientName = $params['recipient_name'] ?? null;
        $recipientEmail = $params['recipient_email'] ?? null;
        $messageTemplate = $params['message_template'] ?? null;
        $attachmentFilename = $params['attachment_filename'] ?? null;
        $attachmentSent = $params['attachment_sent'] ?? null;

        try {
            // Path to template message and filename attachment
            $pathMessage = BASE_DIRECTORY . 'data' . DS . 'mailer' . DS . $messageTemplate;
            $pathAttachment = BASE_DIRECTORY . 'data' . DS . 'attachment' . DS . $attachmentFilename;

            // Message content, with $this->replaceContent()
            (file_exists($pathMessage))
                ? $content = file_get_contents($pathMessage) : $content = nl2br($messageTemplate);

            $content = $this->replaceContent($content, $params);

            // PHPMailer
            $mail = new PHPMailer(); // Passing true enables exceptions
            $mail->CharSet = "UTF-8"; // set encoding

            // PHPMailer optional SMTP
            $smtpIsTrue = filter_var(getenv('MAIL_SMTP'), FILTER_VALIDATE_BOOLEAN);

            if (($smtpIsTrue === true) && ($senderEmail == getenv('MAIL_FROM_NAME'))) {
                $mail->IsSMTP(); // telling the class to use SMTP
                $mail->SMTPAuth = $this->authentication; // enable SMTP authentication
                $mail->Host = getenv('MAIL_HOST'); // SMTP server
                $mail->Username = getenv('MAIL_USERNAME'); // SMTP username
                $mail->Password = getenv('MAIL_PASSWORD'); // SMTP password
                $mail->SMTPSecure = getenv('MAIL_SECURE'); // enable 'tls' (PHPMailer::ENCRYPTION_STARTTLS) or 'ssl' encryption
                $mail->Port = getenv('MAIL_PORT'); // TCP port to connect
            }

            // PHPMailer c.d.
            $mail->SetFrom($senderEmail, $senderName);
            $mail->AddAddress($recipientEmail, $recipientName); // optional Name
            $mail->Subject = $subject;
            //$mail->IsHTML(true); // option for Body with HTML
            //$mail->Body = "$content"; // option
            $mail->MsgHTML($content);

            // Add attachment
            if (is_file($pathAttachment)) {
                $mail->addAttachment($pathAttachment, $attachmentSent);
            }

            // Send message
            if (!$mail->send()) {
                $errorMessage = "[PHPMailer] " . $mail->ErrorInfo;
            }
        } catch (Exception $exception) {
            $errorMessage = "[Exception] " . $exception->getMessage();
        }

        ### Error logger
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

    private function replaceContent(string $content, array $replace = []): string
    {
        $string = [];
        $arrayKeys = array_keys($replace);

        foreach ($arrayKeys as $item) {
            $string[] = '{' . $item . '}';
        }

        return str_replace($string, $replace, $content);
    }

    private function errorLogger($error): void
    {
        $dir = BASE_DIRECTORY . 'var' . DS . 'log' . DS . 'mailer' . DS;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (is_array($error)) {
            $error = json_encode($error);
        }

        $path = $dir . date('Ymd') . '_error.log';

        $data = "TIME: " . date('Y-m-d H:i:s');
        $data .= "\r\n    REQUEST: " . json_encode($_REQUEST);
        $data .= "\r\n    ERROR: " . $error . "\r\n";

        $file = fopen($path, 'a');
        fwrite($file, $data);
        fclose($file);
    }
}
