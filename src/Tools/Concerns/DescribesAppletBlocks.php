<?php

namespace Platform\Regimen\Tools\Concerns;

/**
 * Shared documentation for interactive applet blocks in Regimen Markdown
 * content (sessions + quiz prompts). Kept in one place so every content-writing
 * tool advertises the exact same convention to authors / LLMs.
 *
 * Rendering lives in {@see \Platform\Regimen\Services\RegimenMarkdownService}:
 * a ```applet fenced block becomes a sandboxed <iframe> (allow-scripts, no
 * allow-same-origin) with auto-resize and design-token styling.
 */
trait DescribesAppletBlocks
{
    protected function appletDoc(): string
    {
        return <<<'TXT'

 INTERAKTIVE APPLETS: Ein Markdown-Codeblock mit der Sprache "applet" (```applet ... ```) wird als sandboxed <iframe> gerendert — beliebiges HTML/JS, vollstaendig isoliert und XSS-sicher, mit automatischer Hoehen-Anpassung. Ideal fuer Live-Demos (Eingabefeld, Rechner, Visualisierung). Vorgestylte CSS-Klassen im Applet: .row (vertikale Spalte mit Abstand), .out (Monospace-Ausgabebox), .muted (kleiner grauer Hinweis); Inputs/Textareas/Buttons sind bereits im Design gestyled (inkl. Dark-Mode). Kein <html>/<body> noetig, nur der Inhalt. Minimalbeispiel (Text -> Bits):
```applet
<input id="t" value="Hi"><pre id="o" class="out"></pre>
<script>t.oninput=()=>o.textContent=[...t.value].map(c=>c.charCodeAt(0).toString(2).padStart(8,"0")).join(" ");t.oninput();</script>
```
TXT;
    }
}
