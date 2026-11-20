<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\BenefitPlans;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Descobre o plano de benefícios pelo host e o deixa no atributo
 * `benefitPlan` da requisição (ver AppController::benefitPlan()).
 *
 * Roda depois do RoutingMiddleware porque a regra de host desconhecido
 * depende da rota: o admin, o healthcheck e os webhooks não pertencem a
 * plano nenhum e respondem em qualquer host.
 */
class BenefitPlanMiddleware implements MiddlewareInterface
{
    public const ATTRIBUTE = 'benefitPlan';

    /**
     * Controllers públicos que não dependem de plano.
     */
    private const PLANLESS_CONTROLLERS = ['Up', 'Webhooks', 'Error'];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $host = strtolower($uri->getHost());
        $plan = BenefitPlans::forHost($host);

        // Monta a partir da baseUrl, e não trocando o host da URI: atrás do
        // proxy do Kamal a requisição chega em http, e redirecionar para
        // http custaria um segundo redirect.
        if ($plan !== null && $host !== BenefitPlans::withoutWww($host) && $plan->host() === BenefitPlans::withoutWww($host)) {
            $query = $uri->getQuery();

            return (new Response())
                ->withStatus(301)
                ->withLocation($plan->baseUrl . $uri->getPath() . ($query === '' ? '' : '?' . $query));
        }

        if ($plan === null) {
            if (!$this->isPlanless($request)) {
                throw new NotFoundException();
            }

            return $handler->handle($request);
        }

        // Só quando o host é de fato o do plano: no fallback de
        // desenvolvimento (localhost) o host da requisição é o que funciona.
        if ($plan->host() === $host) {
            Router::fullBaseUrl($plan->baseUrl);
        }

        return $handler->handle($request->withAttribute(self::ATTRIBUTE, $plan));
    }

    private function isPlanless(ServerRequestInterface $request): bool
    {
        $params = (array)$request->getAttribute('params', []);

        return !empty($params['prefix'])
            || in_array($params['controller'] ?? null, self::PLANLESS_CONTROLLERS, true);
    }
}
