<?
namespace Machine\MdxBuilder;
require __DIR__ . '/vendor/autoload.php';
#require __DIR__. "/src/MDX/MDXQuery.php";
use Machine\MdxBuilder\MDX\Expressions\Range;
use Machine\MdxBuilder\MDX\MDXQuery;
use Machine\MdxBuilder\MDX\Expressions\Tuple;
use Machine\MdxBuilder\MDX\Expressions\Set;
use Machine\MdxBuilder\MDX\Expressions\NonEmpty;


$query = new MDXQuery();
$query
    ->select([ '[Measures].[Amount]', '[Measures].[Rest]' ])
    ->by('[Product].[Product].[Name]')
    ->fromQuery(
        (new MDXQuery())
        ->select(['[Date].[Date].[Month].&[202101] '])
        ->from('Sales'));

echo $query->toString();