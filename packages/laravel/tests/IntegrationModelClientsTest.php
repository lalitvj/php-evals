<?php

declare(strict_types=1);

namespace PhpEvals\Laravel\Tests;

use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Laravel\Integrations\LaravelAI\LaravelAIModelClient;
use PhpEvals\Laravel\Integrations\Prism\PrismModelClient;
use PHPUnit\Framework\TestCase;

final class IntegrationModelClientsTest extends TestCase
{
    public function test_prism_model_client_supports_injected_handler(): void
    {
        $client = new PrismModelClient([
            'response_handler' => static function (ModelRequest $request): array {
                return [
                    'output' => strtoupper($request->input),
                    'prompt_tokens' => 12,
                    'completion_tokens' => 4,
                ];
            },
        ]);

        $response = $client->complete(new ModelRequest('case-1', 'refund'));

        self::assertSame('REFUND', $response->output);
        self::assertSame(12, $response->promptTokens);
        self::assertSame(4, $response->completionTokens);
    }

    public function test_laravel_ai_model_client_supports_injected_handler(): void
    {
        $client = new LaravelAIModelClient([
            'response_handler' => static function (ModelRequest $request): string {
                return 'agent:'.$request->input;
            },
        ]);

        $response = $client->complete(new ModelRequest('case-1', 'refund'));

        self::assertSame('agent:refund', $response->output);
    }
}
