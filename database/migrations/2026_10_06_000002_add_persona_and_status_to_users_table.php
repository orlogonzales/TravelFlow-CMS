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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('persona_id')
                ->nullable()
                ->after('id')
                ->constrained('personas')
                ->nullOnDelete();
            $table->string('status', 20)
                ->default('active')
                ->after('password')
                ->index();
            $table->timestamp('last_login_at')
                ->nullable()
                ->after('remember_token');
            $table->string('last_login_ip', 45)
                ->nullable()
                ->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['persona_id']);
            $table->dropColumn(['persona_id', 'status', 'last_login_at', 'last_login_ip']);
        });
    }
};
