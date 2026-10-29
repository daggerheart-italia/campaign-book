<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MapPin extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'campaign_id',
        'campaign_map_id',
        'wiki_page_id',
        'created_by',
        'title',
        'description',
        'x',
        'y',
        'color',
        'icon',
        'locked',
        'updated_by',
        'sync_version',
    ];

    protected function casts(): array
    {
        return [
            'campaign_map_id' => 'integer',
            'wiki_page_id' => 'integer',
            'x' => 'float',
            'y' => 'float',
            'locked' => 'boolean',
            'sync_version' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function campaignMap(): BelongsTo
    {
        return $this->belongsTo(CampaignMap::class, 'campaign_map_id');
    }

    public function wikiPage(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
