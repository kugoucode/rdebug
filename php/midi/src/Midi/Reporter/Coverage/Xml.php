<?php
/**
 * Modify from PHPUnit.
 * Use PHPUnit code parser phpunit.xml and get coverage config
 */

namespace Midi\Reporter\Coverage;

/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Exception;
use DOMDocument;

final class Xml
{
    public static function load(
        $actual,
        bool $isHtml = false,
        string $filename = '',
        bool $xinclude = false,
        bool $strict = false
    ): DOMDocument {
        if ($actual instanceof DOMDocument) {
            return $actual;
        }

        if (!\is_string($actual)) {
            throw new Exception('Could not load XML from ' . \gettype($actual));
        }

        if ($actual === '') {
            throw new Exception('Could not load XML from empty string');
        }

        // Required for XInclude on Windows.
        if ($xinclude) {
            $cwd = \getcwd();
            @\chdir(\dirname($filename));
        }

        $document = new DOMDocument;
        $document->preserveWhiteSpace = false;

        $internal = \libxml_use_internal_errors(true);
        $message = '';
        $reporting = \error_reporting(0);

        if ($filename !== '') {
            // Required for XInclude
            $document->documentURI = $filename;
        }

        if ($isHtml) {
            $loaded = $document->loadHTML($actual);
        } else {
            $loaded = $document->loadXML($actual);
        }

        if (!$isHtml && $xinclude) {
            $document->xinclude();
        }

        foreach (\libxml_get_errors() as $error) {
            $message .= "\n" . $error->message;
        }

        \libxml_use_internal_errors($internal);
        \error_reporting($reporting);

        if (isset($cwd)) {
            @\chdir($cwd);
        }

        if ($loaded === false || ($strict && $message !== '')) {
            if ($filename !== '') {
                throw new Exception(
                    \sprintf(
                        'Could not load "%s".%s',
                        $filename,
                        $message !== '' ? "\n" . $message : ''
                    )
                );
            }

            if ($message === '') {
                $message = 'Could not load XML for unknown reason';
            }

            throw new Exception($message);
        }

        return $document;
    }

    /**
     * Loads an XML (or HTML) file into a DOMDocument object.
     *
     * @throws Exception
     */
    public static function loadFile(
        string $filename,
        bool $isHtml = false,
        bool $xinclude = false,
        bool $strict = false
    ): DOMDocument {
        $reporting = \error_reporting(0);
        $contents = \file_get_contents($filename);

        \error_reporting($reporting);

        if ($contents === false) {
            throw new Exception(
                \sprintf(
                    'Could not read "%s".',
                    $filename
                )
            );
        }

        return self::load($contents, $isHtml, $filename, $xinclude, $strict);
    }
}