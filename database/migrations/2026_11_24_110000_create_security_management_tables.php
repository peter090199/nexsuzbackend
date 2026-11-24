<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Role & permission management.
 *  - role_permissions: the Add / Edit / Delete / Approve / Manage flags of a role on a menu or submenu. "View" is not
 *    stored here: it is the existing menu access (roleaccessmenus / roleaccesssubmenus), so there is one source of truth.
 *  - security_audit_logs: role created / edited / deleted, role assigned / removed, permissions changed.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->id();
                $t->string('rolecode', 64)->index();
                $t->string('item_type', 8);                 // menu | submenu
                $t->unsignedBigInteger('item_id');
                $t->boolean('can_add')->default(false);
                $t->boolean('can_edit')->default(false);
                $t->boolean('can_delete')->default(false);
                $t->boolean('can_approve')->default(false);
                $t->boolean('can_manage')->default(false);
                $t->string('updated_by')->nullable();
                $t->timestamps();
                $t->unique(['rolecode', 'item_type', 'item_id']);
            });
        }
        if (!Schema::hasTable('security_audit_logs')) {
            Schema::create('security_audit_logs', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->id();
                $t->string('action', 32)->index();          // role.create | role.update | role.delete | role.assign | role.remove | permission.update
                $t->string('actor_code', 32)->index();
                $t->string('actor_name')->nullable();
                $t->string('target_type', 16);              // role | user | permission
                $t->string('target_ref', 64)->nullable();   // role code or user code
                $t->string('summary');
                $t->json('details')->nullable();
                $t->string('ip', 45)->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_logs');
        Schema::dropIfExists('role_permissions');
    }
};
