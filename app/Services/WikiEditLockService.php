<?php

namespace App\Services;

use App\Models\User;
use App\Models\WikiPage;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WikiEditLockService
{
    private const LOCK_TTL_SECONDS = 90;

    private const OFFER_TTL_SECONDS = 45;

    private const COORDINATION_TTL_SECONDS = 120;

    private const COORDINATION_WAIT_SECONDS = 5;

    /**
     * @return array<string, mixed>
     */
    public function acquire(WikiPage $wiki, User $user, string $tabId, bool $takeover = false): array
    {
        return $this->synchronized($wiki, function (array $state) use ($wiki, $user, $tabId, $takeover): array {
            $state = $this->advanceExpiredState($state);

            if ($state['owner'] !== null && $this->isExpired($state['owner']['expires_at'])) {
                $state['owner'] = null;
            }
            $state = $this->offerNext($state);

            $owner = $state['owner'];
            if ($owner !== null) {
                if ($owner['user_id'] === $user->id && $owner['tab_id'] === $tabId) {
                    $state['owner']['last_heartbeat_at'] = now()->toIso8601String();
                    $state['owner']['expires_at'] = now()->addSeconds(self::LOCK_TTL_SECONDS)->toIso8601String();

                    return $this->persist($wiki, $state, ['status' => 'acquired', 'lock_token' => $owner['lock_token']]);
                }

                if ($owner['user_id'] === $user->id && $takeover) {
                    $state['owner'] = $this->newOwner($user, $tabId);
                    $state['queue'] = array_values(array_filter(
                        $state['queue'],
                        fn (array $entry): bool => $entry['user_id'] !== $user->id
                    ));
                    $state['offer'] = null;

                    return $this->persist($wiki, $state, [
                        'status' => 'acquired',
                        'lock_token' => $state['owner']['lock_token'],
                    ]);
                }

                return $this->persist($wiki, $state, [
                    'status' => $owner['user_id'] === $user->id ? 'same_user_other_tab' : 'occupied',
                    'owner' => ['user_id' => $owner['user_id'], 'name' => $owner['name']],
                ]);
            }

            if ($state['offer'] !== null) {
                return $this->persist($wiki, $state, ['status' => 'queued']);
            }

            if ($state['queue'] !== []) {
                if ($state['queue'][0]['user_id'] === $user->id && $state['queue'][0]['tab_id'] === $tabId) {
                    $state = $this->offerNext($state);

                    return $this->persist($wiki, $state, ['status' => 'offered']);
                }

                return $this->persist($wiki, $state, ['status' => 'queued']);
            }

            $state['owner'] = $this->newOwner($user, $tabId);

            return $this->persist($wiki, $state, [
                'status' => 'acquired',
                'lock_token' => $state['owner']['lock_token'],
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function heartbeat(WikiPage $wiki, User $user, string $tabId, string $lockToken): array
    {
        return $this->synchronized($wiki, function (array $state) use ($wiki, $user, $tabId, $lockToken): array {
            $state = $this->advanceExpiredState($state);
            $owner = $state['owner'];

            if ($owner === null || $this->isExpired($owner['expires_at'])) {
                $this->persist($wiki, $state);
                abort(410, 'Il lock di modifica è scaduto. Il testo inserito è ancora disponibile in questa pagina.');
            }

            if (! $this->owns($owner, $user, $tabId, $lockToken)) {
                abort(423, 'Il lock di modifica è stato assegnato a un’altra scheda o a un altro utente.');
            }

            $state['owner']['last_heartbeat_at'] = now()->toIso8601String();
            $state['owner']['expires_at'] = now()->addSeconds(self::LOCK_TTL_SECONDS)->toIso8601String();

            return $this->persist($wiki, $state, ['status' => 'acquired']);
        });
    }

    public function joinQueue(WikiPage $wiki, User $user, string $tabId): array
    {
        return $this->synchronized($wiki, function (array $state) use ($wiki, $user, $tabId): array {
            $state = $this->advanceExpiredState($state);
            $state = $this->offerNext($state);

            if ($state['owner'] === null && $state['offer'] === null && $state['queue'] === []) {
                $state['owner'] = $this->newOwner($user, $tabId);

                return $this->persist($wiki, $state, [
                    'status' => 'acquired',
                    'lock_token' => $state['owner']['lock_token'],
                ]);
            }

            if (($state['owner']['user_id'] ?? null) === $user->id) {
                return ['status' => 'owner'];
            }

            $alreadyQueued = collect($state['queue'])->contains(
                fn (array $entry): bool => $entry['user_id'] === $user->id
            );

            if ($alreadyQueued) {
                foreach ($state['queue'] as &$entry) {
                    if ($entry['user_id'] === $user->id) {
                        $entry['tab_id'] = $tabId;
                    }
                }
                unset($entry);
                if (($state['offer']['user_id'] ?? null) === $user->id) {
                    $state['offer']['tab_id'] = $tabId;
                }
            } else {
                $state['queue'][] = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'tab_id' => $tabId,
                    'requested_at' => now()->toIso8601String(),
                ];
            }

            return $this->persist($wiki, $state);
        });
    }

    public function leaveQueue(WikiPage $wiki, User $user): void
    {
        $this->synchronized($wiki, function (array $state) use ($wiki, $user): array {
            $state['queue'] = array_values(array_filter(
                $state['queue'],
                fn (array $entry): bool => $entry['user_id'] !== $user->id
            ));

            if (($state['offer']['user_id'] ?? null) === $user->id) {
                $state['offer'] = null;
                $state = $this->offerNext($state);
            }

            return $this->persist($wiki, $state);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function acceptOffer(WikiPage $wiki, User $user, string $tabId): array
    {
        return $this->synchronized($wiki, function (array $state) use ($wiki, $user, $tabId): array {
            $state = $this->advanceExpiredState($state);
            $offer = $state['offer'];

            if ($offer === null || $offer['user_id'] !== $user->id || $offer['tab_id'] !== $tabId) {
                abort(409, 'Non è disponibile un’offerta di modifica per questa scheda.');
            }

            $state['queue'] = array_values(array_filter(
                $state['queue'],
                fn (array $entry): bool => $entry['user_id'] !== $user->id
            ));
            $state['offer'] = null;
            $state['owner'] = $this->newOwner($user, $tabId);

            return $this->persist($wiki, $state, [
                'status' => 'acquired',
                'lock_token' => $state['owner']['lock_token'],
            ]);
        });
    }

    public function advance(WikiPage $wiki): void
    {
        $this->synchronized($wiki, function (array $state) use ($wiki): array {
            $state = $this->advanceExpiredState($state);

            if ($state['owner'] === null && $state['offer'] === null) {
                $state = $this->offerNext($state);
            }

            return $this->persist($wiki, $state);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function state(WikiPage $wiki, User $user, ?string $tabId = null): array
    {
        $state = Cache::get($this->stateKey($wiki), $this->emptyState());
        $owner = $state['owner'] !== null && ! $this->isExpired($state['owner']['expires_at'])
            ? $state['owner']
            : null;
        $offer = $state['offer'] !== null
            && $state['offer']['user_id'] === $user->id
            && $tabId !== null
            && hash_equals($state['offer']['tab_id'], $tabId)
            && ! $this->isExpired($state['offer']['offer_expires_at'])
                ? ['available' => true, 'expires_at' => $state['offer']['offer_expires_at']]
                : null;

        return [
            'owner' => $owner === null ? null : [
                'user_id' => $owner['user_id'],
                'name' => $owner['name'],
            ],
            'queue' => array_map(
                fn (array $entry, int $index): array => [
                    'position' => $index + 1,
                    'name' => $entry['name'],
                    'is_current_user' => $entry['user_id'] === $user->id,
                ],
                $state['queue'],
                array_keys($state['queue'])
            ),
            'offer' => $offer,
        ];
    }

    public function release(WikiPage $wiki, User $user, string $tabId, string $lockToken): void
    {
        $this->synchronized($wiki, function (array $state) use ($wiki, $user, $tabId, $lockToken): array {
            $owner = $state['owner'];
            if ($owner === null || ! $this->owns($owner, $user, $tabId, $lockToken)) {
                return $state;
            }

            $state['owner'] = null;

            return $this->persist($wiki, $this->offerNext($state));
        });
    }

    public function forceRelease(WikiPage $wiki): void
    {
        $this->synchronized($wiki, function (array $state) use ($wiki): array {
            $state['owner'] = null;
            $state['offer'] = null;

            return $this->persist($wiki, $this->offerNext($state));
        });
    }

    public function saveWithLock(
        WikiPage $wiki,
        User $user,
        string $tabId,
        string $lockToken,
        Closure $save
    ): mixed {
        return $this->synchronized($wiki, function (array $state) use ($wiki, $user, $tabId, $lockToken, $save): mixed {
            $owner = $state['owner'];

            if ($owner === null || $this->isExpired($owner['expires_at'])) {
                abort(409, 'Il lock di modifica è scaduto. Il testo inserito è ancora disponibile; riacquisisci il turno prima di salvare.');
            }

            if (! $this->owns($owner, $user, $tabId, $lockToken)) {
                abort(409, 'Il lock di modifica non appartiene più a questa scheda. Il testo inserito non è stato salvato.');
            }

            $result = $save();
            $state['owner'] = null;
            $this->persist($wiki, $this->offerNext($state));

            return $result;
        });
    }

    /**
     * @template T
     *
     * @param  Closure(array<string, mixed>): T  $callback
     * @return T
     */
    private function synchronized(WikiPage $wiki, Closure $callback): mixed
    {
        try {
            $lock = Cache::lock($this->coordinationKey($wiki), self::COORDINATION_TTL_SECONDS);

            return $lock->block(self::COORDINATION_WAIT_SECONDS, function () use ($wiki, $callback): mixed {
                $state = Cache::get($this->stateKey($wiki), $this->emptyState());

                return $callback($state);
            });
        } catch (LockTimeoutException) {
            abort(503, 'Il coordinamento della modifica non è disponibile. Riprova tra poco.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function persist(WikiPage $wiki, array $state, array $result = []): array
    {
        Cache::forever($this->stateKey($wiki), $state);

        return $result;
    }

    /**
     * @return array{owner: ?array, queue: array, offer: ?array}
     */
    private function emptyState(): array
    {
        return ['owner' => null, 'queue' => [], 'offer' => null];
    }

    private function stateKey(WikiPage $wiki): string
    {
        return 'edit:wiki:'.$wiki->getKey().':state';
    }

    private function coordinationKey(WikiPage $wiki): string
    {
        return 'edit:wiki:'.$wiki->getKey().':coord';
    }

    /**
     * @return array<string, mixed>
     */
    private function newOwner(User $user, string $tabId): array
    {
        $now = now();

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'tab_id' => $tabId,
            'lock_token' => Str::random(64),
            'acquired_at' => $now->toIso8601String(),
            'last_heartbeat_at' => $now->toIso8601String(),
            'expires_at' => $now->addSeconds(self::LOCK_TTL_SECONDS)->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $owner
     */
    private function owns(array $owner, User $user, string $tabId, string $lockToken): bool
    {
        return $owner['user_id'] === $user->id
            && hash_equals($owner['tab_id'], $tabId)
            && hash_equals($owner['lock_token'], $lockToken);
    }

    private function isExpired(string $timestamp): bool
    {
        return now()->greaterThanOrEqualTo($timestamp);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function advanceExpiredState(array $state): array
    {
        if ($state['owner'] !== null && $this->isExpired($state['owner']['expires_at'])) {
            $state['owner'] = null;
        }

        if ($state['offer'] !== null && $this->isExpired($state['offer']['offer_expires_at'])) {
            $expiredUserId = $state['offer']['user_id'];
            $state['queue'] = array_values(array_filter(
                $state['queue'],
                fn (array $entry): bool => $entry['user_id'] !== $expiredUserId
            ));
            $state['offer'] = null;
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function offerNext(array $state): array
    {
        if ($state['owner'] !== null || $state['offer'] !== null || $state['queue'] === []) {
            return $state;
        }

        $next = $state['queue'][0];
        $state['offer'] = [
            'user_id' => $next['user_id'],
            'name' => $next['name'],
            'tab_id' => $next['tab_id'],
            'offered_at' => now()->toIso8601String(),
            'offer_expires_at' => now()->addSeconds(self::OFFER_TTL_SECONDS)->toIso8601String(),
        ];

        return $state;
    }
}
