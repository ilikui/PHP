<?php
declare(strict_types=1);

namespace Learning\Cms;

final class MarkdownRenderer
{
    /** Safe subset of Markdown; raw HTML is always escaped. */
    public static function render(string $markdown): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
        $html = [];
        $paragraph = [];
        $listType = null;
        $inCode = false;
        $code = [];
        $fence = str_repeat(chr(96), 3);
        $flushParagraph = static function () use (&$paragraph, &$html): void {
            if ($paragraph !== []) {
                $html[] = '<p>' . self::inline(implode("\n", $paragraph)) . '</p>';
                $paragraph = [];
            }
        };
        $flushList = static function () use (&$listType, &$html): void {
            if ($listType !== null) { $html[] = '</' . $listType . '>'; $listType = null; }
        };
        foreach ($lines as $line) {
            if (str_starts_with(ltrim($line), $fence)) {
                $flushParagraph(); $flushList();
                if ($inCode) {
                    $html[] = '<pre><code>' . htmlspecialchars(implode("\n", $code), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>';
                    $code = []; $inCode = false;
                } else { $inCode = true; }
                continue;
            }
            if ($inCode) { $code[] = $line; continue; }
            if (trim($line) === '') { $flushParagraph(); $flushList(); continue; }
            if (preg_match('/^(#{1,6})\s+(.+)$/u', $line, $m) === 1) {
                $flushParagraph(); $flushList();
                $level = strlen($m[1]);
                $html[] = '<h' . $level . '>' . self::inline($m[2]) . '</h' . $level . '>';
                continue;
            }
            if (preg_match('/^\s*>\s?(.*)$/u', $line, $m) === 1) {
                $flushParagraph(); $flushList();
                $html[] = '<blockquote><p>' . self::inline($m[1]) . '</p></blockquote>';
                continue;
            }
            if (preg_match('/^\s*[-*+]\s+(.+)$/u', $line, $m) === 1) {
                $flushParagraph();
                if ($listType !== 'ul') { $flushList(); $html[] = '<ul>'; $listType = 'ul'; }
                $html[] = '<li>' . self::inline($m[1]) . '</li>';
                continue;
            }
            if (preg_match('/^\s*\d+[.)]\s+(.+)$/u', $line, $m) === 1) {
                $flushParagraph();
                if ($listType !== 'ol') { $flushList(); $html[] = '<ol>'; $listType = 'ol'; }
                $html[] = '<li>' . self::inline($m[1]) . '</li>';
                continue;
            }
            $flushList(); $paragraph[] = $line;
        }
        if ($inCode) $html[] = '<pre><code>' . htmlspecialchars(implode("\n", $code), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>';
        $flushParagraph(); $flushList();
        return implode("\n", $html);
    }

    private static function inline(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $tick = chr(96);
        $escaped = preg_replace_callback('/' . $tick . '([^' . $tick . ']+)' . $tick . '/u', static fn(array $m): string => '<code>' . $m[1] . '</code>', $escaped) ?? $escaped;
        $escaped = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/u', static function (array $m): string {
            $url = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (preg_match('#^(https?://|mailto:|/(?!/))#i', $url) !== 1) return $m[1];
            return '<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" rel="nofollow noopener noreferrer">' . $m[1] . '</a>';
        }, $escaped) ?? $escaped;
        $escaped = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/u', '<em>$1</em>', $escaped) ?? $escaped;
        return nl2br($escaped, false);
    }
}
