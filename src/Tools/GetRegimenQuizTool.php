<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class GetRegimenQuizTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.quiz.GET';
    }

    public function getDescription(): string
    {
        return 'GET /regimen/quiz - Liefert den Concept-Check (Quiz) einer Lektion inkl. Fragen, Optionen und Loesungsschluessel. Identifikation per session_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'session_id' => ['type' => 'integer'],
            ],
            'required' => ['session_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $sessionId = (int) ($arguments['session_id'] ?? 0);
            $session = RegimenSession::where('team_id', $resolved['team_id'])
                ->with('quiz.questions.options')
                ->find($sessionId);
            if (!$session) {
                return ToolResult::error('NOT_FOUND', 'Session nicht gefunden.');
            }

            $quiz = $session->quiz;
            if (!$quiz) {
                return ToolResult::success([
                    'session_id' => $session->id,
                    'quiz' => null,
                    'message' => 'Diese Lektion hat noch keinen Concept-Check.',
                ]);
            }

            return ToolResult::success([
                'session_id' => $session->id,
                'quiz' => [
                    'id' => $quiz->id,
                    'uuid' => $quiz->uuid,
                    'title' => $quiz->title,
                    'pass_pct' => $quiz->passThreshold(),
                    'shuffle_questions' => $quiz->shuffle_questions,
                    'questions' => $quiz->questions->map(fn ($q) => [
                        'id' => $q->id,
                        'type' => $q->type,
                        'prompt' => $q->prompt,
                        'explanation' => $q->explanation,
                        'options' => $q->options->map(fn ($o) => [
                            'id' => $o->id,
                            'label' => $o->label,
                            'is_correct' => $o->is_correct,
                        ])->values(),
                    ])->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query', 'tags' => ['regimen', 'quizzes', 'get'],
            'read_only' => true, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'read', 'idempotent' => true,
        ];
    }
}
