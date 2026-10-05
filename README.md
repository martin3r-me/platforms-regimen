# Regimen Module

Sport- und Trainingspläne für Teams und Personen — abgeleitet aus dem Academy-Modul (gleiche Grundlogik: Pläne bündeln Einheiten, werden an Personen gehängt und tracken Fortschritt).

## Concepts

- **Discipline (Category)** — Disziplin / Sportart-Gruppe (z.B. "Laufen", "Kraft & Geräte"). Hat eine Signalfarbe (`color`) und ein `code_prefix`. Treibt Plan-Cover, Chips und Code-Vorschläge. *(Klassenname aktuell noch `RegimenCategory`.)*
- **Topic** — Übungs-/Einheiten-Cluster im Hintergrund (Bibliothek).
- **Session (Einheit)** — Einzelne Trainingseinheit / Workout (Markdown-Content, `summary` = beschreibender Text) — gehört zu einem Topic.
- **Plan** — Die zuweisbare Einheit: kuratierte Reihenfolge von Sessions, mit `code`, `level` (beginner/intermediate/advanced), `type` und `category`. Sessions können in mehreren Plänen auftauchen.
  - **`type`** — Sportart-Gattung des Plans: `running` (Laufplan), `equipment` (Fitnessgeräte-Plan). Erweiterbar.
- **Enrollment** — Pro Person pro Plan: bewusstes Einschreiben (`active` / `completed`), mit Resume-Punkt (`last_session_id`).
- **Assignment** — Delegierte Zuweisung eines Plans an Personen (mit Start/Deadline) — „Plan an Person hängen".
- **Progress** — Pro Person pro Session: `in_progress` / `completed`. Speist den Plan-Fortschritt und die automatische Abschluss-Erkennung.

## Cover-Design

Pläne haben **keine Bild-Uploads**. Das Cover ist typografisch: der Plan-`code` groß in JetBrains Mono auf einem aus der Kategorie-`color` abgeleiteten Verlauf. Siehe `resources/views/partials/plan-cover.blade.php` (Größen: `card` / `rail` / `hero`).

## Architecture

Folgt dem Platform-Modul-Pattern:
- `src/Models/` — Eloquent Models mit UuidV7
- `src/Services/` — Business Logic, dünne Livewire Components
- `src/Livewire/` — Read-Views + Mark-Complete
- Team-scoped via `team_id` + `created_by_user_id`
- Markdown-Content, sauber entkoppelt von der UI

## Namespace
- PHP: `Platform\Regimen\...`
- Views: `regimen::livewire.xxx`
- Routes: `regimen.xxx`

## Herkunft / TODO

Geklont aus `modules/academy`. Noch offen (bewusst nicht umbenannt):
- `Topic` / `Category` behalten vorerst Academy-nahe Klassennamen (UI-Texte = „Übungen" / „Disziplin").
- `Quiz` / `Certificate` übernommen — Eignung für Sport-Kontext noch zu prüfen.
- UI-Texte (Blade/Livewire) sind teils noch Academy-Wortlaut und müssen fachlich nachgezogen werden.
- Instanz-Wiring (`composer require martin3r/platform-regimen`) im Instanz-Repo fehlt noch.
