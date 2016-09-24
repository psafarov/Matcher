<?php

namespace Psafarov\Matcher\Tests;

use Psafarov\Matcher\Processor;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @dataProvider getMatchingPatternTrees */
    public function testReturnsTrueIfMatches($patternTree, $subject)
    {
        $this->assertTrue((new Processor)->match($patternTree, $subject));
    }

    public function getMatchingPatternTrees()
    {
        return [

            'true' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => true
                    ]
                ],
                true
            ],

            'false' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => false
                    ]
                ],
                false
            ],

            'null' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => null
                    ]
                ],
                null
            ],

            ' 1' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => 1
                    ]
                ],
                1
            ],

            "'string'" => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => 'string'
                    ]
                ],
                'string'
            ],

            '0..5' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type' => 'range',
                        'min'  => 0,
                        'max'  => 5
                    ]
                ],
                3
            ],

            '/\w$/' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'regex',
                        'value' => '\w$'
                    ]
                ],
                '11a'
            ],

            '\stdClass(foo, bar)' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'       => 'object',
                        'name'       => '\stdClass',
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
                ],
                (function () {
                    $object = new \stdClass;
                    $object->foo = 0;
                    $object->bar = 1;
                    return $object;
                })()
            ],

            '\stdClass("foo" => fooAlias, "bar" => 0..5, baz: boolean)' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'       => 'object',
                        'name'       => '\stdClass',
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
                ],
                (function () {
                    $object = new \stdClass;
                    $object->foo = 0;
                    $object->bar = 2.5;
                    $object->baz = true;
                    return $object;
                })()
            ],

            'a: int @ 1..2' => [
                [
                    'type'          => 'expression',
                    'reference'     => 'a',
                    'referenceType' => 'int',
                    'pattern'       => [
                        'type' => 'range',
                        'min'  => 1,
                        'max'  => 2
                    ]
                ],
                1
            ],

            '[1, 2, "key" => 3, 4]' => [
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
                ],
                [1, 2, 'key' => 3, 4]
            ],
        ];
    }

    /** @dataProvider getNonMatchingPatternTrees */
    public function testReturnsFalseIfDoesNotMatch($patternTree, $subject)
    {
        $this->assertFalse((new Processor)->match($patternTree, $subject));
    }

    public function getNonMatchingPatternTrees()
    {
        return [

            'true' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => true
                    ]
                ],
                false
            ],

            'false' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => false
                    ]
                ],
                0
            ],

            'null' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => null
                    ]
                ],
                0
            ],

            ' 1' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => 1
                    ]
                ],
                '1'
            ],

            "'string'" => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'value',
                        'value' => 'string'
                    ]
                ],
                'otherString'
            ],

            '0..5' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type' => 'range',
                        'min'  => 0,
                        'max'  => 5
                    ]
                ],
                5
            ],

            '/\w$/' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'  => 'regex',
                        'value' => '^\w$'
                    ]
                ],
                'a11'
            ],

            '\stdClass(foo, bar)' => [
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
                ],
                new \Directory
            ],

            '\stdClass("foo" => fooAlias, "bar" => 0..5, baz: boolean)' => [
                [
                    'type'    => 'expression',
                    'pattern' => [
                        'type'       => 'object',
                        'name'       => '\stdClass',
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
                ],
                (function () {
                    $object = new \stdClass;
                    $object->foo = 0;
                    $object->bar = 2.5;
                    $object->baz = 1;
                    return $object;
                })()
            ],

            'a: int @ 1..2' => [
                [
                    'type'          => 'expression',
                    'reference'     => 'a',
                    'referenceType' => 'int',
                    'pattern'       => [
                        'type' => 'range',
                        'min'  => 1,
                        'max'  => 2
                    ]
                ],
                1.0
            ],

            '[1, 2, "key" => 3, 4]' => [
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
                ],
                [1, 2, 'key' => 3]
            ],
        ];
    }

    public function testCreatesValidReferences()
    {
        // \stdClass(a, 'b' => [b, 'c' => c])
        $patternTree = [
            'type'    => 'expression',
            'pattern' => [
                'type'       => 'object',
                'name'       => '\stdClass',
                'properties' => [
                    [
                        'type'  => 'property',
                        'name'  => 'a',
                        'value' => [
                            'type'      => 'expression',
                            'reference' => 'a',
                        ]
                    ],
                    [
                        'type'  => 'property',
                        'name'  => 'b',
                        'value' => [
                            'type'    => 'expression',
                            'pattern' => [
                                'type' => 'array',
                                'items'=> [
                                    [
                                        'type' => 'item',
                                        'name' => 0,
                                        'value' => [
                                            'type' => 'expression',
                                            'reference' => 'b'
                                        ]
                                    ],
                                    [
                                        'type' => 'item',
                                        'name' => 'c',
                                        'value' => [
                                            'type' => 'expression',
                                            'reference' => 'c'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $subject = new \stdClass;
        $subject->a = 1;
        $subject->b = [2, 'c' => 3];

        (new Processor)->match($patternTree, $subject, $references);
        $this->assertSame(1, $references['a']);
        $this->assertSame(2, $references['b']);
        $this->assertSame(3, $references['c']);
    }
}
