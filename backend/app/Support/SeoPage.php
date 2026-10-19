<?php

namespace App\Support;

/**
 * BE-56: the SPA's index.html, rewritten per URL so crawlers and link previews
 * (WhatsApp, Facebook) see a real title, description, image and canonical.
 * Every value is escaped here; callers pass plain text.
 */
final class SeoPage
{
    public const SITE = 'مداد';

    public const TAGLINE = 'عطرٌ يُكتَب، فيبقى';

    public function __construct(
        public string $title,
        public string $description,
        public string $path = '/',
        public ?string $image = null,
        public string $type = 'website',
        public bool $index = true,
        public array $jsonLd = [],
        public int $status = 200,
        public ?array $boot = null,
        public ?string $firstPaint = null,
    ) {}

    public function render(string $template): string
    {
        $base = rtrim(config('app.url'), '/');
        $title = $this->title === self::SITE ? self::SITE.' | '.self::TAGLINE : $this->title.' | '.self::SITE;
        $image = $this->image ? (str_starts_with($this->image, 'http') ? $this->image : $base.$this->image) : $base.'/og-default.png';
        $e = fn (string $v) => htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $head = [
            '<link rel="canonical" href="'.$e($base.$this->path).'" />',
            '<meta property="og:site_name" content="'.self::SITE.'" />',
            '<meta property="og:locale" content="ar_EG" />',
            '<meta property="og:type" content="'.$e($this->type).'" />',
            '<meta property="og:title" content="'.$e($title).'" />',
            '<meta property="og:description" content="'.$e($this->description).'" />',
            '<meta property="og:url" content="'.$e($base.$this->path).'" />',
            '<meta property="og:image" content="'.$e($image).'" />',
            '<meta name="twitter:card" content="summary_large_image" />',
        ];
        if (! $this->index) {
            $head[] = '<meta name="robots" content="noindex, nofollow" />';
        }
        foreach ($this->jsonLd as $ld) {
            // JSON inside <script>: escape "</" so a value can never close the tag
            $json = json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
            $head[] = '<script type="application/ld+json">'.$json.'</script>';
        }

        $html = preg_replace('#<title>.*?</title>#s', '<title>'.$e($title).'</title>', $template, 1);
        $html = preg_replace('#<meta name="description"[^>]*>#', '<meta name="description" content="'.$e($this->description).'" />', $html, 1);

        $html = str_replace('</head>', '    '.implode("\n    ", $head)."\n  </head>", $html);
        if ($this->firstPaint) {
            // built by frontend/scripts/prerender-home.mjs from our own app, not user input
            $html = str_replace('<div id="app"></div>', '<div id="app">'.$this->firstPaint.'</div>', $html);
        }
        if ($this->boot !== null) {
            // a data block, never executed, so script-src 'self' allows it
            $json = json_encode($this->boot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
            $html = str_replace('</body>', '<script type="application/json" id="midad-boot">'.$json."</script>\n  </body>", $html);
        }

        return $html;
    }

    /** Collapse whitespace and cut on a word boundary for meta descriptions. */
    public static function excerpt(?string $text, int $max = 160): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)));
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space ? mb_substr($cut, 0, $space) : $cut, '،,.؛ ').'…';
    }
}
