<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chat_conversations')) {
            return;
        }

        $tenantKey = (string) config('filament-chat-widget.tenant_foreign_key', 'team_id');

        Schema::create('chat_conversations', function (Blueprint $table) use ($tenantKey): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger($tenantKey)->nullable();
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->string('visitor_ip')->nullable();
            $table->string('status')->default('open');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->integer('unread_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index([$tenantKey, 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
