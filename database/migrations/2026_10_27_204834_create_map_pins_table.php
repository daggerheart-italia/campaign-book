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
        Schema::create('map_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_map_id')->nullable()->constrained('campaign_maps')->cascadeOnDelete();
            $table->foreignId('wiki_page_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('x', 8, 4)->default(50.0); // percentage X (0.0000 - 100.0000)
            $table->decimal('y', 8, 4)->default(50.0); // percentage Y (0.0000 - 100.0000)
            $table->string('color')->default('#e11d48');
            $table->string('icon')->default('map-pin');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_pins');
    }
};
