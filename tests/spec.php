<?php
declare(strict_types=1);

require __DIR__ . './../vendor/autoload.php';

class TestSpec
{
    protected $specification;

    protected $validator;

    public function __construct(object $specification)
    {
        $this->specification = new \FrontLayer\OpenApi\Specification($specification);
        $this->validator = new \FrontLayer\JsonSchema\Validator();
    }

    public function run(): void
    {
        //var_dump($this->specification->storage());
    }
}

$testSpecification = json_decode(json_encode(yaml_parse_file(__DIR__ . '/spec.yaml')));
$testPath = new TestSpec($testSpecification);
$testPath->run();
