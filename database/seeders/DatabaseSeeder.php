<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Models\NetworkAgent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'super_admin',
        ]);

        foreach ([['PKU', 'Pekanbaru', 'Pekanbaru', '192.168.10.0/24', '192.168.10.1'], ['BTM', 'Batam', 'Batam', '192.168.20.0/24', '192.168.20.1'], ['MDN', 'Medan', 'Medan', '192.168.30.0/24', '192.168.30.1']] as [$code, $name, $city, $cidr, $gateway]) {
            $branch = Branch::create(['code' => $code, 'name' => $name, 'city' => $city, 'network_cidr' => $cidr, 'gateway_ip' => $gateway, 'status' => 'unreachable']);
            NetworkAgent::create(['branch_id' => $branch->id, 'name' => $name.' monitoring agent', 'token' => Hash::make(Str::random(40)), 'status' => 'offline']);
        }
    }
}
