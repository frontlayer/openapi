<?php
declare(strict_types=1);

namespace FrontLayer\OpenApi;

use FrontLayer\JsonSchema\Schema;
use FrontLayer\JsonSchema\Validator;

class Specification
{
    protected $paramLocations = [
        'path',
        'query',
        'header',
        'header',
        'cookie',
    ];

    protected $operations = [
        'get',
        'put',
        'post',
        'delete',
        'options',
        'head',
        'patch',
        'trace',
    ];

    /**
     * Open API specification
     * @var object
     */
    protected $storage;

    public function __construct(object $specification)
    {
        $this->storage = $specification;

        // Validate OpenAPI schema
        $validator = new Validator();
        $schema = new Schema(json_decode(file_get_contents(__DIR__ . '/openapi-schema.json')), '4');
        $validator->validate($specification, $schema);

        // Process the schema
        $this->mergeGlobalOperationParameters();
        $this->processParameters();
    }

    public function storage(): object
    {
        return $this->storage;
    }

    protected function mergeGlobalOperationParameters(): void
    {
        foreach ($this->storage->paths as $path => $operations) {
            // Assign default blank object when there is no global parameters
            if (!property_exists($operations, 'parameters')) {
                $operations->parameters = (object)[];
            }

            foreach ($operations as $method => $operation) {
                // When the item is not in official operation methods will be skipped
                if (!in_array($method, $this->operations)) {
                    continue;
                }

                // If there is no parameters in current operation, global will be assigned
                if (!property_exists($operation, 'parameters') || count((array)$operation->parameters) === 0) {
                    $operation->parameters = $operations->parameters;
                    continue;
                }

                // Merge global parameters with operation parameters
                foreach ($operations->parameters as $globalParameter) {
                    foreach ($operation->parameters as $operationParameter) {
                        // If global parameter exists in operation parameter then it will be skipped
                        // Operation parameters has higher priority than global parameters
                        if ($globalParameter->name === $operationParameter->name && $globalParameter->in === $operationParameter->in) {
                            continue 2;
                        }
                    }

                    // If global parameter is not already defined in the operation parameters then it will be added to them
                    $operation->parameters[] = $globalParameter;
                }
            }

            // Remove global parameters because they are assigned to each operation
            unset($operations->parameters);
        }
    }

    protected function processParameters(): void
    {
        // Rebuild operation parameters in Schema way
        foreach ($this->storage->paths as $path => $operations) {
            foreach ($operations as $method => $operation) {
                // Set all parameters in single variable and reset them as new Json Schema object
                $operationParameters = $operation->parameters;

                $blank = (object)[
                    'type' => 'object',
                    'required' => [],
                    'properties' => (object)[]
                ];

                $operation->parameters = (object)[
                    'path' => clone $blank,
                    'query' => clone $blank,
                    'header' => clone $blank,
                    'cookie' => clone $blank,
                ];

                // Reassign the parameters
                foreach ($operationParameters as $parameter) {
                    $operation->parameters->{$parameter->in}->properties->{$parameter->name} = $parameter->schema;

                    if (!empty($parameter->required)) {
                        $operation->parameters->{$parameter->in}->required[] = $parameter->name;
                    }
                }

                // Transform each parameters collection (Json Schema object) to Schema
                foreach ($operation->parameters as $place => $collection) {
                    $operation->parameters->{$place} = new Schema($collection);
                }
            }
        }
    }
}
