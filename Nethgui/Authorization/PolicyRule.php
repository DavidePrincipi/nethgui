<?php
namespace Nethgui\Authorization;

/*
 * Copyright (C) 2012 Nethesis S.r.l.
 *
 * This script is part of NethServer.
 *
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Authorization rules decide if a Subject is allowed to perform an Action on
 * a given Resource
 *
 * Rules are ordered by their specificity/generality, so that more specific rules
 * are considered more relevant to the authorization decision.
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class PolicyRule
{

    /**
     *
     * @var integer
     */
    private $id;

    /**
     *
     * @var string
     */
    private $effect;

    /**
     * @var string
     */
    private $description;

    /**
     *
     * @var array
     */
    private $matcher;

    /**
     *
     * @var float
     */
    private $specificity;

    /**
     * By default a rule is overridable by another rule with the same Id
     * @var boolean
     */
    private $isFinal = FALSE;

    private function __construct()
    {
        $this->id = 0;
        $this->description = '';
        $this->effect = 'DENY';
        $this->matcher = array();
    }

    /**
     * Construct an instance from a plain object
     * 
     * @param object $o
     * @return PolicyRule
     */
    public static function createFromObject($o)
    {
        $instance = new static();

        foreach ($o as $property => $value) {
            $property = strtolower($property);

            if ($property === 'id') {
                $instance->id = intval($value);
            } elseif ($property === 'description') {
                $instance->description = $value;
            } elseif ($property === 'effect') {
                $instance->effect = strtoupper($value);
            } elseif ($property === 'final') {
                $instance->isFinal = (boolean) $value;
            } else {
                $instance->matcher[$property] = $instance->parseMatcher($value);
            }
        }

        return $instance;
    }

    /**
     *
     * @return string
     */
    public function getDescription()
    {
        return sprintf('%s (score = %.2f)', $this->description, $this->getSpecificity());
    }

    /**
     *
     * @return integer
     */
    public function getIdentifier()
    {
        return $this->id;
    }

    public function isAllow()
    {
        return $this->effect === 'ALLOW';
    }

    /**
     * @param array $request
     * @return boolean
     */
    public function isApplicableTo($request)
    {

        foreach ($request as $matcherName => $obj) {
            if ( ! isset($this->matcher[$matcherName])) {
                continue; // undefined matchers are skipped
            }

            $result = $this->matcher[$matcherName]->evaluate($this->asAttributeProvider($obj));
            if ( ! $result) {
                // matcher failed
                return FALSE;
            }
        }

        // all matchers evaluates to TRUE
        return TRUE;
    }

    /**
     * Convert the given argument to a AuthorizationAttributesProviderInterface
     * object
     *
     * @param mixed $o
     * @return AuthorizationAttributesProviderInterface
     */
    private function asAttributeProvider($o)
    {
        $retval = NULL;

        if ($o instanceof AuthorizationAttributesProviderInterface) {
            $retval = $o;
        } else {
            $retval = new StringAttributesProvider($o);
        }

        return $retval;
    }

    /**
     * If a rule is not final it is overridable by
     * another rule with the same Identifier
     * 
     * @return boolean
     */
    public function isFinal()
    {
        return $this->isFinal === TRUE;
    }

    /**
     * @return float
     */
    private function getSpecificity()
    {
        if ( ! isset($this->specificity)) {
            $this->specificity = 0.0;
            foreach ($this->matcher as $matcher) {
                $this->specificity += $matcher->getSpecificity();
            }
        }

        return $this->specificity;
    }

    /**
     * Return an integer less than, equal to, or greater than zero if the
     * this instance is considered to be respectively less than, equal to,
     * or greater than the $other rule;
     *
     * @param PolicyRule $other
     * @return integer
     */
    public function compare(PolicyRule $other)
    {
        if ($this->getSpecificity() === $other->getSpecificity()) {
            return 0;
        } elseif ($this->getSpecificity() > $other->getSpecificity()) {
            return 1;
        } else {
            return -1;
        }
    }

    /**
     *
     * @param mixed $value
     * @return AbstractExpression
     */
    private function parseMatcher($value)
    {
        // Convert an array into AnyOfPolicyRuleMatcher:
        if (is_array($value)) {
            if (empty($value)) {
                return $this->parseMatcher('!*');
            }
            $a = $this->parseMatcher(\Nethgui\array_head($value));
            if (count($value) == 1) {
                return $a;
            }
            $b = $this->parseMatcher(\Nethgui\array_rest($value));
            return new AnyOfExpression($a, $b);
        }

        $parser = new PolicyExpressionParser($value);
        $parseTree = $parser->parse();

        return $parseTree;
    }

}

/**
 * A simple descent parser for the following grammar:
 *
 * expression  := orExp
 * orExp       := orOp orExpTail
 * orExpTail   := EPS | "OR" orExp
 * orOp        := andExp
 * andExp      := andOp andExpTail
 * andExpTail  := EPS | "AND" andExp
 * andOp       := "NOT" matchExp | matchExp
 * matchExp    := matchOp matchExpTail
 * matchExpTail:= EPS | "IS" operand | "HAS" operand
 * matchOp     := operand
 * operand     := <LITERAL> | <ATTRIBUTE> | "(" expression ")" | "TRUE" | "FALSE"
 *
 * @internal
 * @since 1.0
 * @author Davide Principi <davide.principi@nethesis.it>
 */
class PolicyExpressionParser
{

    private $tokens;
    private $symbol;
    private $value;
    private $offset;

    const T_EOF = 0;
    const T_LITERAL = 1;
    const T_WHITESPACE = 2;
    const T_BOOLEAN_AND = 3;
    const T_BOOLEAN_OR = 4;
    const T_NEGATION = 5;
    const T_LEFT_PARENS = 6;
    const T_RIGHT_PARENS = 7;
    const T_ATTRIBUTE = 8;
    const T_EQUAL = 9;
    const T_IN = 10;
    const T_IS = 11;
    const T_HAS = 12;
    const T_TRUE = 13;
    const T_FALSE = 14;

    /**
     *
     * @param string $text
     */
    public function __construct($text)
    {
        if ( ! is_string($text)) {
            throw new \InvalidArgumentException(sprintf('%s: data must be a string', __CLASS__), 1327658033);
        }

        $this->input = $text;
        $this->tokens = preg_split("/(\b(?:TRUE|FALSE|AND|OR|NOT|HAS|IS)\b|\.[A-Za-z][A-Za-z0-9_-]*|\(|\))/ ", $text, NULL, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);

        //print_r($this->tokens);
    }

    /**
     * @return AbstractExpression
     */
    public function parse()
    {
        return $this->expression();
    }

    private function eat($token)
    {

        if ($token !== $this->symbol) {
            $this->throwError();
            // @codeCoverageIgnoreStart
        }
        // @codeCoverageIgnoreEnd

        return $this->read();
    }

    private function read()
    {

        list($token, $offset) = array_shift($this->tokens);

        $this->offset = $offset;

        switch ($token) {
            case FALSE;
                $this->symbol = self::T_EOF;
                break;
            case 'AND':
                $this->symbol = self::T_BOOLEAN_AND;
                break;
            case 'OR':
                $this->symbol = self::T_BOOLEAN_OR;
                break;
            case 'NOT':
                $this->symbol = self::T_NEGATION;
                break;
            case "(":
                $this->symbol = self::T_LEFT_PARENS;
                break;
            case ")":
                $this->symbol = self::T_RIGHT_PARENS;
                break;
            case "IS":
                $this->symbol = self::T_IS;
                break;
            case "HAS":
                $this->symbol = self::T_HAS;
                break;
            case "TRUE":
                $this->symbol = self::T_TRUE;
                break;
            case "FALSE":
                $this->symbol = self::T_FALSE;
                break;
            default:
                $token = trim($token);
                if (strlen($token) > 0) {
                    if ($token{0} === '.') {
                        $this->symbol = self::T_ATTRIBUTE;
                    } else {
                        $this->symbol = self::T_LITERAL;
                    }
                } else {
                    $this->symbol = self::T_WHITESPACE;
                }
        }

        $this->value = $token;

        if ($this->symbol === self::T_WHITESPACE) {
            $this->read();
        }

        return $this;
    }

    private function tokenName($s)
    {
        switch ($s) {
            case self::T_LITERAL: return 'T_LITERAL';
            case self::T_WHITESPACE: return 'T_WHITESPACE';
            case self::T_BOOLEAN_AND: return 'T_BOOLEAN_AND';
            case self::T_BOOLEAN_OR: return 'T_BOOLEAN_OR';
            case self::T_NEGATION: return 'T_NEGATION';
            case self::T_LEFT_PARENS: return 'T_LEFT_PARENS';
            case self::T_RIGHT_PARENS: return 'T_RIGHT_PARENS';
            case self::T_ATTRIBUTE: return 'T_ATTRIBUTE';
            case self::T_IS: return 'T_IS';
            case self::T_HAS: return 'T_HAS';
            case self::T_FALSE: return 'T_FALSE';
            case self::T_TRUE: return 'T_TRUE';
            default: return 'UNKNOWN ' . var_export($s, TRUE);
        }
    }

    /**
     * @throws \UnexpectedValueException
     * @param integer $s
     */
    private function throwError()
    {
        $tok = $this->tokenName($this->symbol);
        $expr = '`' . substr($this->input, 0, $this->offset) . '` {!} `' . substr($this->input, $this->offset) . '`';
        throw new \UnexpectedValueException(sprintf('%s: unexpected token %s; %s', __CLASS__, $tok, $expr), 1327593544);
    }

    private function expression()
    {
        $this->read();
        return $this->orExp();
    }

    private function orExp()
    {
        return $this->orExpTail($this->orOp());
    }

    private function orExpTail(AbstractExpression $p)
    {
        if ($this->symbol === self::T_BOOLEAN_OR) {
            $this->eat(self::T_BOOLEAN_OR);
            return new AnyOfExpression($p, $this->orExp());
        }
        return $p; // EPS
    }

    private function orOp()
    {
        return $this->andExp();
    }

    private function andExp()
    {
        return $this->andExpTail($this->andOp());
    }

    private function andExpTail(AbstractExpression $p)
    {
        if ($this->symbol == self::T_BOOLEAN_AND) {
            $this->eat(self::T_BOOLEAN_AND);
            return new AllOfExpression($p, $this->andExp());
        }
        return $p; // EPS
    }

    private function andOp()
    {
        if ($this->symbol === self::T_NEGATION) {
            $this->eat(self::T_NEGATION);
            return new NegationExpression($this->matchExp());
        } else {
            return $this->matchExp();
        }
        $this->throwError();
    }

    private function matchExp()
    {
        return $this->matchExpTail($this->operand());
    }

    private function operand()
    {
        switch ($this->symbol) {
            case self::T_ATTRIBUTE:
                $operand = new AttributeExpression(substr($this->value, 1));
                $this->eat(self::T_ATTRIBUTE);
                return $operand;
            case self::T_LITERAL:
                $operand = new StringMatchExpression($this->value);
                $this->eat(self::T_LITERAL);
                return $operand;
            case self::T_LEFT_PARENS:
                $this->eat(self::T_LEFT_PARENS);
                $operand = $this->orExp();
                $this->eat(self::T_RIGHT_PARENS);
                return $operand;
            case self::T_TRUE:
                $this->eat(self::T_TRUE);
                return new Constant(TRUE);
            case self::T_FALSE:
                $this->eat(self::T_FALSE);
                return new Constant(FALSE);
        }
        $this->throwError();
        // @codeCoverageIgnoreStart
    }
    // @codeCoverageIgnoreEnd

    private function matchExpTail(AbstractExpression $p)
    {
        if ($this->symbol === self::T_IS) {
            $this->eat(self::T_IS);
            return new IsExpression($p, $this->operand());
        } elseif ($this->symbol === self::T_HAS) {
            $this->eat(self::T_HAS);
            return new HasExpression($p, $this->operand());
        }
        return $p;  // EPS
    }

}

/**
 * @internal
 */
abstract class AbstractExpression
{

    /**
     *
     * @param mixed $value 
     * @return mixed - boolean TRUE if $value matches, FALSE otherwise, or a string
     */
    abstract public function evaluate(AuthorizationAttributesProviderInterface $value);

    /**
     * @codeCoverageIgnore
     * @return float
     */
    public function getSpecificity()
    {
        return 0.0;
    }

}

/**
 * Generic binary operator
 *
 * @internal
 */
abstract class BinaryExpression extends AbstractExpression
{

    /**
     *
     * @var AbstractExpression
     */
    protected $a;

    /**
     *
     * @var AbstractExpression
     */
    protected $b;

    /**
     *
     * @param AbstractExpression $a
     * @param AbstractExpression $b
     */
    public function __construct(AbstractExpression $a, AbstractExpression $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

}

/**
 * @internal
 */
class IsExpression extends BinaryExpression
{

    public function evaluate(AuthorizationAttributesProviderInterface $value)
    {
        return $this->b->evaluate(new StringAttributesProvider(strval($this->a->evaluate($value))));
    }

    public function getSpecificity()
    {
        return $this->b->getSpecificity();
    }

}

/**
 * @internal
 */
class HasExpression extends BinaryExpression
{

    public function evaluate(AuthorizationAttributesProviderInterface $value)
    {
        $arr = $this->a->evaluate($value);

        if ( ! is_array($arr)) {
            return FALSE;
        }

        foreach ($arr as $itemValue) {
            if ($this->b->evaluate(new StringAttributesProvider($itemValue)) === TRUE) {
                return TRUE;
            }
        }

        return FALSE;
    }

    public function getSpecificity()
    {
        return $this->b->getSpecificity();
    }

}

/**
 * @internal
 */
class AnyOfExpression extends BinaryExpression
{

    public function evaluate(AuthorizationAttributesProviderInterface $value)
    {
        $evA = $this->a->evaluate($value);
        $evB = $this->b->evaluate($value);
        return $evA || $evB;
    }

    /**
     * The MEAN of sub all matchers specificity
     * @return float
     */
    public function getSpecificity()
    {
        return min($this->a->getSpecificity(), $this->b->getSpecificity());
    }

}

/**
 * @internal
 */
class AllOfExpression extends BinaryExpression
{

    public function evaluate(AuthorizationAttributesProviderInterface $value)
    {
        $evA = (bool) $this->a->evaluate($value);
        $evB = (bool) $this->b->evaluate($value);
        return $evA && $evB;
    }

    /**
     * The MAX of any sub matcher specificity
     * @return float
     */
    public function getSpecificity()
    {
        return max($this->a->getSpecificity(), $this->b->getSpecificity());
    }

}

/**
 * @internal
 */
class AttributeExpression extends AbstractExpression
{

    private $attributeName;

    /**
     *
     * @param string $attributeName
     */
    public function __construct($attributeName)
    {
        $this->attributeName = $attributeName;
    }

    public function evaluate(AuthorizationAttributesProviderInterface $value)
    {
        return $value->getAuthorizationAttribute($this->attributeName);
    }

    public function getSpecificity()
    {
        return 0.5;
    }

}

/**
 * @internal
 */
class NegationExpression extends AbstractExpression
{

    /**
     *
     * @var AbstractExpression
     */
    private $inner;

    public function __construct(AbstractExpression $inner)
    {
        $this->inner = $inner;
    }

    public function evaluate(AuthorizationAttributesProviderInterface $value)
    {
        return ! $this->inner->evaluate($value);
    }

    public function getSpecificity()
    {
        return 1.0 - $this->inner->getSpecificity();
    }

}

/**
 * @internal
 */
class StringMatchExpression extends AbstractExpression
{

    /**
     *
     * @var string
     */
    private $pattern;

    /**
     *
     * @var string
     */
    private $regexPattern;
    private $stars;

    public function __construct($pattern)
    {
        $this->stars = 0;
        $this->pattern = $pattern;

        $regexPattern = preg_quote($pattern, '#');
        $regexPattern = str_replace('\*', '.*', $regexPattern, $this->stars);

        $this->regexPattern = '#^' . $regexPattern . '$#';
    }

    public function evaluate(AuthorizationAttributesProviderInterface $value)
    {
        if ($this->stars === 0) {
            // exact match:
            return $value->asAuthorizationString() === $this->pattern;
        }

        return preg_match($this->regexPattern, $value->asAuthorizationString()) > 0;
    }

    public function getSpecificity()
    {
        if ($this->stars === 0) {
            return 1.0;
        } elseif ($this->pattern === '*') {
            return 0.0;
        }

        return 1.0 / (1.0 + (float) $this->stars);
    }

}

/**
 * @internal
 */
class Constant extends AbstractExpression
{

    private $value;

    public function __construct($constValue)
    {
        $this->value = $constValue;
    }

    public function evaluate(AuthorizationAttributesProviderInterface $value)
    {
        return $this->value;
    }

}