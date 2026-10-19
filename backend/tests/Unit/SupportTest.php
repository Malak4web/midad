<?php

namespace Tests\Unit;

use App\Support\ArabicNormalizer;
use App\Support\Money;
use PHPUnit\Framework\TestCase;

class SupportTest extends TestCase
{
    public function test_arabic_normalization_matches_the_frontend_rules(): void
    {
        $this->assertSame('انفيكتوس', ArabicNormalizer::normalize('إنفيكتوس'));
        $this->assertSame('عطر', ArabicNormalizer::normalize('عِطْرٌ'));
        $this->assertSame('قهوه', ArabicNormalizer::normalize('قهوة'));
        $this->assertSame('مصطفي', ArabicNormalizer::normalize('مصطفى'));
        $this->assertSame('مداد', ArabicNormalizer::normalize('  مـــداد  '));
        $this->assertSame('black oud', ArabicNormalizer::normalize('BLACK   Oud'));
    }

    public function test_search_blob_skips_empty_parts(): void
    {
        $this->assertSame('فيكتوري اكسير victory elixir', ArabicNormalizer::searchBlob(['فيكتوري إكسير', null, 'Victory Elixir', '']));
    }

    public function test_money_converts_between_piasters_and_pounds(): void
    {
        $this->assertSame(950, Money::egp(95000));
        $this->assertSame(12.5, Money::egp(1250));
        $this->assertNull(Money::egp(null));
        $this->assertSame(95000, Money::piasters(950));
        $this->assertSame(1250, Money::piasters('12.50'));
    }
}
