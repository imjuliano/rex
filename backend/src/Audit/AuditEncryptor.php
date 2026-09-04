<?php
declare(strict_types=1);

namespace App\Audit;

use App\Audit\Exception\AuditDecryptionFailedException;
use App\Audit\Exception\AuditEncryptionFailedException;
use App\Audit\Exception\AuditEncryptionKeyMissingException;
use App\Audit\Exception\AuditInvalidEncryptedDataException;

final class AuditEncryptor {
    private const CIPHER = 'AES-256-CBC';
    private string $key;

    public function __construct(string $key) {
        if ($key === '') {
            throw new AuditEncryptionKeyMissingException();
        }
        $this->key = hash('sha256', $key, true);
    }

    public function encrypt(?string $plain): ?string {
        if ($plain === null || $plain === '') {
            return null;
        }
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $cipher = openssl_encrypt($plain, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new AuditEncryptionFailedException();
        }
        return base64_encode($iv . $cipher);
    }

    public function decrypt(?string $encoded): ?string {
        if ($encoded === null || $encoded === '') {
            return null;
        }
        $raw = base64_decode($encoded, true);
        if ($raw === false) {
            throw new AuditInvalidEncryptedDataException();
        }
        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($raw, 0, $ivLen);
        $cipher = substr($raw, $ivLen);
        $plain = openssl_decrypt($cipher, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            throw new AuditDecryptionFailedException();
        }
        return $plain;
    }
}
