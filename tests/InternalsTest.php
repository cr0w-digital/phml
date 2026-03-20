<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use function phml\{is_assoc, is_node, kebab};

// ============================================================================
// Tests for internal helpers: kebab(), is_assoc(), is_node().
// These are @internal but worth pinning so refactors don't silently break
// attribute expansion or node detection.
// ============================================================================

final class InternalsTest extends TestCase
{
    // ---- kebab() ------------------------------------------------------------

    public function test_kebab_camelCase(): void
    {
        $this->assertSame('font-size', kebab('fontSize'));
    }

    public function test_kebab_underscores_become_hyphens(): void
    {
        $this->assertSame('background-color', kebab('background_color'));
    }

    public function test_kebab_already_kebab_unchanged(): void
    {
        $this->assertSame('color', kebab('color'));
    }

    public function test_kebab_PascalCase(): void
    {
        $this->assertSame('background-color', kebab('BackgroundColor'));
    }

    // ---- is_assoc() ---------------------------------------------------------

    public function test_is_assoc_true_for_string_keys(): void
    {
        $this->assertTrue(is_assoc(['a' => 1, 'b' => 2]));
    }

    public function test_is_assoc_false_for_sequential_int_keys(): void
    {
        $this->assertFalse(is_assoc([1, 2, 3]));
    }

    public function test_is_assoc_false_for_empty_array(): void
    {
        $this->assertFalse(is_assoc([]));
    }

    // ---- is_node() ----------------------------------------------------------

    public function test_is_node_true_for_h_output(): void
    {
        $this->assertTrue(is_node(h('div')));
    }

    public function test_is_node_false_for_plain_list(): void
    {
        $this->assertFalse(is_node(['a', 'b', 'c']));
    }

    public function test_is_node_false_for_empty_array(): void
    {
        $this->assertFalse(is_node([]));
    }
}
