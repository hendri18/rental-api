<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin123')
        ]);

        $role = Role::where('name', 'admin')->first();
        if (!$role) {
            $role = Role::create(['name' => 'admin']);
        }

        $user->assignRole($role);
    }
}
