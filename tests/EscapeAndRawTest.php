<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// ============================================================================
// Tests for e() and raw() — the two escaping primitives.
// ============================================================================

final class EscapeAndRawTest extends TestCase
{
    // ---- e() ----------------------------------------------------------------

    public function test_escapes_angle_brackets(): void
    {
        $this->assertSame('&lt;b&gt;bold&lt;/b&gt;', e('<b>bold</b>'));
    }

    public function test_escapes_double_quotes(): void
    {
        $this->assertSame('&quot;hello&quot;', e('"hello"'));
    }

    public function test_escapes_single_quotes(): void
    {
        $this->assertSame('&#039;hello&#039;', e("'hello'"));
    }

    public function test_escapes_ampersand(): void
    {
        $this->assertSame('a &amp; b', e('a & b'));
    }

    public function test_casts_int_to_string(): void
    {
        $this->assertSame('42', e(42));
    }

    public function test_casts_float_to_string(): void
    {
        $this->assertSame('3.14', e(3.14));
    }

    public function test_safe_string_returned_unchanged(): void
    {
        $this->assertSame('hello world', e('hello world'));
    }

    // ---- raw() --------------------------------------------------------------

    public function test_raw_returns_rawhtml_instance(): void
    {
        $result = raw('<b>bold</b>');
        $this->assertInstanceOf(\phml\RawHtml::class, $result);
    }

    public function test_raw_preserves_value_verbatim(): void
    {
        $html   = '<b>bold</b>';
        $result = raw($html);
        $this->assertSame($html, $result->value);
    }
}
