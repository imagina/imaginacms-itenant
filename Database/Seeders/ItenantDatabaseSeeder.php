<?php

namespace Modules\Itenant\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

use Modules\Isite\Jobs\ProcessSeeds;

class ItenantDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();
        ProcessSeeds::dispatch([
            'baseClass' => "\Modules\Itenant\Database\Seeders",
            'seeds' => ['ItenantModuleTableSeeder'],
        ]);
    }
}
