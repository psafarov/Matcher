<?php

namespace Psafarov\Matcher;

function match($pattern, $subject, &$references = null) {
    $parser = new Parser(Lexer::getTokens($pattern));
    $processor = new Processor;
    return $processor->match($parser->getAST(), $subject, $references);
}
