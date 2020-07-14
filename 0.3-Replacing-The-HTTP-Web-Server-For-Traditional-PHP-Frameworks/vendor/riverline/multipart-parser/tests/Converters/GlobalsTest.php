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

/**
 * Class GlobalsTest
 */
class GlobalsTest extends Commun
{
    /**
     * Create a part using globals
     *
     * @return StreamedPart
     */
    protected function createPart()
    {
        $contentType = 'multipart/form-data; boundary=----------------------------83ff53821b7c';
        // Create PSR7 server request

        $_SERVER['HTTP_CONTENT_TYPE'] = $contentType;
        $_SERVER['HTTP_CONTENT_LENGTH'] = strlen($contentType);

        // Test the converter
        return Globals::convert($this->createBodyStream());
    }
}
