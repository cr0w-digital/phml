<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// ============================================================================
// Tests for c() — the class-string composer.
// ============================================================================

final class ClassHelperTest extends TestCase
{
    public function test_single_string(): void
    {
        $this->assertSame('btn', c('btn'));
    }

    public function test_multiple_strings_joined_with_space(): void
    {
        $this->assertSame('btn btn-lg', c('btn', 'btn-lg'));
    }

    public function test_false_value_skipped(): void
    {
        $this->assertSame('btn', c('btn', false));
    }

    public function test_null_value_skipped(): void
    {
        $this->assertSame('active', c(null, 'active'));
    }

    public function test_empty_string_skipped(): void
    {
        $this->assertSame('a b', c('a', '', 'b'));
    }

    public function test_all_falsy_returns_empty_string(): void
    {
        $this->assertSame('', c(null, false, ''));
    }

    public function test_class_names_trimmed(): void
    {
        $this->assertSame('btn', c('  btn  '));
    }

    public function test_assoc_array_includes_truthy_keys(): void
    {
        $this->assertSame('is-active', c(['is-active' => true, 'is-disabled' => false]));
    }

    public function test_assoc_array_excludes_falsy_keys(): void
    {
        $this->assertSame('a c', c(['a' => true, 'b' => false, 'c' => true]));
    }

    public function test_mixed_string_and_assoc_array(): void
    {
        $this->assertSame('btn btn-lg', c('btn', ['btn-lg' => true, 'btn-block' => false]));
    }

    public function test_deeply_nested_array(): void
    {
        $this->assertSame('base extra', c(['base', ['extra' => true]]));
    }
}
