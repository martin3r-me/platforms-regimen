<?php

namespace Platform\Regimen\Livewire\Session;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Services\RegimenEnrollmentService;
use Platform\Regimen\Services\RegimenMarkdownService;
use Platform\Regimen\Services\RegimenProgressService;
use Platform\Regimen\Services\RegimenQuizService;

class Show extends Component
{
    public string $uuid;

    /** @var array<int, mixed> question_id => option_id (single) | [option_id,...] (multiple) */
    public array $quizAnswers = [];

    /** Ergebnis des letzten Auswertens (null = noch nicht abgegeben). */
    public ?array $quizResult = null;

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function markComplete(): void
    {
        $user = Auth::user();
        $session = $this->resolveSession($user);

        // Manueller Abschluss nur fuer Einheiten ohne Concept-Check.
        if ($session->quiz()->exists()) {
            return;
        }

        app(RegimenProgressService::class)->complete($user->id, $session);
    }

    public function reopen(): void
    {
        $user = Auth::user();
        $session = $this->resolveSession($user);
        app(RegimenProgressService::class)->reopen($user->id, $session);
        $this->quizResult = null;
        $this->quizAnswers = [];
    }

    public function submitQuiz(): void
    {
        $user = Auth::user();
        $session = $this->resolveSession($user);
        $quiz = $session->quiz()->with('questions.options')->first();

        if (!$quiz || $quiz->questions->isEmpty()) {
            return;
        }

        // Antworten normalisieren: alles zu Listen von Option-IDs.
        $answers = [];
        foreach ($quiz->questions as $question) {
            $raw = $this->quizAnswers[$question->id] ?? [];
            $ids = is_array($raw) ? $raw : [$raw];
            $answers[$question->id] = array_values(array_filter(array_map('intval', $ids)));
        }

        $outcome = app(RegimenQuizService::class)->submit($user->id, $quiz, $answers);
        $this->quizResult = $outcome['result'];
    }

    public function retryQuiz(): void
    {
        $this->quizAnswers = [];
        $this->quizResult = null;
    }

    public function startIfNeeded(): void
    {
        $user = Auth::user();
        $session = $this->resolveSession($user);

        $existing = $session->progressFor($user->id);
        if (!$existing) {
            app(RegimenProgressService::class)->start($user->id, $session);
        }

        // Resume-Punkt fuer eingeschriebene Pläne mitziehen.
        app(RegimenEnrollmentService::class)->touch($user->id, $session);
    }

    public function render()
    {
        $user = Auth::user();
        $session = $this->resolveSession($user);
        $this->startIfNeeded();

        $progress = $session->progressFor($user->id);
        $isCompleted = $progress && $progress->isCompleted();

        $markdown = app(RegimenMarkdownService::class);
        $renderedContent = $markdown->render($session->content);

        // Concept-Check dieser Einheit (optional). Existiert er, gatet er den Abschluss.
        $quiz = $session->quiz()->with('questions.options')->first();
        $quizQuestions = [];
        if ($quiz && $quiz->questions->isNotEmpty()) {
            foreach ($quiz->questions as $question) {
                $quizQuestions[] = [
                    'id' => $question->id,
                    'type' => $question->type,
                    'is_multiple' => $question->isMultiple(),
                    'prompt_html' => $markdown->render($question->prompt),
                    'explanation_html' => $question->explanation ? $markdown->render($question->explanation) : null,
                    'options' => $question->options->map(fn ($o) => [
                        'id' => $o->id,
                        'label' => $o->label,
                    ])->all(),
                ];
            }
        }
        $hasQuiz = $quizQuestions !== [];

        // Checkbox-Bindung braucht fuer Mehrfach-Fragen ein Array als Startwert — sonst
        // behandelt Livewire die Checkboxen als Boolean und hakt beim ersten Klick alle
        // Optionen derselben Frage an. Single-Fragen bekommen einen Skalar-Startwert.
        if ($hasQuiz && $this->quizResult === null) {
            foreach ($quiz->questions as $question) {
                if (!array_key_exists($question->id, $this->quizAnswers)) {
                    $this->quizAnswers[$question->id] = $question->isMultiple() ? [] : '';
                }
            }
        }

        $topicSessions = $session->topic->publishedSessions()->get(['id', 'uuid', 'title', 'sort_order']);

        $completedIdsInTopic = app(RegimenProgressService::class)
            ->completedSessionIdsForUser($user->id, $topicSessions->pluck('id')->all());
        $completedSet = array_flip($completedIdsInTopic);

        $planMemberships = $session->plans()
            ->where('status', \Platform\Regimen\Models\RegimenPlan::STATUS_PUBLISHED)
            ->with('category')
            ->get();

        // Prev/Next folgen dem KURS (Plan), damit die Navigation ueber Themen-/Kapitel-
        // grenzen hinweg funktioniert. Ohne Plan-Kontext (reines Bibliotheks-Stoebern)
        // bleibt es themenintern.
        $primaryPlan = $planMemberships->first();
        $sequence = $primaryPlan
            ? $primaryPlan->sessions()
                ->where('regimen_sessions.status', RegimenSession::STATUS_PUBLISHED)
                ->with('topic:id,title')
                ->get(['regimen_sessions.id', 'regimen_sessions.uuid', 'regimen_sessions.title', 'regimen_sessions.regimen_topic_id'])
            : null;

        // Fallback auf die themeninterne Reihenfolge, wenn kein Plan existiert oder die
        // Einheit (unerwartet) nicht in der Plan-Sequenz liegt.
        if (!$sequence || $sequence->search(fn ($l) => $l->id === $session->id) === false) {
            $sequence = $topicSessions;
        }

        $currentIndex = $sequence->search(fn ($l) => $l->id === $session->id);
        $prev = $currentIndex !== false && $currentIndex > 0 ? $sequence[$currentIndex - 1] : null;
        $next = $currentIndex !== false && $currentIndex < $sequence->count() - 1 ? $sequence[$currentIndex + 1] : null;

        // Wechselt die naechste Einheit das Thema, ist es ein neues Kapitel.
        $nextIsNewChapter = $next && isset($next->regimen_topic_id) && $next->regimen_topic_id !== $session->regimen_topic_id;
        $nextChapterTitle = $nextIsNewChapter ? ($next->topic?->title) : null;

        // Akzentfarbe des Hero: erbt die Farbe des Plans (sonst Thema-Farbe, sonst Indigo).
        $accentColor = $planMemberships->isNotEmpty()
            ? $planMemberships->first()->coverColor()
            : (($session->topic->color && str_starts_with($session->topic->color, '#')) ? $session->topic->color : '#4F46E5');

        $this->dispatch('comms', [
            'model' => RegimenSession::class,
            'modelId' => $session->id,
            'subject' => 'Session: ' . $session->title,
            'description' => $session->summary,
            'url' => route('regimen.sessions.show', ['uuid' => $session->uuid]),
            'source' => 'regimen.sessions.show',
            'recipients' => [],
            'meta' => ['view_type' => 'show', 'resource' => 'session', 'topic_id' => $session->topic->id],
        ]);

        return view('regimen::livewire.session.show', [
            'session' => $session,
            'renderedContent' => $renderedContent,
            'isCompleted' => $isCompleted,
            'prev' => $prev,
            'next' => $next,
            'nextIsNewChapter' => $nextIsNewChapter,
            'nextChapterTitle' => $nextChapterTitle,
            'primaryPlan' => $primaryPlan,
            'topicSessions' => $topicSessions,
            'completedSet' => $completedSet,
            'planMemberships' => $planMemberships,
            'accentColor' => $accentColor,
            'hasQuiz' => $hasQuiz,
            'quiz' => $quiz,
            'quizQuestions' => $quizQuestions,
            'quizResult' => $this->quizResult,
        ])->layout('platform::layouts.app');
    }

    protected function resolveSession($user): RegimenSession
    {
        return RegimenSession::query()
            ->where('uuid', $this->uuid)
            ->where('team_id', $user->currentTeam->id)
            ->firstOrFail();
    }
}
