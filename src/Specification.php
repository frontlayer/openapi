<?php
declare(strict_types=1);

namespace FrontLayer\OpenApi;

class Specification
{
    /**
     * Open API specification
     * @var object
     */
    protected $storage;

    /**
     * Specification constructor.
     * @param object $specification
     */
    public function __construct(object $specification)
    {
        $this->storage = $specification;
    }

    public function storage(): object
    {
        return $this->storage;
    }
}
