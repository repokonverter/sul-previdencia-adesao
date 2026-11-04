<?php

declare(strict_types=1);

namespace App\View\Helper;

use App\Services\PlanProjection;
use Cake\View\Helper;

/**
 * O gráfico da projeção do plano em SVG, para o PDF.
 *
 * O dompdf não executa JavaScript, então o Chart.js da aba Plano não chega ao
 * PDF. Este é o mesmo gráfico (mesma série, mesmas cores) desenhado no
 * servidor, com o que o php-svg-lib do dompdf sabe renderizar: linhas,
 * caminhos, círculos e texto simples. Legenda e títulos ficam no HTML ao
 * redor, onde a fonte é a do documento.
 */
class PlanChartHelper extends Helper
{
    public const PURE_COLOR = '#1f5f99';
    public const COMPOUNDED_COLOR = '#e8711a';

    private const GRID_COLOR = '#e5e8ee';
    private const AXIS_TEXT_COLOR = '#6b7280';
    private const MAX_X_LABELS = 12;

    public function svg(PlanProjection $projection, int $width = 680, int $height = 270): string
    {
        $series = $projection->series();

        if ($series['ages'] === []) {
            return '';
        }

        $plot = ['left' => 70, 'right' => $width - 70, 'top' => 14, 'bottom' => $height - 28];
        [$step, $maxY] = $this->yScale(max([...$series['compounded'], ...$series['pure'], 1]));

        $count = count($series['ages']);
        $x = fn(int $i): float => $count === 1
            ? ($plot['left'] + $plot['right']) / 2
            : $plot['left'] + ($plot['right'] - $plot['left']) * $i / ($count - 1);
        $y = fn(float $value): float => $plot['bottom'] - ($plot['bottom'] - $plot['top']) * $value / $maxY;

        $parts = [];

        for ($value = 0.0; $value <= $maxY + 0.0001; $value += $step) {
            $lineY = round($y($value), 1);
            $parts[] = sprintf(
                '<line x1="%d" y1="%s" x2="%d" y2="%s" stroke="%s" stroke-width="%s"/>',
                $plot['left'],
                $lineY,
                $plot['right'],
                $lineY,
                $value === 0.0 ? '#c9ced8' : self::GRID_COLOR,
                $value === 0.0 ? '1.2' : '0.8'
            );
            $parts[] = $this->text($plot['left'] - 8, $lineY + 3.5, $this->shortCurrency($value), 'end');
        }

        $labelEvery = (int)ceil($count / self::MAX_X_LABELS);

        foreach ($series['ages'] as $i => $age) {
            // A última idade (65) sempre aparece: é onde a projeção chega.
            if ($i % $labelEvery !== 0 && $i !== $count - 1) {
                continue;
            }

            if ($i !== $count - 1 && $count - 1 - $i < $labelEvery) {
                continue;
            }

            $parts[] = $this->text($x($i), $plot['bottom'] + 16, (string)$age, 'middle');
        }

        $points = fn(array $values): array => array_map(
            fn(int $i): string => round($x($i), 1) . ',' . round($y((float)$values[$i]), 1),
            array_keys($values)
        );

        $compounded = $points($series['compounded']);
        $pure = $points($series['pure']);
        $baseline = round($y(0), 1);

        $parts[] = sprintf(
            '<path d="M %s L %s L %s,%s L %s,%s Z" fill="%s" fill-opacity="0.10" stroke="none"/>',
            $compounded[0],
            implode(' L ', $compounded),
            round($x($count - 1), 1),
            $baseline,
            round($x(0), 1),
            $baseline,
            self::COMPOUNDED_COLOR
        );
        $parts[] = $this->line($pure, self::PURE_COLOR);
        $parts[] = $this->line($compounded, self::COMPOUNDED_COLOR);

        // Rótulo direto só no fim de cada linha: é o valor que importa ao
        // cliente, e um número em cada ponto viraria ruído.
        $last = $count - 1;
        $cx = round($x($last), 1);
        $compoundedY = round($y((float)$series['compounded'][$last]), 1);
        $pureY = round($y((float)$series['pure'][$last]), 1);

        // Com pouco tempo até os 65 as duas linhas terminam quase no mesmo
        // ponto e os rótulos se sobrepunham: afasta um do outro, a
        // rentabilizada para cima, que é a que fica acima.
        $labelGap = 12;
        $compoundedLabelY = $compoundedY;
        $pureLabelY = $pureY;

        if ($pureLabelY - $compoundedLabelY < $labelGap) {
            $middle = ($pureLabelY + $compoundedLabelY) / 2;
            $compoundedLabelY = $middle - $labelGap / 2;
            $pureLabelY = $middle + $labelGap / 2;
        }

        foreach ([
            [$series['compounded'][$last], $compoundedY, $compoundedLabelY, self::COMPOUNDED_COLOR],
            [$series['pure'][$last], $pureY, $pureLabelY, self::PURE_COLOR],
        ] as [$value, $cy, $labelY, $color]) {
            $parts[] = sprintf('<circle cx="%s" cy="%s" r="4.5" fill="#ffffff"/>', $cx, $cy);
            $parts[] = sprintf('<circle cx="%s" cy="%s" r="3.2" fill="%s"/>', $cx, $cy, $color);
            $parts[] = $this->text($cx + 8, $labelY + 3.5, $this->shortCurrency((float)$value), 'start', '#1f2937', 'bold');
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">%s</svg>',
            $width,
            $height,
            $width,
            $height,
            implode('', $parts)
        );
    }

    public function dataUri(PlanProjection $projection, int $width = 680, int $height = 270): ?string
    {
        $svg = $this->svg($projection, $width, $height);

        return $svg === '' ? null : 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Passo "redondo" (1, 2, 2,5 ou 5 vezes uma potência de dez) para umas
     * quatro faixas, e o topo do eixo arredondado para cima nesse passo.
     *
     * @return array{0: float, 1: float}
     */
    private function yScale(float $max): array
    {
        $raw = $max / 4;
        $magnitude = 10 ** floor(log10($raw));
        $residual = $raw / $magnitude;

        $step = match (true) {
            $residual <= 1 => 1,
            $residual <= 2 => 2,
            $residual <= 2.5 => 2.5,
            $residual <= 5 => 5,
            default => 10,
        } * $magnitude;

        return [$step, ceil($max / $step) * $step];
    }

    private function shortCurrency(float $value): string
    {
        if ($value >= 1_000_000) {
            return 'R$ ' . $this->compactNumber($value / 1_000_000, 2) . ' mi';
        }

        if ($value >= 1_000) {
            // Abaixo de 100 mil uma casa decimal ainda diz alguma coisa
            // (13,7 mil x 13,6 mil); acima disso é ruído.
            $decimals = $value < 100_000 ? 1 : 0;

            return 'R$ ' . $this->compactNumber($value / 1_000, $decimals) . ' mil';
        }

        return 'R$ ' . number_format($value, 0, ',', '.');
    }

    /**
     * number_format() sem os zeros à direita da vírgula: "1,50" vira "1,5"
     * e "2,00" vira "2" -- mas "150" continua 150.
     */
    private function compactNumber(float $value, int $decimals): string
    {
        $formatted = number_format($value, $decimals, ',', '.');

        return $decimals > 0 ? rtrim(rtrim($formatted, '0'), ',') : $formatted;
    }

    private function line(array $points, string $color): string
    {
        return sprintf(
            '<polyline points="%s" fill="none" stroke="%s" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round"/>',
            implode(' ', $points),
            $color
        );
    }

    private function text(
        float $x,
        float $y,
        string $content,
        string $anchor,
        string $color = self::AXIS_TEXT_COLOR,
        string $weight = 'normal',
    ): string {
        return sprintf(
            '<text x="%s" y="%s" text-anchor="%s" font-family="DejaVu Sans, sans-serif" font-size="10" font-weight="%s" fill="%s">%s</text>',
            round($x, 1),
            round($y, 1),
            $anchor,
            $weight,
            $color,
            htmlspecialchars($content, ENT_XML1)
        );
    }
}
