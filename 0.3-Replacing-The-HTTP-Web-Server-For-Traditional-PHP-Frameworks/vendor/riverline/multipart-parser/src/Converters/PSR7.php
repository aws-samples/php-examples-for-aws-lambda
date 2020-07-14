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

use Psr\Http\Message\MessageInterface;
use Riverline\MultiPartParser\StreamedPart;

/**
 * Class PSR7
 */
class PSR7
{
    /**
     * @param MessageInterface $message
     *
     * @return StreamedPart
     */
    public static function convert(MessageInterface $message)
    {
        $stream = fopen('php://temp', 'rw');

        foreach ($message->getHeaders() as $key => $values) {
            foreach ($values as $value) {
                fwrite($stream, "$key: $value\r\n");
            }
        }
        fwrite($stream, "\r\n");

        $body = $message->getBody();
        $body->rewind();

        while (!$body->eof()) {
            fwrite($stream, $body->read(1024));
        }

        rewind($stream);

        return new StreamedPart($stream);
    }
}
