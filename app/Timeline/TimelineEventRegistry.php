<?php

namespace App\Timeline;

use App\Contracts\Timeline\TimelineEventable;
use App\Enums\TimelineEventKind;
use App\Models\IssueAssignmentEvent;
use App\Models\IssueComment;
use App\Models\IssueCreationEvent;
use App\Models\IssueStatusChangeEvent;
use InvalidArgumentException;

/**
 * Central registry for timeline event types.
 *
 * Each registration links a kind to:
 *   - its stable morph alias (persisted in DB, MUST NEVER CHANGE)
 *   - its eventable model FQCN (implements TimelineEventable)
 *
 * Adding a new timeline event type = register it here + create the model + migration.
 * No changes required anywhere else in the codebase.
 */
class TimelineEventRegistry
{
    /**
     * @var array<string, array{alias: string, class: class-string<TimelineEventable>}>
     */
    private array $registry = [];

    public function __construct()
    {
        $this->register(TimelineEventKind::IssueCreated, 'issue_created', IssueCreationEvent::class);
        $this->register(TimelineEventKind::StatusChanged, 'issue_status_changed', IssueStatusChangeEvent::class);
        $this->register(TimelineEventKind::AssignmentChanged, 'issue_assignment_changed', IssueAssignmentEvent::class);
        $this->register(TimelineEventKind::Commented, 'issue_comment', IssueComment::class);
    }

    /**
     * @param  class-string<TimelineEventable>  $class
     */
    public function register(TimelineEventKind $kind, string $alias, string $class): void
    {
        $this->registry[$kind->value] = ['alias' => $alias, 'class' => $class];
    }

    /**
     * Morph map consumable by Relation::enforceMorphMap.
     *
     * @return array<string, class-string<TimelineEventable>>
     */
    public function morphMap(): array
    {
        $map = [];
        foreach ($this->registry as $entry) {
            $map[$entry['alias']] = $entry['class'];
        }

        return $map;
    }

    /**
     * @return list<class-string<TimelineEventable>>
     */
    public function eventableClasses(): array
    {
        return array_values(array_map(fn (array $e): string => $e['class'], $this->registry));
    }

    public function classFor(TimelineEventKind $kind): string
    {
        if (! isset($this->registry[$kind->value])) {
            throw new InvalidArgumentException("Unregistered timeline event kind: {$kind->value}");
        }

        return $this->registry[$kind->value]['class'];
    }
}
