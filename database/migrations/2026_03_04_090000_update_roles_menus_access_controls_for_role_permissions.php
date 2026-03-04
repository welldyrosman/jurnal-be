<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('roles', 'code')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('code')->nullable()->unique()->after('name');
            });
        }

        if (!Schema::hasColumn('menus', 'code')) {
            Schema::table('menus', function (Blueprint $table) {
                $table->string('code')->nullable()->unique()->after('name');
            });
        }



        if (Schema::hasColumn('access_controls', 'user_id')) {
            Schema::table('access_controls', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        if (!Schema::hasColumn('access_controls', 'role_id')) {
            Schema::table('access_controls', function (Blueprint $table) {
                $table->foreignId('role_id')->after('menu_id')->constrained('roles')->onDelete('cascade');
            });
        }



        Schema::table('access_controls', function (Blueprint $table) {
            $table->unique(['role_id', 'menu_id'], 'access_controls_role_menu_permission_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {






        if (Schema::hasColumn('menus', 'code')) {
            Schema::table('menus', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }

        if (Schema::hasColumn('roles', 'code')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }
    }
};
