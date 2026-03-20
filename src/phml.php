<?php

declare(strict_types=1);

namespace phml;

final class RawHtml
{
    public function __construct(
        public readonly string $value
    ) {}
}

/* ----------------------------- public API ----------------------------- */

/**
 * Build an HTML node as a plain PHP array.
 *
 * h('.card#main', h('p', 'Hello'))
 * h('input', ['type' => 'text', 'required' => true])
 */
function h(string $selector, mixed ...$args): array
{
    $attrs = [];

    if (isset($args[0]) && is_array($args[0]) && is_assoc($args[0]) && !is_node($args[0])) {
        $attrs = array_shift($args);
    }

    [$tag, $selectorId, $selectorClasses] = parse_selector($selector);

    if ($selectorId !== null && !array_key_exists('id', $attrs)) {
        $attrs['id'] = $selectorId;
    }

    if ($selectorClasses !== []) {
        $attrs['class'] = [$selectorClasses, $attrs['class'] ?? null];
    }

    $attrs = normalize_attrs($attrs);

    $children = [];
    foreach ($args as $arg) {
        append_child($children, $arg);
    }

    return [$tag, $attrs, ...$children];
}

/**
 * Compose a class string from mixed inputs.
 *
 * c('btn', $active && 'is-active')
 * c('btn', ['btn-lg' => $large, 'btn-block' => $block])
 * c(['px-2', ['text-lg' => $big]])
 */
function c(mixed ...$parts): string
{
    $out = [];
    append_classes($out, $parts);
    return implode(' ', $out);
}

/**
 * Escape a value for safe HTML output.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Mark a string as already-safe HTML, bypassing escaping in render().
 */
function raw(string $html): RawHtml
{
    return new RawHtml($html);
}

/**
 * Render a node (or tree of nodes) to an HTML string.
 */
function render(mixed $node): string
{
    if ($node === null || $node === false) {
        return '';
    }

    if ($node instanceof RawHtml) {
        return $node->value;
    }

    if (is_string($node) || is_int($node) || is_float($node)) {
        return e($node);
    }

    if (!is_array($node)) {
        throw new \RuntimeException('phml: Invalid node passed to render().');
    }

    // Fragment / flat list of nodes
    if (!is_node($node)) {
        $out = '';
        foreach ($node as $child) {
            $out .= render($child);
        }
        return $out;
    }

    [$tag, $attrs] = $node;
    $children = array_slice($node, 2);

    $out = '<' . $tag . render_attrs($attrs) . '>';

    if (is_void_tag($tag)) {
        return $out;
    }

    foreach ($children as $child) {
        $out .= render($child);
    }

    $out .= "</{$tag}>";

    return $out;
}

/* ------------------------------ internals ------------------------------ */

/**
 * @internal
 */
function normalize_attrs(array $attrs): array
{
    if (array_key_exists('class', $attrs)) {
        $attrs['class'] = c($attrs['class']);

        if ($attrs['class'] === '') {
            unset($attrs['class']);
        }
    }

    if (isset($attrs['style']) && is_array($attrs['style'])) {
        $attrs['style'] = style_attr($attrs['style']);

        if ($attrs['style'] === '') {
            unset($attrs['style']);
        }
    }

    // Any key ending in '-' with an array value is expanded as a prefix shorthand.
    // e.g. ['data-' => ['userId' => 1]]  → ['data-user-id' => 1]
    //      ['aria-' => ['label' => 'x']] → ['aria-label' => 'x']
    //      ['hx-'   => ['post' => '/x']] → ['hx-post' => '/x']
    foreach (array_keys($attrs) as $key) {
        if (is_array($attrs[$key]) && str_ends_with($key, '-')) {
            foreach ($attrs[$key] as $subKey => $subValue) {
                $attrs[$key . kebab((string) $subKey)] = $subValue;
            }
            unset($attrs[$key]);
        }
    }

    foreach (array_keys($attrs) as $key) {
        if (is_array($attrs[$key]) && !str_ends_with($key, '-')) {
            trigger_error(
                "phml: '{$key}' has an array value but is not a recognised shorthand — did you mean '{$key}-'?",
                E_USER_WARNING
            );
            unset($attrs[$key]);
        }
    }

    return $attrs;
}

/**
 * @internal
 */
function render_attrs(array $attrs): string
{
    $out = '';

    foreach ($attrs as $name => $value) {
        if (!is_string($name)) {
            continue;
        }

        if ($value === null || $value === false) {
            continue;
        }

        if ($value === true) {
            $out .= ' ' . $name;
            continue;
        }

        $out .= ' ' . $name . '="' . e($value) . '"';
    }

    return $out;
}

/**
 * @internal
 */
function append_child(array &$children, mixed $child): void
{
    if ($child === null || $child === false) {
        return;
    }

    if (is_array($child) && !is_node($child)) {
        foreach ($child as $nested) {
            append_child($children, $nested);
        }
        return;
    }

    $children[] = $child;
}

/**
 * @internal
 */
function append_classes(array &$out, mixed $value): void
{
    if ($value === null || $value === false || $value === '') {
        return;
    }

    if (is_string($value) || is_int($value) || is_float($value)) {
        $value = trim((string) $value);
        if ($value !== '') {
            $out[] = $value;
        }
        return;
    }

    if (!is_array($value)) {
        $value = trim((string) $value);
        if ($value !== '') {
            $out[] = $value;
        }
        return;
    }

    if (is_assoc($value)) {
        foreach ($value as $class => $enabled) {
            if ($enabled) {
                $class = trim((string) $class);
                if ($class !== '') {
                    $out[] = $class;
                }
            }
        }
        return;
    }

    foreach ($value as $item) {
        append_classes($out, $item);
    }
}

/**
 * @internal
 */
function parse_selector(string $input): array
{
    $tag = 'div';
    $id  = null;
    $classes = [];

    if (preg_match('/^[a-zA-Z][a-zA-Z0-9-]*/', $input, $m)) {
        $tag   = $m[0];
        $input = substr($input, strlen($tag));
    }

    if (preg_match('/#([a-zA-Z0-9_-]+)/', $input, $m)) {
        $id = $m[1];
    }

    if (preg_match_all('/\.([a-zA-Z0-9_-]+)/', $input, $m)) {
        $classes = $m[1];
    }

    return [$tag, $id, $classes];
}

/**
 * @internal
 */
function style_attr(array $styles): string
{
    $out = [];

    foreach ($styles as $prop => $value) {
        if ($value === null || $value === false || $value === '') {
            continue;
        }

        $out[] = kebab((string) $prop) . ': ' . $value;
    }

    return implode('; ', $out);
}

/**
 * @internal
 */
function kebab(string $value): string
{
    $value = preg_replace('/[A-Z]/', '-$0', $value);
    $value = strtolower((string) $value);
    $value = str_replace('_', '-', $value);

    return trim($value, '-');
}

/**
 * @internal
 */
function is_assoc(array $value): bool
{
    return $value !== [] && array_keys($value) !== range(0, count($value) - 1);
}

/**
 * @internal
 */
function is_node(array $value): bool
{
    return isset($value[0], $value[1])
        && is_string($value[0])
        && is_array($value[1]);
}

/**
 * @internal
 */
function is_void_tag(string $tag): bool
{
    static $void = [
        'area'   => true,
        'base'   => true,
        'br'     => true,
        'col'    => true,
        'embed'  => true,
        'hr'     => true,
        'img'    => true,
        'input'  => true,
        'link'   => true,
        'meta'   => true,
        'param'  => true,
        'source' => true,
        'track'  => true,
        'wbr'    => true,
    ];

    return isset($void[strtolower($tag)]);
}