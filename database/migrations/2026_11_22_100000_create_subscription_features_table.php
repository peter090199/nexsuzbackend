<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Feature catalog for the master-admin Subscription Management pages.
 *
 * Plans (userplanheader), per-plan feature rows (userplandetails) and subscriptions (usersubscription)
 * already exist and are what the app enforces, so they are NOT changed. This table only adds the catalog
 * metadata the UI needs (category, icon, description, enabled flag, toggle vs limit). `feature_code` is the
 * immutable key that joins the catalog to userplandetails.feature_code.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('subscription_features')) {
            return;
        }

        Schema::create('subscription_features', function (Blueprint $table) {
            $table->engine = "InnoDB";
            $table->id();
            $table->string('feature_code', 100)->unique();      // immutable key
            $table->string('name', 150);
            $table->string('category', 50)->default('General');
            $table->string('icon', 50)->default('star');
            $table->string('description', 255)->nullable();
            $table->enum('value_type', ['toggle', 'limit'])->default('toggle');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed the catalog from the feature codes plans already use.
        $rows = DB::table('userplandetails')
            ->whereNotNull('feature_code')->where('feature_code', '<>', '')
            ->select('feature_code', DB::raw('MIN(feature_name) as feature_name'), DB::raw('MAX(feature_value) as sample'))
            ->groupBy('feature_code')
            ->get();

        $now = now();
        $insert = [];
        foreach ($rows as $r) {
            $code = strtoupper(trim($r->feature_code));
            $sample = strtoupper((string) $r->sample);
            $isLimit = str_ends_with($code, '_LIMIT') || is_numeric($r->sample) || $sample === 'UNLIMITED';
            $insert[$code] = [
                'feature_code' => $code,
                'name'         => $r->feature_name ?: ucwords(strtolower(str_replace('_', ' ', $code))),
                'category'     => $this->guessCategory($code),
                'icon'         => 'star',
                'description'  => null,
                'value_type'   => $isLimit ? 'limit' : 'toggle',
                'is_active'    => true,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }
        if ($insert) {
            DB::table('subscription_features')->insert(array_values($insert));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_features');
    }

    private function guessCategory(string $code): string
    {
        return match (true) {
            str_contains($code, 'JOB'), str_contains($code, 'APPLY'), str_contains($code, 'APPLICAN') => 'Jobs',
            str_contains($code, 'CONNECT'), str_contains($code, 'NETWORK'), str_contains($code, 'MESSAG') => 'Networking',
            str_contains($code, 'ANALYT'), str_contains($code, 'INSIGHT'), str_contains($code, 'VIEWED') => 'Analytics',
            str_contains($code, 'SUPPORT') => 'Support',
            str_contains($code, 'PROFILE'), str_contains($code, 'BADGE'), str_contains($code, 'RESUME') => 'Profile',
            default => 'General',
        };
    }
};
