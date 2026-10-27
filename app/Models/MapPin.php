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
    ];

    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'locked' => 'boolean',
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
}
