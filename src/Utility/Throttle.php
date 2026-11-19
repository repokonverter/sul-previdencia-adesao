<?php

declare(strict_types=1);

namespace App\Utility;

use Cake\Cache\Cache;

/**
 * Contador de tentativas por chave, em janela fixa. Ao atingir o máximo, a
 * janela recomeça a partir dali: "5 erros bloqueiam por 15 minutos" conta
 * do 5º erro, não do 1º.
 *
 * Fica no cache em disco do container: zera a cada deploy, o que para o
 * porte do admin é aceitável.
 */
final class Throttle
{
    private const CONFIG = 'throttle';

    public static function tooMany(string $key, int $max): bool
    {
        return self::current($key)['count'] >= $max;
    }

    public static function hit(string $key, int $max, int $windowSeconds): void
    {
        $entry = self::current($key);
        $now = time();

        $entry['count']++;
        $entry['until'] = $entry['count'] >= $max || $entry['until'] === 0
            ? $now + $windowSeconds
            : $entry['until'];

        Cache::write(self::cacheKey($key), $entry, self::CONFIG);
    }

    public static function clear(string $key): void
    {
        Cache::delete(self::cacheKey($key), self::CONFIG);
    }

    /**
     * Chave estável para valores que não devem ir crus para o disco (e-mail,
     * IP) nem conter caracteres que o cache recusa.
     */
    public static function key(string $scope, string|int $value): string
    {
        return $scope . '_' . hash('sha256', strtolower(trim((string)$value)));
    }

    /**
     * @return array{count: int, until: int}
     */
    private static function current(string $key): array
    {
        $entry = Cache::read(self::cacheKey($key), self::CONFIG);

        if (!is_array($entry) || ($entry['until'] ?? 0) < time()) {
            return ['count' => 0, 'until' => 0];
        }

        return $entry;
    }

    private static function cacheKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
    }
}
