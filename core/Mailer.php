<?php
namespace Core;

use App\Models\AppSettings;

class Mailer
{
    public static function send(string $to, string $subject, string $body): array
    {
        $config = AppSettings::getEmailConfig();
        if (empty($config->smtp_host)) {
            return ['success' => false, 'error' => 'SMTP host is not configured.'];
        }

        $encryption = strtolower($config->smtp_encryption ?? '');
        $host = $config->smtp_host;
        $port = (int) $config->smtp_port ?: 587;
        $secure = ($encryption === 'ssl');
        if ($encryption === 'ssl' && $port === 587) {
            $port = 465;
        }
        $prefix = $secure ? 'ssl://' : '';
        $fp = @stream_socket_client(
            $prefix . $host . ':' . $port,
            $errno, $errstr, 10,
            STREAM_CLIENT_CONNECT,
            stream_context_create(['ssl' => ['verify_peer' => false]])
        );
        if (!$fp) {
            return ['success' => false, 'error' => "Connection failed: $errstr ($errno)"];
        }

        stream_set_timeout($fp, 10);
        $read = function () use ($fp) {
            $r = '';
            while ($line = fgets($fp, 515)) {
                $r .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $r;
        };
        $write = function ($cmd) use ($fp) {
            fwrite($fp, $cmd . "\r\n");
        };

        $read();
        $write("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        $read();
        if ($encryption === 'tls' && !$secure) {
            $write('STARTTLS');
            $read();
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            $read();
        }
        if (!empty($config->smtp_username)) {
            $write('AUTH LOGIN');
            $read();
            $write(base64_encode($config->smtp_username));
            $read();
            $write(base64_encode($config->smtp_password ?? ''));
            $resp = $read();
            if (strpos($resp, '235') === false && strpos($resp, 'Authentication successful') === false) {
                fclose($fp);
                return ['success' => false, 'error' => 'SMTP authentication failed.'];
            }
        }
        $from = $config->from_email ?: $config->smtp_username ?: 'noreply@localhost';
        $fromName = $config->from_name ?: 'PAPS';
        $write('MAIL FROM:<' . $from . '>');
        $read();
        $write('RCPT TO:<' . $to . '>');
        $read();
        $write('DATA');
        $read();
        $headers = "From: " . ($fromName ? "\"$fromName\" <$from>" : $from) . "\r\n";
        $headers .= "To: $to\r\nSubject: $subject\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n";
        $write($headers . $body . "\r\n.");
        $resp = $read();
        $write('QUIT');
        fclose($fp);
        if (strpos($resp, '250') !== false) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => trim($resp)];
    }
}
