<?php

namespace Psafarov\Matcher;

class Processor
{
    protected $references = [];

    public function match($patternTree, $subject, &$references = null)
    {
        $result = $this->matchExpression($patternTree, $subject);
        $references = $this->references;
        $this->references = [];
        return $result;
    }

    protected function matchExpression($expressionAst, $subject)
    {
        if ($reference = $expressionAst['reference'] ?? null) {
            $this->references[$reference] = $subject;
        }

        if ($type = $expressionAst['referenceType'] ?? null) {
            if ($type === 'callable') {
                if (!is_callable($subject)) {
                    return false;
                }
            } else {
                static $types = [
                    'bool'     => 'boolean',
                    'boolean'  => 'boolean',
                    'int'      => 'integer',
                    'integer'  => 'integer',
                    'float'    => 'double',
                    'double'    => 'double',
                    'string'   => 'string',
                    'array'    => 'array',
                    'object'   => 'object',
                    'resource' => 'resource',
                    'null'     => 'NULL',
                ];
                if (isset($types[$type])) {
                    if (gettype($subject) !== $types[$type]) {
                        return false;
                    }
                } elseif (!$subject instanceof $type) {
                    return false;
                }
            }
        }

        if (isset($expressionAst['pattern'])) {
            $method = 'match' . $expressionAst['pattern']['type'];
            return $this->{$method}($expressionAst['pattern'], $subject);
        }

        return true;
    }

    protected function matchArray($arrayAst, $subject)
    {
        if (!is_array($subject)) {
            return false;
        }

        foreach ($arrayAst['items'] as $itemAst) {
            if (
                !isset($subject[$itemAst['name']]) ||
                !$this->matchExpression($itemAst['value'], $subject[$itemAst['name']])
            ) {
                return false;
            }
        }

        return true;
    }

    protected function matchObject($objectAst, $subject)
    {
        if (!$subject instanceof $objectAst['name']) {
            return false;
        }

        foreach ($objectAst['properties'] as $propertyAst) {
            $name = $propertyAst['name'];
            $getter = 'get' . $name;
            if (method_exists($subject, $getter)) {
                $value = $subject->$getter();
            } elseif (property_exists($subject, $name)) {
                $value = $subject->$name;
            } else {
                return false;
            }
            if (!$this->matchExpression($propertyAst['value'], $value)) {
                return false;
            }
        }

        return true;
    }

    protected function matchRange($rangeAst, $subject)
    {
        return is_numeric($subject) && $subject >= $rangeAst['min'] && $subject < $rangeAst['max'];
    }

    protected function matchRegex($regexAst, $subject)
    {
        return is_string($subject) && (bool)preg_match("/{$regexAst['value']}/", $subject);
    }

    protected function matchValue($valueAst, $subject)
    {
        return $valueAst['value'] === $subject;
    }
}