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
        Schema::table('wiki_pages', function (Blueprint $table): void {
            $table->index(['campaign_id', 'sync_version', 'id'], 'wiki_pages_campaign_sync_idx');
        });

        Schema::table('session_notes', function (Blueprint $table): void {
            $table->index(['campaign_id', 'sync_version', 'id'], 'session_notes_campaign_sync_idx');
        });

        Schema::table('map_pins', function (Blueprint $table): void {
            $table->index(['campaign_id', 'sync_version', 'id'], 'map_pins_campaign_sync_idx');
        });

        Schema::table('campaign_maps', function (Blueprint $table): void {
            $table->index(['campaign_id', 'sync_version', 'id'], 'campaign_maps_campaign_sync_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_maps', function (Blueprint $table): void {
            $table->dropIndex('campaign_maps_campaign_sync_idx');
        });

        Schema::table('map_pins', function (Blueprint $table): void {
            $table->dropIndex('map_pins_campaign_sync_idx');
        });

        Schema::table('session_notes', function (Blueprint $table): void {
            $table->dropIndex('session_notes_campaign_sync_idx');
        });

        Schema::table('wiki_pages', function (Blueprint $table): void {
            $table->dropIndex('wiki_pages_campaign_sync_idx');
        });
    }
};
