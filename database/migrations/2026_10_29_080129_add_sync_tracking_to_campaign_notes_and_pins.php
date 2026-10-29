<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->unsignedBigInteger('sync_version')->default(0);
            $table->unsignedInteger('sync_generation')->default(0);
        });

        Schema::table('session_notes', function (Blueprint $table): void {
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('sync_version')->default(0);
        });

        Schema::table('map_pins', function (Blueprint $table): void {
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('sync_version')->default(0);
        });

        Schema::create('campaign_sync_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('version');
            $table->string('entity_type', 20);
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('campaign_map_id')->nullable();
            $table->unsignedBigInteger('previous_campaign_map_id')->nullable();
            $table->boolean('deleted')->default(false);
            $table->json('payload');
            $table->timestamps();

            $table->unique(['campaign_id', 'version']);
            $table->index(['campaign_id', 'entity_type', 'version'], 'sync_events_type_version_idx');
            $table->index(['campaign_id', 'campaign_map_id', 'version'], 'sync_events_map_version_idx');
            $table->index(
                ['campaign_id', 'previous_campaign_map_id', 'version'],
                'sync_events_previous_map_version_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_sync_events');

        Schema::table('map_pins', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn('sync_version');
        });

        Schema::table('session_notes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn('sync_version');
        });

        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropColumn(['sync_version', 'sync_generation']);
        });
    }
};
