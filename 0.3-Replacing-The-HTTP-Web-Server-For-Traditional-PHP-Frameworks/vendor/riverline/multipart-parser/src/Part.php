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

/**
 * Class Part
 *
 * @deprecated Wrapper class, use StreamedPart
 */
class Part extends StreamedPart
{
    /**
     * MultiPart constructor.
     *
     * @param string $content
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($content)
    {
        $stream = fopen('php://temp', 'rw');
        fwrite($stream, $content);
        rewind($stream);

        parent::__construct($stream);
    }
}
