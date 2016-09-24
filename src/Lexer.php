<?php

namespace Psafarov\Matcher;

/**
 * @TODO: use lexer generator
 */
class Lexer
{
    const T_L_PAREN      = 0;
    const T_R_PAREN      = 1;
    const T_L_BRACK      = 2;
    const T_R_BRACK      = 3;
    const T_PIPELINE     = 4;
    const T_BACKSLASH    = 5;
    const T_COLON        = 6;
    const T_COMMA        = 7;
    const T_DOTDOTDOT    = 8;
    const T_DOTDOT       = 9;
    const T_DOUBLE_ARROW = 10;
    const T_AT           = 11;
    const T_TRUE         = 12;
    const T_FALSE        = 13;
    const T_NULL         = 14;
    const T_NUMBER       = 15;
    const T_STRING       = 16;
    const T_REGEX        = 17;
    const T_IDENTIFIER   = 18;
    const T_EOF          = 19;

    const TOKENS = [
        '('     => self::T_L_PAREN,
        ')'     => self::T_R_PAREN,
        '['     => self::T_L_BRACK,
        ']'     => self::T_R_BRACK,
        '|'     => self::T_PIPELINE,
        '\\'    => self::T_BACKSLASH,
        ':'     => self::T_COLON,
        ','     => self::T_COMMA,
        '...'   => self::T_DOTDOTDOT,
        '..'    => self::T_DOTDOT,
        '=>'    => self::T_DOUBLE_ARROW,
        '@'     => self::T_AT,
        'true'  => self::T_TRUE,
        'false' => self::T_FALSE,
        'null'  => self::T_NULL,
    ];

    public static function getTokens($input)
    {
        $tokens = [];
        while (list($token, $input) = self::nextToken($input)) {
            $tokens[] = $token;
        }
        return $tokens;
    }

    public static function nextToken($input)
    {
        $input = ltrim($input);

        foreach (self::TOKENS as $token => $name) {
            $tokenLength = strlen($token);
            if (substr($input, 0, $tokenLength) === $token) {
                return [
                    [$name],
                    substr($input, $tokenLength),
                ];
            }
        }

        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*/', $input, $matches)) {
            return [
                [self::T_IDENTIFIER, $matches[0]],
                substr($input, strlen($matches[0])),
            ];
        }

        if (preg_match('/^-?\d+(\.\d+)?/', $input, $matches)) {
            return [
                [self::T_NUMBER, isset($matches[1]) ? (float)$matches[0] : (int)$matches[0]],
                substr($input, strlen($matches[0])),
            ];
        }

        if (
            preg_match('/^\'(.*?)\'/', $input, $matches) ||
            preg_match('/^"(.*?)"/', $input, $matches)
        ) {
            return [
                [self::T_STRING, $matches[1]],
                substr($input, strlen($matches[0])),
            ];
        }

        if (preg_match('/^\/(.*?)\//', $input, $matches)) {
            return [
                [self::T_REGEX, $matches[1]],
                substr($input, strlen($matches[0])),
            ];
        }

        if ($input === '') {
            return false;
        }

        throw new \UnexpectedValueException('Unexpected character: ' . $input[0]);
    }
}
