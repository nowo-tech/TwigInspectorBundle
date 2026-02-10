<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Twig;

/**
 * Value object holding template name, block/template name, line number, and a unique id.
 * Passed from compiled Twig code to HtmlCommentsExtension::start() and ::end().
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
class NodeReference
{
    /** @var string Unique identifier for this node instance */
    private readonly string $id;

    /**
     * Constructor.
     *
     * @param string $name     Block or template name
     * @param string $template Template name (e.g. @App/demo/home.html.twig)
     * @param int    $line     Line number in the template
     */
    public function __construct(
        private readonly string $name,
        private readonly string $template,
        private readonly int $line
    ) {
        $this->id = uniqid('', false);
    }

    /**
     * Gets the unique ID.
     *
     * @return string The unique ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets the node name.
     *
     * @return string The node name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the template name.
     *
     * @return string The template name
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * Gets the line number.
     *
     * @return int The line number
     */
    public function getLine(): int
    {
        return $this->line;
    }
}
