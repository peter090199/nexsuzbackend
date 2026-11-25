<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contact Inquiries: messages from the public Contact Us form plus the replies sent from Nexsuz, so a whole email
 * conversation is kept in one place. Nothing existed for this (the form only emailed the business). Also adds the
 * "Contact Inquiries" menu and gives it to Master Admin and Admin (change it in Security Roles).
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('contact_inquiries')) {
            Schema::create('contact_inquiries', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->id();
                $t->string('inquiry_no', 20)->nullable()->unique();
                $t->string('name', 120);
                $t->string('email', 190)->index();
                $t->string('phone', 30)->nullable();
                $t->string('subject', 150);
                $t->text('message');
                $t->string('status', 12)->default('new')->index();      // new | read | replied | closed
                $t->timestamp('read_at')->nullable()->index();          // null = unread (independent of replied / closed)
                $t->string('assigned_code', 40)->nullable();            // administrator handling it
                $t->timestamp('archived_at')->nullable()->index();
                $t->string('archived_by', 40)->nullable();
                $t->timestamp('notified_at')->nullable();               // the "new inquiry" email reached the business inbox
                $t->text('notify_error')->nullable();
                $t->string('ip', 45)->nullable();
                $t->timestamp('last_activity_at')->nullable()->index();
                $t->timestamps();
            });
        }
        if (!Schema::hasTable('contact_inquiry_messages')) {
            Schema::create('contact_inquiry_messages', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->id();
                $t->unsignedBigInteger('inquiry_id')->index();
                $t->foreign('inquiry_id')->references('id')->on('contact_inquiries')->cascadeOnDelete();
                $t->string('direction', 3);                              // in (visitor) | out (administrator reply)
                $t->string('from_email', 190);
                $t->string('from_name', 120)->nullable();
                $t->string('to_email', 190);
                $t->string('subject', 190);
                $t->longText('body');                                    // visitor: plain text; reply: sanitised HTML
                $t->boolean('is_html')->default(false);
                $t->string('status', 10)->default('received');          // received | sent | failed
                $t->text('error')->nullable();
                $t->string('sent_by_code', 40)->nullable();
                $t->json('attachments')->nullable();                     // [{name,size,mime,path}] on the private disk
                $t->timestamps();
            });
        }

        // menu entry (top level, material icon "mail")
        $menuId = DB::table('menus')->where('routes', 'contactinquiries')->value('id');
        if (!$menuId) {
            $menuId = DB::table('menus')->insertGetId([
                'transNo' => (int) DB::table('menus')->max('transNo') + 1, 'desc_code' => 'top_navigation', 'description' => 'Contact Inquiries', 'icon' => 'mail',
                'class' => '-', 'routes' => 'contactinquiries', 'sort' => 14, 'status' => 'A', 'created_by' => 'system', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        foreach (['DEF-MASTERADMIN', 'DEF-ADMIN'] as $role) {
            if (!DB::table('roleaccessmenus')->where('rolecode', $role)->where('menus_id', $menuId)->exists()) {
                DB::table('roleaccessmenus')->insert(['rolecode' => $role, 'transNo' => (int) DB::table('roleaccessmenus')->max('transNo') + 1, 'menus_id' => $menuId,
                    'created_by' => 'system', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        Cache::flush();
    }

    public function down(): void
    {
        $menuId = DB::table('menus')->where('routes', 'contactinquiries')->value('id');
        if ($menuId) {
            DB::table('roleaccessmenus')->where('menus_id', $menuId)->delete();
            DB::table('menus')->where('id', $menuId)->delete();
        }
        Schema::dropIfExists('contact_inquiry_messages');
        Schema::dropIfExists('contact_inquiries');
    }
};
