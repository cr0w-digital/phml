<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// ============================================================================
// Tests for h() — node construction, selector parsing, attribute normalization,
// and child flattening. Pure data-structure tests; render() is not called here
// except for the one case that specifically verifies render-time behavior of a
// node flag (boolean false attr).
// ============================================================================

final class HBuilderTest extends TestCase
{
    // ---- basic tag output ---------------------------------------------------

    public function test_simple_tag_produces_correct_node_structure(): void
    {
        $node = h('div');
        $this->assertSame('div', $node[0]);
        $this->assertSame([], $node[1]);
    }

    public function test_defaults_to_div_when_selector_has_no_tag(): void
    {
        $node = h('.card');
        $this->assertSame('div', $node[0]);
    }

    public function test_returns_array_node_tuple(): void
    {
        $node = h('span');
        $this->assertIsArray($node);
        $this->assertIsString($node[0]);
        $this->assertIsArray($node[1]);
    }

    // ---- selector parsing ---------------------------------------------------

    public function test_parses_id_from_selector(): void
    {
        $node = h('div#hero');
        $this->assertSame('hero', $node[1]['id']);
    }

    public function test_parses_single_class_from_selector(): void
    {
        $node = h('div.card');
        $this->assertSame('card', $node[1]['class']);
    }

    public function test_parses_multiple_classes_from_selector(): void
    {
        $node = h('div.card.active');
        $this->assertSame('card active', $node[1]['class']);
    }

    public function test_parses_id_and_classes_from_selector(): void
    {
        $node = h('section#main.container.fluid');
        $this->assertSame('main', $node[1]['id']);
        $this->assertSame('container fluid', $node[1]['class']);
    }

    public function test_selector_only_classes_default_tag_is_div(): void
    {
        $node = h('.foo.bar');
        $this->assertSame('div', $node[0]);
        $this->assertSame('foo bar', $node[1]['class']);
    }

    public function test_selector_class_merged_with_attrs_class(): void
    {
        $node = h('div.base', ['class' => 'extra']);
        $this->assertStringContainsString('base', $node[1]['class']);
        $this->assertStringContainsString('extra', $node[1]['class']);
    }

    public function test_selector_id_not_set_when_attrs_already_has_id(): void
    {
        // Explicit attrs['id'] wins over the selector id.
        $node = h('div#from-selector', ['id' => 'from-attrs']);
        $this->assertSame('from-attrs', $node[1]['id']);
    }

    public function test_selector_id_used_when_attrs_has_no_id(): void
    {
        $node = h('div#my-id', []);
        $this->assertSame('my-id', $node[1]['id']);
    }

    // ---- attribute normalisation --------------------------------------------

    public function test_accepts_plain_attrs_array(): void
    {
        $node = h('a', ['href' => 'https://example.com']);
        $this->assertSame('https://example.com', $node[1]['href']);
    }

    public function test_boolean_true_attr_stored_as_true(): void
    {
        $node = h('input', ['required' => true]);
        $this->assertTrue($node[1]['required']);
    }

    public function test_boolean_false_attr_not_rendered(): void
    {
        // false is stored in the node but render() must omit it from output
        $node = h('input', ['disabled' => false]);
        $html = \phml\render($node);
        $this->assertStringNotContainsString('disabled', $html);
    }

    public function test_data_prefix_shorthand_expanded(): void
    {
        $node = h('div', ['data-' => ['userId' => 42, 'role' => 'admin']]);
        $this->assertArrayHasKey('data-user-id', $node[1]);
        $this->assertArrayHasKey('data-role', $node[1]);
        $this->assertSame(42, $node[1]['data-user-id']);
        $this->assertArrayNotHasKey('data-', $node[1]);
    }

    public function test_aria_prefix_shorthand_expanded(): void
    {
        $node = h('button', ['aria-' => ['label' => 'Close', 'expanded' => 'false']]);
        $this->assertArrayHasKey('aria-label', $node[1]);
        $this->assertArrayHasKey('aria-expanded', $node[1]);
        $this->assertArrayNotHasKey('aria-', $node[1]);
    }

    public function test_hx_prefix_shorthand_expanded(): void
    {
        $node = h('button', ['hx-' => ['post' => '/submit', 'swapOob' => 'true']]);
        $this->assertArrayHasKey('hx-post', $node[1]);
        $this->assertArrayHasKey('hx-swap-oob', $node[1]);
        $this->assertArrayNotHasKey('hx-', $node[1]);
    }

    public function test_arbitrary_prefix_shorthand_expanded(): void
    {
        $node = h('div', ['x-' => ['data' => '{ open: false }', 'show' => 'open']]);
        $this->assertArrayHasKey('x-data', $node[1]);
        $this->assertArrayHasKey('x-show', $node[1]);
    }

    public function test_style_array_converted_to_inline_string(): void
    {
        $node = h('div', ['style' => ['color' => 'red', 'fontSize' => '16px']]);
        $this->assertSame('color: red; font-size: 16px', $node[1]['style']);
    }

    public function test_style_array_null_values_omitted(): void
    {
        $node = h('div', ['style' => ['color' => null, 'display' => 'flex']]);
        $this->assertStringNotContainsString('color', $node[1]['style']);
        $this->assertStringContainsString('display: flex', $node[1]['style']);
    }

    public function test_style_array_false_values_omitted(): void
    {
        $node = h('div', ['style' => ['color' => false, 'display' => 'block']]);
        $this->assertSame('display: block', $node[1]['style']);
    }

    public function test_empty_style_array_removes_style_attr(): void
    {
        $node = h('div', ['style' => ['color' => null]]);
        $this->assertArrayNotHasKey('style', $node[1]);
    }

    public function test_empty_class_attr_removed(): void
    {
        $node = h('div', ['class' => '']);
        $this->assertArrayNotHasKey('class', $node[1]);
    }

    // ---- children -----------------------------------------------------------

    public function test_string_child_stored_at_index_2(): void
    {
        $node = h('p', 'Hello');
        $this->assertSame('Hello', $node[2]);
    }

    public function test_multiple_children_all_appended(): void
    {
        $node = h('ul', h('li', 'A'), h('li', 'B'));
        $this->assertCount(4, $node); // tag + attrs + 2 children
    }

    public function test_nested_node_child_stored_by_reference(): void
    {
        $inner = h('span', 'inner');
        $outer = h('div', $inner);
        $this->assertSame($inner, $outer[2]);
    }

    public function test_null_child_ignored(): void
    {
        $node     = h('div', null, 'visible');
        $children = array_slice($node, 2);
        $this->assertCount(1, $children);
        $this->assertSame('visible', $children[0]);
    }

    public function test_false_child_ignored(): void
    {
        $node     = h('div', false, 'visible');
        $children = array_slice($node, 2);
        $this->assertCount(1, $children);
    }

    public function test_array_of_children_flattened_one_level(): void
    {
        $items    = [h('li', 'A'), h('li', 'B'), h('li', 'C')];
        $node     = h('ul', $items);
        $children = array_slice($node, 2);
        $this->assertCount(3, $children);
    }

    public function test_deeply_nested_array_children_fully_flattened(): void
    {
        $node     = h('ul', [[h('li', 'A'), h('li', 'B')]]);
        $children = array_slice($node, 2);
        $this->assertCount(2, $children);
    }
}
