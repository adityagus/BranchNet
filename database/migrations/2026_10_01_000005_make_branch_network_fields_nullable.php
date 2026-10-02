<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->string('network_cidr', 43)->nullable()->change();
            $table->ipAddress('gateway_ip')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->string('network_cidr', 43)->nullable(false)->change();
            $table->ipAddress('gateway_ip')->nullable(false)->change();
        });
    }
};
