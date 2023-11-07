<?php
/**
 * Created by PhpStorm.
 * User: rsv08
 * Date: 05.11.2023
 * Time: 9:58
 */

namespace Machine\MdxBuilder\MDX\Expressions;


use Machine\MdxBuilder\MDX\MdxQueryException;

class Range extends MDXExpression
{
    public function __construct($startDate, $endDate)
    {
        if (empty($startDate) or empty($endDate)) throw new MdxQueryException("Period must have 'startDate' and 'endDate' parameters!");
        $this->parts = [$startDate, $endDate];
    }

    public function __toString(): string
    {
        return (!empty($this->parts)) ? "{{$this->parts[0]}:{$this->parts[1]}}" : "";
    }
}