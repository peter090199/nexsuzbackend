<?php

use App\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * "Google Translate" menu (route "translationplans") for DEF-ADMIN, and Google's published Cloud Translation price
 * as the starting configuration. Data only, no schema. The plan stays Free and no budget is chosen here.
 *
 * Price read on 2026-11-27 from https://cloud.google.com/translate : first 500,000 characters a month free, then
 * USD 20 per million (standard NMT). Administrators change it on the page; saved settings are never overwritten.
 */
return new class extends Migration {
    public function up(): void
    {
        $menuId = DB::table('menus')->where('routes', 'translationplans')->value('id');
        if (!$menuId) {
            $menuId = DB::table('menus')->insertGetId([
                'transNo' => (int) DB::table('menus')->max('transNo') + 1, 'desc_code' => 'top_navigation', 'description' => 'Google Translate',
                'icon' => 'translate', 'class' => '-', 'routes' => 'translationplans', 'sort' => 14, 'status' => 'A',
                'created_by' => 'system', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        if (!DB::table('roleaccessmenus')->where('rolecode', 'DEF-ADMIN')->where('menus_id', $menuId)->exists()) {
            DB::table('roleaccessmenus')->insert(['rolecode' => 'DEF-ADMIN', 'transNo' => (int) DB::table('roleaccessmenus')->max('transNo') + 1,
                'menus_id' => $menuId, 'created_by' => 'system', 'created_at' => now(), 'updated_at' => now()]);
        }

        $config = AppSetting::read('i18n.google.config') ?: [];
        if (!isset($config['price_per_million'])) {
            AppSetting::write('i18n.google.config', array_merge($config, [
                'currency' => 'USD', 'price_per_million' => 20, 'free_allowance' => 500000,
                'pricing_url' => 'https://cloud.google.com/translate/pricing',
            ]));
        }
        Cache::flush();
    }

    /** removes the menu; the pricing settings are the administrator's and stay */
    public function down(): void
    {
        $menuId = DB::table('menus')->where('routes', 'translationplans')->value('id');
        if ($menuId) {
            DB::table('roleaccessmenus')->where('menus_id', $menuId)->delete();
            DB::table('menus')->where('id', $menuId)->delete();
        }
        Cache::flush();
    }
};
