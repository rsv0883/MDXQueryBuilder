<?php
function convert_to_array($value)
{
    if (is_null($value)) return [];
    return !is_array($value) ? [$value] : $value;
}