<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use function phml\render;

// ============================================================================
// Tests for render() — HTML serialisation, void-tag handling, attribute
// output, escaping, RawHtml passthrough, and fragment rendering.
// ============================================================================

final class RenderTest extends TestCase
{
    // ---- basic output -------------------------------------------------------

    public function test_simple_element(): void
    {
        $this->assertSame('<div></div>', render(h('div')));
    }

    public function test_element_with_text_child(): void
    {
        $this->assertSame('<p>Hello</p>', render(h('p', 'Hello')));
    }

    public function test_nested_elements(): void
    {
        $html = render(h('div', h('span', 'hi')));
        $this->assertSame('<div><span>hi</span></div>', $html);
    }

    public function test_integer_scalar_rendered_as_string(): void
    {
        $this->assertSame('42', render(42));
    }

    public function test_float_scalar_rendered_as_string(): void
    {
        $this->assertSame('3.14', render(3.14));
    }

    public function test_null_renders_empty_string(): void
    {
        $this->assertSame('', render(null));
    }

    public function test_false_renders_empty_string(): void
    {
        $this->assertSame('', render(false));
    }

    public function test_invalid_node_throws_runtime_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        render(new \stdClass());
    }

    // ---- void tags ----------------------------------------------------------

    public function test_all_void_tags_have_no_closing_tag(): void
    {
        $voids = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
                  'link', 'meta', 'param', 'source', 'track', 'wbr'];

        foreach ($voids as $tag) {
            $html = render(h($tag));
            $this->assertStringNotContainsString("</{$tag}>", $html, "Closing tag found for void: {$tag}");
            $this->assertStringStartsWith("<{$tag}", $html);
        }
    }

    public function test_void_tag_renders_attrs(): void
    {
        $html = render(h('input', ['type' => 'text', 'required' => true]));
        $this->assertSame('<input type="text" required>', $html);
    }

    // ---- attribute serialisation --------------------------------------------

    public function test_null_attr_omitted(): void
    {
        $html = render(h('div', ['id' => null]));
        $this->assertSame('<div></div>', $html);
    }

    public function test_false_attr_omitted(): void
    {
        $html = render(h('div', ['hidden' => false]));
        $this->assertSame('<div></div>', $html);
    }

    public function test_true_attr_rendered_as_name_only(): void
    {
        $html = render(h('details', ['open' => true]));
        $this->assertSame('<details open></details>', $html);
    }

    public function test_style_array_rendered_as_inline_style(): void
    {
        $html = render(h('div', ['style' => ['color' => 'blue']]));
        $this->assertStringContainsString('style="color: blue"', $html);
    }

    // ---- escaping -----------------------------------------------------------

    public function test_text_content_html_escaped(): void
    {
        $html = render(h('p', '<script>alert(1)</script>'));
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_attribute_value_html_escaped(): void
    {
        $html = render(h('a', ['href' => '"><img src=x onerror=alert(1)>']));
        $this->assertStringContainsString('&quot;&gt;&lt;img', $html);
        $this->assertStringContainsString('onerror=alert(1)&gt;', $html);
    }

    // ---- RawHtml ------------------------------------------------------------

    public function test_raw_html_not_escaped_at_top_level(): void
    {
        $html = render(raw('<strong>bold</strong>'));
        $this->assertSame('<strong>bold</strong>', $html);
    }

    public function test_raw_html_child_not_escaped(): void
    {
        $html = render(h('div', raw('<b>bold</b>')));
        $this->assertSame('<div><b>bold</b></div>', $html);
    }

    // ---- fragments ----------------------------------------------------------

    public function test_flat_list_of_nodes_rendered_as_fragment(): void
    {
        $nodes = [h('li', 'A'), h('li', 'B')];
        $html  = render($nodes);
        $this->assertSame('<li>A</li><li>B</li>', $html);
    }
}
