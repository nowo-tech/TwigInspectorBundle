<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests for DemoController.
 *
 * Verifies that the demo controller works correctly and integrates
 * with the Twig Inspector Bundle.
 *
 * @covers \App\Controller\DemoController
 */
final class DemoControllerTest extends WebTestCase
{
    /**
     * Tests that the home page is accessible and returns a successful response.
     */
    public function testHomePageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Twig Inspector Bundle');
    }

    /**
     * Tests that the home page contains the expected Symfony version badge.
     */
    public function testHomePageContainsSymfonyVersion(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('[data-testid="demo-platform-bundle"]', 'Twig Inspector');
        $this->assertSelectorTextContains('.nowo-ui-demo-platform__version', 'Symfony 8.0');
    }

    /**
     * Tests that the home page renders the demo message correctly.
     */
    public function testHomePageRendersMessage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('main', 'showcase the Twig Inspector Bundle functionality with Symfony 8.0');
    }

    /**
     * Tests that the home page contains the expected items list.
     */
    public function testHomePageContainsItemsList(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists("[data-nowo-ui-demo-section=list] ul");
        $this->assertSelectorTextContains('[data-nowo-ui-demo-section="list"] ul', 'Enable Twig Inspector');
    }

    /**
     * Tests that the home page contains instructions.
     */
    public function testHomePageContainsInstructions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h2', 'Instructions');
    }
}
