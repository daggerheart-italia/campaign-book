<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_maps', function (Blueprint $table): void {
            $table->unsignedBigInteger('sync_version')->default(0)->after('is_default');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_maps', function (Blueprint $table): void {
            $table->dropColumn(['sync_version', 'updated_by']);
        });
    }
};
