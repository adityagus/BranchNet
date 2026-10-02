<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('city');
            $table->string('network_cidr', 43);
            $table->ipAddress('gateway_ip');
            $table->string('agent_token')->nullable();
            $table->timestamp('agent_last_seen')->nullable();
            $table->enum('status', ['healthy', 'warning', 'critical', 'unreachable'])->default('unreachable')->index();
            $table->timestamps();
        });

        Schema::create('network_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('hostname')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('token')->unique();
            $table->string('version')->nullable();
            $table->enum('status', ['active', 'offline'])->default('offline')->index();
            $table->timestamp('last_seen')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('network_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->ipAddress('ip_address');
            $table->string('mac_address')->nullable();
            $table->string('hostname')->nullable();
            $table->string('device_name')->default('Unknown Device');
            $table->enum('device_type', ['router', 'switch', 'access_point', 'server', 'cctv', 'printer', 'pc', 'laptop', 'pos', 'unknown'])->default('unknown');
            $table->string('vendor')->nullable();
            $table->text('description')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->boolean('is_registered')->default(false)->index();
            $table->enum('status', ['online', 'offline', 'warning', 'unknown'])->default('unknown')->index();
            $table->decimal('latency_ms', 10, 2)->nullable();
            $table->decimal('packet_loss', 5, 2)->nullable();
            $table->timestamp('last_seen')->nullable()->index();
            $table->timestamps();
            $table->unique(['branch_id', 'ip_address']);
            $table->index(['branch_id', 'status']);
        });

        Schema::create('monitoring_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('network_devices')->nullOnDelete();
            $table->ipAddress('ip_address');
            $table->string('status', 20);
            $table->decimal('latency_ms', 10, 2)->nullable();
            $table->decimal('packet_loss', 5, 2)->nullable();
            $table->timestamp('checked_at')->index();
            $table->index(['branch_id', 'device_id', 'ip_address', 'checked_at']);
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('network_devices')->nullOnDelete();
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->enum('status', ['active', 'resolved'])->default('active')->index();
            $table->timestamps();
            $table->index(['branch_id', 'device_id', 'type', 'status']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->nullableMorphs('subject');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('monitoring_logs');
        Schema::dropIfExists('network_devices');
        Schema::dropIfExists('network_agents');
        Schema::dropIfExists('branches');
    }
};
