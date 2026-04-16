<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\services\DomCheckersService;

final class DomCheckersServiceTest extends TestCase
{
    private DomCheckersService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DomCheckersService();
    }

    // -------------------------------------------------------------------------
    // getDomCheckers
    // -------------------------------------------------------------------------

    public function testGetDomCheckersReturnsNonEmptyList(): void
    {
        // The @weglot/craftweglot alias must be resolvable in the test bootstrap.
        self::assertNotEmpty($this->service->getDomCheckers());
    }

    public function testGetDomCheckersReturnsFullyQualifiedClassNames(): void
    {
        foreach ($this->service->getDomCheckers() as $class) {
            self::assertStringContainsString('weglot\\craftweglot\\checkers\\dom\\', $class);
        }
    }

    public function testGetDomCheckersClassesAllExist(): void
    {
        foreach ($this->service->getDomCheckers() as $class) {
            self::assertTrue(class_exists($class), "Expected checker class $class to exist");
        }
    }
}
