<?php

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Colapsa simulacao_previdencia para quatro parametros numericos: a
 * contribuicao de cada risco chega pronta, em reais, em vez de dois booleanos
 * que a procedure traduzia em 16% e 10% hardcoded.
 *
 * Quem decide esses valores passa a ser o PHP (App\Services\PlanSimulator),
 * a partir de plan_parameters: 0 quando o risco nao foi contratado, o ajuste
 * do admin quando houver, senao a taxa padrao. A procedure fica sendo apenas a
 * calculadora atuarial -- tabela de custo por idade, tetos, capital, saldo e
 * beneficio -- sem nenhuma regra de negocio.
 *
 * Os DROP abaixo sao a correcao de um bug latente: nas duas migrations
 * anteriores o DROP estava comentado, e no Postgres CREATE OR REPLACE com
 * assinatura diferente cria SOBRECARGA, nao substituicao. As versoes de 2 e de
 * 4 parametros coexistiam, e como os parametros 3 e 4 tinham DEFAULT, uma
 * chamada com 2 argumentos respondia "function ... is not unique". So nao
 * quebrava porque a aplicacao sempre chamava com 4.
 */
class CollapseSimulatorFunctionToRiskAmounts extends BaseMigration
{
    public function up(): void
    {
        // As tres assinaturas, incluindo a que esta migration cria: Migrator
        // reconstroi o banco de teste apagando TABELAS, e funcao nao e tabela.
        // Sem derrubar a propria, uma reconstrucao esbarra em "function
        // already exists with same argument types".
        $this->execute('DROP FUNCTION IF EXISTS public.simulacao_previdencia(date, numeric)');
        $this->execute('DROP FUNCTION IF EXISTS public.simulacao_previdencia(date, numeric, boolean, boolean)');
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

                    -- Determinar taxas de custo e limites por idade
                    CASE v_anos
                        WHEN 1 THEN v_custo_morte_1 := 0.000127381; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 2 THEN v_custo_morte_1 := 0.000117857; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 3 THEN v_custo_morte_1 := 0.000116667; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 4 THEN v_custo_morte_1 := 0.000113095; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 5 THEN v_custo_morte_1 := 0.000107143; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 6 THEN v_custo_morte_1 := 0.000102381; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 7 THEN v_custo_morte_1 := 0.000095238; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 8 THEN v_custo_morte_1 := 0.000090476; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 9 THEN v_custo_morte_1 := 0.000088095; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 10 THEN v_custo_morte_1 := 0.000086905; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 11 THEN v_custo_morte_1 := 0.000091667; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 12 THEN v_custo_morte_1 := 0.000101190; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 13 THEN v_custo_morte_1 := 0.000117857; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 14 THEN v_custo_morte_1 := 0.000136905; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 15 THEN v_custo_morte_1 := 0.000158333; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 16 THEN v_custo_morte_1 := 0.000179762; v_custo_invalidez_1 := 0.000068214; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 17 THEN v_custo_morte_1 := 0.000198810; v_custo_invalidez_1 := 0.000068095; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 18 THEN v_custo_morte_1 := 0.000211905; v_custo_invalidez_1 := 0.000067857; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 19 THEN v_custo_morte_1 := 0.000221429; v_custo_invalidez_1 := 0.000067738; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 20 THEN v_custo_morte_1 := 0.000226190; v_custo_invalidez_1 := 0.000067738; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 21 THEN v_custo_morte_1 := 0.000227381; v_custo_invalidez_1 := 0.000067738; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 22 THEN v_custo_morte_1 := 0.000225000; v_custo_invalidez_1 := 0.000067738; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 23 THEN v_custo_morte_1 := 0.000221429; v_custo_invalidez_1 := 0.000067857; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 24 THEN v_custo_morte_1 := 0.000216667; v_custo_invalidez_1 := 0.000068095; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 25 THEN v_custo_morte_1 := 0.000210714; v_custo_invalidez_1 := 0.000068452; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 26 THEN v_custo_morte_1 := 0.000205952; v_custo_invalidez_1 := 0.000068929; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 27 THEN v_custo_morte_1 := 0.000203571; v_custo_invalidez_1 := 0.000069405; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 28 THEN v_custo_morte_1 := 0.000202381; v_custo_invalidez_1 := 0.000070119; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 29 THEN v_custo_morte_1 := 0.000203571; v_custo_invalidez_1 := 0.000070952; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 30 THEN v_custo_morte_1 := 0.000205952; v_custo_invalidez_1 := 0.000072024; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 31 THEN v_custo_morte_1 := 0.000211905; v_custo_invalidez_1 := 0.000073214; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 32 THEN v_custo_morte_1 := 0.000217857; v_custo_invalidez_1 := 0.000074762; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 33 THEN v_custo_morte_1 := 0.000227381; v_custo_invalidez_1 := 0.000076548; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 34 THEN v_custo_morte_1 := 0.000238095; v_custo_invalidez_1 := 0.000078571; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 35 THEN v_custo_morte_1 := 0.000251190; v_custo_invalidez_1 := 0.000081071; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 36 THEN v_custo_morte_1 := 0.000266667; v_custo_invalidez_1 := 0.000083810; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 37 THEN v_custo_morte_1 := 0.000285714; v_custo_invalidez_1 := 0.000087143; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 38 THEN v_custo_morte_1 := 0.000307143; v_custo_invalidez_1 := 0.000090952; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 39 THEN v_custo_morte_1 := 0.000332143; v_custo_invalidez_1 := 0.000095357; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 40 THEN v_custo_morte_1 := 0.000359524; v_custo_invalidez_1 := 0.000100476; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 41 THEN v_custo_morte_1 := 0.000391667; v_custo_invalidez_1 := 0.000106310; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 42 THEN v_custo_morte_1 := 0.000423810; v_custo_invalidez_1 := 0.000112976; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 43 THEN v_custo_morte_1 := 0.000460714; v_custo_invalidez_1 := 0.000120714; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 44 THEN v_custo_morte_1 := 0.000498810; v_custo_invalidez_1 := 0.000129524; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 45 THEN v_custo_morte_1 := 0.000541667; v_custo_invalidez_1 := 0.000139762; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 46 THEN v_custo_morte_1 := 0.000585714; v_custo_invalidez_1 := 0.000151310; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 47 THEN v_custo_morte_1 := 0.000633333; v_custo_invalidez_1 := 0.000164643; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 48 THEN v_custo_morte_1 := 0.000683333; v_custo_invalidez_1 := 0.000179881; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 49 THEN v_custo_morte_1 := 0.000735714; v_custo_invalidez_1 := 0.000197262; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 50 THEN v_custo_morte_1 := 0.000798810; v_custo_invalidez_1 := 0.000217024; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 51 THEN v_custo_morte_1 := 0.000869048; v_custo_invalidez_1 := 0.000239762; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 52 THEN v_custo_morte_1 := 0.000947619; v_custo_invalidez_1 := 0.000265595; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 53 THEN v_custo_morte_1 := 0.001036905; v_custo_invalidez_1 := 0.000295119; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 54 THEN v_custo_morte_1 := 0.001138095; v_custo_invalidez_1 := 0.000328810; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 55 THEN v_custo_morte_1 := 0.001246429; v_custo_invalidez_1 := 0.000367262; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 56 THEN v_custo_morte_1 := 0.001364286; v_custo_invalidez_1 := 0.000410952; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 57 THEN v_custo_morte_1 := 0.001486905; v_custo_invalidez_1 := 0.000460952; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 58 THEN v_custo_morte_1 := 0.001617857; v_custo_invalidez_1 := 0.000517857; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 59 THEN v_custo_morte_1 := 0.001758333; v_custo_invalidez_1 := 0.000582738; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 60 THEN v_custo_morte_1 := 0.001914286; v_custo_invalidez_1 := 0.000656667; v_limite_morte := 1700000.00; v_limite_invalidez := 1700000.00;
                        WHEN 61 THEN v_custo_morte_1 := 0.002088095; v_custo_invalidez_1 := 0.000740833; v_limite_morte := 600000.00; v_limite_invalidez := 500000.00;
                        WHEN 62 THEN v_custo_morte_1 := 0.002284524; v_custo_invalidez_1 := 0.000836786; v_limite_morte := 500000.00; v_limite_invalidez := 500000.00;
                        WHEN 63 THEN v_custo_morte_1 := 0.002507143; v_custo_invalidez_1 := 0.000946071; v_limite_morte := 5000000.00; v_limite_invalidez := 5000000.00;
                        WHEN 64 THEN v_custo_morte_1 := 0.002754762; v_custo_invalidez_1 := 0.001070595; v_limite_morte := 500000.00; v_limite_invalidez := 500000.00;
                        WHEN 65 THEN v_custo_morte_1 := 0.003026190; v_custo_invalidez_1 := 0.001212262; v_limite_morte := 500000.00; v_limite_invalidez := 500000.00;
                        WHEN 66 THEN v_custo_morte_1 := 0.003315476; v_custo_invalidez_1 := 0.001374048; v_limite_morte := 120000.00; v_limite_invalidez := 120000.00;
                        WHEN 67 THEN v_custo_morte_1 := 0.003623810; v_custo_invalidez_1 := 0.001557976; v_limite_morte := 120000.00; v_limite_invalidez := 120000.00;
                        WHEN 68 THEN v_custo_morte_1 := 0.003951190; v_custo_invalidez_1 := 0.001767500; v_limite_morte := 120000.00; v_limite_invalidez := 120000.00;
                        WHEN 69 THEN v_custo_morte_1 := 0.004305952; v_custo_invalidez_1 := 0.002006190; v_limite_morte := 120000.00; v_limite_invalidez := 120000.00;
                        WHEN 70 THEN v_custo_morte_1 := 0.004703571; v_custo_invalidez_1 := 0.002277976; v_limite_morte := 120000.00; v_limite_invalidez := 120000.00;
                        WHEN 71 THEN v_custo_morte_1 := 0.005154762; v_custo_invalidez_1 := 0.002587381; v_limite_morte := 96000.00; v_limite_invalidez := 96000.00;
                        WHEN 72 THEN v_custo_morte_1 := 0.005672619; v_custo_invalidez_1 := 0.002939881; v_limite_morte := 96000.00; v_limite_invalidez := 96000.00;
                        WHEN 73 THEN v_custo_morte_1 := 0.006266667; v_custo_invalidez_1 := 0.003341190; v_limite_morte := 96000.00; v_limite_invalidez := 96000.00;
                        WHEN 74 THEN v_custo_morte_1 := 0.006927381; v_custo_invalidez_1 := 0.003798095; v_limite_morte := 96000.00; v_limite_invalidez := 96000.00;
                        WHEN 75 THEN v_custo_morte_1 := 0.007641667; v_custo_invalidez_1 := 0.004318452; v_limite_morte := 96000.00; v_limite_invalidez := 96000.00;
                        WHEN 76 THEN v_custo_morte_1 := 0.008396429; v_custo_invalidez_1 := 0.004910952; v_limite_morte := 72000.00; v_limite_invalidez := 72000.00;
                        WHEN 77 THEN v_custo_morte_1 := 0.009180952; v_custo_invalidez_1 := 0.005585595; v_limite_morte := 72000.00; v_limite_invalidez := 72000.00;
                        WHEN 78 THEN v_custo_morte_1 := 0.009988095; v_custo_invalidez_1 := 0.006591786; v_limite_morte := 72000.00; v_limite_invalidez := 72000.00;
                        WHEN 79 THEN v_custo_morte_1 := 0.010839286; v_custo_invalidez_1 := 0.007228333; v_limite_morte := 72000.00; v_limite_invalidez := 72000.00;
                        WHEN 80 THEN v_custo_morte_1 := 0.011766667; v_custo_invalidez_1 := 0.008224286; v_limite_morte := 72000.00; v_limite_invalidez := 72000.00;
                        ELSE
                            v_custo_morte_1 := 0.011766667; v_custo_invalidez_1 := 0.008224286; v_limite_morte := 72000.00; v_limite_invalidez := 72000.00;
                    END CASE;

                    -- Calcular coberturas
                    v_cobertura_morte := LEAST(v_contribuicao_morte / v_custo_morte_1, v_limite_morte);
                    v_cobertura_invalidez := LEAST(v_contribuicao_invalidez / v_custo_invalidez_1, v_limite_invalidez);

                    -- Calcular rendas mensais de morte e invalidez
                    v_renda_morte_mensal := v_cobertura_morte / 109.558922; -- Fator fixo
                    v_renda_invalidez_mensal := v_cobertura_invalidez / 109.558922; -- Fator fixo

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
                    cobertura_morte := FLOOR(v_cobertura_morte * 100) / 100;
                    renda_morte_mensal := FLOOR(v_renda_morte_mensal * 100) / 100;
                    cobertura_invalidez := FLOOR(v_cobertura_invalidez * 100) / 100;
                    renda_invalidez_mensal := FLOOR(v_renda_invalidez_mensal * 100) / 100;
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
        // A forma anterior volta re-executando
        // 20260921010000_ParameterizeSimulatorFunctionRisks: reproduzir as 160
        // linhas do corpo aqui so para o rollback criaria uma segunda copia da
        // tabela atuarial, que e exatamente o tipo de duplicata que se
        // desatualiza sem ninguem perceber.
        $this->execute('DROP FUNCTION IF EXISTS public.simulacao_previdencia(date, numeric, numeric, numeric)');
    }
}
