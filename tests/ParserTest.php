<?php

namespace Psafarov\Matcher\Tests;

use Psafarov\Matcher\Lexer;
use Psafarov\Matcher\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /** @dataProvider getValidTokens */
    public function testGetsAstFromValidTokens($tokens, $expectedTree)
    {
        $parser = new Parser($tokens);
        $this->assertSame($expectedTree, $parser->getAST());
    }

    public function getValidTokens()
    {
        return [

            'true' => [
                [[Lexer::T_TRUE]],
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => true
                    ]
                ]
            ],

            'false' => [
                [[Lexer::T_FALSE]],
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => false
                    ]
                ]
            ],

            'null' => [
                [[Lexer::T_NULL]],
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => null
                    ]
                ]
            ],

            ' 1' => [
                [[Lexer::T_NUMBER, 1]],
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => 1
                    ]
                ]
            ],

            "'string'" => [
                [[Lexer::T_STRING, 'string']],
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => 'string'
                    ]
                ]
            ],

            '0..5' => [
                [
                    [Lexer::T_NUMBER, 0],
                    [Lexer::T_DOTDOT],
                    [Lexer::T_NUMBER, 5]
                ],
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type' => 'range',
                        'min'  => 0,
                        'max'  => 5
                    ]
                ]
            ],

            '/^w$/' => [
                [[Lexer::T_REGEX, '^\w$']],
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'regex',
                        'value' => '^\w$'
                    ]
                ]
            ],

            'Option\Some(foo, bar)' => [
                [
                    [Lexer::T_IDENTIFIER, 'Option'],
                    [Lexer::T_BACKSLASH],
                    [Lexer::T_IDENTIFIER, 'Some'],
                    [Lexer::T_L_PAREN],
                    [Lexer::T_IDENTIFIER, 'foo'],
                    [Lexer::T_COMMA],
                    [Lexer::T_IDENTIFIER, 'bar'],
                    [Lexer::T_R_PAREN]
                ],
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'       => 'object',
                        'name'       => 'Option\Some',
                        'properties' => [
                            [
                                'type'  => 'property',
                                'name'  => 'foo',
                                'value' => [
                                    'type'      => 'expression',
                                    'reference' => 'foo'
                                ]
                            ],
                            [
                                'type'  => 'property',
                                'name'  => 'bar',
                                'value' => [
                                    'type'      => 'expression',
                                    'reference' => 'bar'
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            'Some("foo" => fooAlias, "bar" => 0..5, baz: boolean)' => [
                [
                    [Lexer::T_IDENTIFIER, 'Some'],
                    [Lexer::T_L_PAREN],
                    [Lexer::T_STRING, 'foo'],
                    [Lexer::T_DOUBLE_ARROW],
                    [Lexer::T_IDENTIFIER, 'fooAlias'],
                    [Lexer::T_COMMA],
                    [Lexer::T_STRING, 'bar'],
                    [Lexer::T_DOUBLE_ARROW],
                    [Lexer::T_NUMBER, 0],
                    [Lexer::T_DOTDOT],
                    [Lexer::T_NUMBER, 5],
                    [Lexer::T_COMMA],
                    [Lexer::T_IDENTIFIER, 'baz'],
                    [Lexer::T_COLON],
                    [Lexer::T_IDENTIFIER, 'boolean'],
                    [Lexer::T_R_PAREN]
                ],
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'       => 'object',
                        'name'       => 'Some',
                        'properties' => [
                            [
                                'type'  => 'property',
                                'name'  => 'foo',
                                'value' => [
                                    'type'      => 'expression',
                                    'reference' => 'fooAlias',
                                ]
                            ],
                            [
                                'type'  => 'property',
                                'name'  => 'bar',
                                'value' => [
                                    'type'    => 'expression',
                                    'pattern' => [
                                        'type' => 'range',
                                        'min'  => 0,
                                        'max'  => 5
                                    ]
                                ]
                            ],
                            [
                                'type'  => 'property',
                                'name'  => 'baz',
                                'value' => [
                                    'type'          => 'expression',
                                    'reference'     => 'baz',
                                    'referenceType' => 'boolean'
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            'a: int @ 1..2' => [
                [
                    [Lexer::T_IDENTIFIER, 'a'],
                    [Lexer::T_COLON],
                    [Lexer::T_IDENTIFIER, 'int'],
                    [Lexer::T_AT],
                    [Lexer::T_NUMBER, 1],
                    [Lexer::T_DOTDOT],
                    [Lexer::T_NUMBER, 2]
                ],
                [
                    'type'          => 'expression',
                    'reference'     => 'a',
                    'referenceType' => 'int',
                    'pattern'       => [
                        'type' => 'range',
                        'min'  => 1,
                        'max'  => 2
                    ]
                ]
            ],

            '[1, 2, "key" => 3, 4]' => [
                [
                    [Lexer::T_L_BRACK],
                    [Lexer::T_NUMBER, 1],
                    [Lexer::T_COMMA],
                    [Lexer::T_NUMBER, 2],
                    [Lexer::T_COMMA],
                    [Lexer::T_STRING, 'key'],
                    [Lexer::T_DOUBLE_ARROW],
                    [Lexer::T_NUMBER, 3],
                    [Lexer::T_COMMA],
                    [Lexer::T_NUMBER, 4],
                    [Lexer::T_R_BRACK]
                ],
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'array',
                        'items' => [
                            [
                                'type'  => 'item',
                                'name'  => 0,
                                'value' => [
                                    'type'    => 'expression',
                                    'pattern' => [
                                        'type'  => 'value',
                                        'value' => 1
                                    ]
                                ]
                            ],
                            [
                                'type'  => 'item',
                                'name'  => 1,
                                'value' => [
                                    'type'    => 'expression',
                                    'pattern' => [
                                        'type'  => 'value',
                                        'value' => 2
                                    ]
                                ]
                            ],
                            [
                                'type'  => 'item',
                                'name'  => 'key',
                                'value' => [
                                    'type'    => 'expression',
                                    'pattern' => [
                                        'type'  => 'value',
                                        'value' => 3
                                    ]
                                ]
                            ],
                            [
                                'type'  => 'item',
                                'name'  => 2,
                                'value' => [
                                    'type'    => 'expression',
                                    'pattern' => [
                                        'type'  => 'value',
                                        'value' => 4
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }
}
