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

use Riverline\MultiPartParser\StreamedPart;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class HttpFoundation
 */
class HttpFoundation
{
    /**
     * @param Request $request
     *
     * @return StreamedPart
     */
    public static function convert(Request $request)
    {
        $stream = fopen('php://temp', 'rw');

        fwrite($stream, (string) $request->headers."\r\n");

        stream_copy_to_stream($request->getContent(true), $stream);

        rewind($stream);

        return new StreamedPart($stream);
    }
}
