<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('chat_widgets', 'opening_hours')) {
            return;
        }

        Schema::table('chat_widgets', function (Blueprint $table): void {
            $table->json('opening_hours')->nullable()->after('business_hours');
            $table->string('timezone')->nullable()->after('opening_hours');
        });
    }

    public function down(): void
    {
        Schema::table('chat_widgets', function (Blueprint $table): void {
            $table->dropColumn(['opening_hours', 'timezone']);
        });
    }
};
