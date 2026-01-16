<?php

namespace EduardoRibeiroDev\BasePolicies\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void {
        Artisan::call('generate:permissions');
    }
}
