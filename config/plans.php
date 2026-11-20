<?php

/**
 * Os planos de benefícios atendidos por este deploy, um por subdomínio.
 *
 * Fica em arquivo versionado, e não no banco, de propósito: estes dados vão
 * impressos na proposta que o participante assina, e mudar um deles é mudar
 * um documento jurídico -- tem que passar por revisão e deploy. Os parâmetros
 * numéricos (contribuição mínima, percentuais de risco) ficam em
 * plan_parameters, editáveis no admin, uma linha por plano.
 *
 * O primeiro plano da lista é o principal: é o host dos links que o admin
 * manda por e-mail (convite, redefinição de senha), que não pertencem a
 * plano nenhum.
 *
 * Ver docs/plano-multiplano-maispremium.md.
 */
return [
    'BenefitPlans' => [
        'plenoprev' => [
            'name' => 'PlenoPrev',
            // Em desenvolvimento, PLENOPREV_BASE_URL=http://plenoprev.localhost:8765
            // (todo *.localhost resolve para 127.0.0.1 no navegador).
            'baseUrl' => env('PLENOPREV_BASE_URL', 'https://plenoprev.konverter.com.br'),
            'cnpj' => '48.307.525/0001-09',
            'cnpb' => '2011/0017-65',
            'loading' => '0%',
            'slogan' => 'A realização do seu futuro',
            'proposalPrefix' => 'BFX',
            // O txid (padrão Bacen) tem no máximo 35 caracteres alfanuméricos,
            // e o prefixo divide esse espaço com 21 fixos (adesão, tentativa e
            // hash): até 14 aqui. Ver PixPaymentService::generateTxid().
            'txidPrefix' => 'PLENOPREV',
            'institution' => [
                'number' => '009',
                'name' => 'CEPREV',
            ],
            'insurer' => [
                'name' => 'Mongeral Aegon Seguros e Previdência',
                'shortName' => 'MAG Seguros',
                'cnpj' => '33.608.308/0001-73',
                'loading' => '30%',
                'code' => '100350',
                'membershipAgreement' => 'AD2550',
                'marketingAction' => 'AM0493',
                'branch' => 'F22',
                'alternative' => '01',
                'secureBroker1' => 'MT 8002897',
            ],
            'broker' => [
                'name' => 'Corretop Corretora de Seguros',
                'code' => '202104784',
            ],
            // `config` é a chave do Configure com as credenciais e a chave Pix
            // da conta que recebe por este plano (ver config/app.php).
            'pix' => [
                'provider' => 'sicoob',
                'config' => 'Sicoob',
            ],
        ],
    ],
];
