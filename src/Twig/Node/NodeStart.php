<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Twig\Node;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

use function sprintf;

/**
 * Twig AST node that compiles to a call to HtmlCommentsExtension::start() with a NodeReference.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[YieldReady]
class NodeStart extends Node
{
    /**
     * Constructor.
     *
     * @param string $extensionName Extension class name (HtmlCommentsExtension::class)
     * @param string $name Block or template name
     * @param int $line Line number in the template
     * @param string $varName Variable name for the extension instance in compiled code
     */
    public function __construct(string $extensionName, string $name, int $line, string $varName)
    {
        parent::__construct(
            [],
            ['extension_name' => $extensionName, 'name' => $name, 'line' => $line, 'var_name' => $varName],
        );
    }

    /**
     * Compiles the node to PHP that calls the extension's start() with a NodeReference.
     *
     * @param Compiler $compiler Twig compiler
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
          ->write(sprintf('$%s = $this->env->getExtension(', $this->getAttribute('var_name')))
          ->repr($this->getAttribute('extension_name'))
          ->raw(");\n")
          ->write(
              sprintf(
                  '$%s->start($%s = new \Nowo\TwigInspectorBundle\Twig\NodeReference(',
                  $this->getAttribute('var_name'),
                  $this->getAttribute('var_name') . '_ref',
              ),
          )
          ->repr($this->getAttribute('name'))
          ->raw(', $this->getTemplateName(), ')
          ->repr($this->getAttribute('line'))
          ->raw("));\n\n");
    }
}
