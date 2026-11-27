<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** "My Job Applications" menu (route "my-applications") for DEF-USERS. Data only, no schema. */
return new class extends Migration {
    public function up(): void
    {
        $menuId = DB::table('menus')->where('routes', 'my-applications')->value('id');
        if (!$menuId) {
            $menuId = DB::table('menus')->insertGetId([
                'transNo' => (int) DB::table('menus')->max('transNo') + 1, 'desc_code' => 'top_navigation', 'description' => 'My Job Applications',
                'icon' => 'work_history', 'class' => '-', 'routes' => 'my-applications', 'sort' => 4, 'status' => 'A',
                'created_by' => 'system', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        if (!DB::table('roleaccessmenus')->where('rolecode', 'DEF-USERS')->where('menus_id', $menuId)->exists()) {
            DB::table('roleaccessmenus')->insert(['rolecode' => 'DEF-USERS', 'transNo' => (int) DB::table('roleaccessmenus')->max('transNo') + 1,
                'menus_id' => $menuId, 'created_by' => 'system', 'created_at' => now(), 'updated_at' => now()]);
        }
        Cache::flush();
    }

    public function down(): void
    {
        $menuId = DB::table('menus')->where('routes', 'my-applications')->value('id');
        if ($menuId) {
            DB::table('roleaccessmenus')->where('menus_id', $menuId)->delete();
            DB::table('menus')->where('id', $menuId)->delete();
        }
        Cache::flush();
    }
};
