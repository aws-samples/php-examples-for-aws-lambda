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
 * Class HttpFoundationTest
 */
class HttpFoundationTest extends Commun
{
    /**
     * Create a part using symfony
     *
     * @return StreamedPart
     */
    protected function createPart()
    {
        $request = Request::create(
            '/',
            'GET',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'multipart/form-data; boundary=----------------------------83ff53821b7c'],
            $this->createBodyStream()
        );

        // Test the converter
        return HttpFoundation::convert($request);
    }
}
