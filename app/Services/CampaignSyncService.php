<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\MapPin;
use App\Models\SessionNote;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class CampaignSyncService
{
    public const PAGE_SIZE = 100;

    /**
     * Record a note or pin mutation. Call inside the same transaction as the mutation.
     */
    public function record(
        Campaign $campaign,
        Model $entity,
        User $user,
        bool $deleted = false,
        ?int $previousMapId = null
    ): void {
        $lockedCampaign = Campaign::query()->whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();
        $version = $lockedCampaign->sync_version + 1;
        $lockedCampaign->sync_version = $version;
        $lockedCampaign->save();

        $entity->forceFill([
            'updated_by' => $user->id,
            'sync_version' => $version,
        ])->save();

        $entityType = match (true) {
            $entity instanceof SessionNote => 'note',
            $entity instanceof MapPin => 'pin',
            default => throw new \InvalidArgumentException('Unsupported campaign sync entity.'),
        };

        $payload = $entity instanceof SessionNote
            ? [
                'id' => $entity->getKey(),
                'title' => $entity->title,
                'session_date' => $entity->session_date?->toDateString(),
                'session_date_label' => $entity->session_date?->format('d/m/Y') ?? $entity->created_at?->format('d/m/Y'),
                'is_pinned' => $entity->is_pinned,
                'content' => $entity->content,
                'content_html' => $campaign->renderMarkdown($entity->content),
                'author_name' => $entity->user?->name,
                'created_at' => $entity->created_at?->toIso8601String(),
                'updated_at' => $entity->updated_at?->toIso8601String(),
                'updated_by' => $user
                    ? ['id' => $user->id, 'name' => $user->name]
                    : ($pin->editor ? ['id' => $pin->editor->id, 'name' => $pin->editor->name] : null),
                'sync_version' => $version,
                'deleted' => $deleted,
            ]
            : $this->pinPayload($campaign, $entity, $user, $version, $deleted);

        DB::table('campaign_sync_events')->insert([
            'campaign_id' => $campaign->getKey(),
            'version' => $version,
            'entity_type' => $entityType,
            'entity_id' => $entity->getKey(),
            'campaign_map_id' => $entity instanceof MapPin ? $entity->campaign_map_id : null,
            'previous_campaign_map_id' => $previousMapId,
            'deleted' => $deleted,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pinPayload(Campaign $campaign, MapPin $pin, ?User $user, int $version, bool $deleted): array
    {
        $pin->load('wikiPage');

        return [
            'id' => $pin->getKey(),
            'campaign_map_id' => $pin->campaign_map_id,
            'title' => $pin->title,
            'description' => $pin->description,
            'x' => (float) $pin->x,
            'y' => (float) $pin->y,
            'color' => $pin->color,
            'locked' => (bool) $pin->locked,
            'wiki_page_id' => $pin->wiki_page_id,
            'wiki_page_title' => $pin->wikiPage?->title,
            'wiki_page_url' => $pin->wikiPage
                ? route('wiki.show', [$campaign, $pin->wikiPage])
                : null,
            'updated_at' => $pin->updated_at?->toIso8601String(),
            'updated_by' => ['id' => $user->id, 'name' => $user->name],
            'sync_version' => $version,
            'deleted' => $deleted,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(
        Campaign $campaign,
        string $entityType,
        ?string $cursor = null,
        ?int $mapId = null
    ): array {
        if (! in_array($entityType, ['note', 'pin'], true)) {
            abort(422, 'Il tipo di snapshot non è valido.');
        }

        if ($cursor === null) {
            $generation = (int) $campaign->sync_generation;
            $snapshotVersion = (int) $campaign->sync_version;
            $lastId = 0;
        } else {
            $snapshot = $this->decodeSnapshotCursor($cursor, (int) $campaign->getKey(), $entityType, $mapId);
            $generation = $snapshot['generation'];
            $snapshotVersion = $snapshot['snapshot_version'];
            $lastId = $snapshot['last_id'];
        }

        if ($generation !== $campaign->sync_generation) {
            return ['items' => [], 'next_cursor' => null, 'delta_cursor' => null, 'has_more' => false, 'reset_required' => true];
        }

        $query = $entityType === 'note'
            ? $campaign->sessionNotes()->with(['user', 'editor'])
            : $campaign->mapPins()->with(['wikiPage', 'editor']);
        $query->where('sync_version', '<=', $snapshotVersion)
            ->where('id', '>', $lastId)
            ->orderBy('id');

        if ($entityType === 'pin' && $mapId !== null) {
            $query->where('campaign_map_id', $mapId);
        }

        $records = $query->limit(self::PAGE_SIZE + 1)->get();
        $hasMore = $records->count() > self::PAGE_SIZE;
        $page = $records->take(self::PAGE_SIZE);
        $lastRecord = $page->last();
        $items = $page->map(function (Model $entity) use ($campaign): array {
            if ($entity instanceof SessionNote) {
                $entity->loadMissing(['user', 'editor']);

                return [
                    'id' => $entity->getKey(),
                    'title' => $entity->title,
                    'session_date' => $entity->session_date?->toDateString(),
                    'session_date_label' => $entity->session_date?->format('d/m/Y') ?? $entity->created_at?->format('d/m/Y'),
                    'is_pinned' => $entity->is_pinned,
                    'content' => $entity->content,
                    'content_html' => $campaign->renderMarkdown($entity->content),
                    'author_name' => $entity->user?->name ?? 'Autore sconosciuto',
                    'created_at' => $entity->created_at?->toIso8601String(),
                    'updated_at' => $entity->updated_at?->toIso8601String(),
                    'updated_by' => $entity->editor
                        ? ['id' => $entity->editor->id, 'name' => $entity->editor->name]
                        : null,
                    'sync_version' => $entity->sync_version,
                    'deleted' => false,
                ];
            }

            if ($entity instanceof MapPin) {
                return $this->pinPayload(
                    $campaign,
                    $entity,
                    $entity->editor,
                    $entity->sync_version,
                    false
                );
            }

            throw new \InvalidArgumentException('Unsupported campaign sync snapshot entity.');
        })->values();

        return [
            'items' => $items,
            'next_cursor' => $hasMore && $lastRecord !== null
                ? $this->createSnapshotCursor($campaign, $entityType, $mapId, $generation, $snapshotVersion, $lastRecord->getKey())
                : null,
            'delta_cursor' => $hasMore
                ? null
                : $this->createCursorAt($campaign, $mapId, $snapshotVersion, $snapshotVersion),
            'has_more' => $hasMore,
            'reset_required' => false,
        ];
    }

    public function createCursor(Campaign $campaign, ?int $mapId = null): string
    {
        $version = (int) $campaign->sync_version;

        return $this->createCursorAt($campaign, $mapId, $version, $version);
    }

    /**
     * @return array<string, mixed>
     */
    public function changes(Campaign $campaign, string $cursor, string $entityType, ?int $mapId = null): array
    {
        $decoded = $this->decodeCursor($cursor, (int) $campaign->getKey(), $mapId);

        if ($decoded['generation'] !== $campaign->sync_generation) {
            return ['changes' => [], 'next_cursor' => null, 'has_more' => false, 'reset_required' => true];
        }

        $snapshotVersion = $decoded['snapshot_version'];
        if ($decoded['version'] >= $snapshotVersion) {
            $snapshotVersion = (int) $campaign->sync_version;
        }
        $query = DB::table('campaign_sync_events')
            ->where('campaign_id', $campaign->getKey())
            ->where('entity_type', $entityType)
            ->where('version', '>', $decoded['version'])
            ->where('version', '<=', $snapshotVersion)
            ->orderBy('version')
            ->orderBy('id');

        if ($entityType === 'pin' && $mapId !== null) {
            $query->where(function ($query) use ($mapId): void {
                $query->where('campaign_map_id', $mapId)
                    ->orWhere('previous_campaign_map_id', $mapId);
            });
        }

        $events = $query->limit(self::PAGE_SIZE + 1)->get();
        $hasMore = $events->count() > self::PAGE_SIZE;
        $page = $events->take(self::PAGE_SIZE);
        $lastEvent = $page->last();
        $nextVersion = $hasMore && $lastEvent !== null
            ? (int) $lastEvent->version
            : $snapshotVersion;

        return [
            'changes' => $page->map(fn (object $event): array => json_decode($event->payload, true, flags: JSON_THROW_ON_ERROR))->values(),
            'next_cursor' => $this->createCursorAt(
                $campaign->fresh(),
                $mapId,
                $nextVersion,
                $hasMore ? $snapshotVersion : $nextVersion
            ),
            'has_more' => $hasMore,
            'reset_required' => false,
        ];
    }

    public function decodeCursor(string $cursor, int $campaignId, ?int $mapId = null): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($cursor), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            abort(422, 'Il cursore di sincronizzazione non è valido.');
        }

        if (
            ! is_array($payload)
            || ($payload['campaign_id'] ?? null) !== $campaignId
            || ($payload['map_id'] ?? null) !== $mapId
            || ! is_int($payload['generation'] ?? null)
            || ! is_int($payload['version'] ?? null)
        ) {
            abort(422, 'Il cursore di sincronizzazione non è valido.');
        }

        $payload['snapshot_version'] = $payload['snapshot_version'] ?? $payload['version'];
        if (
            ! is_int($payload['snapshot_version'])
            || $payload['snapshot_version'] < $payload['version']
        ) {
            abort(422, 'Il cursore di sincronizzazione non è valido.');
        }

        return $payload;
    }

    public function createCursorAt(
        Campaign $campaign,
        ?int $mapId,
        int $version,
        ?int $snapshotVersion = null
    ): string {
        return Crypt::encryptString(json_encode([
            'campaign_id' => $campaign->getKey(),
            'generation' => $campaign->sync_generation,
            'version' => $version,
            'snapshot_version' => $snapshotVersion ?? $version,
            'map_id' => $mapId,
        ], JSON_THROW_ON_ERROR));
    }

    private function createSnapshotCursor(
        Campaign $campaign,
        string $entityType,
        ?int $mapId,
        int $generation,
        int $snapshotVersion,
        int $lastId
    ): string {
        return Crypt::encryptString(json_encode([
            'campaign_id' => $campaign->getKey(),
            'entity_type' => $entityType,
            'generation' => $generation,
            'snapshot_version' => $snapshotVersion,
            'last_id' => $lastId,
            'map_id' => $mapId,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{generation: int, snapshot_version: int, last_id: int}
     */
    private function decodeSnapshotCursor(
        string $cursor,
        int $campaignId,
        string $entityType,
        ?int $mapId
    ): array {
        try {
            $payload = json_decode(Crypt::decryptString($cursor), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            abort(422, 'Il cursore dello snapshot non è valido.');
        }

        if (
            ! is_array($payload)
            || ($payload['campaign_id'] ?? null) !== $campaignId
            || ($payload['entity_type'] ?? null) !== $entityType
            || ($payload['map_id'] ?? null) !== $mapId
            || ! is_int($payload['generation'] ?? null)
            || ! is_int($payload['snapshot_version'] ?? null)
            || ! is_int($payload['last_id'] ?? null)
            || $payload['last_id'] < 0
        ) {
            abort(422, 'Il cursore dello snapshot non è valido.');
        }

        return [
            'generation' => $payload['generation'],
            'snapshot_version' => $payload['snapshot_version'],
            'last_id' => $payload['last_id'],
        ];
    }
}
