<?php
declare(strict_types=1);

use FrontLayer\JsonSchema\Validator;
use FrontLayer\OpenApi\PathMatch;
use FrontLayer\OpenApi\Request;
use FrontLayer\OpenApi\Specification;

require __DIR__ . './../vendor/autoload.php';

class TestPaths
{
    /**
     * Test specification
     * @var Specification
     */
    protected $specification;

    /**
     * Validator
     * @var Validator
     */
    protected $validator;

    /**
     * TestPaths constructor
     * @param Specification $specification
     */
    public function __construct(Specification $specification)
    {
        $this->specification = $specification;
        $this->validator = new Validator(Validator::MODE_CAST);
    }

    public function run(): void
    {
        foreach ($this->specification->storage()->paths as $path => $operations) {
            foreach ($operations as $method => $operation) {
                // Get tests
                $tests = $operation->{'x-tests'};

                // Test matches
                if (!empty($tests->correct)) {
                    foreach ($tests->correct as $expectPath) {
                        try {
                            // Prepare the emulated request
                            $request = new Request();
                            $request->setPath($expectPath);
                            $request->setMethod($method);

                            $match = new PathMatch($this->specification, $request, $this->validator);
                            $match->getOperationSpecification();
                            $match->getParameters();

                            if (!in_array($expectPath, $match->getOperationSpecification()->{'x-tests'}->correct, true)) {
                                throw new \Exception('Incorrect match');
                            }
                        } catch (\Exception $e) {
                            var_dump('MATCH FAIL');
                            var_dump($e->getMessage());
                            var_dump($method . '::' . $expectPath);
                        }
                    }
                }

                // Test non matches
                if (!empty($tests->wrong)) {
                    foreach ($tests->wrong as $expectPath) {
                        try {
                            $request = new Request();
                            $request->setPath($expectPath);
                            $request->setMethod($method);

                            $match = new PathMatch($this->specification, $request, $this->validator);
                            $match->getOperationSpecification();
                            $match->getParameters();

                            var_dump('-----');
                            var_dump('NOT MATCH FAIL');
                            var_dump($method . '::' . $expectPath);
                        } catch (\FrontLayer\OpenApi\PathNotFoundException $e) {
                        }
                    }
                }
            }
        }
    }
}

$specification = new Specification(json_decode(json_encode(yaml_parse_file(__DIR__ . '/path.yaml'))));
$testPath = new TestPaths($specification);
$testPath->run();
