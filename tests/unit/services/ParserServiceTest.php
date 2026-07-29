<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\checkers\dom\ImageSourceSet;
use Weglot\Vendor\Weglot\Parser\Check\Dom\ImageDataSource;
use Weglot\Vendor\Weglot\Parser\Check\Dom\ImageSource;
use Weglot\Vendor\Weglot\Parser\ConfigProvider\ServerConfigProvider;
use Weglot\Vendor\Weglot\Parser\Definitions\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Parser;

final class ParserServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // ImageSourceSet — restores the checker dropped by weglot-parser-php 0.1.1
    // -------------------------------------------------------------------------

    public function testImageSourceSetCheckerDefinition(): void
    {
        self::assertSame('img', ImageSourceSet::DOM);
        self::assertSame('srcset', ImageSourceSet::PROPERTY);
        self::assertSame(WordType::IMG_SRC, ImageSourceSet::WORD_TYPE);
    }

    // -------------------------------------------------------------------------
    // removeCheckers contract — the media/external toggles depend on it
    // -------------------------------------------------------------------------

    /**
     * DomCheckerProvider stores checker names with a leading backslash (built from
     * DEFAULT_CHECKERS_NAMESPACE, and prefixed the same way for plugin checkers by
     * DomCheckersService). removeCheckers() uses array_diff, so the removed names must carry the
     * same leading backslash. ::class has none, making the removal a silent no-op — ParserService
     * therefore passes '\'.::class for its media_enabled / external_enabled toggles, and this test
     * guards that contract against a "simplification" back to ::class.
     */
    public function testRemoveCheckersRequiresLeadingBackslash(): void
    {
        $provider = (new Parser(2, $this->config()))->getDomCheckerProvider();
        self::assertTrue($this->hasChecker($provider->getCheckers(), 'Dom', 'ImageSource'));

        $provider->removeCheckers(['\\'.ImageSource::class, '\\'.ImageDataSource::class]);
        self::assertFalse($this->hasChecker($provider->getCheckers(), 'Dom', 'ImageSource'));
        self::assertFalse($this->hasChecker($provider->getCheckers(), 'Dom', 'ImageDataSource'));

        // Without the leading backslash the removal does nothing (documents the pitfall).
        $noBackslash = (new Parser(2, $this->config()))->getDomCheckerProvider();
        $noBackslash->removeCheckers([ImageSource::class]);
        self::assertTrue($this->hasChecker($noBackslash->getCheckers(), 'Dom', 'ImageSource'));
    }

    public function testPluginImageSourceSetIsRemovableWhenGated(): void
    {
        $provider = (new Parser(2, $this->config()))->getDomCheckerProvider();

        // DomCheckersService registers plugin checkers with a leading backslash.
        $provider->addCheckers(['\\'.ImageSourceSet::class]);
        self::assertTrue($this->hasChecker($provider->getCheckers(), 'dom', 'ImageSourceSet'));

        $provider->removeCheckers(['\\'.ImageSourceSet::class]);
        self::assertFalse($this->hasChecker($provider->getCheckers(), 'dom', 'ImageSourceSet'));
    }

    private function config(): ServerConfigProvider
    {
        $config = new ServerConfigProvider();
        $config->loadFromServer();

        return $config;
    }

    /**
     * @param array<int, mixed> $checkers
     */
    private function hasChecker(array $checkers, string $namespaceSegment, string $shortName): bool
    {
        $suffix = '\\'.$namespaceSegment.'\\'.$shortName;
        foreach ($checkers as $checker) {
            if (str_ends_with((string) $checker, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
