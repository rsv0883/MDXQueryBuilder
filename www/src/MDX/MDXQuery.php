<?php
declare(strict_types=1);
namespace Machine\MdxBuilder\MDX;

class MDXQuery implements QueryInterface{

    /* Columns array */
    protected $columns = [];

    /* Rows array */
    protected $rows = [];

    /* Wheres array */
    protected $wheres = [];

    /* Members array */
    protected $members = [];

    /* Sets array */
    protected $sets = [];

    /* Cube string*/
    protected $cube;

    /* Subquery QueryInterface*/
    protected $subquery;

    /* DB connection*/
    protected $connection;

    /*public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }*/

    public function __construct()
    {
    }

    public function select($columns): QueryInterface
    {
        $this->columns = array_merge($this->columns, convert_to_array($columns));
        return $this;
    }

    public function by($rows): QueryInterface
    {
        $this->rows = array_merge($this->rows, convert_to_array($rows));
        return $this;
    }


    public function from(string $cube): QueryInterface
    {
        if (!empty($this->subquery)) {
            throw new MdxQueryException('Method fromQuery() had already been applied!');
        }
        $this->cube = $cube;
        return $this;
    }

    public function fromQuery(QueryInterface $subquery): QueryInterface
    {
        if (!empty($this->cube)) {
            throw new MdxQueryException('Method from() had already been applied!');
        }
        $this->subquery = $subquery;
        return $this;
    }

    public function withSet(string $alias, $expression): QueryInterface
    {
        $this->withSets[$alias] = $expression;

        return $this;
    }

    protected function normalizeQuery(string $mdx): string
    {
        return trim($mdx);
    }

    public function toString(): string
    {
        $mdx =
            $this->buildWith() .
            $this->buildSelect() .
            $this->buildRows() .
            $this->buildFrom() ;
            //$this->buildWhere();

        return $this->normalizeQuery($mdx);
    }

    protected function buildWith(): string
    {
        $with = '';

        if (empty($this->withSets)) {
            return $with;
        }

        foreach ($this->withSets as $alias => $expression) {
            $with .= "SET {$alias} AS {$expression} ";
        }

        return "WITH {$with}";
    }


    protected function buildSelect(): string
    {
        if (empty($this->columns)) {
            throw new MdxQueryException('No columns passed!');
        }

        $columns = implode(', ', $this->columns);
        if (count($this->columns) > 1) $columns = "{{$columns}}";
        return "SELECT {$columns} ON COLUMNS";
    }

    /**
     * @return string
     */
    protected function buildRows(): string
    {
        if (empty($this->rows)) return '';
        $rows = implode(', ', $this->rows);
        if (count($this->rows) > 1) $rows = "{{$rows}}";
        return ", {$rows} ON ROWS";
    }

    protected function buildFrom(): string
    {
        if ((empty($this->cube)) and (empty($this->subquery))) {
            throw new MdxQueryException('No cube selected!');
        }
        return " FROM ". ((!empty($this->cube)) ? "[{$this->cube}]" : "({$this->subquery->toString()})");
    }
}