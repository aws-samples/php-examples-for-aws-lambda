# README

[![Build Status](https://travis-ci.org/Riverline/multipart-parser.svg?branch=master)](https://travis-ci.org/Riverline/multipart-parser)

## What is Riverline\MultiPartParser

``Riverline\MultiPartParse`` is a one class library to parse multipart document ( multipart email, multipart form, etc ...) 
and manage each part encoding and charset to extract their content.

## Requirements

* PHP >= 5.6

## Installation

``Riverline\MultiPartParse`` is compatible with composer and any psr-0/psr-4 autoloader.

```
composer require riverline/multipart-parser
```

## Usage

```php
<?php

use Riverline\MultiPartParser\StreamedPart;

// Prepare a test stream
$data = <<<EOL
User-Agent: curl/7.21.2 (x86_64-apple-darwin)
Host: localhost:8080
Accept: */*
Content-Type: multipart/form-data; boundary=----------------------------83ff53821b7c

------------------------------83ff53821b7c
Content-Disposition: form-data; name="foo"

bar
------------------------------83ff53821b7c
Content-Transfer-Encoding: base64

YmFzZTY0
------------------------------83ff53821b7c
Content-Disposition: form-data; name="upload"; filename="text.txt"
Content-Type: text/plain

File content
------------------------------83ff53821b7c--
EOL;
$stream = fopen('php://temp', 'rw');
fwrite($stream, $data);
rewind($stream);

$document = new StreamedPart($stream);

if ($document->isMultiPart()) {
    $parts = $document->getParts();
    echo $parts[0]->getBody(); // Output bar
    // It decode encoded content
    echo $parts[1]->getBody(); // Output base64

    // You can also filter by part name
    $parts = $document->getPartsByName('foo');
    echo $parts[0]->getName(); // Output foo

    // You can extract the headers
    $contentDisposition = $parts[0]->getHeader('Content-Disposition');
    echo $contentDisposition; // Output Content-Disposition: form-data; name="foo"
    // Helpers
    echo StreamedPart::getHeaderValue($contentDisposition); // Output form-data
    echo StreamedPart::getHeaderOption($contentDisposition, 'name'); // Output foo

    // File helper
    if ($parts[2]->isFile()) {
        echo $parts[2]->getFileName(); // Output text.txt
        echo $parts[2]->getMimeType(); // Output text/plain
    }
}
```

## Converters

The libary also provide three converters to quickly parse `PSR-7`, `HttpFoundation` and native requests.

```php
<?php

use \Riverline\MultiPartParser\Converters;

// Parse $_SERVER and STDIN
$document = Converters\Globals::convert();
```

## Backward compatibility

The old `Part` parser is now deprecated and replaced with a wrapper class that create a temporary stream
from the string content and call the new `StreamedPart` parser.