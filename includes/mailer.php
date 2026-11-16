<?php
declare(strict_types=1);

/**
 * Sends email via configured driver ('php_mail' or 'smtp').
 * Returns true ONLY if the delivery operation actually succeeded.
 */
function send_mail(string $toEmail, string $subject, string $htmlBody): array
{
    $toEmail = trim($toEmail);
    if (!Validator::isEmail($toEmail)) {
        return ['success' => false, 'error' => 'Invalid recipient email address.'];
    }

    $driver = (string)get_setting('mail_driver', 'php_mail');
    $fromEmail = (string)get_setting('mail_from_email', 'noreply@example.com');
    $fromName = (string)get_setting('mail_from_name', APP_DEFAULT_NAME);

    if ($driver === 'smtp') {
        $host = trim((string)get_setting('smtp_host', ''));
        $port = (int)get_setting('smtp_port', '587');
        $user = trim((string)get_setting('smtp_username', ''));
        $encPass = (string)get_setting('smtp_password_encrypted', '');
        $pass = $encPass !== '' ? crypto_decrypt($encPass) : '';
        $encryption = strtolower((string)get_setting('smtp_encryption', 'tls'));

        if ($host === '' || $port <= 0) {
            Logger::file('mail', 'ERROR', 'SMTP driver selected but host/port not configured');
            return ['success' => false, 'error' => 'SMTP server is not configured.'];
        }

        return smtp_send_socket($host, $port, $user, $pass, $encryption, $fromEmail, $fromName, $toEmail, $subject, $htmlBody);
    }

    // Standard PHP mail()
    $encodedFrom = sprintf('=?UTF-8?B?%s?= <%s>', base64_encode($fromName), $fromEmail);
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $encodedFrom,
        'Reply-To: ' . $fromEmail,
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    $ok = @mail($toEmail, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, implode("\r\n", $headers));
    if (!$ok) {
        Logger::file('mail', 'ERROR', 'PHP mail() returned false', ['to' => $toEmail, 'subject' => $subject]);
        return ['success' => false, 'error' => 'Server mail() could not deliver the message. Configure SMTP in Admin > Settings.'];
    }
    return ['success' => true];
}

function smtp_send_socket(
    string $host,
    int $port,
    string $username,
    string $password,
    string $encryption,
    string $fromEmail,
    string $fromName,
    string $toEmail,
    string $subject,
    string $htmlBody
): array {
    $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $fp = @stream_socket_client($remote, $errno, $errstr, 12);
    if (!$fp) {
        Logger::file('mail', 'ERROR', "SMTP connect failed: {$errstr} ({$errno})");
        return ['success' => false, 'error' => "SMTP connection failed: {$errstr}"];
    }
    stream_set_timeout($fp, 12);

    $read = static function () use ($fp): string {
        $resp = '';
        while ($line = fgets($fp, 515)) {
            $resp .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $resp;
    };
    $cmd = static function (string $command, int $expectedCode) use ($fp, $read): bool {
        fwrite($fp, $command . "\r\n");
        $resp = $read();
        return (int)substr($resp, 0, 3) === $expectedCode;
    };

    try {
        if ((int)substr($read(), 0, 3) !== 220) {
            throw new RuntimeException('SMTP greeting rejected.');
        }
        $ehloHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if (!$cmd("EHLO {$ehloHost}", 250)) {
            throw new RuntimeException('EHLO rejected.');
        }
        if ($encryption === 'tls') {
            if (!$cmd('STARTTLS', 220)) {
                throw new RuntimeException('STARTTLS failed.');
            }
            if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('TLS handshake failed.');
            }
            if (!$cmd("EHLO {$ehloHost}", 250)) {
                throw new RuntimeException('Post-TLS EHLO failed.');
            }
        }
        if ($username !== '') {
            if (!$cmd('AUTH LOGIN', 334)) {
                throw new RuntimeException('AUTH LOGIN rejected.');
            }
            if (!$cmd(base64_encode($username), 334)) {
                throw new RuntimeException('SMTP username rejected.');
            }
            if (!$cmd(base64_encode($password), 235)) {
                throw new RuntimeException('SMTP password rejected.');
            }
        }
        if (!$cmd("MAIL FROM:<{$fromEmail}>", 250)) {
            throw new RuntimeException('MAIL FROM rejected.');
        }
        if (!$cmd("RCPT TO:<{$toEmail}>", 250)) {
            throw new RuntimeException('RCPT TO rejected.');
        }
        if (!$cmd('DATA', 354)) {
            throw new RuntimeException('DATA command rejected.');
        }

        $safeFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $safeSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $msg = "From: {$safeFromName} <{$fromEmail}>\r\n"
            . "To: <{$toEmail}>\r\n"
            . "Subject: {$safeSubject}\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($htmlBody)) . "\r\n.";

        if (!$cmd($msg, 250)) {
            throw new RuntimeException('Message body rejected by SMTP server.');
        }
        fwrite($fp, "QUIT\r\n");
        fclose($fp);
        return ['success' => true];
    } catch (Throwable $e) {
        @fclose($fp);
        Logger::file('mail', 'ERROR', 'SMTP error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
