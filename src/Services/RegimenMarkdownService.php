<?php

namespace Platform\Regimen\Services;

use Illuminate\Support\Str;

class RegimenMarkdownService
{
    /**
     * Render Markdown to HTML with regimen enhancements.
     */
    public function render(?string $markdown): string
    {
        if (empty($markdown)) {
            return '';
        }

        [$markdown, $applets] = $this->extractApplets($markdown);

        $html = (string) Str::of($markdown)->markdown();
        $html = $this->transformAlerts($html);
        $html = $this->restoreApplets($html, $applets);

        return $html;
    }

    /**
     * Pull ```applet fenced blocks out of the Markdown before conversion and
     * replace each with a stable placeholder token. The captured body is raw
     * HTML/JS that will later be rendered inside a sandboxed <iframe>.
     *
     * Author writes:
     *   ```applet
     *   <input id="t"><pre id="out"></pre>
     *   <script> ... </script>
     *   ```
     *
     * @return array{0:string,1:array<int,string>}
     */
    protected function extractApplets(string $markdown): array
    {
        $applets = [];
        $pattern = '/^[ \t]*```applet[ \t]*\r?\n(.*?)\r?\n[ \t]*```[ \t]*$/ms';

        $markdown = preg_replace_callback($pattern, function ($match) use (&$applets) {
            $index = count($applets);
            $applets[] = $match[1];

            return "\n\n%%REGIMEN_APPLET_{$index}%%\n\n";
        }, $markdown) ?? $markdown;

        return [$markdown, $applets];
    }

    /**
     * Swap the placeholder tokens back in as sandboxed applet iframes.
     *
     * @param  array<int,string>  $applets
     */
    protected function restoreApplets(string $html, array $applets): string
    {
        foreach ($applets as $index => $body) {
            $iframe = $this->buildAppletIframe($body);
            $token = "%%REGIMEN_APPLET_{$index}%%";

            // The Markdown converter usually wraps the lone token in a <p>.
            $html = str_replace(["<p>{$token}</p>", $token], [$iframe, $iframe], $html);
        }

        return $html;
    }

    /**
     * Wrap author HTML/JS in a self-contained document and hand it to a
     * sandboxed iframe. sandbox="allow-scripts" (deliberately WITHOUT
     * allow-same-origin) gives the applet an opaque origin: it can run JS but
     * cannot touch the host page, cookies or Livewire — so session content
     * stays XSS-safe even though it may contain arbitrary scripts.
     */
    protected function buildAppletIframe(string $body): string
    {
        $srcdoc = htmlspecialchars($this->appletDocument($body), ENT_QUOTES, 'UTF-8');

        return '<div class="regimen-applet-wrap">'
            . '<div class="regimen-applet-bar"><span class="regimen-applet-dot"></span>Interaktiv &middot; ausprobieren</div>'
            . '<iframe class="regimen-applet" sandbox="allow-scripts" loading="lazy" '
            . 'title="Interaktives Beispiel" srcdoc="' . $srcdoc . '"></iframe>'
            . '</div>';
    }

    /**
     * The full HTML document rendered inside the applet iframe: a minimal reset
     * + design-token-flavoured styling (light/dark aware) so authors only write
     * the widget body, plus a resize reporter that posts its height to the host.
     */
    protected function appletDocument(string $body): string
    {
        // Festes Dark-Theme: die Applets sind für die dunkle Plattform-UI gebaut.
        // Solider dunkler Body-Hintergrund + heller Text als Default, damit auch
        // Widgets ohne eigene Textfarbe lesbar bleiben (kein dunkel-auf-dunkel).
        $style = <<<'CSS'
            :root{color-scheme:dark}
            *{box-sizing:border-box}
            html,body{margin:0;padding:0}
            body{font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;font-size:15px;line-height:1.55;color:#e5e7eb;background:#0d1117;padding:14px 16px}
            h1,h2,h3,h4{margin:0 0 .5rem;font-family:ui-monospace,"JetBrains Mono",monospace;color:#f3f4f6}
            a{color:#93c5fd}
            label{display:block;font-weight:600;margin:0 0 .35rem;color:#e5e7eb}
            input,textarea,select{font:inherit;width:100%;max-width:100%;padding:.55rem .7rem;border:1px solid #3a3b52;border-radius:.65rem;background:#1f2030;color:#e5e7eb}
            textarea{min-height:3rem}
            button{font:inherit;cursor:pointer;padding:.55rem .9rem;border:0;border-radius:.65rem;background:#4F46E5;color:#fff;font-weight:600}
            button:hover{background:#4338ca}
            pre,.out{background:#15161f;border:1px solid #23252f;border-radius:.65rem;padding:.65rem .8rem;margin:.6rem 0 0;overflow-x:auto;font-family:ui-monospace,"JetBrains Mono",monospace;font-size:14px;white-space:pre-wrap;word-break:break-word;min-height:1.2rem;color:#e5e7eb}
            .row{display:flex;flex-direction:column;gap:.6rem}
            .muted{color:#9aa0ae;font-size:13px}
            CSS;

        $resize = <<<'JS'
            (function(){
              function report(){
                var h=Math.ceil(document.body.getBoundingClientRect().height)+2;
                parent.postMessage({__regimenApplet:true,height:h},'*');
              }
              window.addEventListener('load',report);
              document.addEventListener('input',report,true);
              document.addEventListener('change',report,true);
              document.addEventListener('click',report,true);
              if(window.ResizeObserver){try{new ResizeObserver(report).observe(document.body);}catch(e){}}
              setTimeout(report,60);setTimeout(report,300);
            })();
            JS;

        return '<!doctype html><html lang="de"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<style>' . $style . '</style></head><body>'
            . $body
            . '<script>' . $resize . '</script>'
            . '</body></html>';
    }

    /**
     * Convert GitHub-style alerts in rendered HTML to styled callout boxes.
     *
     * Markdown:
     *   > [!INFO]
     *   > Some info text
     *
     * Supported: INFO, TIP, WARNING, NOTE, IMPORTANT, CAUTION
     */
    protected function transformAlerts(string $html): string
    {
        $pattern = '/<blockquote>\s*<p>\[!(INFO|TIP|WARNING|NOTE|IMPORTANT|CAUTION)\](?:\s*<br\s*\/?>)?\s*([\s\S]*?)<\/p>\s*<\/blockquote>/i';

        return preg_replace_callback($pattern, function ($match) {
            $type = strtolower($match[1]);
            $content = trim($match[2]);
            return $this->renderAlert($type, $content);
        }, $html);
    }

    protected function renderAlert(string $type, string $content): string
    {
        $config = $this->alertConfig()[$type] ?? $this->alertConfig()['note'];

        return sprintf(
            '<div class="regimen-alert regimen-alert-%s">'
            . '<div class="regimen-alert-label">%s<span>%s</span></div>'
            . '<div class="regimen-alert-body">%s</div>'
            . '</div>',
            e($type),
            $config['icon'],
            e($config['label']),
            $content
        );
    }

    protected function alertConfig(): array
    {
        $icon = fn (string $svg) => '<svg class="regimen-alert-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' . $svg . '</svg>';

        return [
            'info' => [
                'label' => 'Info',
                'icon' => $icon('<plan fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9Z" clip-rule="evenodd" />'),
            ],
            'tip' => [
                'label' => 'Tipp',
                'icon' => $icon('<plan d="M11 3a1 1 0 1 0-2 0v1a1 1 0 1 0 2 0V3ZM15.657 5.757a1 1 0 0 0-1.414-1.414l-.707.707a1 1 0 0 0 1.414 1.414l.707-.707ZM18 10a1 1 0 0 1-1 1h-1a1 1 0 1 1 0-2h1a1 1 0 0 1 1 1ZM5.05 6.464A1 1 0 1 0 6.464 5.05l-.707-.707a1 1 0 0 0-1.414 1.414l.707.707ZM5 10a1 1 0 0 1-1 1H3a1 1 0 1 1 0-2h1a1 1 0 0 1 1 1ZM8 16v-1h4v1a2 2 0 1 1-4 0ZM12 14c.015-.34.208-.646.477-.859a4 4 0 1 0-4.954 0c.27.213.462.519.476.859h4.002Z" />'),
            ],
            'warning' => [
                'label' => 'Warnung',
                'icon' => $icon('<plan fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />'),
            ],
            'note' => [
                'label' => 'Notiz',
                'icon' => $icon('<plan d="M3.5 2.75a.75.75 0 0 0-1.5 0v14.5a.75.75 0 0 0 1.5 0v-4.392l1.657-.348a6.449 6.449 0 0 1 4.271.572 7.948 7.948 0 0 0 5.965.524l2.078-.64A.75.75 0 0 0 18 12.25v-8.5a.75.75 0 0 0-.904-.734l-2.38.501a7.25 7.25 0 0 1-4.186-.363l-.502-.2a8.75 8.75 0 0 0-5.053-.439L3.5 2.879V2.75Z" />'),
            ],
            'important' => [
                'label' => 'Wichtig',
                'icon' => $icon('<plan fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-5a1 1 0 0 0-2 0v6a1 1 0 1 0 2 0V5Zm-1 9a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" clip-rule="evenodd" />'),
            ],
            'caution' => [
                'label' => 'Vorsicht',
                'icon' => $icon('<plan fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" />'),
            ],
        ];
    }
}
