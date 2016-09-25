# Matcher

[![Build Status](https://travis-ci.org/psafarov/Matcher.svg?branch=master)](https://travis-ci.org/psafarov/Matcher)

An experimental library heavily inspired by pattern matching in functional languages

## Usage
```php
<?php

use function Psafarov\Matcher\match;

if (match('[1, a: int, 0..5, "key" => [b]]', [1, 2, 3, "key" => [4]], $refs)) {
    echo "It is an array."
     . "The first element is 1"
     . "The second element is integer {$ref['a']}"
     . "The third element is between 0 and 5"
     . "The forth element has key and contains array containing {$refs['b']}" ;
}
```
As you see the `match` function reminds of `preg_match` function, but it works for every php variable
```
match($pattern, $subject, &$references = null) : bool
```

### Available Patterns
* `1` `1.0` `true` `'string'` - value
* `0..100` - range
* `/^REGEX$/` - regular expression
* `[0, 1, 'a' => 2]` - array
* `\stdClass('a' => 1)` - object
* `someReference` - reference 
* `someReference: \stdClass` - reference with type
* `someReference @ 0..100` - bounded reference
