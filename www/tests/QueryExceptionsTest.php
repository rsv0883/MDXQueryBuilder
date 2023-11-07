<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Machine\MdxBuilder\MDX\MDXQuery;
use Machine\MdxBuilder\MDX\MdxQueryException;

class QueryExceptionsTest extends TestCase
{
    protected $query;

    public function setUp():void
    {
        $this->query = new MDXQuery();
        parent::setUp();
    }


    public function testNoColumnsInSelect()
    {
        $this->query->from('Cube');

        $this->expectExceptionObject(new MdxQueryException('No columns passed!'));
        //$this->expectException(MdxQueryException::class);

        $this->query->toString();
    }

}