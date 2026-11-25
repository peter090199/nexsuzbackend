<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Menu entry for the Nexsuz Information page (route "nexsuzinfo") under System, given to the Master Admin.
 * Admins get it the usual way: Security Roles -> assign the submenu. The data itself needs no new table (app_settings).
 */
return new class extends Migration {
    public function up(): void
    {
        $id = DB::table('submenus')->where('routes', 'nexsuzinfo')->value('id');
        if (!$id) {
            $id = DB::table('submenus')->insertGetId([
                'transNo' => 2, 'desc_code' => 'top_navigation', 'description' => 'Nexsuz Information', 'icon' => 'business', 'class' => '-',
                'routes' => 'nexsuzinfo', 'sort' => 5, 'status' => 'A', 'created_by' => 'system', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $parent = DB::table('roleaccessmenus')->where('rolecode', 'DEF-MASTERADMIN')->where('menus_id', 2)->value('transNo');
        if ($parent && !DB::table('roleaccesssubmenus')->where('rolecode', 'DEF-MASTERADMIN')->where('submenus_id', $id)->exists()) {
            DB::table('roleaccesssubmenus')->insert(['rolecode' => 'DEF-MASTERADMIN', 'transNo' => $parent, 'submenus_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
        }
        Cache::flush();
    }

    public function down(): void
    {
        $id = DB::table('submenus')->where('routes', 'nexsuzinfo')->value('id');
        if ($id) {
            DB::table('roleaccesssubmenus')->where('submenus_id', $id)->delete();
            DB::table('submenus')->where('id', $id)->delete();
        }
    }
};
