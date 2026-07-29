<?php

namespace Weglot\Vendor\Weglot\Tests\Client;

use Weglot\Vendor\PHPUnit\Framework\TestCase;
use Weglot\Vendor\Weglot\Client\Profile;
class ProfileTest extends TestCase
{
    public function testV1KeyOf35CharsIsForcedToEngineOne(): void
    {
        $profile = new Profile('wg_0123456789abcdef0123456789abcdef', 3);
        $this->assertSame(35, \strlen('wg_0123456789abcdef0123456789abcdef'));
        $this->assertSame(1, $profile->getApiVersion());
        $this->assertSame(1, $profile->getTranslationEngine());
    }
    public function testWgKeyOf36CharsFallsBackToV2WithPassedEngine(): void
    {
        $profile = new Profile('wg_f33869c543b74329044eeef0902702a79', 3);
        $this->assertSame(36, \strlen('wg_f33869c543b74329044eeef0902702a79'));
        $this->assertSame(2, $profile->getApiVersion());
        $this->assertSame(3, $profile->getTranslationEngine());
    }
    public function testV2KeyKeepsPassedEngine(): void
    {
        $profile = new Profile('sk_019e6a91fbf579e0832a67de3433962b', 3);
        $this->assertSame(2, $profile->getApiVersion());
        $this->assertSame(3, $profile->getTranslationEngine());
    }
    public function testEmptyKeyDefaultsToV2WithPassedEngine(): void
    {
        $profile = new Profile('', 3);
        $this->assertSame(2, $profile->getApiVersion());
        $this->assertSame(3, $profile->getTranslationEngine());
    }
    public function testNonStringKeyDefaultsToV2WithPassedEngine(): void
    {
        $profile = new Profile(null, 2);
        $this->assertSame(2, $profile->getApiVersion());
        $this->assertSame(2, $profile->getTranslationEngine());
    }
}
