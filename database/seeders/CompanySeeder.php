<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $companies = [
            ['title' => 'SCID', 'isClient' => false],
            ['title' => 'JetCase', 'isClient' => false],
        ];

        foreach ($companies as $company) {
            \App\Models\Company::create($company);
        }
    }
}
