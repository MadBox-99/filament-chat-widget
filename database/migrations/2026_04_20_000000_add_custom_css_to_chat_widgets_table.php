<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('chat_widgets', 'custom_css')) {
            return;
        }

        Schema::table('chat_widgets', function (Blueprint $table): void {
            $table->text('custom_css')->nullable()->after('auto_reply_message');
        });
    }

    public function down(): void
    {
        Schema::table('chat_widgets', function (Blueprint $table): void {
            $table->dropColumn('custom_css');
        });
    }
};
