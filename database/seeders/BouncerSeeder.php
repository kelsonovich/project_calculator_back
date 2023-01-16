<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Silber\Bouncer\Bouncer;

class BouncerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Bouncer::allow('admin')->everything();
        Bouncer::forbid('admin')->toManage(User::class);

        Bouncer::allow('manager')->to('view', Project::class);
        Bouncer::allow('manager')->to('create', Project::class);
        Bouncer::allow('manager')->to('update', Project::class);
        Bouncer::allow('manager')->to('delete', Project::class);
        Bouncer::allow('manager')->toOwn(Project::class);
    }
}
