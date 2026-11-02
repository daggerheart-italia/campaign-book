/**
 * CampaignSyncManager
 *
 * Orchestrates adaptive polling of the unified `sync/delta` endpoint across
 * the campaign workspace tabs (wiki, map, notes). Replaces the per-partial
 * inline timers and cursors.
 *
 * Usage:
 *   const manager = createCampaignSyncManager({
 *     campaignId,
 *     syncDeltaUrl,
 *     snapshotUrls: { note, pin, wiki },
 *     initialCursors: { note, pin, wiki },
 *     initialActiveEntity: 'note' | 'pin' | 'wiki' | null,
 *     pollIntervalActiveMs: 5000,
 *     pollIntervalInactiveMs: 60000,
 *   });
 *
 *   manager.registerEntity('note', {
 *     applyChange(change) { ... },
 *     onStatsUpdate(stats) { ... },
 *     onReset(items, deltaCursor) { ... },
 *   });
 *   manager.start();
 */

export function createCampaignSyncManager(config) {
    const self = {
        campaignId: config.campaignId,
        syncDeltaUrl: config.syncDeltaUrl,
        snapshotUrls: config.snapshotUrls || {},
        initialCursors: config.initialCursors || {},
        pollIntervalActiveMs: config.pollIntervalActiveMs ?? 5000,
        pollIntervalInactiveMs: config.pollIntervalInactiveMs ?? 60000,

        cursors: {},
        campaignStats: null,
        activeEntity: config.initialActiveEntity || null,
        isVisible: document.visibilityState === 'visible',
        syncing: false,
        timer: null,
        entities: {},

        init() {
            self.cursors = { ...self.initialCursors };
        },

        registerEntity(entityType, handlers) {
            self.entities[entityType] = {
                applyChange: handlers.applyChange || null,
                onStatsUpdate: handlers.onStatsUpdate || null,
                onReset: handlers.onReset || null,
            };

            if (!self.cursors[entityType]) {
                self.cursors[entityType] = null;
            }


            if (entityType === self.activeEntity) {
                self.syncActiveEntity();
            }
        },

        setActiveEntity(entityType) {
            self.activeEntity = entityType;
            self.syncActiveEntity();
            self.restartTimer();
        },

        start() {
            self.restartTimer();
        },

        stop() {
            if (self.timer) {
                clearInterval(self.timer);
                self.timer = null;
            }
        },

        handleVisibilityChange() {
            self.isVisible = document.visibilityState === 'visible';
            self.restartTimer();
            if (self.isVisible && self.activeEntity) {
                self.syncActiveEntity();
            }
        },

        updateCampaignStats(stats) {
            self.campaignStats = stats;
            const event = new CustomEvent('campaign-stats-updated', {
                detail: stats,
                bubbles: true,
            });
            window.dispatchEvent(event);

            Object.values(self.entities).forEach((entity) => {
                if (entity.onStatsUpdate) {
                    entity.onStatsUpdate(stats);
                }
            });
        },

        restartTimer() {
            self.stop();
            const interval = self.isVisible
                ? self.pollIntervalActiveMs
                : self.pollIntervalInactiveMs;
            self.timer = setInterval(() => self.poll(), interval);
        },

        poll() {
            if (self.syncing || !self.activeEntity) {
                return;
            }
            self.syncActiveEntity();
        },

        async syncActiveEntity() {
            if (self.syncing || !self.activeEntity) {
                return;
            }

            self.syncing = true;
            let generationChanged = false;
            let data = null;

            try {
                const entityType = self.activeEntity;
                let cursor = self.cursors[entityType] || null;
                let hasMore = true;
                let pagesProcessed = 0;


                while (hasMore && pagesProcessed < 5) {
                    pagesProcessed++;

                    const url = new URL(self.syncDeltaUrl, window.location.origin);
                    url.searchParams.set('entity_type', entityType);
                    if (cursor) {
                        url.searchParams.set('cursor', cursor);
                    }


                    const response = await fetch(url, {
                        headers: { Accept: 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error(
                            `Sincronizzazione ${entityType} non riuscita (${response.status}).`
                        );
                    }

                    data = await response.json();


                    if (data.reset_required) {
                        generationChanged = true;
                        break;
                    }

                    const changes = data.changes || [];
                    const entity = self.entities[entityType];
                    for (const change of changes) {
                        if (entity && entity.applyChange) {
                            entity.applyChange(change);
                        }
                    }

                    self.cursors[entityType] = data.next_cursor ?? cursor;
                    cursor = self.cursors[entityType];
                    hasMore = !!data.has_more;
                }


                if (generationChanged) {
                    await self.handleReset();
                } else if (data && data.campaign_stats) {
                    self.updateCampaignStats(data.campaign_stats);
                }
            } catch (error) {
            } finally {
                self.syncing = false;
            }
        },

        async handleReset() {
            const promises = Object.keys(self.entities).map((entityType) => {
                const entity = self.entities[entityType];
                if (!entity.onReset) {
                    return null;
                }
                return self.fetchSnapshot(entityType);
            });

            await Promise.all(promises);
            try {
                const url = new URL(self.syncDeltaUrl, window.location.origin);
                if (self.activeEntity) {
                    url.searchParams.set('entity_type', self.activeEntity);
                } else if (Object.keys(self.entities).length > 0) {
                    url.searchParams.set('entity_type', Object.keys(self.entities)[0]);
                } else {
                    return;
                }
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (response.ok) {
                    const data = await response.json();
                    if (data && data.campaign_stats) {
                        self.updateCampaignStats(data.campaign_stats);
                    }
                }
            } catch (e) {
            }
        },

        async fetchSnapshot(entityType) {
            const snapshotUrl = self.snapshotUrls[entityType] || null;
            if (!snapshotUrl) {
                return;
            }


            const entity = self.entities[entityType];
            let cursor = null;
            let hasMore = true;
            const items = [];
            let deltaCursor = null;

            while (hasMore) {
                const url = new URL(snapshotUrl, window.location.origin);
                if (cursor) {
                    url.searchParams.set('cursor', cursor);
                }

                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    throw new Error(
                        `Snapshot ${entityType} non riuscita (${response.status}).`
                    );
                }

                const data = await response.json();

                if (data.reset_required) {
                    throw new Error(
                        `La campagna è stata reimpostata durante la snapshot ${entityType}.`
                    );
                }

                const pageItems = data.items || [];
                items.push(...pageItems);

                cursor = data.next_cursor || null;
                hasMore = !!data.has_more;
                if (data.delta_cursor) {
                    deltaCursor = data.delta_cursor;
                }
            }

            self.cursors[entityType] = deltaCursor ?? (data && data.delta_cursor ? data.delta_cursor : self.cursors[entityType]);


            if (entity && entity.onReset) {
                entity.onReset(items, self.cursors[entityType]);
            }
        },
    };

    return self;
}
