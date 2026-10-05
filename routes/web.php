<?php

use Platform\Regimen\Livewire\Dashboard;
use Platform\Regimen\Livewire\Topic\Index as TopicIndex;
use Platform\Regimen\Livewire\Topic\Show as TopicShow;
use Platform\Regimen\Livewire\Session\Show as SessionShow;
use Platform\Regimen\Livewire\Plan\Index as PlanIndex;
use Platform\Regimen\Livewire\Plan\Show as PlanShow;
use Platform\Regimen\Livewire\Certificate\Show as CertificateShow;

Route::get('/', Dashboard::class)->name('regimen.dashboard');

Route::get('/topics', TopicIndex::class)->name('regimen.topics.index');
Route::get('/topics/{uuid}', TopicShow::class)->name('regimen.topics.show');

Route::get('/plans', PlanIndex::class)->name('regimen.plans.index');
Route::get('/plans/{uuid}', PlanShow::class)->name('regimen.plans.show');

Route::get('/sessions/{uuid}', SessionShow::class)->name('regimen.sessions.show');

Route::get('/certificates/{uuid}', CertificateShow::class)->name('regimen.certificates.show');
