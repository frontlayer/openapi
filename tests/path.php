<?php
declare(strict_types=1);

require __DIR__ . './../vendor/autoload.php';

class TestPaths
{
    protected $tests;

    protected $validator;

    public function __construct(object $tests)
    {
        $this->tests = $tests;
        $this->validator = new \FrontLayer\JsonSchema\Validator();
    }

    public function run(): void
    {
        foreach ($this->tests->paths as $path => $pathSpecification) {
            $parameters = (object)[];

            // Get global path parameters
            $parameters = $this->collectPathParameters($pathSpecification, $parameters);

            // Check each method
            foreach ($pathSpecification as $method => $methodData) {
                // Skip non-method properties
                if (!in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'head', 'options', 'trace'])) {
                    continue;
                }

                // Get method parameters
                $parameters = $this->collectPathParameters($methodData, $parameters);

                // Get tests
                $tests = $methodData->{'$tests'};

                // Test matches
                if (!empty($tests->match)) {
                    foreach ($tests->match as $expect) {
                        try {
                            $request = new \FrontLayer\OpenApi\Request();
                            $request->setPath($expect);
                            $request->setMethod($method);

                            $testSpecification = new \FrontLayer\OpenApi\Specification((object)[
                                'openapi' => '3.0.0',
                                'paths' => (object)[
                                    $path => $pathSpecification
                                ]
                            ]);

                            $zzzz = new \FrontLayer\OpenApi\Path($testSpecification, $request, $this->validator);
                            $zzzz->getOperationSpecification();
                            $zzzz->getParameters();
                        } catch (\Exception $e) {
                            var_dump('MATCH FAIL');
                            var_dump($e->getMessage());
                            var_dump($method . '::' . $expect);
                        }
                    }
                }

                // Test non matches
                if (!empty($tests->notMatch)) {
                    foreach ($tests->notMatch as $expect) {
                        try {
                            $request = new \FrontLayer\OpenApi\Request();
                            $request->setPath($expect);
                            $request->setMethod($method);

                            $testSpecification = new \FrontLayer\OpenApi\Specification((object)[
                                'openapi' => '3.0.0',
                                'paths' => (object)[
                                    $path => $pathSpecification
                                ]
                            ]);

                            new \FrontLayer\OpenApi\Path($testSpecification, $request, $this->validator);

                            var_dump('-----');
                            var_dump('NOT MATCH FAIL');
                            var_dump($method . '::' . $expect);
                        } catch (\FrontLayer\OpenApi\PathNotFoundException $e) {
                        }
                    }
                }
            }
        }
    }

    public function collectPathParameters(object $object, object $currentParameters): object
    {
        if (property_exists($object, 'parameters')) {
            foreach ($object->parameters as $parameter) {
                if ($parameter->in === 'path') {
                    $currentParameters->{$parameter->name} = $parameter->schema;
                }
            }
        }

        return $currentParameters;
    }
}

$testSpecification = json_decode(json_encode(yaml_parse_file('./path.yaml')));
$testPath = new TestPaths($testSpecification);
$testPath->run();
