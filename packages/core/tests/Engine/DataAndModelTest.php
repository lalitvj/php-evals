<?php

declare(strict_types=1);

namespace PhpEvals\Core\Tests\Engine;

use PhpEvals\Core\Data\ModelRequest;
use PhpEvals\Core\Data\ModelResponse;
use PhpEvals\Core\Exceptions\EvalExecutionException;
use PhpEvals\Core\Model\ArrayMapModelClient;
use PhpEvals\Core\Model\NullModelClient;
use PHPUnit\Framework\TestCase;

final class DataAndModelTest extends TestCase
{
    public function test_model_response_from_array_maps_tool_calls(): void
    {
        $response = ModelResponse::fromArray([
            'output' => 'ok',
            'tool_calls' => [
                ['name' => 'create_ticket', 'arguments' => ['order_id' => 'A1']],
            ],
            'raw' => ['provider' => 'demo'],
        ]);

        self::assertSame('ok', $response->output);
        self::assertCount(1, $response->toolCalls);
        self::assertSame('create_ticket', $response->toolCalls[0]->name);
    }

    public function test_array_map_model_client_uses_case_id_lookup(): void
    {
        $client = new ArrayMapModelClient([
            'responses' => [
                'case-1' => [
                    'output' => 'resolved',
                    'tool_calls' => [['name' => 'tool', 'arguments' => []]],
                ],
            ],
        ]);

        $response = $client->complete(new ModelRequest('case-1', 'input'));
        $fallback = $client->complete(new ModelRequest('missing', 'input'));

        self::assertSame('resolved', $response->output);
        self::assertCount(1, $response->toolCalls);
        self::assertSame('input', $fallback->output);
    }

    public function test_null_model_client_throws_actionable_error(): void
    {
        $client = new NullModelClient;

        $this->expectException(EvalExecutionException::class);
        $client->complete(new ModelRequest('case-1', 'input'));
    }
}
