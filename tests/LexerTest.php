<?php

namespace Psafarov\Matcher\Tests;

use Psafarov\Matcher\Lexer;

class LexerTest extends \PHPUnit_Framework_TestCase
{
    /** @dataProvider getValidInputs */
    public function testGetsTokensFromValidInput($input, $expectedTokens)
    {
        $this->assertSame($expectedTokens, Lexer::getTokens($input));
    }

    public function getValidInputs()
    {
        return [
            // Basic examples
            ['(',      [[Lexer::T_L_PAREN]]],
            [')',      [[Lexer::T_R_PAREN]]],
            ['[',      [[Lexer::T_L_BRACK]]],
            [']',      [[Lexer::T_R_BRACK]]],
            ['|',      [[Lexer::T_PIPELINE]]],
            ['\\',     [[Lexer::T_BACKSLASH]]],
            [':',      [[Lexer::T_COLON]]],
            [',',      [[Lexer::T_COMMA]]],
            ['...',    [[Lexer::T_DOTDOTDOT]]],
            ['..',     [[Lexer::T_DOTDOT]]],
            ['=>',     [[Lexer::T_DOUBLE_ARROW]]],
            ['@',      [[Lexer::T_AT]]],
            ['true',   [[Lexer::T_TRUE]]],
            ['false',  [[Lexer::T_FALSE]]],
            ['null',   [[Lexer::T_NULL]]],
            ['1',      [[Lexer::T_NUMBER, 1]]],
            ['-1',     [[Lexer::T_NUMBER, -1]]],
            ['2.4',    [[Lexer::T_NUMBER, 2.4]]],
            ['2.0',    [[Lexer::T_NUMBER, 2.0]]],
            ['"foo"',  [[Lexer::T_STRING, 'foo']]],
            ["'foo'",  [[Lexer::T_STRING, 'foo']]],
            ["/^f$/",  [[Lexer::T_REGEX, '^f$']]],

            // Complex examples
            [
                '_: integer',
                [
                    [Lexer::T_IDENTIFIER, '_'],
                    [Lexer::T_COLON],
                    [Lexer::T_IDENTIFIER, 'integer'],
                ]
            ],
            [
                'betweenZeroAndFive @ 0..5',
                [
                    [Lexer::T_IDENTIFIER, 'betweenZeroAndFive'],
                    [Lexer::T_AT],
                    [Lexer::T_NUMBER, 0],
                    [Lexer::T_DOTDOT],
                    [Lexer::T_NUMBER, 5],
                ]
            ],
            [
                'stringStartingWithA @ /^A/',
                [
                    [Lexer::T_IDENTIFIER, 'stringStartingWithA'],
                    [Lexer::T_AT],
                    [Lexer::T_REGEX, '^A'],
                ]
            ],
            [
                '["a" => 1, Option\Some(property)]',
                [
                    [Lexer::T_L_BRACK],
                    [Lexer::T_STRING, 'a'],
                    [Lexer::T_DOUBLE_ARROW],
                    [Lexer::T_NUMBER, 1],
                    [Lexer::T_COMMA],
                    [Lexer::T_IDENTIFIER, 'Option'],
                    [Lexer::T_BACKSLASH],
                    [Lexer::T_IDENTIFIER, 'Some'],
                    [Lexer::T_L_PAREN],
                    [Lexer::T_IDENTIFIER, 'property'],
                    [Lexer::T_R_PAREN],
                    [Lexer::T_R_BRACK],
                ]
            ],
            [
                'Foo(bar @ [1, 2, ..., 5], baz @ /^\w+$/)',
                [
                    [Lexer::T_IDENTIFIER, 'Foo'],
                    [Lexer::T_L_PAREN],
                    [Lexer::T_IDENTIFIER, 'bar'],
                    [Lexer::T_AT],
                    [Lexer::T_L_BRACK],
                    [Lexer::T_NUMBER, 1],
                    [Lexer::T_COMMA],
                    [Lexer::T_NUMBER, 2],
                    [Lexer::T_COMMA],
                    [Lexer::T_DOTDOTDOT],
                    [Lexer::T_COMMA],
                    [Lexer::T_NUMBER, 5],
                    [Lexer::T_R_BRACK],
                    [Lexer::T_COMMA],
                    [Lexer::T_IDENTIFIER, 'baz'],
                    [Lexer::T_AT],
                    [Lexer::T_REGEX, '^\w+$'],
                    [Lexer::T_R_PAREN],
                ]
            ],
        ];
    }
}
