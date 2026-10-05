<?php

namespace Platform\Regimen\Livewire\Plan;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Models\RegimenPlanEnrollment;
use Platform\Regimen\Services\RegimenCategoryService;

class Index extends Component
{
    #[Url(as: 'category', history: true)]
    public ?string $categorySlug = null;

    public function rendered(): void
    {
        $this->dispatch('comms', [
            'model' => null, 'modelId' => null,
            'subject' => 'Regimen: Kurse',
            'description' => 'Kurskatalog',
            'url' => route('regimen.plans.index'),
            'source' => 'regimen.plans.index',
            'recipients' => [],
            'meta' => ['view_type' => 'index', 'resource' => 'plans'],
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        $categories = app(RegimenCategoryService::class)->listForTeam($teamId);
        $activeCategory = $this->categorySlug
            ? $categories->firstWhere('slug', $this->categorySlug)
            : null;

        $plans = RegimenPlan::query()
            ->where('team_id', $teamId)
            ->where('status', RegimenPlan::STATUS_PUBLISHED)
            ->when($activeCategory, fn ($q) => $q->where('regimen_category_id', $activeCategory->id))
            ->with('category')
            ->withCount(['publishedSessions as sessions_count'])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(function (RegimenPlan $plan) use ($user) {
                $plan->setAttribute('progress_pct', $plan->progressFor($user->id)['pct']);
                return $plan;
            });

        $enrolledSet = array_flip(
            RegimenPlanEnrollment::query()
                ->where('user_id', $user->id)
                ->where('team_id', $teamId)
                ->pluck('regimen_plan_id')
                ->all()
        );

        return view('regimen::livewire.plan.index', [
            'plans' => $plans,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'enrolledSet' => $enrolledSet,
        ])->layout('platform::layouts.app');
    }
}
