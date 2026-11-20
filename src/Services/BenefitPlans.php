<?php

declare(strict_types=1);

namespace App\Services;

use Cake\Core\Configure;
use InvalidArgumentException;

/**
 * Os planos de config/plans.php. Único caminho de leitura dessa
 * configuração.
 */
final class BenefitPlans
{
    /**
     * @return array<string, \App\Services\BenefitPlan> Na ordem do arquivo, o principal primeiro.
     */
    public static function all(): array
    {
        $plans = [];

        foreach ((array)Configure::read('BenefitPlans') as $slug => $config) {
            $plans[$slug] = BenefitPlan::fromConfig($slug, $config);
        }

        return $plans;
    }

    /**
     * @throws \InvalidArgumentException Plano que não está na configuração:
     *   um slug gravado no banco sem plano correspondente é erro de deploy,
     *   e cair num plano qualquer imprimiria o CNPJ errado na proposta.
     */
    public static function get(string $slug): BenefitPlan
    {
        $config = Configure::read('BenefitPlans.' . $slug);

        if (!is_array($config)) {
            throw new InvalidArgumentException("Plano de benefícios desconhecido: \"$slug\".");
        }

        return BenefitPlan::fromConfig($slug, $config);
    }

    public static function exists(?string $slug): bool
    {
        return $slug !== null && $slug !== '' && is_array(Configure::read('BenefitPlans.' . $slug));
    }

    /**
     * O plano principal: o primeiro da configuração.
     */
    public static function primary(): BenefitPlan
    {
        $plans = self::all();

        return reset($plans);
    }

    /**
     * O plano servido num host, aceitando "www." na frente. Fora do mapa
     * vale o plano de DEFAULT_PLAN, que só existe em desenvolvimento e nos
     * testes; em produção, host desconhecido não tem plano.
     */
    public static function forHost(string $host): ?BenefitPlan
    {
        $host = self::withoutWww(strtolower(explode(':', $host)[0]));

        foreach (self::all() as $plan) {
            if ($plan->host() === $host) {
                return $plan;
            }
        }

        $fallback = Configure::read('App.defaultBenefitPlan');

        return self::exists($fallback) ? self::get($fallback) : null;
    }

    public static function withoutWww(string $host): string
    {
        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }

    /**
     * Para selects: slug => nome.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_map(fn (BenefitPlan $plan): string => $plan->name, self::all());
    }
}
