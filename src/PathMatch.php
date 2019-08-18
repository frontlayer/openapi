<?php
declare(strict_types=1);

namespace FrontLayer\OpenApi;

use FrontLayer\JsonSchema\ValidationException;
use FrontLayer\JsonSchema\Validator;

class PathMatch
{
    protected $specification;

    protected $request;

    protected $validator;

    protected $operationSpecification = null;

    protected $matchParameters = [];

    public function __construct(Specification $specification, Request $request)
    {
        $this->matchParameters = (object)[];

        $this->specification = $specification;
        $this->request = $request;

        $this->validator = new Validator(Validator::MODE_CAST);

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

        foreach ($this->specification->storage()->paths as $path => $operations) {
            $hasPathParameters = strstr($path, '{') !== false;

            // Quick check is the method exist
            if (!property_exists($operations, $requestMethod)) {
                continue;
            }

            // Collect parameters
            if (!$hasPathParameters) {
                // Quick check for exact match if there is no path parameters
                if ($this->request->getPath() === $path) {
                    // Set match specification
                    $this->operationSpecification = $operations->{$requestMethod};

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

                // Check hardcoded directories (which are not parameters)
                // In the same time build the parameters object which will be validated against the path schema
                $pathParameters = (object)[];
                for ($i = 0; $i < $partsLength; $i++) {
                    if (substr($specificationPathParts[$i], 0, 1) == '{' && substr($specificationPathParts[$i], -1) == '}') {
                        // Get param name
                        $paramName = substr($specificationPathParts[$i], 1, -1);

                        // Empty values are not allowed in params check
                        if (!$requestParts[$i]) {
                            continue 2;
                        }

                        // Build the parameters collection
                        $pathParameters->{$paramName} = $requestParts[$i];
                    } elseif ($requestParts[$i] !== $specificationPathParts[$i]) {
                        // Non param parts not match
                        continue 2;
                    }
                }

                // Validate parameters collection
                try {
                    $pathParametersSchema = $operations->{$requestMethod}->parameters->path;
                    $this->matchParameters = $this->validator->validate($pathParameters, $pathParametersSchema);
                } catch (ValidationException $e) {
                    // Params are not valid against schema
                    continue;
                }

                // If all match
                {
                    // Set match specification
                    $this->operationSpecification = $operations->{$requestMethod};

                    // Break the check
                    return;
                }
            }
        }

        throw new PathNotFoundException('path not found.....');
    }
}
