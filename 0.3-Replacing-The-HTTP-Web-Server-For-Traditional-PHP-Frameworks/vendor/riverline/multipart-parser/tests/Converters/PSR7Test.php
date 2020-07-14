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

use Laminas\Diactoros\ServerRequest;
use Riverline\MultiPartParser\StreamedPart;

/**
 * Class PSR7Test
 */
class PSR7Test extends Commun
{
    /**
     * Crate a part using PSR7
     *
     * @return StreamedPart
     */
    protected function createPart()
    {
        $request = new ServerRequest(
            [],
            [],
            '/',
            'GET',
            $this->createBodyStream(),
            ['Content-type' => 'multipart/form-data; boundary=----------------------------83ff53821b7c']
        );

        // Test the converter
        return PSR7::convert($request);
    }
}
