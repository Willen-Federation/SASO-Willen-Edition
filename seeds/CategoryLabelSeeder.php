<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class CategoryLabelSeeder extends AbstractSeed
{
    public function run(): void
    {
        // Default Categories (Legacy)
        $categories = [
            ['categoryId' => 1, 'categoryName' => 'General',      'categoryLeft' => 1, 'categoryRight' => 2],
            ['categoryId' => 2, 'categoryName' => 'Raw Materials', 'categoryLeft' => 3, 'categoryRight' => 4],
            ['categoryId' => 3, 'categoryName' => 'Finished Goods','categoryLeft' => 5, 'categoryRight' => 6],
            ['categoryId' => 4, 'categoryName' => 'Tools',         'categoryLeft' => 7, 'categoryRight' => 8],
        ];

        $table = $this->table('Category');
        foreach ($categories as $cat) {
            $exists = $this->fetchRow(sprintf("SELECT categoryId FROM Category WHERE categoryId = %d", $cat['categoryId']));
            if (!$exists) {
                $table->insert($cat)->saveData();
            }
        }

        // Default Labels (Legacy)
        $labels = [
            [
                'labelName' => 'Standard_A4_2x6',
                'marginTop' => 21.2,
                'marginLeft' => 14.8,
                'width' => 90.2,
                'height' => 42.3,
                'intervalColomn' => 0.0,
                'intervalRow' => 0.0,
            ],
            [
                'labelName' => 'Standard_A4_2x5',
                'marginTop' => 37.25,
                'marginLeft' => 8.5,
                'width' => 96.5,
                'height' => 44.5,
                'intervalColomn' => 0.0,
                'intervalRow' => 0.0,
            ],
        ];

        $tableLabel = $this->table('Label');
        foreach ($labels as $lbl) {
            $exists = $this->fetchRow(sprintf("SELECT labelName FROM Label WHERE labelName = '%s'", $lbl['labelName']));
            if (!$exists) {
                $tableLabel->insert($lbl)->saveData();
            }
        }
    }
}
