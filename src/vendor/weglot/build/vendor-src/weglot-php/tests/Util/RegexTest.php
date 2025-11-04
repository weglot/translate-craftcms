<?php

namespace Weglot\Vendor\Weglot\Tests\Util;

use Weglot\Vendor\PHPUnit\Framework\TestCase;
use Weglot\Vendor\Weglot\Util\Regex;
use Weglot\Vendor\Weglot\Util\Regex\RegexEnum;
class RegexTest extends TestCase
{
    /**
     * @return void
     */
    public function testRegexStartWith()
    {
        $regexInstance = new Regex(RegexEnum::START_WITH, 'http://');
        $regex = str_replace('/', '\/', $regexInstance->getRegex());
        $this->assertEquals('^http\:\/\/', $regex);
        $this->assertMatchesRegularExpression('#' . $regex . '#', 'http://');
        $this->assertTrue($regexInstance->match('http:// foo'));
        $this->assertFalse($regexInstance->match('foo http://'));
    }
    /**
     * @return void
     */
    public function testRegexEndWith()
    {
        $regexInstance = new Regex(RegexEnum::END_WITH, 'http://');
        $regex = str_replace('/', '\/', $regexInstance->getRegex());
        $this->assertEquals('http\:\/\/$', $regex);
        $this->assertMatchesRegularExpression('#' . $regex . '#', 'test string http://');
        $this->assertTrue($regexInstance->match('test string http://'));
        $this->assertFalse($regexInstance->match('http:// test'));
    }
    /**
     * @return void
     */
    public function testRegexContain()
    {
        $regexInstance = new Regex(RegexEnum::CONTAIN, 'http://');
        $regex = str_replace('/', '\/', $regexInstance->getRegex());
        $this->assertEquals('http\:\/\/', $regex);
        $this->assertMatchesRegularExpression('#' . $regex . '#', 'test http:// string');
        $this->assertTrue($regexInstance->match('test http:// string'));
    }
    /**
     * @return void
     */
    public function testRegexIsExactly()
    {
        $regexInstance = new Regex(RegexEnum::IS_EXACTLY, 'http://');
        $regex = str_replace('/', '\/', $regexInstance->getRegex());
        $this->assertEquals('^http\:\/\/$', $regex);
        $this->assertMatchesRegularExpression('#' . $regex . '#', 'http://');
        $this->assertTrue($regexInstance->match('http://'));
        $this->assertFalse($regexInstance->match('http://weglot.com'));
        $this->assertFalse($regexInstance->match('some http://'));
    }
    /**
     * @return void
     */
    public function testMatchRegex()
    {
        $regexInstance = new Regex(RegexEnum::MATCH_REGEX, '^http:\/\/');
        $regex = $regexInstance->getRegex();
        $this->assertEquals('^http:\/\/', $regex);
        $this->assertMatchesRegularExpression('#' . $regex . '#', 'http://weglot.com');
        $this->assertTrue($regexInstance->match('http://weglot.com'));
        $this->assertFalse($regexInstance->match('some http://weglot.com'));
    }
}
