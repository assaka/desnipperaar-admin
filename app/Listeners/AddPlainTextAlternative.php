<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Email;

/**
 * Gives every outbound e-mail a plain-text alternative.
 *
 * All mailables render a Blade view and nothing more, so without this the
 * messages leave as HTML-only. Spam filters treat a missing text/plain part as
 * a bulk-mail signal, so we derive a readable text body from the rendered HTML
 * whenever the mailable did not supply one itself. Runs on MessageSending so
 * the part is attached before the transport hands the message to Resend, which
 * also means LogSentMessage records a text body in the order history.
 */
class AddPlainTextAlternative
{
    public function handle(MessageSending $event): void
    {
        try {
            $message = $event->message;

            if (! $message instanceof Email) {
                return;
            }

            // A mailable that declared its own text: view wins.
            if ($message->getTextBody() !== null) {
                return;
            }

            $html = $message->getHtmlBody();
            if (! is_string($html) || trim($html) === '') {
                return;
            }

            $text = $this->toText($html);
            if ($text !== '') {
                $message->text($text);
            }
        } catch (\Throwable $e) {
            // Never let the text part block the actual send.
            report($e);
        }
    }

    /**
     * Flattens our e-mail HTML into readable plain text. The templates are
     * table-based transactional layouts, so block-level tags become newlines
     * and links keep their target in parentheses.
     */
    private function toText(string $html): string
    {
        // Drop anything that is markup-only noise.
        $text = preg_replace('#<(head|style|script|title)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;

        // Keep the destination of a link the reader would otherwise lose.
        $text = preg_replace_callback(
            '#<a\b[^>]*href=(["\'])(.*?)\1[^>]*>(.*?)</a>#is',
            function (array $m): string {
                $href  = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $label = trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($label === '' || $label === $href) {
                    return $href;
                }
                if (str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                    return $label;
                }

                return $label.' ('.$href.')';
            },
            $text
        ) ?? $text;

        $text = preg_replace('#<li\b[^>]*>#i', "\n- ", $text) ?? $text;
        $text = preg_replace('#<br\s*/?>#i', "\n", $text) ?? $text;
        $text = preg_replace('#</(p|div|tr|h1|h2|h3|h4|h5|h6|ul|ol|table|blockquote)>#i', "\n\n", $text) ?? $text;
        $text = preg_replace('#</t[dh]>#i', ' ', $text) ?? $text;
        $text = preg_replace('#<hr\s*/?>#i', "\n\n----\n\n", $text) ?? $text;

        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xC2\xA0", ' ', $text);

        // Trim each line, then collapse runs of blank lines to a single one.
        $lines = array_map(
            static fn (string $line): string => trim(preg_replace('/[ \t]+/', ' ', $line) ?? $line),
            preg_split('/\R/', $text) ?: []
        );
        $text = implode("\n", $lines);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
