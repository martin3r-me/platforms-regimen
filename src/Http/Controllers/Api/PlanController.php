<?php

namespace Platform\Regimen\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Models\RegimenPlan;
use Platform\Core\Http\Controllers\ApiController;

/**
 * Regimen Plan API — liefert veröffentlichte Kurse (Plans) des Token-Teams.
 *
 * Auth: Bearer-Token (Passport) via `api.auth`. Das Team ergibt sich aus dem
 * `current_team_id` des Token-Users — es werden nur Kurse dieses Teams
 * ausgeliefert. Gedacht für die öffentliche Website (Kurskatalog + Kursdetail).
 *
 * Sichtbarkeit: nur Kurse mit status = published UND public = true
 * (Website-Freigabe). Siehe scopedQuery().
 */
class PlanController extends ApiController
{
    /**
     * GET /api/regimen/plans
     * Liste der veröffentlichten Kurse des Token-Teams.
     */
    public function index(Request $request)
    {
        $teamId = $this->teamId();
        if (! $teamId) {
            return $this->error('Kein Team im Token-Kontext.', null, 422);
        }

        $plans = $this->scopedQuery($teamId)
            ->with('category')
            ->withCount(['publishedSessions as sessions_count'])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn (RegimenPlan $plan) => $this->formatPlan($plan))
            ->all();

        return $this->success($plans, 'Kurse geladen');
    }

    /**
     * GET /api/regimen/plans/{uuid}
     * Kursdetail inkl. geordneter, veröffentlichter Lektionen (mit Inhalt).
     */
    public function show(Request $request, string $uuid)
    {
        $teamId = $this->teamId();
        if (! $teamId) {
            return $this->error('Kein Team im Token-Kontext.', null, 422);
        }

        $plan = $this->scopedQuery($teamId)
            ->where('uuid', $uuid)
            ->with('category')
            ->first();

        if (! $plan) {
            return $this->notFound('Kurs nicht gefunden.');
        }

        $markdown = app(\Platform\Regimen\Services\RegimenMarkdownService::class);

        $sessions = $plan->publishedSessions()
            ->get()
            ->map(fn (RegimenSession $session) => [
                'uuid' => $session->uuid,
                'title' => $session->title,
                'summary' => $session->summary,
                // Fertig gerendertes HTML inkl. interaktiver Applet-iframes und Alerts.
                'content_html' => $markdown->render($session->content),
                'estimated_minutes' => $session->estimated_minutes,
                'sort_order' => $session->pivot->sort_order ?? $session->sort_order,
            ])
            ->all();

        $plan = $this->formatPlan($plan);
        $plan['sessions'] = $sessions;
        $plan['sessions_count'] = count($sessions);
        $plan['duration_minutes'] = array_sum(array_column($sessions, 'estimated_minutes'));

        return $this->success($plan, 'Kurs geladen');
    }

    /**
     * GET /api/regimen/plans/health
     * Erreichbarkeits-Check inkl. Beispielkurs.
     */
    public function health(Request $request)
    {
        $teamId = $this->teamId();
        $example = $teamId
            ? $this->scopedQuery($teamId)->with('category')->orderByDesc('id')->first()
            : null;

        return $this->success([
            'status' => 'ok',
            'team_id' => $teamId,
            'example' => $example ? $this->formatPlan($example) : null,
            'timestamp' => now()->toIso8601String(),
        ], 'Regimen Plan API erreichbar');
    }

    /**
     * Basis-Query: nur veröffentlichte UND für die Website freigegebene Kurse
     * des Teams. `status = published` ist der Redaktions-Status, `public = true`
     * die explizite Freigabe nach außen — beides muss gesetzt sein.
     */
    protected function scopedQuery(int $teamId)
    {
        return RegimenPlan::query()
            ->where('team_id', $teamId)
            ->where('status', RegimenPlan::STATUS_PUBLISHED)
            ->where('public', true);
    }

    /** Team aus dem authentifizierten Token-User. */
    protected function teamId(): ?int
    {
        $user = Auth::user();

        return $user?->current_team_id ? (int) $user->current_team_id : null;
    }

    /** Kurs in ein flaches, für die Website geeignetes Array überführen. */
    protected function formatPlan(RegimenPlan $plan): array
    {
        $category = $plan->category;

        return [
            'uuid' => $plan->uuid,
            'slug' => $plan->slug,
            'code' => $plan->code,
            'title' => $plan->title,
            'description' => $plan->description,
            'level' => $plan->level,
            'level_label' => $plan->levelLabel(),
            'target_audience' => $plan->target_audience,
            'icon' => $plan->icon,
            'cover_color' => $plan->coverColor(),
            'sort_order' => $plan->sort_order,
            'sessions_count' => $plan->sessions_count ?? null,
            'category' => $category ? [
                'slug' => $category->slug,
                'title' => $category->title,
                'color' => $category->color(),
                'code_prefix' => $category->code_prefix,
            ] : null,
        ];
    }
}
