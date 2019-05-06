<?php
/**
 * Copyright (c) 2019 Ryan Parman <https://ryanparman.com>.
 * Copyright (c) 2019 Contributors.
 *
 * http://opensource.org/licenses/Apache2.0
 */

declare(strict_types=1);

namespace FeedLocator\Parser;

use DOMComment;
use DOMDocument;
use DOMNode;
use DOMText;
use DOMXPath;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use SimplePie\UtilityPack\Mixin as UpTr;
use SimplePie\UtilityPack\Parser\AbstractParser;

/**
 * The core parser for all XML content.
 */
class Html extends AbstractParser
{
    use UpTr\DomDocumentTrait;
    use UpTr\LibxmlTrait;
    use UpTr\LoggerTrait;
    use UpTr\RawDocumentTrait;

    /**
     * Constructs a new instance of this class.
     *
     * @param StreamInterface $stream A PSR-7 `StreamInterface` which is typically returned by
     *                                the `getBody()` method of a `ResponseInterface` class.
     * @param LoggerInterface $logger The PSR-3 logger.
     * @param int             $libxml The libxml value to use for parsing XML.
     *
     * @throws Error
     * @throws TypeError
     */
    public function __construct(StreamInterface $stream, LoggerInterface $logger, int $libxml = null)
    {
        // Logger
        $this->logger = $logger;

        // Libxml2
        $this->libxml = $libxml;

        // Default libxml2 settings
        if (null === $libxml) {
            $this->libxml = static::getDefaultConfig();
        }

        // Raw stream
        $this->rawDocument = $this->readStream($stream);

        // DOMDocument
        $this->domDocument = new DOMDocument('1.0', 'utf-8');

        // Don't barf errors all over the output
        \libxml_use_internal_errors(true);

        // DOMDocument configuration
        $this->domDocument->recover             = true;
        $this->domDocument->formatOutput        = false;
        $this->domDocument->preserveWhiteSpace  = false;
        $this->domDocument->resolveExternals    = true;
        $this->domDocument->substituteEntities  = true;
        $this->domDocument->strictErrorChecking = false;
        $this->domDocument->validateOnParse     = false;

        // Parse the XML document with the configured libxml options
        $this->domDocument->loadHTML($this->rawDocument, $this->libxml);

        // Clear the libxml errors to avoid excessive memory usage
        \libxml_clear_errors();
    }

    /**
     * Gets a reference to the `DOMXPath` object, with the default namespace
     * already registered.
     *
     * @return DOMXPath
     */
    public function xpath()
    {
        return new DOMXPath($this->domDocument);
    }

    /**
     * Some DOMNode names are `#comment` or `#text`. This method will move the
     * pointer to the next node, then the next until it finds a real XML node.
     *
     * @param DOMNode $node The `DOMNode` element to evaluate.
     */
    public function findNextRealNode(DOMNode $node): DOMNode
    {
        $n = $node;

        while (($n instanceof DOMComment || $n instanceof DOMText) && null !== $n) {
            $n = $n->nextSibling;
        }

        return $n;
    }
}
