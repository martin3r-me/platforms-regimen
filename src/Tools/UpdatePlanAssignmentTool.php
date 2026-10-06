<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenPlanAssignment;
use Platform\Regimen\Models\RegimenUserAssignment;
use Platform\Regimen\Services\RegimenAssignmentService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class UpdatePlanAssignmentTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.assignments.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /regimen/assignments - Aendert eine Plan-Zuweisung. ERFORDERLICH: assignment_id. Optional: due_at (YYYY-MM-DD), is_mandatory, note, status (active|archived). Deadline/Pflicht werden auf offene pro-Person-Zuweisungen uebernommen. status=archived widerruft die Zuweisung (Enrollment/Fortschritt bleiben).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'assignment_id' => ['type' => 'integer'],
                'due_at' => ['type' => 'string', 'description' => 'Neue Deadline (YYYY-MM-DD) oder leer zum Entfernen.'],
                'is_mandatory' => ['type' => 'boolean'],
                'note' => ['type' => 'string'],
                'status' => ['type' => 'string', 'enum' => ['active', 'archived']],
            ],
            'required' => ['assignment_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $rule = RegimenPlanAssignment::where('team_id', $resolved['team_id'])
                ->find((int) ($arguments['assignment_id'] ?? 0));
            if (!$rule) {
                return ToolResult::error('NOT_FOUND', 'Zuweisung nicht gefunden.');
            }

            // Widerruf?
            if (($arguments['status'] ?? null) === RegimenPlanAssignment::STATUS_ARCHIVED) {
                app(RegimenAssignmentService::class)->revoke($rule);
                return ToolResult::success([
                    'id' => $rule->id,
                    'status' => $rule->status,
                    'message' => 'Zuweisung widerrufen. Bestehende Einschreibungen bleiben erhalten.',
                ]);
            }

            $dueChanged = array_key_exists('due_at', $arguments);
            $mandatoryChanged = array_key_exists('is_mandatory', $arguments);

            if ($dueChanged) {
                $rule->due_at = $arguments['due_at'] ?: null;
            }
            if ($mandatoryChanged) {
                $rule->is_mandatory = (bool) $arguments['is_mandatory'];
            }
            if (array_key_exists('note', $arguments)) {
                $rule->note = $arguments['note'];
            }
            if (($arguments['status'] ?? null) === RegimenPlanAssignment::STATUS_ACTIVE) {
                $rule->status = RegimenPlanAssignment::STATUS_ACTIVE;
            }
            $rule->save();

            // Auf offene (nicht abgeschlossene/widerrufene) pro-Person-Zuweisungen uebernehmen.
            if ($dueChanged || $mandatoryChanged) {
                $open = RegimenUserAssignment::where('regimen_plan_assignment_id', $rule->id)
                    ->whereNotIn('status', [RegimenUserAssignment::STATUS_COMPLETED, RegimenUserAssignment::STATUS_REVOKED])
                    ->get();
                foreach ($open as $ua) {
                    if ($dueChanged) {
                        $ua->due_at = $rule->due_at;
                        // Ueberfaelligkeit anhand neuer Deadline neu bestimmen.
                        if ($ua->status === RegimenUserAssignment::STATUS_OVERDUE
                            && (!$rule->due_at || $rule->due_at->gte(now()->startOfDay()))) {
                            $ua->status = RegimenUserAssignment::STATUS_ASSIGNED;
                        }
                    }
                    if ($mandatoryChanged) {
                        $ua->is_mandatory = $rule->is_mandatory;
                    }
                    $ua->save();
                }
            }

            return ToolResult::success([
                'id' => $rule->id,
                'due_at' => $rule->due_at?->toDateString(),
                'is_mandatory' => (bool) $rule->is_mandatory,
                'status' => $rule->status,
                'message' => 'Zuweisung aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'assignments', 'update'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => false,
        ];
    }
}
