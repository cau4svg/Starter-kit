<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->longText('bearer_apibrasil')->nullable()->after('password');
            $table->boolean('is_admin')->default(false)->after('bearer_apibrasil');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bearer_apibrasil', 'is_admin']);
        });
    }
};
