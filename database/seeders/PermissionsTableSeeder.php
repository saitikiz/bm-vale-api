<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            [
                'id'    => 1,
                'title' => 'user_management_access',
            ],
            [
                'id'    => 2,
                'title' => 'permission_create',
            ],
            [
                'id'    => 3,
                'title' => 'permission_edit',
            ],
            [
                'id'    => 4,
                'title' => 'permission_show',
            ],
            [
                'id'    => 5,
                'title' => 'permission_delete',
            ],
            [
                'id'    => 6,
                'title' => 'permission_access',
            ],
            [
                'id'    => 7,
                'title' => 'role_create',
            ],
            [
                'id'    => 8,
                'title' => 'role_edit',
            ],
            [
                'id'    => 9,
                'title' => 'role_show',
            ],
            [
                'id'    => 10,
                'title' => 'role_delete',
            ],
            [
                'id'    => 11,
                'title' => 'role_access',
            ],
            [
                'id'    => 12,
                'title' => 'user_create',
            ],
            [
                'id'    => 13,
                'title' => 'user_edit',
            ],
            [
                'id'    => 14,
                'title' => 'user_show',
            ],
            [
                'id'    => 15,
                'title' => 'user_delete',
            ],
            [
                'id'    => 16,
                'title' => 'user_access',
            ],
            [
                'id'    => 17,
                'title' => 'audit_log_show',
            ],
            [
                'id'    => 18,
                'title' => 'audit_log_access',
            ],
            [
                'id'    => 19,
                'title' => 'user_alert_create',
            ],
            [
                'id'    => 20,
                'title' => 'user_alert_show',
            ],
            [
                'id'    => 21,
                'title' => 'user_alert_delete',
            ],
            [
                'id'    => 22,
                'title' => 'user_alert_access',
            ],
            [
                'id'    => 23,
                'title' => 'site_create',
            ],
            [
                'id'    => 24,
                'title' => 'site_edit',
            ],
            [
                'id'    => 25,
                'title' => 'site_show',
            ],
            [
                'id'    => 26,
                'title' => 'site_delete',
            ],
            [
                'id'    => 27,
                'title' => 'site_access',
            ],
            [
                'id'    => 28,
                'title' => 'worker_create',
            ],
            [
                'id'    => 29,
                'title' => 'worker_edit',
            ],
            [
                'id'    => 30,
                'title' => 'worker_show',
            ],
            [
                'id'    => 31,
                'title' => 'worker_delete',
            ],
            [
                'id'    => 32,
                'title' => 'worker_access',
            ],
            [
                'id'    => 33,
                'title' => 'bonus_request_create',
            ],
            [
                'id'    => 34,
                'title' => 'bonus_request_edit',
            ],
            [
                'id'    => 35,
                'title' => 'bonus_request_show',
            ],
            [
                'id'    => 36,
                'title' => 'bonus_request_delete',
            ],
            [
                'id'    => 37,
                'title' => 'bonus_request_access',
            ],
            [
                'id'    => 38,
                'title' => 'player_create',
            ],
            [
                'id'    => 39,
                'title' => 'player_edit',
            ],
            [
                'id'    => 40,
                'title' => 'player_show',
            ],
            [
                'id'    => 41,
                'title' => 'player_delete',
            ],
            [
                'id'    => 42,
                'title' => 'player_access',
            ],
            [
                'id'    => 43,
                'title' => 'bonu_create',
            ],
            [
                'id'    => 44,
                'title' => 'bonu_edit',
            ],
            [
                'id'    => 45,
                'title' => 'bonu_show',
            ],
            [
                'id'    => 46,
                'title' => 'bonu_delete',
            ],
            [
                'id'    => 47,
                'title' => 'bonu_access',
            ],
            [
                'id'    => 48,
                'title' => 'profile_password_edit',
            ],
        ];

        Permission::insert($permissions);
    }
}
