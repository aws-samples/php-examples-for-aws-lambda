<?php /** @noinspection PhpDeprecationInspection */

/*
 * This file is part of the MultiPartParser package.
 *
 * (c) Romain Cambien <romain@cambien.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Riverline\MultiPartParser;

use PHPUnit\Framework\TestCase;

/**
 * Class PartTest
 */
class PartTest extends TestCase
{
    /**
     * Test a empty document
     */
    public function testEmptyPart()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Content is not valid");
        new Part('');
    }

    /**
     * Test a simple multipart document
     */
    public function testSimpleMultiPart()
    {
        $content = file_get_contents(__DIR__ . '/_data/simple_multipart.txt');

        $part = new Part($content);

        self::assertTrue($part->isMultiPart());
        self::assertCount(3, $part->getParts());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("MultiPart content, there aren't body");
        $part->getBody();
    }
}
