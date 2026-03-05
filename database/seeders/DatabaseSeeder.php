<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting database seeding...');
        $this->command->info('');

        $this->call([
            // 1. Create roles and permissions first
            RolesAndPermissionsSeeder::class,
            
            // 2. Create departments
            DepartmentSeeder::class,
            
            // 3. Create users with roles (depends on roles and departments)
            UserSeeder::class,
            
            // 4. Create CAPA areas (depends on departments and users)
            CapaAreaSeeder::class,

            
            // 5. Other seeders
            //DashboardTableSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('✓ Database seeding completed successfully!');
        $this->command->info('========================================');
    }
}
