<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Twig\Node;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Twig AST node that compiles to a call to HtmlCommentsExtension::end() with the same NodeReference.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2025 Nowo.tech
 */
#[YieldReady]
class NodeEnd extends Node
{
    /**
     * Constructor.
     *
     * @param string $varName Variable name of the extension instance in compiled code (must match NodeStart)
     */
    public function __construct(string $varName)
    {
        parent::__construct([], ['var_name' => $varName]);
    }

    /**
     * Compiles the node to PHP that calls the extension's end() with the stored NodeReference.
     *
     * @param Compiler $compiler Twig compiler
     *
     * @return void
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
          ->write("\n")
          ->write(
              sprintf(
                  "\$%s->end(\$%s);\n\n",
                  $this->getAttribute('var_name'),
                  $this->getAttribute('var_name') . '_ref'
              )
          );
    }
}
