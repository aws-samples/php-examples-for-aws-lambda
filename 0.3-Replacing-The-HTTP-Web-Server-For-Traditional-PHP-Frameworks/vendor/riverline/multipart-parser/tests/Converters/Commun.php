<?php

/*
 * This file is part of the MultiPartParser package.
 *
 * (c) Romain Cambien <romain@cambien.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Riverline\MultiPartParser\Converters;

use PHPUnit\Framework\TestCase;
use Riverline\MultiPartParser\StreamedPart;

/**
 * Class Commun
 */
abstract class Commun extends TestCase
{
    /**
     * @return resource
     */
    protected function createBodyStream()
    {
        $content = file_get_contents(__DIR__ . '/../_data/simple_multipart.txt');

        list(, $body) = preg_split("/(\r\n){2}/", $content, 2);

        $stream = fopen('php://temp', 'rw');
        fwrite($stream, $body);

        rewind($stream);

        return $stream;
    }

    /**
     * @return StreamedPart
     */
    protected abstract function createPart();


    /**
     * Test the parser
     */
    public function testParser()
    {
        // Test the converter
        $part = $this->createPart();

        self::assertTrue($part->isMultiPart());
        self::assertCount(3, $part->getParts());
    }
}
