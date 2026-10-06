<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Services\RegimenPlanService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class CreateRegimenPlanTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.plans.POST';
    }

    public function getDescription(): string
    {
        return 'POST /regimen/plans - Erstellt einen neuen Plan (kuratierte Session-Reihenfolge). Sessions werden separat via attach hinzugefuegt.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'regimen_category_id' => ['type' => 'integer', 'description' => 'Kategorie/"Disziplin" des Plans (bestimmt Cover-Farbe).'],
                'code' => ['type' => 'string', 'description' => 'Plan-Code, z.B. "AI-101". Wird bei Kollision eindeutig gemacht.'],
                'level' => ['type' => 'string', 'enum' => ['beginner', 'intermediate', 'advanced'], 'description' => 'Schwierigkeitsgrad.'],
                'type' => ['type' => 'string', 'enum' => ['running', 'equipment'], 'description' => 'Plan-Typ: running = Laufplan, equipment = Fitnessgeräte-Plan. Default: running.'],
                'duration_weeks' => ['type' => 'integer', 'description' => 'Länge des Plans in Wochen (treibt das Wochen×7-Raster für Einheiten).'],
                'description' => ['type' => 'string'],
                'target_audience' => ['type' => 'string', 'description' => 'z.B. "Sales", "Dev", "Operations".'],
                'status' => ['type' => 'string', 'enum' => ['draft', 'published', 'archived']],
                'public' => ['type' => 'boolean', 'description' => 'Website-Freigabe. Nur Pläne mit status=published UND public=true werden über die Public Plan API ausgeliefert. Default: false.'],
                'icon' => ['type' => 'string'],
                'color' => ['type' => 'string', 'description' => 'Optionaler Cover-Farb-Override (Hex). Sonst erbt der Plan die Kategorie-Farbe.'],
                'slug' => ['type' => 'string'],
                'sort_order' => ['type' => 'integer'],
            ],
            'required' => ['title'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $title = trim((string) ($arguments['title'] ?? ''));
            if ($title === '') {
                return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');
            }

            $plan = app(RegimenPlanService::class)->create(
                $resolved['team_id'],
                $context->user->id,
                array_merge($arguments, ['title' => $title]),
            );

            return ToolResult::success([
                'id' => $plan->id,
                'uuid' => $plan->uuid,
                'slug' => $plan->slug,
                'title' => $plan->title,
                'code' => $plan->code,
                'level' => $plan->level,
                'type' => $plan->type,
                'regimen_category_id' => $plan->regimen_category_id,
                'status' => $plan->status,
                'public' => $plan->public,
                'message' => "Plan '{$plan->title}' erstellt (Status: {$plan->status}).",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'plans', 'create'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => false,
        ];
    }
}
