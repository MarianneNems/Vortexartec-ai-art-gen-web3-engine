<?php
namespace VortexAI\Tests\Integration;

use WP_UnitTestCase;

class TestCase extends WP_UnitTestCase {\n    protected function setUp(): void {\n    parent::setUp();
        // Add common setup for integration tests
    }

    protected function tearDown(): void
    {
        // Add common cleanup for integration tests
        parent::tearDown();
    }
}
