<?php
declare(strict_types=1);

namespace FrontLayer\OpenApi;

use FrontLayer\JsonSchema\ValidationException;
use FrontLayer\JsonSchema\Validator;

class Path
{
    protected $specification;

    protected $request;

    protected $validator;

    protected $operationSpecification = null;

    protected $matchParameters = [];

    public function __construct(Specification $specification, Request $request, Validator $validator)
    {
        $this->matchParameters = (object)[]; // @todo move it to class body when PHP is ready for this syntax

        $this->specification = $specification;
        $this->request = $request;
        $this->validator = $validator;

        $this->match();
    }

    public function getOperationSpecification(): ?object
    {
        return $this->operationSpecification;
    }

    public function getParameters(): object
    {
        return $this->matchParameters;
    }

    protected function match(): void
    {
        $requestMethod = $this->request->getMethod();
        $requestParts = explode('/', $this->request->getPath());
        $partsLength = count($requestParts);

        foreach ($this->specification->storage()->paths as $path => $pathData) {
            $hasPathParameters = strstr($path, '{') !== false;

            // Quick check is the method exist
            if (!property_exists($pathData, $requestMethod)) {
                continue;
            }

            // Collect parameters
            $operationParameters = [];

            if (property_exists($pathData->{$requestMethod}, 'parameters')) {
                $operationParameters = $pathData->{$requestMethod}->parameters;
            }

            if (!$hasPathParameters) {
                // Quick check for exact match if there is no path parameters
                if ($this->request->getPath() === $path) {
                    // Set match specification
                    $this->operationSpecification = $pathData->{$requestMethod};
                    $this->operationSpecification->parameters = $operationParameters;

                    // Break the check
                    return;
                }
            } else {
                // Get specification path parts
                $specificationPathParts = explode('/', $path);

                // Quick check for equal slashes number
                if ($partsLength !== count($specificationPathParts)) {
                    continue;
                }

                // Collect path parameters
                $pathParameters = (object)[];
                foreach ($operationParameters as $parameter) {
                    if ($parameter->in === 'path') {
                        $pathParameters->{$parameter->name} = $parameter->schema;
                    }
                }

                // Check parts
                $successfulParts = 0;

                for ($i = 0; $i < $partsLength; $i++) {
                    if (substr($specificationPathParts[$i], 0, 1) == '{' && substr($specificationPathParts[$i], -1) == '}') {
                        // Get param name
                        $paramName = substr($specificationPathParts[$i], 1, -1);

                        // Empty values are not allowed in params check
                        if (!$requestParts[$i]) {
                            break;
                        }

                        // Try to validate
                        try {
                            // If parameters is valid then cast the data and set it to match parameters
                            $this->matchParameters->{$paramName} = $this->validator->validate($requestParts[$i], $pathParameters->{$paramName}, Validator::MODE_CAST);
                        } catch (ValidationException $e) {
                            // Param is not valid against schema
                            break;
                        }
                    } elseif ($requestParts[$i] !== $specificationPathParts[$i]) {
                        // Non param parts not match
                        break;
                    }

                    $successfulParts++;
                }

                if ($successfulParts !== $partsLength) {
                    break;
                }

                // If all match
                {
                    // Set match specification
                    $this->operationSpecification = $pathData->{$requestMethod};
                    $this->operationSpecification->parameters = $operationParameters;

                    // Break the check
                    return;
                }
            }
        }

        throw new PathNotFoundException('path not found.....');
    }
}
