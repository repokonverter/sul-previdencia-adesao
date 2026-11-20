<?php

declare(strict_types=1);

namespace App\Services;

use Cake\Routing\Router;

/**
 * Um plano de benefícios (PlenoPrev, MaisPremium): o que muda de um para o
 * outro no mesmo formulário de adesão.
 *
 * Não confundir com AdhesionPlan, que é o plano de contribuição escolhido
 * numa adesão (valores e riscos). Este é o produto ao qual a adesão
 * pertence, definido em config/plans.php e escolhido pelo subdomínio.
 */
final class BenefitPlan
{
    /**
     * @param array{number: string, name: string} $institution Instituidor padrão.
     * @param array<string, string> $insurer Seguradora dos pecúlios e os códigos dela na proposta.
     * @param array{name: string, code: string} $broker Corretora de seguros.
     * @param array{provider: string, config: string} $pix Banco que recebe e onde estão as credenciais.
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $baseUrl,
        public readonly string $cnpj,
        public readonly string $cnpb,
        public readonly string $loading,
        public readonly string $slogan,
        public readonly string $proposalPrefix,
        public readonly string $txidPrefix,
        public readonly array $institution,
        public readonly array $insurer,
        public readonly array $broker,
        public readonly array $pix,
    ) {
    }

    public static function fromConfig(string $slug, array $config): self
    {
        return new self(
            slug: $slug,
            name: $config['name'],
            baseUrl: rtrim($config['baseUrl'], '/'),
            cnpj: $config['cnpj'],
            cnpb: $config['cnpb'],
            loading: $config['loading'],
            slogan: $config['slogan'],
            proposalPrefix: $config['proposalPrefix'],
            txidPrefix: $config['txidPrefix'],
            institution: $config['institution'],
            insurer: $config['insurer'],
            broker: $config['broker'],
            pix: $config['pix'],
        );
    }

    /**
     * O host que identifica o plano, sem porta e sem "www.".
     */
    public function host(): string
    {
        return strtolower((string)parse_url($this->baseUrl, PHP_URL_HOST));
    }

    /**
     * URL absoluta no host deste plano, qualquer que seja o host da
     * requisição atual -- ou mesmo sem requisição (webhook, comando).
     *
     * @param array|string $route O mesmo que Router::url() aceita.
     */
    public function url(array|string $route): string
    {
        return $this->baseUrl . Router::url($route);
    }
}
