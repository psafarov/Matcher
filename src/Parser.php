<?php

namespace Psafarov\Matcher;

/**
 * @TODO: use parser generator
 */
class Parser
{
    protected $tokens;
    protected $errorPointer;

    public function __construct($tokens)
    {
        $this->tokens = $tokens;
    }

    public function getAST()
    {
        $expressionAst = $this->parseExpression(0);
        if (!$expressionAst) {
            $errorPointer = $this->errorPointer;
        } elseif ($expressionAst['pointer'] != count($this->tokens)) {
            $errorPointer = max($this->errorPointer, $expressionAst['pointer']);
        }
        if (isset($errorPointer)) {
            $tokensMapping = array_flip(Lexer::TOKENS);
            $token = $tokensMapping[$this->tokens[$errorPointer][0]] ?? $this->tokens[$errorPointer][1];
            throw new \UnexpectedValueException('Unexpected token: ' . $token);
        }
        unset($expressionAst['pointer']);
        return $expressionAst;
    }

    protected function parseExpression($p)
    {
        if ($patternAst = $this->parsePattern($p)) {
            $p = $patternAst['pointer'];
            unset($patternAst['pointer']);
            return [
                'type'    => 'expression',
                'pattern' => $patternAst,
                'pointer' => $p
            ];
        }

        if ($this->getToken($p)[0] === Lexer::T_IDENTIFIER) {
            $result = [
                'type'      => 'expression',
                'reference' => $this->getToken($p)[1],
                'pointer'   => ++$p
            ];
            if (
                $this->getToken($p)[0] === Lexer::T_COLON &&
                $type = $this->parseType($p + 1)
            ) {
                $result['referenceType'] = $type['value'];
                $p = $type['pointer'];
            }
            if (
                $this->getToken($p)[0] === Lexer::T_AT &&
                $patternAst = $this->parsePattern($p + 1)
            ) {
                $p = $patternAst['pointer'];
                unset($patternAst['pointer']);
                $result['pattern'] = $patternAst;
            }

            $result['pointer'] = $p;
            return $result;
        }

        return $this->invalidToken($p);
    }

    protected function parseType($p)
    {
        $value = '';

        if ($this->getToken($p)[0] === Lexer::T_BACKSLASH) {
            $value .= '\\';
            $p += 1;
        }

        while (
            $this->getToken($p)[0] === Lexer::T_IDENTIFIER &&
            $this->getToken($p + 1)[0] === Lexer::T_BACKSLASH
        ) {
            $value .= $this->getToken($p)[1] . '\\';
            $p += 2;
        }

        if ($this->getToken($p)[0] === Lexer::T_IDENTIFIER) {
            $value .= $this->getToken($p)[1];
            return [
                'value'   => $value,
                'pointer' => $p + 1
            ];
        }

        return $this->invalidToken($p);
    }

    protected function parsePattern($p)
    {
        return $this->parseArray($p)
            ?: $this->parseObject($p)
            ?: $this->parseRange($p)
            ?: $this->parseRegex($p)
            ?: $this->parseValue($p);
    }

    protected function parseArray($p)
    {
        if ($this->getToken($p)[0] === Lexer::T_L_BRACK) {
            $result = [
                'type'  => 'array',
                'items' => []
            ];
            $p += 1;
            $lastIndex = -1;
            while (true) {
                if ($itemAst = $this->parseItem($p, $lastIndex)) {
                    $p = $itemAst['pointer'];
                    unset($itemAst['pointer']);
                    $result['items'][] = $itemAst;
                    if (is_int($itemAst['name'])) {
                        $lastIndex = max($lastIndex, $itemAst['name']);
                    }
                    if ($this->getToken($p)[0] === Lexer::T_COMMA) {
                        $p += 1;
                        continue;
                    }
                }

                break;
            }
            if ($this->getToken($p)[0] === Lexer::T_R_BRACK) {
                $result['pointer'] = $p + 1;
                return $result;
            }
        }
        return $this->invalidToken($p);
    }

    protected function parseItem($p, $lastIndex)
    {
        $result = ['type' => 'item'];
        if (
            ($valueAst = $this->parseValue($p)) &&
            $this->getToken($valueAst['pointer'])[0] === Lexer::T_DOUBLE_ARROW
        ) {
            $p = $valueAst['pointer'] + 1;
            $result['name'] = $valueAst['value'];
            if (is_numeric($result['name']) || is_bool($result['name'])) {
                $result['name'] = (int)$result['name'];
            }
        }

        if ($expressionAst = $this->parseExpression($p)) {
            $result['pointer'] = $expressionAst['pointer'];
            unset($expressionAst['pointer']);
            if (!isset($result['name'])) {
                $result['name'] = $lastIndex + 1;
            }
            $result['value'] = $expressionAst;
            return $result;
        }

        return $this->invalidToken($p);
    }

    protected function parseObject($p)
    {
        if (
            ($type = $this->parseType($p)) &&
            ($p = $type['pointer']) &&
            $this->getToken($p)[0] === Lexer::T_L_PAREN
        ) {
            $result = [
                'type'       => 'object',
                'name'       => $type['value'],
                'properties' => []
            ];
            $p += 1;
            while (true) {
                if ($propertyAst = $this->parseProperty($p)) {
                    $p = $propertyAst['pointer'];
                    unset($propertyAst['pointer']);
                    $result['properties'][] = $propertyAst;
                    if ($this->getToken($p)[0] === Lexer::T_COMMA) {
                        $p += 1;
                        continue;
                    }
                }

                break;
            }
            if ($this->getToken($p)[0] === Lexer::T_R_PAREN) {
                $result['pointer'] = $p + 1;
                return $result;
            }
        }

        return $this->invalidToken($p);
    }

    protected function parseProperty($p)
    {
        if (
            $this->getToken($p)[0] === Lexer::T_STRING &&
            $this->getToken($p + 1)[0] === Lexer::T_DOUBLE_ARROW
        ) {
            $name = $this->getToken($p)[1];
            $p += 2;
        }

        if (
            ($expressionAst = $this->parseExpression($p)) &&
            (null !== $name = $name ?? $expressionAst['reference'] ?? null)
        ) {
            $result = ['type' => 'property'];
            $result['pointer'] = $expressionAst['pointer'];
            unset($expressionAst['pointer']);
            $result['name'] = $name;
            $result['value'] = $expressionAst;
            return $result;
        }

        return $this->invalidToken($p);
    }

    protected function parseRange($p)
    {
        if (
            $this->getToken($p)[0] === Lexer::T_NUMBER &&
            $this->getToken(++$p)[0] === Lexer::T_DOTDOT &&
            $this->getToken(++$p)[0] === Lexer::T_NUMBER
        ) {
            return [
                'type'    => 'range',
                'min'     => $this->getToken($p - 2)[1],
                'max'     => $this->getToken($p)[1],
                'pointer' => $p + 1
            ];
        }

        return $this->invalidToken($p);
    }

    protected function parseRegex($p)
    {
        if ($this->getToken($p)[0] == Lexer::T_REGEX) {
            return [
                'type'    => 'regex',
                'value'   => $this->getToken($p)[1],
                'pointer' => $p + 1
            ];
        }

        return $this->invalidToken($p);
    }

    protected function parseValue($p)
    {
        if (in_array($this->getToken($p)[0], [Lexer::T_NUMBER, Lexer::T_STRING], true)) {
            return [
                'type'    => 'value',
                'value'   => $this->getToken($p)[1],
                'pointer' => $p + 1
            ];
        }

        if (in_array($this->getToken($p)[0], [Lexer::T_TRUE, Lexer::T_FALSE], true)) {
            return [
                'type'    => 'value',
                'value'   => $this->getToken($p)[0] === Lexer::T_TRUE,
                'pointer' => $p + 1
            ];
        }

        if ($this->getToken($p)[0] == Lexer::T_NULL) {
            return [
                'type'    => 'value',
                'value'   => null,
                'pointer' => $p + 1
            ];
        }

        return $this->invalidToken($p);
    }

    protected function getToken($p)
    {
        return $this->tokens[$p] ?? [null, null];
    }

    protected function invalidToken($p)
    {
        $this->errorPointer = max($p, $this->errorPointer);
        return false;
    }
}