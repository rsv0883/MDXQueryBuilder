<?php
namespace Machine\MdxBuilder\MDX\Expressions;

abstract class MDXExpression
{
    protected $parts = [];

    public function __construct(array $parts = null)
    {
        $this->parts = $parts;
    }

    abstract function __toString(): string;
}
