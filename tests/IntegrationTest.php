<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use function phml\render;

// ============================================================================
// End-to-end tests that exercise h() + render() together. These catch
// regressions that unit tests can miss because they cross module boundaries.
// ============================================================================

final class IntegrationTest extends TestCase
{
    public function test_full_card_component(): void
    {
        $card = h('div.card', ['id' => 'c1'],
            h('h2.card__title', 'Hello'),
            h('p.card__body', 'World'),
        );
        $html = render($card);

        $this->assertStringContainsString('id="c1"', $html);
        $this->assertStringContainsString('class="card"', $html);
        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('<p', $html);
        $this->assertStringContainsString('Hello', $html);
        $this->assertStringContainsString('World', $html);
    }

    public function test_conditional_child_via_ternary_renders_nothing(): void
    {
        $show = false;
        $node = h('div', $show ? h('span', 'visible') : false);
        $this->assertSame('<div></div>', render($node));
    }

    public function test_list_rendered_from_array_map(): void
    {
        $items = array_map(fn($i) => h('li', (string) $i), range(1, 3));
        $html  = render(h('ul', $items));
        $this->assertSame('<ul><li>1</li><li>2</li><li>3</li></ul>', $html);
    }

    public function test_xss_escaped_in_both_attr_and_text(): void
    {
        $payload = '"><script>alert(1)</script>';
        $html    = render(h('div', ['title' => $payload], $payload));

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_raw_allows_intentional_html_inside_element(): void
    {
        $html = render(h('p', raw('<em>Emphasis</em>')));
        $this->assertSame('<p><em>Emphasis</em></p>', $html);
    }
}
