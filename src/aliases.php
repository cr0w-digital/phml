<?php

declare(strict_types=1);

(static function (): void {
    $candidates = ['h', 'e', 'c', 'raw'];
    $conflicts  = array_filter($candidates, 'function_exists');

    if ($conflicts !== []) {
        trigger_error(
            'phml: Could not define global aliases — already defined: '
                . implode(', ', $conflicts)
                . '. Use \phml\h(), \phml\e(), \phml\c(), \phml\raw() instead.',
            E_USER_NOTICE,
        );
        return;
    }

    function h(string $selector, mixed ...$args): array
    {
        return \phml\h($selector, ...$args);
    }

    function e(mixed $value): string
    {
        return \phml\e($value);
    }

    function c(mixed ...$parts): string
    {
        return \phml\c(...$parts);
    }

    function raw(string $html): \phml\RawHtml
    {
        return \phml\raw($html);
    }
})();