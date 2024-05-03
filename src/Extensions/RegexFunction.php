<?php
 
declare(strict_types=1);
namespace App\Extensions;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;
use  Doctrine\ORM\Query\AST\InputParameter;
 
/**
 * RegexFunction 
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch 
 * 
 * "REGEXP" "(" {StateFieldPathExpression ","}* InParameter {Literal}? ")"
 */
class RegexFunction extends FunctionNode {

    public array $columns  = array();
    public InputParameter $needle; 
     
    /** 
     * @param Parser $parser 
     *
     * @return void
     */
    public function parse(Parser $parser): void 
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        do {
            $this->columns[] = $parser->StateFieldPathExpression();
            $parser->match(Lexer::T_COMMA);
        } while ($parser->getLexer()->isNextToken(Lexer::T_IDENTIFIER));
        $this->needle = $parser->InParameter(); 
         
        $parser->match(Lexer::T_CLOSE_PARENTHESIS); 
    }
     
    /** 
     * @param SqlWalker $sqlWalker 
     *
     * @return string
     */
    public function getSql(SqlWalker $sqlWalker) : string
    {
        $first = true;
        $haystack = '(';
        foreach ($this->columns as $column) {
            $first ? $first = false : $haystack .= ' OR ';
            $haystack .= $column->dispatch($sqlWalker);
            $haystack .= " REGEXP " . $this->needle->dispatch($sqlWalker);
        }
        $query = $haystack;
        $query .= " )";
  
        return $query;
    }

}