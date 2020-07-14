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
 * Class StreamedPart
 */
class StreamedPart
{
    /**
     * @var resource
     */
    private $stream;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var int
     */
    private $bodyOffset;

    /**
     * @var StreamedPart[]
     */
    private $parts = array();

    /**
     * The length of the EOL character.
     *
     * @var int
     */
    private $EOLCharacterLength;

    /**
     * StreamParser constructor.
     *
     * @param resource $stream
     * @param int $EOLCharacterLength
     */
    public function __construct($stream, $EOLCharacterLength = 2)
    {
        if (false === is_resource($stream)) {
            throw new \InvalidArgumentException('Input is not a stream');
        }

        if (false === is_integer($EOLCharacterLength)) {
            throw new \InvalidArgumentException('EOL Length is not an integer');
        }

        $this->stream = $stream;
        $this->EOLCharacterLength = $EOLCharacterLength;

        // Reset the stream
        rewind($this->stream);

        // Parse headers
        $endOfHeaders = false;
        $bufferSize = 8192;
        $headerLines = [];
        $buffer = '';

        while (false !== ($line = fgets($this->stream, $bufferSize))) {
            // Append to buffer
            $buffer .= rtrim($line, "\r\n");

            if (strlen($line) === $bufferSize-1) {
                // EOL not reached, continue
                continue;
            }

            if ('' === $buffer) {
                // Empty line cause by double new line, we reached the end of the headers section
                $endOfHeaders = true;
                break;
            }

            // Detect horizontal whitescapes before header
            $trimmed = ltrim($buffer);
            if (strlen($buffer) > strlen($trimmed)) {
                // Multi lines header, append to previous line
                $headerLines[count($headerLines)-1] .= "\x20".$trimmed;
            } else {
                $headerLines[] = $buffer;
            }

            // Reset buffer
            $buffer = '';
        }

        if (false === $endOfHeaders) {
            throw new \InvalidArgumentException('Content is not valid');
        }

        $this->headers = [];
        foreach ($headerLines as $line) {
            $lineSplit = explode(':', $line, 2);

            if (2 === count($lineSplit)) {
                list($key, $value) = $lineSplit;
                // Decode value
                $value = mb_decode_mimeheader(trim($value));
            } else {
                // Bogus header
                $key = $lineSplit[0];
                $value = '';
            }

            // Case-insensitive key
            $key = strtolower($key);
            if (false === key_exists($key, $this->headers)) {
                $this->headers[$key] = $value;
            } else {
                // Already got an header with this key, convert to array
                if (false === is_array($this->headers[$key])) {
                    $this->headers[$key] = (array) $this->headers[$key];
                }
                $this->headers[$key][] = $value;
            }
        }

        $this->bodyOffset = ftell($stream);

        // Is MultiPart ?
        if ($this->isMultiPart()) {
            // MultiPart !
            $boundary = self::getHeaderOption($this->getHeader('Content-Type'), 'boundary');

            if (null === $boundary) {
                throw new \InvalidArgumentException("Can't find boundary in content type");
            }

            $separator = '--'.$boundary;

            $partOffset = 0;
            $endOfBody = false;
            while ($line = fgets($this->stream, $bufferSize)) {
                $trimmed = rtrim($line, "\r\n");

                // Search the separator
                if ($trimmed === $separator || $trimmed === $separator.'--') {
                    if ($partOffset > 0) {
                        $currentOffset = ftell($this->stream);
                        // Get end of line length (should be 2)
                        $eofLength = strlen($line) - strlen($trimmed);
                        $partLength = $currentOffset - $partOffset - strlen($trimmed) - (2 * $eofLength);

                        // if we are at the end of a part, and there is no trailing new line ($eofLength == 0)
                        // means that we are also at the end of the stream.
                        // we do not know if $eofLength is 1 or two, so we'll use the EOLCharacterLength value
                        // which is 2 by default.
                        if ($eofLength === 0 && feof($this->stream)) {
                            $partLength = $currentOffset - $partOffset - strlen($line) - $this->EOLCharacterLength;
                        }

                        // Copy part in a new stream
                        $partStream = fopen('php://temp', 'rw');
                        stream_copy_to_stream($this->stream, $partStream, $partLength, $partOffset);
                        $this->parts[] = new self($partStream, $this->EOLCharacterLength);
                        // Reset current stream offset
                        fseek($this->stream, $currentOffset);
                    }

                    if ($trimmed === $separator.'--') {
                        // We reach the end separator
                        $endOfBody = true;
                        break;
                    }

                    // Update the part offset
                    $partOffset = ftell($this->stream);
                }
            }


            if (0 === count($this->parts)
                || false === $endOfBody
            ) {
                throw new \LogicException("Can't find multi-part content");
            }
        }
    }


    /**
     * @return bool
     */
    public function isMultiPart()
    {
        return ('multipart' === mb_strstr(
            self::getHeaderValue($this->getHeader('Content-Type')),
            '/',
            true
        ));
    }

    /**
     * @return string
     *
     * @throws \LogicException if is multipart
     */
    public function getBody()
    {
        if ($this->isMultiPart()) {
            throw new \LogicException("MultiPart content, there aren't body");
        }

        $body = stream_get_contents($this->stream, -1, $this->bodyOffset);

        // Decode
        $encoding = strtolower($this->getHeader('Content-Transfer-Encoding'));
        switch ($encoding) {
            case 'base64':
                $body = base64_decode($body);
                break;
            case 'quoted-printable':
                $body = quoted_printable_decode($body);
                break;
        }

        // Convert to UTF-8 ( Not if binary or 7bit ( aka Ascii ) )
        if (false === in_array($encoding, array('binary', '7bit'))) {
            // Charset
            $contentType = $this->getHeader('Content-Type');
            $charset = self::getHeaderOption($contentType, 'charset');
            if (null === $charset) {
                // Try to detect
                $charset = mb_detect_encoding($body) ?: 'utf-8';
            }

            // Only convert if not UTF-8
            if ('utf-8' !== strtolower($charset)) {
                $body = mb_convert_encoding($body, 'utf-8', $charset);
            }
        }

        return $body;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $key
     *
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getHeader($key, $default = null)
    {
        // Case-insensitive key
        $key = strtolower($key);

        if (false === isset($this->headers[$key])) {
            return $default;
        }

        return $this->headers[$key];
    }

    /**
     * @param string $header
     *
     * @return string
     */
    public static function getHeaderValue($header)
    {
        list($value) = self::parseHeaderContent($header);

        return $value;
    }

    /**
     * @param string $header
     *
     * @return array
     */
    public static function getHeaderOptions($header)
    {
        list(, $options) = self::parseHeaderContent($header);

        return $options;
    }

    /**
     * @param string $header
     * @param string $key
     *
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function getHeaderOption($header, $key, $default = null)
    {
        $options = self::getHeaderOptions($header);

        if (false === isset($options[$key])) {
            return $default;
        }

        return $options[$key];
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        // Find Content-Disposition
        $contentType = $this->getHeader('Content-Type');

        return self::getHeaderValue($contentType) ?: 'application/octet-stream';
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        // Find Content-Disposition
        $contentDisposition = $this->getHeader('Content-Disposition');

        return self::getHeaderOption($contentDisposition, 'name');
    }

    /**
     * @return string|null
     */
    public function getFileName()
    {
        // Find Content-Disposition
        $contentDisposition = $this->getHeader('Content-Disposition');

        return self::getHeaderOption($contentDisposition, 'filename');
    }

    /**
     * @return bool
     */
    public function isFile()
    {
        return (false === is_null($this->getFileName()));
    }

    /**
     * @return StreamedPart[]
     *
     * @throws \LogicException if is not multipart
     */
    public function getParts()
    {
        if (false === $this->isMultiPart()) {
            throw new \LogicException("Not MultiPart content, there aren't any parts");
        }

        return $this->parts;
    }

    /**
     * @param string $name
     *
     * @return Part[]
     *
     * @throws \LogicException if is not multipart
     */
    public function getPartsByName($name)
    {
        $parts = array();

        foreach ($this->getParts() as $part) {
            if ($part->getName() === $name) {
                $parts[] = $part;
            }
        }

        return $parts;
    }

    /**
     * @param string $content
     *
     * @return array
     */
    private static function parseHeaderContent($content)
    {
        $parts = explode(';', $content);
        $headerValue = array_shift($parts);
        $options = array();
        // Parse options
        foreach ($parts as $part) {
            if (false === empty($part)) {
                $partSplit = explode('=', $part, 2);
                if (2 === count($partSplit)) {
                    list ($key, $value) = $partSplit;
                    if ('*' === substr($key, -1)) {
                        // RFC 5987
                        $key = substr($key, 0, -1);
                        if (preg_match(
                            "/(?P<charset>[\w!#$%&+^_`{}~-]+)'(?P<language>[\w-]*)'(?P<value>.*)$/",
                            $value,
                            $matches
                        )) {
                            $value = mb_convert_encoding(
                                rawurldecode($matches['value']),
                                'utf-8',
                                $matches['charset']
                            );
                        }
                    }
                    $options[trim($key)] = trim($value, ' "');
                } else {
                    // Bogus option
                    $options[$partSplit[0]] = '';
                }
            }
        }

        return array($headerValue, $options);
    }
}
