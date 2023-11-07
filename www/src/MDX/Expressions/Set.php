<?php
/**
 * Created by PhpStorm.
 * User: rsv08
 * Date: 05.11.2023
 * Time: 9:58
 */

namespace Machine\MdxBuilder\MDX\Expressions;


class Set extends MDXExpression
{
    public function __toString(): string
    {
        $expression = "";
        foreach($this->parts as $part) {
            $expression .= "{$part}, ";
        }
        $expression = trim($expression, " ,");
        return (!empty($expression)) ? "{{$expression}}" : "";
    }
}