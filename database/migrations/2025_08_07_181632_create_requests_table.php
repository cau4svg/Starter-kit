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
        Schema::create('requests', function (Blueprint $table) {
            $table->uuid("id")->primary();
             $table->string("ip");
             $table->enum('type',["post","get"]);
             $table->string("endpoint");
             $table->longText("request");
             $table->longText("response");
             $table->string("status");
             $table->double("ammount",10,2);
             $table->foreignUuid('price_id')->nullable()->constrained('prices')->onDelete('cascade');
             $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
