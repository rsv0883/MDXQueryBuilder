<?php
declare(strict_types=1);

namespace Tests;

use Machine\MdxBuilder\MDX\Expressions\CrossJoin;
use Machine\MdxBuilder\MDX\Expressions\NonEmpty;
use Machine\MdxBuilder\MDX\Expressions\Range;
use Machine\MdxBuilder\MDX\Expressions\Set;
use PHPUnit\Framework\TestCase;
use Machine\MdxBuilder\MDX\MDXQuery;

class QueryTest extends TestCase
{
    protected $query;

    protected $resultMdx;

    public function setUp():void
    {
        $this->query = new MDXQuery();
        parent::setUp();
    }

    public function tearDown():void
    {
        parent::tearDown();
        $this->assertEqualsIgnoringCase($this->expectedMdx, $this->query->toString());
    }

    public function testSelectSimpleQuery()
    {
        $this->query
            ->select('[Measures].[Amount]')
            ->by('[Product].[Product].[Name]')
            ->from('Sales');

        $this->expectedMdx =
            'SELECT [Measures].[Amount] ON COLUMNS, [Product].[Product].[Name] ON ROWS FROM [Sales]';

    }

    public function testSelectSimpleQuery2()
    {
        $this->query
            ->select('[Measures].[Amount]')
            ->by('([Product].[Product].[Name].&[Носок], [Product].[Product].[Name].&[Валенок])')
            ->from('Sales');

        $this->expectedMdx =
            'SELECT [Measures].[Amount] ON COLUMNS, '.
            '([Product].[Product].[Name].&[Носок], [Product].[Product].[Name].&[Валенок]) ON ROWS '.
            'FROM [Sales]';

    }

    public function testSelectQuerySubquery1()
    {
        $this->query
            ->select('[Measures].[Amount]')
            ->by('([Product].[Product].[Name].&[Носок], [Product].[Product].[Name].&[Валенок])')
            ->fromQuery(
                (new MDXQuery())->select(['[Date].[Date].[Month].&[202101]'])
                    ->from('Sales')
            );

        $this->expectedMdx =
        'SELECT '.
        '[Measures].[Amount] ON COLUMNS, '.
        '([Product].[Product].[Name].&[Носок], [Product].[Product].[Name].&[Валенок]) ON ROWS '.
        'FROM ('.
            'SELECT [Date].[Date].[Month].&[202101] ON COLUMNS FROM [Sales]'.
        ')';
    }

    public function testSelectQuerySubquery2()
    {
        $this->query
            ->select(['[Measures].[Amount]'])
            ->by('([Product].[Product].[Name].&[Носок], [Product].[Product].[Name].&[Валенок])')
            ->fromQuery(
                (new MDXQuery())->select(['[Date].[Date].[Month].&[202101]'])
                ->fromQuery(
                    (new MDXQuery())->select('[Product].[Category].[Id].&[10]')
                        ->from('Sales')
                ));

        $this->expectedMdx =
            'SELECT '.
            '[Measures].[Amount] ON COLUMNS, '.
            '([Product].[Product].[Name].&[Носок], [Product].[Product].[Name].&[Валенок]) ON ROWS '.
            'FROM ('.
                'SELECT [Date].[Date].[Month].&[202101] ON COLUMNS FROM ('.
                    'SELECT [Product].[Category].[Id].&[10] ON COLUMNS FROM [Sales]'.
                ')'.
            ')';

    }

    public function testSetInRows()
    {
        $this->query
        ->select('[Measures].[Amount]')
        ->by(['[Product].[Product].[Name]', '[Date].[Year]'])
        ->from('Sales');

        $this->expectedMdx =
            'SELECT [Measures].[Amount] ON COLUMNS, {[Product].[Product].[Name], [Date].[Year]} ON ROWS FROM [Sales]';
    }

    public function testSetInColumnsAndRows()
    {
        $this->query
            ->select(['[Measures].[Amount]', '[Measures].[Rest]'])
            ->by(['[Product].[Product].[Name]', '[Date].[Year]'])
            ->from('Sales');

        $this->expectedMdx =
            'SELECT {[Measures].[Amount], [Measures].[Rest]} ON COLUMNS, {[Product].[Product].[Name], [Date].[Year]} ON ROWS FROM [Sales]';
    }

    public function testWithSet()
    {
        $this->query
            ->withSet('MySetName', '{[Measures].[Amount], [Measures].[Rest]}')
            ->select('MySetName')
            ->by('[Product].[Product].[Name]')
            ->from('Sales');

        $this->expectedMdx =
            'WITH SET MySetName AS {[Measures].[Amount], [Measures].[Rest]} '.
            'SELECT MySetName ON COLUMNS, '.
            '[Product].[Product].[Name] ON ROWS FROM [Sales]';
    }

    public function testWithSetAndRange()
    {
        $this->query
            ->withSet('MySetName', '{[Measures].[Amount], [Measures].[Rest]}')
            ->select('MySetName')
            ->by('[Product].[Product].[Name]')
            ->fromQuery(
                (new MDXQuery())->select(new Range('[Date].[Date].[Month].&[202101]','[Date].[Date].[Month].&[202112]'))
                ->from('Sales')
            );

        $this->expectedMdx =
            'WITH SET MySetName AS {[Measures].[Amount], [Measures].[Rest]} '.
            'SELECT MySetName ON COLUMNS, '.
            '[Product].[Product].[Name] ON ROWS FROM ('.
                'SELECT {[Date].[Date].[Month].&[202101]:[Date].[Date].[Month].&[202112]} ON COLUMNS FROM [Sales]'.
            ')';
    }

    public function testNonEmpty()
    {
        $this->query
            ->withSet('MySetName', '{[Measures].[Amount], [Measures].[Rest]}')
            ->select('MySetName')
            ->by(new NonEmpty(['[Product].[Product].[Name]', 'MySetName']))
            ->fromQuery(
                (new MDXQuery())->select(new Range('[Date].[Date].[Month].&[202101]','[Date].[Date].[Month].&[202112]'))
                    ->from('Sales')
            );

        $this->expectedMdx =
            'WITH SET MySetName AS {[Measures].[Amount], [Measures].[Rest]} '.
            'SELECT MySetName ON COLUMNS, '.
            'NonEmpty([Product].[Product].[Name], MySetName) ON ROWS FROM ('.
            'SELECT {[Date].[Date].[Month].&[202101]:[Date].[Date].[Month].&[202112]} ON COLUMNS FROM [Sales]'.
            ')';
    }

    public function testNonEmpty2()
    {
        $this->query
            ->withSet('MySetName', '{[Measures].[Amount], [Measures].[Rest]}')
            ->select(new Set(['[Address].[Town]', 'MySetName']))
            ->by(new NonEmpty(['[Product].[Product].[Name]', 'MySetName']))
            ->fromQuery(
                (new MDXQuery())->select(new Range('[Date].[Date].[Month].&[202101]','[Date].[Date].[Month].&[202112]'))
                    ->from('Sales')
            );

        $this->expectedMdx =
            'WITH SET MySetName AS {[Measures].[Amount], [Measures].[Rest]} '.
            'SELECT {[Address].[Town], MySetName} ON COLUMNS, '.
            'NonEmpty([Product].[Product].[Name], MySetName) ON ROWS FROM ('.
            'SELECT {[Date].[Date].[Month].&[202101]:[Date].[Date].[Month].&[202112]} ON COLUMNS FROM [Sales]'.
            ')';
    }

    public function testCrossJoin()
    {
        $this->query
            ->withSet('MySetName', '{[Measures].[Amount], [Measures].[Rest]}')
            ->select(new CrossJoin(['[Address].[Town]', 'MySetName']))
            ->by(new NonEmpty(['[Product].[Product].[Name]', 'MySetName']))
            ->fromQuery(
                (new MDXQuery())->select(new Range('[Date].[Date].[Month].&[202101]','[Date].[Date].[Month].&[202112]'))
                    ->from('Sales')
            );

        $this->expectedMdx =
            'WITH SET MySetName AS {[Measures].[Amount], [Measures].[Rest]} '.
            'SELECT [Address].[Town] * MySetName ON COLUMNS, '.
            'NonEmpty([Product].[Product].[Name], MySetName) ON ROWS FROM ('.
            'SELECT {[Date].[Date].[Month].&[202101]:[Date].[Date].[Month].&[202112]} ON COLUMNS FROM [Sales]'.
            ')';
    }

    public function testCrossJoinAndNonEmpty()
    {
        $this->query
            ->withSet('MySetName', '{[Measures].[Amount], [Measures].[Rest]}')
            ->select(
                    new CrossJoin([
                        new NonEmpty([
                            new CrossJoin(['[Address].[Town]', new NonEmpty(['[Branch].[Name]', 'MySetName'])]),
                            'MySetName'
                        ]),
                        'MySetName'
                    ])
            )
            ->by(
                new NonEmpty([
                    new CrossJoin([
                        '[Product].[Product].[Name]',
                        new NonEmpty(['[Date].[Date].[Day]', 'MySetName'])
                    ]),
                    'MySetName'
                ])
            )
            ->fromQuery(
                (new MDXQuery())->select(new Range('[Date].[Date].[Month].&[202101]','[Date].[Date].[Month].&[202112]'))
                    ->from('Sales')
            );

        $this->expectedMdx =
            'WITH SET MySetName AS {[Measures].[Amount], [Measures].[Rest]} '.
            'SELECT NonEmpty([Address].[Town] * NonEmpty([Branch].[Name], MySetName), MySetName) * MySetName ON COLUMNS, '.
            'NonEmpty([Product].[Product].[Name] * NonEmpty([Date].[Date].[Day], MySetName), MySetName) ON ROWS FROM ('.
                'SELECT {[Date].[Date].[Month].&[202101]:[Date].[Date].[Month].&[202112]} ON COLUMNS FROM [Sales]'.
            ')';
    }

    public function testCrossJoinAndNonEmpty2()
    {
        $this->query
            ->withSet('MySetName', '{[Measures].[Amount], [Measures].[Rest]}')
            ->select(
                new CrossJoin([
                    new NonEmpty([
                        new CrossJoin([
                            '[Address].[Town]',
                            new NonEmpty([
                                new CrossJoin([
                                    '[Branch].[Name]',
                                     new NonEmpty(['[SaleType].[Name]', 'MySetName'])
                                ]),
                                'MySetName'
                            ])]),
                        'MySetName'
                    ]),
                    'MySetName'
                ])
            )
            ->by(
                new NonEmpty([
                    new CrossJoin([
                        '[Product].[Product].[Name]',
                        new NonEmpty(['[Date].[Date].[Day]', 'MySetName'])
                    ]),
                    'MySetName'
                ])
            )
            ->fromQuery(
                (new MDXQuery())->select(new Range('[Date].[Date].[Month].&[202101]','[Date].[Date].[Month].&[202112]'))
                    ->from('Sales')
            );

        $this->expectedMdx =
            'WITH SET MySetName AS {[Measures].[Amount], [Measures].[Rest]} '.
            'SELECT NonEmpty([Address].[Town] * NonEmpty([Branch].[Name] * NonEmpty([SaleType].[Name], MySetName), MySetName), MySetName) * MySetName ON COLUMNS, '.
            'NonEmpty([Product].[Product].[Name] * NonEmpty([Date].[Date].[Day], MySetName), MySetName) ON ROWS FROM ('.
            'SELECT {[Date].[Date].[Month].&[202101]:[Date].[Date].[Month].&[202112]} ON COLUMNS FROM [Sales]'.
            ')';
    }

    public function testCrossJoinAndNonEmpty3()
    {
        $this->query
            ->withSet('MySetName', '{[Measures].[Amount], [Measures].[Rest]}')
            ->select(
                new CrossJoin([
                    new NonEmpty([
                        new CrossJoin([
                            '[Address].[Town]',
                            new NonEmpty([
                                new CrossJoin([
                                    '[Branch].[Name]',
                                    new NonEmpty(['[SaleType].[Name]', 'MySetName'])
                                ]),
                                'MySetName'
                            ])]),
                        'MySetName'
                    ]),
                    'MySetName'
                ])
            )
            ->by(
                new NonEmpty([
                    new CrossJoin([
                        '[Product].[Product].[Name]',
                        new NonEmpty(['[Date].[Date].[Day]', 'MySetName'])
                    ]),
                    'MySetName'
                ])
            )
            ->fromQuery(
                (new MDXQuery())->select(
                    new CrossJoin([
                        new Set(['[Product].[Product].&[134947954]', '[Product].[Product].&[134947981]', '[Product].[Product].&[11145970]', '[Product].[Product].&[101362503]']),
                        new Set(['[Address].[Region].&[77]', '[Address].[Region].&[54]']),
                        new Set(['[Branch].[Name].&[6332]', '[Branch].[Name].&[295]'])
                    ])
                )
                    ->fromQuery(
                        (new MDXQuery())->select(
                            new Range('[Date].[Date].[Month].&[202101]', '[Date].[Date].[Month].&[202112]')
                        )
                        ->from('Sales')
                    )
            );

        $this->expectedMdx =
            'WITH SET MySetName AS {[Measures].[Amount], [Measures].[Rest]} '.
            'SELECT NonEmpty([Address].[Town] * NonEmpty([Branch].[Name] * NonEmpty([SaleType].[Name], MySetName), MySetName), MySetName) * MySetName ON COLUMNS, '.
	        'NonEmpty([Product].[Product].[Name] * NonEmpty([Date].[Date].[Day], MySetName), MySetName) ON ROWS '.
            'FROM ('.
                'SELECT {[Product].[Product].&[134947954], [Product].[Product].&[134947981], [Product].[Product].&[11145970], [Product].[Product].&[101362503]} * {[Address].[Region].&[77], [Address].[Region].&[54]} * {[Branch].[Name].&[6332], [Branch].[Name].&[295]} ON COLUMNS '.
	            'FROM ('.
                    'SELECT {[Date].[Date].[Month].&[202101]:[Date].[Date].[Month].&[202112]} ON COLUMNS FROM [Sales]'.
	            ')'.
            ')';
    }
}

