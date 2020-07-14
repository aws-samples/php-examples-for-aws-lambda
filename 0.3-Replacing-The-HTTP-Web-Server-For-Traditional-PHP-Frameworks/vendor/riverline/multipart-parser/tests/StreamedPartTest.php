<?php

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
 * Class StreamedPartTest
 */
class StreamedPartTest extends TestCase
{
    /**
     * Test a multipart with invalid stream resource
     */
    public function testInvalidStreamResource()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Input is not a stream");
        new StreamedPart('invalid stream resource');
    }

    /**
     * Test a multipart with invalid EOL character length
     */
    public function testInvalidEOLCharacterLength()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("EOL Length is not an integer");
        new StreamedPart(fopen(__DIR__ . '/_data/no_boundary.txt', 'r'), 'invalid EOL character length');
    }

    /**
     * Test a multipart document without boundary header
     */
    public function testNoBoundaryPart()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Can't find boundary in content type");
        new StreamedPart(fopen(__DIR__ . '/_data/no_boundary.txt', 'r'));
    }

    /**
     * Test a multipart document without first boundary
     */
    public function testNoFirstBoundaryPart()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Can't find multi-part content");
        new StreamedPart(fopen(__DIR__ . '/_data/no_first_boundary.txt', 'r'));
    }

    /**
     * Test a multipart document without last boundary
     */
    public function testNoLastBoundaryPart()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Can't find multi-part content");
        new StreamedPart(fopen(__DIR__ . '/_data/no_last_boundary.txt', 'r'));
    }

    /**
     * Test a document without multipart
     */
    public function testNoMultiPart()
    {
        $part = new StreamedPart(fopen(__DIR__ . '/_data/no_multipart.txt', 'r'));

        self::assertFalse($part->isMultiPart());
        self::assertEquals('bar', $part->getBody());
        self::assertEquals(array(
            'user-agent' => 'curl/7.21.2 (x86_64-apple-darwin)',
            'host' => 'localhost:8080',
            'accept' => '*/*',
            'expect' => '100-continue',
            'content-type' => 'text/plain',
        ), $part->getHeaders());
    }

    /**
     * Test that is not possible to get a body for a multi part document
     */
    public function testCantGetBodyForAMultiPartMessage()
    {
        $part = new StreamedPart(fopen(__DIR__ . '/_data/simple_multipart.txt', 'r'));

        self::assertTrue($part->isMultiPart());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("MultiPart content, there aren't body");
        $part->getBody();
    }

    /**
     * Test multipart without new line at the end
     */
    public function testNoNewLineAtTheEndOfTheParts()
    {
        $content = "Content-Type: multipart/related; boundary=delimiter\r\n".
            "\r\n" .
            "--delimiter\r\n" .
            "Content-Type:mime/type\r\n" .
            "\r\n" .
            "Content\r\n" .
            "--delimiter--";


        $stream = fopen('php://temp', 'rw');
        fwrite($stream, $content);
        rewind($stream);

        $part = new StreamedPart($stream);
          /** @var Part[] $parts */
        $parts = $part->getParts();
        self::assertEquals('Content', $parts[0]->getBody());

    }

    /**
     * Test multipart without new line at the end
     */
    public function testNoNewLineAtTheEndOfThePartsWhenNewLineIsOneCharacterLong()
    {
        $content = "Content-Type: multipart/related; boundary=delimiter\n".
            "\n" .
            "--delimiter\n" .
            "Content-Type:mime/type\n" .
            "\n" .
            "Content\n" .
            "--delimiter--";


        $stream = fopen('php://temp', 'rw');
        fwrite($stream, $content);
        rewind($stream);

        $part = new StreamedPart($stream, 1);
        /** @var Part[] $parts */
        $parts = $part->getParts();
        self::assertEquals('Content', $parts[0]->getBody());
    }

    /**
     * Test that is not possible to get parts for a not multi part document
     */
    public function testCantGetPartsForANotMultiPartMessage()
    {
        $part = new StreamedPart(fopen(__DIR__ . '/_data/no_multipart.txt', 'r'));

        self::assertFalse($part->isMultiPart());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Not MultiPart content, there aren't any parts");
        $part->getParts();
    }

    /**
     * Test a simple multipart document
     */
    public function testSimpleMultiPart()
    {
        $part = new StreamedPart(fopen(__DIR__ . '/_data/simple_multipart.txt', 'r'));

        self::assertTrue($part->isMultiPart());
        self::assertCount(3, $part->getParts());

        /** @var Part[] $parts */
        $parts = $part->getParts();

        self::assertEquals('image/png', $parts[0]->getMimeType());
        self::assertEquals('bar', $parts[1]->getBody());
        self::assertEquals('rfc', $parts[2]->getBody());
    }

    /**
     * Test multi line header
     */
    public function testMultiLineHeader()
    {
        $part = new StreamedPart(fopen(__DIR__ . '/_data/simple_multipart.txt', 'r'));

        self::assertEquals('line one line two with space line three with tab', $part->getHeader('X-Multi-Line'));
    }

    /**
     * Test filter by name on a simple multipart document
     */
    public function testFilterByName()
    {
        $part = new StreamedPart(fopen(__DIR__ . '/_data/simple_multipart.txt', 'r'));

        self::assertCount(1, $part->getPartsByName('foo'));
    }

    /**
     * Test correct part content (e.g. \r of separator not part of body)
     */
    public function testPartContent()
    {
        $part = new StreamedPart(fopen(__DIR__ . '/_data/simple_multipart.txt', 'r'));

        $foo = $part->getPartsByName('foo');

        self::assertEquals("bar", $foo[0]->getBody());
    }

    /**
     * Test RFC 5987 encoded header
     */
    public function testRFC5987Header()
    {
        $part = new StreamedPart(fopen(__DIR__ . '/_data/simple_multipart.txt', 'r'));

        $parts = $part->getParts();
        $header = $parts[2]->getHeader('Content-Disposition');

        self::assertEquals('£ rates', StreamedPart::getHeaderOption($header, 'text1'));
        self::assertEquals('£ and € rates', StreamedPart::getHeaderOption($header, 'text2'));
    }

    /**
     * Test header helpers
     */
    public function testHeaderHelpers()
    {
        $part = new StreamedPart(fopen(__DIR__ . '/_data/simple_multipart.txt', 'r'));

        $parts = $part->getParts();
        $header = $parts[1]->getHeader('Content-Disposition');

        self::assertEquals('form-data', StreamedPart::getHeaderValue($header));
        self::assertEquals('foo', StreamedPart::getHeaderOption($header, 'name'));
    }

    /**
     * Test file helper
     */
    public function testFileHelper()
    {
        $part = new StreamedPart(fopen(__DIR__ . '/_data/simple_multipart.txt', 'r'));

        $parts = $part->getParts();

        self::assertTrue($parts[0]->isFile());
        self::assertEquals('a.png', $parts[0]->getFileName());
    }


    /**
     * Test a nested multipart document
     */
    public function testNestedMultiPart()
    {
        $part = new StreamedPart(fopen(__DIR__ . '/_data/nested_multipart.txt', 'r'));

        self::assertTrue($part->isMultiPart());

        $parts = $part->getParts();

        self::assertTrue($parts[0]->isMultiPart());
    }

    /**
     * Test a email document with base64 encoding
     */
    public function testEmailBase64()
    {
        $part = new StreamedPart(fopen(__DIR__ . '/_data/email_base64.txt', 'r'));

        self::assertTrue($part->isMultiPart());
        self::assertEquals('This is thé subject', $part->getHeader('Subject'));

        /** @var Part[] $parts */
        $parts = $part->getParts();

        self::assertEquals('This is the content', $parts[0]->getBody());
        self::assertEquals('This is the côntént', $parts[1]->getBody());
    }

    /**
     * Test a quoted printable decoding
     */
    public function testQuotedPrintable()
    {
        $part = new StreamedPart(fopen(__DIR__ . '/_data/quoted_printable.txt', 'r'));

        self::assertTrue($part->isMultiPart());
        self::assertEquals('Добро_пожаловать_на Site.ru', $part->getHeader('Subject'));

        /** @var Part[] $parts */
        $parts = $part->getParts();

        self::assertEquals('This is the content', $parts[0]->getBody());
        self::assertEquals('This is the côntént', $parts[1]->getBody());
    }
}
