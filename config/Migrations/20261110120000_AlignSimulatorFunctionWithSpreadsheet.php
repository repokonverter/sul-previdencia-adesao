<?php

declare(strict_types=1);

use Migrations\BaseMigration;

class AlignSimulatorFunctionWithSpreadsheet extends BaseMigration
{
    public function up(): void
    {
        $this->execute('DROP FUNCTION IF EXISTS public.simulacao_previdencia(date, numeric, numeric, numeric)');

        $sql = '
            CREATE FUNCTION public.simulacao_previdencia(p_data_nascimento date, p_contribuicao_mensal numeric, p_contribuicao_morte numeric, p_contribuicao_invalidez numeric)
            RETURNS TABLE(taxa_rentabilidade_anual numeric, saldo_acumulado numeric, beneficio_mensal numeric, cobertura_morte numeric, renda_morte_mensal numeric, cobertura_invalidez numeric, renda_invalidez_mensal numeric, contribuicao_morte numeric, contribuicao_invalidez numeric, contribuicao_aposentadoria numeric)
            LANGUAGE plpgsql
            AS $function$
            DECLARE
                v_idade INTERVAL;
                v_anos INTEGER;
                v_meses INTEGER;
                v_data_aposentadoria DATE;
                v_meses_ate_aposent INTEGER;
                v_taxa_anual NUMERIC;
                v_taxa_mensal NUMERIC;
                v_saldo_acumulado NUMERIC;
                v_beneficio_mensal NUMERIC;
                v_prazo_concessao INTEGER := 60; -- 5 anos em meses
                v_contribuicao_morte NUMERIC;
                v_contribuicao_invalidez NUMERIC;
                v_custo_morte_1 NUMERIC;
                v_custo_invalidez_1 NUMERIC;
                v_limite_morte NUMERIC;
                v_limite_invalidez NUMERIC;
                v_cobertura_morte NUMERIC;
                v_renda_morte_mensal NUMERIC;
                v_cobertura_invalidez NUMERIC;
                v_renda_invalidez_mensal NUMERIC;
                v_contribuicao_aposentadoria NUMERIC;
                v_fator_renda NUMERIC := 109.5589222132795; -- coluna "Anuidade" da planilha
            BEGIN
                -- Calcular idade atual
                v_idade := AGE(CURRENT_DATE, p_data_nascimento);
                v_anos := EXTRACT(YEAR FROM v_idade);
                v_meses := EXTRACT(MONTH FROM v_idade);

                -- Calcular data de aposentadoria (65 anos)
                v_data_aposentadoria := p_data_nascimento + INTERVAL \'65 years\';

                -- Calcular meses até aposentadoria
                v_meses_ate_aposent := (EXTRACT(YEAR FROM AGE(v_data_aposentadoria, CURRENT_DATE)) * 12) +
                                    EXTRACT(MONTH FROM AGE(v_data_aposentadoria, CURRENT_DATE)) - 1;

                -- Ajustar se a data atual for após a aposentadoria
                IF v_meses_ate_aposent < 0 THEN
                    v_meses_ate_aposent := 0;
                END IF;

                -- Iterar sobre as três taxas de rentabilidade (6%, 8%, 10%) usando um array
                FOR v_taxa_anual IN SELECT unnest(ARRAY[0.0606, 0.0803, 0.1008]) LOOP
                    -- Calcular taxa de juros mensal
                    v_taxa_mensal := POWER(1 + v_taxa_anual, 1.0 / 12) - 1;

                    -- Calcular contribuições
                    v_contribuicao_morte := p_contribuicao_morte; -- valor decidido pelo PHP: 0 se o risco foi removido, ajuste do admin se houver, senao a taxa padrao
                    v_contribuicao_invalidez := p_contribuicao_invalidez;
                    v_contribuicao_aposentadoria := p_contribuicao_mensal - v_contribuicao_morte - v_contribuicao_invalidez; -- Absorve automaticamente a fatia de qualquer risco removido

                    -- Custos e tetos por idade da aba "calculo" da planilha do cliente:
                    -- taxa anual da tabua / 12 meses / 0.7 (carregamento de 30%),
                    -- escritos como conta para o Postgres calcular com precisao total
                    CASE v_anos
                        WHEN 1 THEN v_custo_morte_1 := 0.00107 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 2 THEN v_custo_morte_1 := 0.00099 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 3 THEN v_custo_morte_1 := 0.00098 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 4 THEN v_custo_morte_1 := 0.00095 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 5 THEN v_custo_morte_1 := 0.0009 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 6 THEN v_custo_morte_1 := 0.00086 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 7 THEN v_custo_morte_1 := 0.0008 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 8 THEN v_custo_morte_1 := 0.00076 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 9 THEN v_custo_morte_1 := 0.00074 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 10 THEN v_custo_morte_1 := 0.00073 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 11 THEN v_custo_morte_1 := 0.00077 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 12 THEN v_custo_morte_1 := 0.00085 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 13 THEN v_custo_morte_1 := 0.00099 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 14 THEN v_custo_morte_1 := 0.00115 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 15 THEN v_custo_morte_1 := 0.00133 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 16 THEN v_custo_morte_1 := 0.00151 / 12 / 0.7; v_custo_invalidez_1 := 0.000573 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 17 THEN v_custo_morte_1 := 0.00167 / 12 / 0.7; v_custo_invalidez_1 := 0.000572 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 18 THEN v_custo_morte_1 := 0.00178 / 12 / 0.7; v_custo_invalidez_1 := 0.00057 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 19 THEN v_custo_morte_1 := 0.00186 / 12 / 0.7; v_custo_invalidez_1 := 0.000569 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 20 THEN v_custo_morte_1 := 0.0019 / 12 / 0.7; v_custo_invalidez_1 := 0.000569 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 21 THEN v_custo_morte_1 := 0.00191 / 12 / 0.7; v_custo_invalidez_1 := 0.000569 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 22 THEN v_custo_morte_1 := 0.00189 / 12 / 0.7; v_custo_invalidez_1 := 0.000569 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 23 THEN v_custo_morte_1 := 0.00186 / 12 / 0.7; v_custo_invalidez_1 := 0.00057 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 24 THEN v_custo_morte_1 := 0.00182 / 12 / 0.7; v_custo_invalidez_1 := 0.000572 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 25 THEN v_custo_morte_1 := 0.00177 / 12 / 0.7; v_custo_invalidez_1 := 0.000575 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 26 THEN v_custo_morte_1 := 0.00173 / 12 / 0.7; v_custo_invalidez_1 := 0.000579 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 27 THEN v_custo_morte_1 := 0.00171 / 12 / 0.7; v_custo_invalidez_1 := 0.000583 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 28 THEN v_custo_morte_1 := 0.0017 / 12 / 0.7; v_custo_invalidez_1 := 0.000589 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 29 THEN v_custo_morte_1 := 0.00171 / 12 / 0.7; v_custo_invalidez_1 := 0.000596 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 30 THEN v_custo_morte_1 := 0.00173 / 12 / 0.7; v_custo_invalidez_1 := 0.000605 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 31 THEN v_custo_morte_1 := 0.00178 / 12 / 0.7; v_custo_invalidez_1 := 0.000615 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 32 THEN v_custo_morte_1 := 0.00183 / 12 / 0.7; v_custo_invalidez_1 := 0.000628 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 33 THEN v_custo_morte_1 := 0.00191 / 12 / 0.7; v_custo_invalidez_1 := 0.000643 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 34 THEN v_custo_morte_1 := 0.002 / 12 / 0.7; v_custo_invalidez_1 := 0.00066 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 35 THEN v_custo_morte_1 := 0.00211 / 12 / 0.7; v_custo_invalidez_1 := 0.000681 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 36 THEN v_custo_morte_1 := 0.00224 / 12 / 0.7; v_custo_invalidez_1 := 0.000704 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 37 THEN v_custo_morte_1 := 0.0024 / 12 / 0.7; v_custo_invalidez_1 := 0.000732 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 38 THEN v_custo_morte_1 := 0.00258 / 12 / 0.7; v_custo_invalidez_1 := 0.000764 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 39 THEN v_custo_morte_1 := 0.00279 / 12 / 0.7; v_custo_invalidez_1 := 0.000801 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 40 THEN v_custo_morte_1 := 0.00302 / 12 / 0.7; v_custo_invalidez_1 := 0.000844 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 41 THEN v_custo_morte_1 := 0.00329 / 12 / 0.7; v_custo_invalidez_1 := 0.000893 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 42 THEN v_custo_morte_1 := 0.00356 / 12 / 0.7; v_custo_invalidez_1 := 0.000949 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 43 THEN v_custo_morte_1 := 0.00387 / 12 / 0.7; v_custo_invalidez_1 := 0.001014 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 44 THEN v_custo_morte_1 := 0.00419 / 12 / 0.7; v_custo_invalidez_1 := 0.001088 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 45 THEN v_custo_morte_1 := 0.00455 / 12 / 0.7; v_custo_invalidez_1 := 0.001174 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 46 THEN v_custo_morte_1 := 0.00492 / 12 / 0.7; v_custo_invalidez_1 := 0.001271 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 47 THEN v_custo_morte_1 := 0.00532 / 12 / 0.7; v_custo_invalidez_1 := 0.001383 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 48 THEN v_custo_morte_1 := 0.00574 / 12 / 0.7; v_custo_invalidez_1 := 0.001511 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 49 THEN v_custo_morte_1 := 0.00621 / 12 / 0.7; v_custo_invalidez_1 := 0.001657 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 50 THEN v_custo_morte_1 := 0.00671 / 12 / 0.7; v_custo_invalidez_1 := 0.001823 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 51 THEN v_custo_morte_1 := 0.0073 / 12 / 0.7; v_custo_invalidez_1 := 0.002014 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 52 THEN v_custo_morte_1 := 0.00796 / 12 / 0.7; v_custo_invalidez_1 := 0.002231 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 53 THEN v_custo_morte_1 := 0.00871 / 12 / 0.7; v_custo_invalidez_1 := 0.002479 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 54 THEN v_custo_morte_1 := 0.00956 / 12 / 0.7; v_custo_invalidez_1 := 0.002762 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 55 THEN v_custo_morte_1 := 0.01047 / 12 / 0.7; v_custo_invalidez_1 := 0.003085 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 56 THEN v_custo_morte_1 := 0.01146 / 12 / 0.7; v_custo_invalidez_1 := 0.003452 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 57 THEN v_custo_morte_1 := 0.01249 / 12 / 0.7; v_custo_invalidez_1 := 0.003872 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 58 THEN v_custo_morte_1 := 0.01359 / 12 / 0.7; v_custo_invalidez_1 := 0.00435 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 59 THEN v_custo_morte_1 := 0.01477 / 12 / 0.7; v_custo_invalidez_1 := 0.004895 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 60 THEN v_custo_morte_1 := 0.01608 / 12 / 0.7; v_custo_invalidez_1 := 0.005516 / 12 / 0.7; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 61 THEN v_custo_morte_1 := 0.01754 / 12 / 0.7; v_custo_invalidez_1 := 0.006223 / 12 / 0.7; v_limite_morte := 600000.00; v_limite_invalidez := 600000.00;
                        WHEN 62 THEN v_custo_morte_1 := 0.01919 / 12 / 0.7; v_custo_invalidez_1 := 0.007029 / 12 / 0.7; v_limite_morte := 500000.00; v_limite_invalidez := 500000.00;
                        WHEN 63 THEN v_custo_morte_1 := 0.02106 / 12 / 0.7; v_custo_invalidez_1 := 0.007947 / 12 / 0.7; v_limite_morte := 500000.00; v_limite_invalidez := 500000.00;
                        WHEN 64 THEN v_custo_morte_1 := 0.02314 / 12 / 0.7; v_custo_invalidez_1 := 0.008993 / 12 / 0.7; v_limite_morte := 500000.00; v_limite_invalidez := 500000.00;
                        WHEN 65 THEN v_custo_morte_1 := 0.02542 / 12 / 0.7; v_custo_invalidez_1 := 0.010183 / 12 / 0.7; v_limite_morte := 500000.00; v_limite_invalidez := 500000.00;
                        WHEN 66 THEN v_custo_morte_1 := 0.02785 / 12 / 0.7; v_custo_invalidez_1 := 0.011542 / 12 / 0.7; v_limite_morte := 120000.00; v_limite_invalidez := 120000.00;
                        WHEN 67 THEN v_custo_morte_1 := 0.03044 / 12 / 0.7; v_custo_invalidez_1 := 0.013087 / 12 / 0.7; v_limite_morte := 120000.00; v_limite_invalidez := 120000.00;
                        WHEN 68 THEN v_custo_morte_1 := 0.03319 / 12 / 0.7; v_custo_invalidez_1 := 0.014847 / 12 / 0.7; v_limite_morte := 120000.00; v_limite_invalidez := 120000.00;
                        WHEN 69 THEN v_custo_morte_1 := 0.03617 / 12 / 0.7; v_custo_invalidez_1 := 0.016852 / 12 / 0.7; v_limite_morte := 120000.00; v_limite_invalidez := 120000.00;
                        WHEN 70 THEN v_custo_morte_1 := 0.03951 / 12 / 0.7; v_custo_invalidez_1 := 0.019135 / 12 / 0.7; v_limite_morte := 120000.00; v_limite_invalidez := 120000.00;
                        WHEN 71 THEN v_custo_morte_1 := 0.0433 / 12 / 0.7; v_custo_invalidez_1 := 0.021734 / 12 / 0.7; v_limite_morte := 96000.00; v_limite_invalidez := 96000.00;
                        WHEN 72 THEN v_custo_morte_1 := 0.04765 / 12 / 0.7; v_custo_invalidez_1 := 0.024695 / 12 / 0.7; v_limite_morte := 96000.00; v_limite_invalidez := 96000.00;
                        WHEN 73 THEN v_custo_morte_1 := 0.05264 / 12 / 0.7; v_custo_invalidez_1 := 0.028066 / 12 / 0.7; v_limite_morte := 96000.00; v_limite_invalidez := 96000.00;
                        WHEN 74 THEN v_custo_morte_1 := 0.05819 / 12 / 0.7; v_custo_invalidez_1 := 0.031904 / 12 / 0.7; v_limite_morte := 96000.00; v_limite_invalidez := 96000.00;
                        WHEN 75 THEN v_custo_morte_1 := 0.06419 / 12 / 0.7; v_custo_invalidez_1 := 0.036275 / 12 / 0.7; v_limite_morte := 96000.00; v_limite_invalidez := 96000.00;
                        WHEN 76 THEN v_custo_morte_1 := 0.07053 / 12 / 0.7; v_custo_invalidez_1 := 0.041252 / 12 / 0.7; v_limite_morte := 72000.00; v_limite_invalidez := 72000.00;
                        WHEN 77 THEN v_custo_morte_1 := 0.07712 / 12 / 0.7; v_custo_invalidez_1 := 0.046919 / 12 / 0.7; v_limite_morte := 72000.00; v_limite_invalidez := 72000.00;
                        WHEN 78 THEN v_custo_morte_1 := 0.0839 / 12 / 0.7; v_custo_invalidez_1 := 0.055371 / 12 / 0.7; v_limite_morte := 72000.00; v_limite_invalidez := 72000.00;
                        WHEN 79 THEN v_custo_morte_1 := 0.09105 / 12 / 0.7; v_custo_invalidez_1 := 0.060718 / 12 / 0.7; v_limite_morte := 72000.00; v_limite_invalidez := 72000.00;
                        WHEN 80 THEN v_custo_morte_1 := 0.09884 / 12 / 0.7; v_custo_invalidez_1 := 0.069084 / 12 / 0.7; v_limite_morte := 72000.00; v_limite_invalidez := 72000.00;
                        ELSE
                            v_custo_morte_1 := 0.09884 / 12 / 0.7; v_custo_invalidez_1 := 0.069084 / 12 / 0.7; v_limite_morte := 72000.00; v_limite_invalidez := 72000.00;
                    END CASE;

                    -- Calcular coberturas
                    v_cobertura_morte := LEAST(v_contribuicao_morte / v_custo_morte_1, v_limite_morte);
                    v_cobertura_invalidez := LEAST(v_contribuicao_invalidez / v_custo_invalidez_1, v_limite_invalidez);

                    -- Calcular rendas mensais de morte e invalidez
                    v_renda_morte_mensal := v_cobertura_morte / v_fator_renda;
                    v_renda_invalidez_mensal := v_cobertura_invalidez / v_fator_renda;

                    -- Calcular saldo acumulado usando apenas a contribuição para previdência
                    IF v_meses_ate_aposent > 0 THEN
                        v_saldo_acumulado := v_contribuicao_aposentadoria * ((POWER(1 + v_taxa_mensal, v_meses_ate_aposent) - 1) / v_taxa_mensal);
                    ELSE
                        v_saldo_acumulado := 0;
                    END IF;

                    -- Calcular benefício mensal para 60 meses
                    IF v_saldo_acumulado > 0 THEN
                        v_beneficio_mensal := v_saldo_acumulado * v_taxa_mensal / (1 - POWER(1 + v_taxa_mensal, -v_prazo_concessao));
                    ELSE
                        v_beneficio_mensal := 0;
                    END IF;

                    -- Retornar resultados para a taxa atual
                    taxa_rentabilidade_anual := v_taxa_anual;
                    saldo_acumulado := ROUND(v_saldo_acumulado, 2);
                    beneficio_mensal := ROUND(v_beneficio_mensal, 2);
                    cobertura_morte := ROUND(v_cobertura_morte, 2);
                    renda_morte_mensal := ROUND(v_renda_morte_mensal, 2);
                    cobertura_invalidez := ROUND(v_cobertura_invalidez, 2);
                    renda_invalidez_mensal := ROUND(v_renda_invalidez_mensal, 2);
                    contribuicao_morte := v_contribuicao_morte;
                    contribuicao_invalidez := v_contribuicao_invalidez;
                    contribuicao_aposentadoria := v_contribuicao_aposentadoria;

                    RETURN NEXT;
                END LOOP;
            END;
            $function$
            ;        ';
        $this->execute($sql);
    }

    public function down(): void
    {
        // A versao anterior volta pelo up() da propria migration dela, que
        // derruba esta e recria a sua: copiar as 80 linhas da tabela antiga
        // para ca seria manter uma segunda tabela atuarial so para o rollback.
        if (!class_exists('CollapseSimulatorFunctionToRiskAmounts', false)) {
            require __DIR__ . '/20260924130000_CollapseSimulatorFunctionToRiskAmounts.php';
        }

        $previous = new CollapseSimulatorFunctionToRiskAmounts($this->getVersion());
        $previous->setAdapter($this->getAdapter());
        $previous->up();
    }
}
