<?php

declare(strict_types=1);

namespace App\View\Helper;

use App\Utility\Money;
use Cake\I18n\Number;
use Cake\View\Helper;

class UtilsHelper extends Helper
{
  /**
   * Formata um valor em reais (R$ 1.234,50), independente do locale do servidor.
   */
  public function currency($value): string
  {
    return Number::currency((float)$value, 'BRL', ['locale' => 'pt_BR']);
  }

  /**
   * Valor inicial de um campo de dinheiro (máscara money/money2): "1.234,50".
   *
   * O campo recebe o decimal da entidade ("1234.50") ou o POST que voltou
   * com erro ("1.234,50"); Money::parse entende os dois. Sem isto o campo
   * abria no formato do banco até a máscara ou o recálculo reescrevê-lo.
   */
  public function moneyInput($value): ?string
  {
    return $this->numberInput($value, '.');
  }

  /**
   * Valor inicial de um campo decimal sem milhar (peso, altura, percentual):
   * "75,50". O "%" que a máscara de percentual embute sai antes.
   */
  public function decimalInput($value): ?string
  {
    return $this->numberInput(is_string($value) ? rtrim($value, '%') : $value, '');
  }

  private function numberInput($value, string $thousandsSeparator): ?string
  {
    $parsed = Money::parse($value);

    if ($parsed === null) {
      return null;
    }

    if (!is_numeric($parsed)) {
      return (string)$value;
    }

    return number_format((float)$parsed, 2, ',', $thousandsSeparator);
  }

  public function formatCpf($value)
  {
    $cpf = preg_replace('/\D/', '', (string)$value);

    if (strlen($cpf) === 11) {
      return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }

    return $value;
  }

  public function formatCEP($value)
  {
    $cep = preg_replace('/\D/', '', (string)$value);

    if (strlen($cep) === 8) {
      return preg_replace('/(\d{2})(\d{3})(\d{3})/', '$1.$2-$3', $cep);
    }

    return $value;
  }

  public function monthName($month)
  {
    $months = [
      1 => 'Janeiro',
      2 => 'Fevereiro',
      3 => 'Março',
      4 => 'Abril',
      5 => 'Maio',
      6 => 'Junho',
      7 => 'Julho',
      8 => 'Agosto',
      9 => 'Setembro',
      10 => 'Outubro',
      11 => 'Novembro',
      12 => 'Dezembro',
    ];

    return $months[(int)$month] ?? '';
  }
}
