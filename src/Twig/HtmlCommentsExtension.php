<?php

declare(strict_types=1);

namespace Nowo\TwigInspectorBundle\Twig;

use Nowo\TwigInspectorBundle\BoxDrawings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;

/**
 * Adds comments before and after every Twig block and template.
 *
 * @package Nowo\TwigInspectorBundle\Twig
 * @author Héctor Franco Aceituno <hectorfranco@nowo.com>
 * @copyright 2024 Nowo.tech
 */
class HtmlCommentsExtension extends AbstractExtension
{
  protected const ENABLE_FLAG_COOKIE_ID = 'twig_inspector_is_active';

  private ?string $previousContent = null;

  private int $nestingLevel = 0;

  /**
   * Constructor.
   *
   * @param RequestStack $requestStack The request stack
   * @param UrlGeneratorInterface $urlGenerator The URL generator
   * @param BoxDrawings $boxDrawings The box drawings helper
   */
  public function __construct(
    private readonly RequestStack $requestStack,
    private readonly UrlGeneratorInterface $urlGenerator,
    private readonly BoxDrawings $boxDrawings
  ) {
  }

  /**
   * Starts output buffering for a node.
   *
   * @param NodeReference $ref The node reference
   *
   * @return void
   */
  public function start(NodeReference $ref): void
  {
    if (!$this->isEnabled($ref))
    {
      return;
    }

    ob_start();
  }

  /**
   * Ends output buffering and adds comments.
   * Wraps the captured content with HTML comments containing template information.
   * Handles nested blocks by tracking nesting levels and updating box drawing styles.
   *
   * @param NodeReference $ref The node reference
   *
   * @return void
   */
  public function end(NodeReference $ref): void
  {
    if (!$this->isEnabled($ref))
    {
      return;
    }

    $content = ob_get_clean();

    if ($this->isSupported($content))
    {
      // Check if this is a nested block (content contains previous content)
      if ((string) $this->previousContent !== '' && str_contains($content, (string) $this->previousContent))
      {
        // If content changed, update box drawing style
        if (trim($content) !== trim((string) $this->previousContent))
        {
          $this->boxDrawings->blockChanged($this->nestingLevel);
        }

        ++$this->nestingLevel;
      }
      else
      {
        // Reset nesting level for new top-level block
        $this->nestingLevel = 0;
        $this->boxDrawings->blockChanged($this->nestingLevel);
      }

      // Wrap content with start and end comments
      $content = $this->getStartComment($ref) . $content . $this->getEndComment($ref);

      $this->previousContent = $content;
    }

    echo $content;
  }

  /**
   * Checks if the inspector is enabled for the given node.
   * The inspector is enabled when:
   * - A request is available
   * - The 'twig_inspector_is_active' cookie is set to true
   * - The template file ends with '.html.twig'
   *
   * @param NodeReference $ref The node reference
   *
   * @return bool True if enabled, false otherwise
   */
  protected function isEnabled(NodeReference $ref): bool
  {
    $request = $this->requestStack->getCurrentRequest();

    if (!$request instanceof Request || !$request->cookies->getBoolean(self::ENABLE_FLAG_COOKIE_ID))
    {
      return false;
    }

    return str_ends_with($ref->getTemplate(), '.html.twig');
  }

  /**
   * Checks if the content is supported for inspection.
   *
   * @param string $string The content string
   *
   * @return bool True if supported, false otherwise
   */
  /**
   * Checks if the content is supported for inspection.
   * Only HTML content is supported, not plain text, JSON, or Backbone templates.
   *
   * @param string $string The content string
   *
   * @return bool True if supported, false otherwise
   */
  protected function isSupported(string $string): bool
  {
    // Check if content has HTML tags (if strip_tags returns the same string, it's plain text)
    if ($string === strip_tags($string))
    {
      return false;
    }

    // Check if content starts with JSON brackets (faster than json_decode)
    $trimmed = trim($string);
    if ($trimmed !== '' && \in_array($trimmed[0], ['[', '{'], true))
    {
      return false;
    }

    // Check if content is a Backbone template (contains <% %>)
    return !str_contains($string, '<%');
  }

  /**
   * Gets the start comment for a node.
   *
   * @param NodeReference $ref The node reference
   *
   * @return string The start comment
   */
  private function getStartComment(NodeReference $ref): string
  {
    $prefix = $this->boxDrawings->getStartCommentPrefix();

    return $this->getComment($prefix, $ref);
  }

  /**
   * Gets the end comment for a node.
   *
   * @param NodeReference $ref The node reference
   *
   * @return string The end comment
   */
  private function getEndComment(NodeReference $ref): string
  {
    $prefix = $this->boxDrawings->getEndCommentPrefix();

    return $this->getComment($prefix, $ref);
  }

  /**
   * Gets a comment string for a node.
   *
   * @param string $prefix The comment prefix
   * @param NodeReference $ref The node reference
   *
   * @return string The comment string
   */
  protected function getComment(string $prefix, NodeReference $ref): string
  {
    $link = $this->getLink($ref);

    return '<!-- ' . $prefix . ' ' . $ref->getName() . ' [' . $link . '] #' . $ref->getId() . '-->';
  }

  /**
   * Gets the link URL for a node.
   *
   * @param NodeReference $ref The node reference
   *
   * @return string The link URL
   */
  protected function getLink(NodeReference $ref): string
  {
    return $this->urlGenerator->generate(
      'nowo_twig_inspector_template_link',
      [
        'template' => $ref->getTemplate(),
        'line'     => $ref->getLine(),
      ]
    );
  }
}

