<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class NewPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name'=>'manage-role-parent', 'guard_name' => 'api']);
        Permission::create(['name'=>'manage-admin', 'guard_name' => 'api']);
        Permission::create(['name'=>'settlement-account', 'guard_name' => 'api']);
        Permission::create(['name'=>'manage-global-services', 'guard_name' => 'api']);
        Permission::create(['name'=>'manage-operators', 'guard_name' => 'api']);
        Permission::create(['name'=>'manage-operator-categories', 'guard_name' => 'api']);
        Permission::create(['name'=>'manage-cms-billers', 'guard_name' => 'api']);
        Permission::create(['name'=>'manage-banks', 'guard_name' => 'api']);
        Permission::create(['name'=>'preferences', 'guard_name' => 'api']);
        Permission::create(['name'=>'all-organizations', 'guard_name' => 'api']);
        Permission::create(['name'=>'create-whitelabel', 'guard_name' => 'api']);
        Permission::create(['name'=>'report-aeps', 'guard_name' => 'api']);
        Permission::create(['name'=>'report-bbps', 'guard_name' => 'api']);
        Permission::create(['name'=>'report-dmt', 'guard_name' => 'api']);
        Permission::create(['name'=>'report-recharge', 'guard_name' => 'api']);
        Permission::create(['name'=>'report-payout', 'guard_name' => 'api']);
        Permission::create(['name'=>'report-cms', 'guard_name' => 'api']);
        Permission::create(['name'=>'report-fund-request', 'guard_name' => 'api']);
        Permission::create(['name'=>'report-fund-transfer', 'guard_name' => 'api']);
        Permission::create(['name'=>'transaction-ledger', 'guard_name' => 'api']);
        Permission::create(['name'=>'daily-sales', 'guard_name' => 'api']);
        Permission::create(['name'=>'live-sales', 'guard_name' => 'api']);
        Permission::create(['name'=>'user-ledger', 'guard_name' => 'api']);
        Permission::create(['name'=>'login-reports', 'guard_name' => 'api']);
    }
}
