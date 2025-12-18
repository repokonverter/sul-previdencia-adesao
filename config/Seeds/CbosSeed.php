<?php

declare(strict_types=1);

use Migrations\AbstractSeed;

class CbosSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     *
     * @return void
     */
    public function run(): void
    {
        // Optional: Truncate table first to avoid duplicates if run multiple times
        $this->table('cbos')->truncate();

        $url = 'https://raw.githubusercontent.com/datasets-br/cbo/master/data/lista.csv';

        // Ensure we have SSL context options if needed, though default usually works
        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: PHP\r\n"
            ]
        ]);

        $handle = fopen($url, 'r', false, $context);
        if ($handle === false) {
            echo "Failed to download CBO list.\n";
            return;
        }

        $header = fgetcsv($handle); // Skip header: codigo,termo,tipo

        $data = [];
        $count = 0;
        $batchSize = 1000;
        $now = date('Y-m-d H:i:s');

        while (($row = fgetcsv($handle)) !== false) {
            // row[0] = codigo, row[1] = termo
            if (count($row) < 2) {
                continue;
            }

            // Filter logic: only codes with more than 4 digits/characters
            if (strlen($row[0]) <= 4) {
                continue;
            }

            $data[] = [
                'code' => $row[0],
                'description' => $row[1],
                'created' => $now,
                'modified' => $now,
            ];

            if (count($data) >= $batchSize) {
                $this->table('cbos')->insert($data)->save();
                $data = [];
                $count += $batchSize;
                echo "Inserted $count records...\n";
            }
        }

        if (!empty($data)) {
            $this->table('cbos')->insert($data)->save();
            echo "Inserted remaining " . count($data) . " records.\n";
        }

        fclose($handle);
        echo "CBO Seeding completed.\n";
    }
}
