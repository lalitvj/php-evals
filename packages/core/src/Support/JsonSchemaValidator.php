<?php

declare(strict_types=1);

namespace PhpEvals\Core\Support;

final class JsonSchemaValidator
{
    /**
     * @param  array<string, mixed>  $schema
     * @return array<int, string>
     */
    public function validate(mixed $value, array $schema, string $path = '$'): array
    {
        $errors = [];
        $type = $schema['type'] ?? null;

        if (is_string($type) && ! $this->matchesType($value, $type)) {
            $errors[] = sprintf('%s expected type %s.', $path, $type);

            return $errors;
        }

        if (($schema['type'] ?? null) === 'object' && is_array($value)) {
            $errors = array_merge($errors, $this->validateObject($value, $schema, $path));
        }

        if (($schema['type'] ?? null) === 'array' && is_array($value) && isset($schema['items']) && is_array($schema['items'])) {
            foreach ($value as $index => $item) {
                $errors = array_merge($errors, $this->validate($item, $schema['items'], sprintf('%s[%d]', $path, $index)));
            }
        }

        return $errors;
    }

    private function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'number' => is_int($value) || is_float($value),
            'integer' => is_int($value),
            'boolean' => is_bool($value),
            'array' => is_array($value) && array_is_list($value),
            'object' => is_array($value),
            'null' => $value === null,
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  array<string, mixed>  $schema
     * @return array<int, string>
     */
    private function validateObject(array $value, array $schema, string $path): array
    {
        $errors = [];
        $required = $schema['required'] ?? [];

        if (is_array($required)) {
            foreach ($required as $key) {
                if (is_string($key) && ! array_key_exists($key, $value)) {
                    $errors[] = sprintf('%s.%s is required.', $path, $key);
                }
            }
        }

        $properties = $schema['properties'] ?? [];
        if (is_array($properties)) {
            foreach ($properties as $key => $propertySchema) {
                if (! is_string($key) || ! is_array($propertySchema) || ! array_key_exists($key, $value)) {
                    continue;
                }

                $errors = array_merge(
                    $errors,
                    $this->validate($value[$key], $propertySchema, sprintf('%s.%s', $path, $key)),
                );
            }
        }

        $additionalProperties = $schema['additionalProperties'] ?? true;
        if ($additionalProperties === false && is_array($properties)) {
            foreach (array_keys($value) as $key) {
                if (is_string($key) && ! array_key_exists($key, $properties)) {
                    $errors[] = sprintf('%s.%s is not allowed.', $path, $key);
                }
            }
        }

        return $errors;
    }
}
