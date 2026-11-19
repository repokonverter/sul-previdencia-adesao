<?php

declare(strict_types=1);

namespace App\Utility;

use Cake\Utility\Text;

/**
 * Regras de senha do admin, seguindo o NIST SP 800-63B (rev. 4): o que pesa
 * é o comprimento, então não há exigência de maiúscula, número ou símbolo
 * (o NIST proíbe essas regras de composição). Mínimo de 15 porque a senha é
 * o único fator de login. Em vez da lista de senhas vazadas, só as palavras
 * do contexto: o nome do serviço e o nome/e-mail do próprio usuário.
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 15;
    public const MAX_LENGTH = 64;

    /**
     * Já normalizadas (ver normalize()).
     */
    public const SERVICE_WORDS = ['sulprev', 'previdencia'];

    /**
     * Pedaços de nome/e-mail mais curtos que isso ("da", "jo") barrariam
     * senhas demais sem proteger nada.
     */
    private const MIN_PERSONAL_WORD_LENGTH = 4;

    /**
     * @return list<string> Mensagens, vazia quando a senha é aceita.
     */
    public static function violations(string $password, ?string $name = null, ?string $email = null): array
    {
        $errors = [];
        $length = mb_strlen($password);

        if ($length < self::MIN_LENGTH) {
            $errors[] = __('A senha deve ter pelo menos {0} caracteres.', self::MIN_LENGTH);
        }

        if ($length > self::MAX_LENGTH) {
            $errors[] = __('A senha deve ter no máximo {0} caracteres.', self::MAX_LENGTH);
        }

        $normalized = self::normalize($password);

        if (self::containsAny($normalized, self::SERVICE_WORDS)) {
            $errors[] = __('A senha não pode conter o nome da Sul Previdência.');
        }

        if (self::containsAny($normalized, self::personalWords($name, $email))) {
            $errors[] = __('A senha não pode conter seu nome ou e-mail.');
        }

        return $errors;
    }

    /**
     * Partes do nome e do e-mail (antes do @) que a senha não pode conter.
     * Vão também para o checklist da tela, que avisa enquanto a pessoa digita.
     *
     * @return list<string>
     */
    public static function personalWords(?string $name, ?string $email): array
    {
        $local = $email !== null ? strstr($email, '@', true) ?: $email : '';
        $parts = preg_split('/[^\p{L}\p{N}]+/u', ($name ?? '') . ' ' . $local) ?: [];
        $words = [];

        foreach ($parts as $part) {
            $word = self::normalize($part);

            if (strlen($word) >= self::MIN_PERSONAL_WORD_LENGTH) {
                $words[$word] = true;
            }
        }

        return array_keys($words);
    }

    /**
     * Minúsculas, sem acento e só letras e números: "Sul Prev", "SULPREV" e
     * "sul-prev" caem todos em "sulprev".
     */
    public static function normalize(string $value): string
    {
        $ascii = Text::transliterate($value);

        return (string)preg_replace('/[^a-z0-9]/', '', strtolower($ascii));
    }

    /**
     * @param list<string> $words
     */
    private static function containsAny(string $haystack, array $words): bool
    {
        foreach ($words as $word) {
            if ($word !== '' && str_contains($haystack, $word)) {
                return true;
            }
        }

        return false;
    }
}
