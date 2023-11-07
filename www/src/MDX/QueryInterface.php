<?php
declare(strict_types=1);
namespace Machine\MdxBuilder\MDX;

interface QueryInterface {

    #public function withMember($alias, $expression, $formatString = null);

    #public function withSet($alias, $expression);

    public function select($columns): QueryInterface;

    #public function by($rows);

    public function from(string $cube): QueryInterface;

    public function fromQuery(QueryInterface $subquery): QueryInterface;

    #public function where($clause);

    public function toString(): string;
}