<?php

declare(strict_types=1);

namespace App\Utility;

/**
 * Converte o dinheiro que vem do formulário para o formato que o banco aceita.
 *
 * O formulário produz duas grafias para o mesmo valor, e essa é a razão de
 * existir desta classe. Os templates abrem os campos em pt-BR ("1.234,56"),
 * como o recálculo os reescreve com toLocaleString('pt-BR'); mas campo que o
 * template não formata -- ou cliente com a página antiga em cache -- ainda
 * manda o decimal cru do banco, "370.00".
 *
 * Assumir só a grafia brasileira, como se fazia, apagava os pontos de
 * "370.00" e gravava 37000: cem vezes o valor, sem erro nenhum para denunciar.
 * Era invisível enquanto o preenchimento disparava um recálculo por acidente.
 */
final class Money
{
    /**
     * @return string|null valor com ponto decimal, ou null se não havia valor
     */
    public static function parse(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string)$value);

        // Com vírgula, ela é o separador decimal e o ponto é de milhar.
        if (str_contains($value, ',')) {
            return str_replace(',', '.', str_replace('.', '', $value));
        }

        // Sem vírgula, o ponto é o separador decimal e fica onde está. As duas
        // fontes do formulário sempre trazem os centavos, então não existe
        // aqui o caso ambíguo de "1.234" querendo dizer mil duzentos e trinta
        // e quatro.
        return $value;
    }
}
