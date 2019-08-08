<?php
declare(strict_types=1);

namespace FrontLayer\OpenApi;

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

        // Check each attribute
        $this->processOpenApi();
        $this->processInfo();
        $this->processServers();
        $this->processComponents();
        $this->processSecurity();
        $this->processPaths();
    }

    public function storage(): object
    {
        return $this->storage;
    }

    protected function processOpenApi(): void
    {
        if (!property_exists($this->storage, 'openapi')) {
            throw new SpecificationException('... open api version is missing');
        }

        if (!is_string($this->storage->openapi)) {
            throw new SpecificationException('... "openapi" must be a string');
        }

        if (!in_array($this->storage->openapi, ['3.0.0', '3.0.1', '3.0.2'])) {
            throw new SpecificationException('... supported open api versions are between 3.0.0 and 3.0.2');
        }
    }

    protected function processInfo(): void
    {
        // @todo
        // @todo check for version
    }

    protected function processServers(): void
    {
        if (!property_exists($this->storage, 'servers')) {
            return;
        }

        // Transform single server object to array
        if (is_object($this->storage->servers)) {
            $this->storage->servers = [$this->storage->servers];
        }

        // Check is server attribute array
        if (!is_array($this->storage->servers)) {
            throw new SpecificationException('... "servers" needs to be array');
        }

        // Validate each server
        foreach ($this->storage->servers as $key => $value) {
            // Check for object type
            if (!is_object($value)) {
                throw new SpecificationException(sprintf(
                    '... "servers::%d" needs to be object',
                    $key
                ));
            }

            // Check for url property
            if (!property_exists($value, 'url')) {
                throw new SpecificationException(sprintf(
                    '... "servers::%d::url" is missing',
                    $key
                ));
            }

            // Validate url property
            if (!is_string($value->url) || !\FrontLayer\JsonSchema\Check::iri($value->url)) {
                throw new SpecificationException(sprintf(
                    '... "servers::%d::url" is not valid',
                    $key
                ));
            }
        }
    }

    protected function processComponents(): void
    {
        if (!property_exists($this->storage, 'components')) {
            return;
        }

        // All components are already extended and will be parsed with processPaths so we don`t need them anymore
        unset($this->storage->components);
    }

    protected function processSecurity(): void
    {
        // @todo
        //$paramLocations
        //security can be used in operations too
    }

    protected function processPaths(): void
    {
        // @todo

        if (!property_exists($this->storage, 'paths')) {
            throw new SpecificationException('... open api paths is missing');
        }

        if (!is_object($this->storage->paths)) {
            throw new SpecificationException('... "paths" must be an object');
        }

        foreach ($this->storage->paths as $path => $operations) {
            if (!is_object($operations)) {
                throw new SpecificationException(sprintf(
                    '... path "%s" must be an object',
                    $path
                ));
            }

            if (!property_exists($this->storage, 'paths')) {
                throw new SpecificationException('... open api paths is missing');
            }

            if (!is_object($this->storage->paths)) {
                throw new SpecificationException('... "paths" must be an object');
            }

            // Collect global params
            $globalParameters = [];

            if (property_exists($operations, 'parameters')) {
                $globalParameters = $operations->parameters;

                // Unset global parameters because they will be assigned in each operation
                unset($operations->parameters);
            }

            // Check each operation
            foreach ($operations as $method => $operation) {
                if (!in_array($method, $this->operations)) {
                    continue;
                }

                // Parameters
                {
                    // Merge global parameters with operation parameters (operation parameters are with higher priority)
                    $operationParameters = property_exists($operation, 'parameters') ? $operation->parameters : [];
                    try {
                        $operation->parameters = $this->processParameters($globalParameters, $operationParameters);
                    } catch (SpecificationException $e) {
                        throw new SpecificationException(sprintf(
                            '... there is a problem with parameters in "%s::%s". %s',
                            $path,
                            $method,
                            $e->getMessage()
                        ));
                    }
                }

                // @todo operationId
                {
                    //
                }

                // @todo requestBody
                {
                    //
                }

                // @todo responses
                {
                    //
                }
            }
        }
    }

    /**
     * Check parameters collection
     * Second argument will overwrite the first one
     * @param mixed ...$parametersCollection
     * @return array
     * @throws SpecificationException
     */
    protected function processParameters(... $parametersCollection): array
    {
        $tmpParameters = (object)[];

        foreach ($parametersCollection as $collection) {
            if (!is_array($collection)) {
                throw new SpecificationException('... parameters needs to be presented as array');
            }

            foreach ($collection as $parameter) {
                // @todo remove this when $ref extend is ready
                if (property_exists($parameter, '$ref')) {
                    continue;
                }

                if (!property_exists($parameter, 'name') || !is_string($parameter->name)) {
                    throw new SpecificationException('... there is a parameter without string name property');
                }

                if (!property_exists($parameter, 'in') || !is_string($parameter->in)) {
                    throw new SpecificationException(sprintf(
                        '... the param "%s" has missing "in" property or its not a string',
                        $parameter->name
                    ));
                }

                $tmpParameters->{$parameter->in . '::' . $parameter->name} = $parameter;
            }
        }

        return array_values((array)$tmpParameters);
    }
}
