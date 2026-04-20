<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tenantKey = (string) config('filament-chat-widget.tenant_foreign_key', 'team_id');

        Schema::create('chat_widgets', function (Blueprint $table) use ($tenantKey): void {
            $table->id();
            $table->unsignedBigInteger($tenantKey)->nullable()->unique();
            $table->string('title')->default('Chat');
            $table->text('welcome_message')->nullable();
            $table->string('color')->default('#6366f1');
            $table->string('position')->default('bottom-right');
            $table->boolean('is_active')->default(true);
            $table->text('offline_message')->nullable();
            $table->json('business_hours')->nullable();
            $table->text('auto_reply_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_widgets');
    }
};
