<?php

use PHPUnit\Framework\TestCase;

class Parser extends \Amcms\Quad\Quad {

}


class TranslatorTest extends TestCase {

    protected $parser;

    public function setUp() {
        $this->parser = new Parser([
            'cache'     => false,
            'templates' => __DIR__ . '/templates',
        ]);

        $this->parser->registerSnippet('getCacheMode', function($parameters, $cached) {
            return $cached ? 'cached' : 'uncached';
        });

        $this->parser->registerSnippet('getParam', function($parameters) {
            if ($parameters['what'] == 'name') {
                return key($parameters);
            } else {
                return reset($parameters);
            }
        });

        $this->parser->registerSnippet('sum', function($parameters) {
            return array_reduce($parameters, function($sum, $arg) {
                return $sum + intval($arg);
            });
        });

        $this->parser->registerSnippet('setPlaceholder', function($parameters) {
            $this->parser->setPlaceholder($parameters['name'], $parameters['value']);
            return '';
        });

        $this->parser->registerFilter('add', function($input, $parameter) {
            return intval($input) + intval($parameter);
        });

        $this->parser->setPlaceholder('a', 1);
        $this->parser->setPlaceholder('b', 2);
        $this->parser->setPlaceholder('a1', 2);
    }

    public function providerParse() {
        return [
            ['@CODE: [[getCacheMode]]', 'cached'],
            ['@CODE: [!getCacheMode!]', 'uncached'],
            ['@CODE: [[getCacheMode? &p=`1`]]', 'cached'],
            ['@CODE: [!getCacheMode? &p=`1`!]', 'uncached'],
            ['@CODE: [!getCacheMode? &p=`[[getCacheMode]]`!]', 'uncached'],
            ['@CODE: [[getParam? &p=`1` &what=`name`]]', 'p'],
            ['@CODE: [[getParam? &p=`1` &what=`value`]]', 1],
            ['@CODE: [[getParam? &p=`[!getCacheMode!]` &what=`value`]]', 'uncached'],
            ['@CODE: [[getParam:add=`[+a+]`? &p=`1` &what=`value`]]', 2],
            ['@CODE: [[getParam:add=`[+a+]`:add=`[+b+]`? &p=`1` &what=`value`]]', 4],
            ['@CODE: [+a[[getParam? &p=`1` &what=`value`]]+]', 2],
            ['@CODE: [+a[[getParam? &p=`1` &what=`value`]]:add=`[[getParam? &p=`3` &what=`value`]]`+]', 5],
            ['@CODE: [[getParam? &p=`[+a1:add=`[+b+]`+]` &what=`value`]]', 4],
            ['@CODE: [[sum? &a=`1` &b=`2`]]', 3],
            ['@CODE: [!sum? &a=`1` &b=`2` &c=`3`!]', 6],
            ['@CODE: [[sum? &a=`1` &b=`[+b+]`]]', 3],
            ['@CODE: [[sum? &a=`1` &b=`[[sum? &a=`1` &b=`2`]]`]]', 4],
            ['@CODE: [+a+] + [+b+]', '1 + 2'],
            ['@CODE: test[+b+]', 'test2'],
            ['@CODE: [[getParam? &p=`test[+b+]` &what=`value`]]', 'test2'],
            ['@CODE: [[getParam? &p=`test[+b:add=`[[getParam? &p=`[+b+]` &what=`value`]]`+]` &what=`value`]]', 'test4'],
            ['@CODE: [+a[+a+]+]', 2],
            ['@CODE: [+a[+a+]:add=`1`+]', 3],
            ['@CODE: [+a[+a+]:add=`[+a[+a+]+]`+]', 4],
            ['@CODE: [+a:add=`3`+]', 4],
            ['@CODE: [+a:add=`[+b+]`+]', 3],
            ['@CODE: [+a:add=`[+b:add=`[+a+]`+]`+]', 4],
            ['@CODE: [+a:add=`[+b:add=`[+a+]`+]`:add=`[+a+]`+]', 5],
            ['@CODE: [+a:add=`[+b:add=`[+a+]`+]`:add=`[+a:add=`[[getParam:add=`[+a+]`? &p=`3` &what=`value`]]`+]`+]', 9],
            ['@CODE: [!setPlaceholder? &name=`c` &value=`3`!][+c+]', 3],
            ['@CODE: [(a)]', 'a'],
            ['@CODE: [(a[+a+])]', 'a1'],
            ['@CODE: [*a*]', 'a'],
            ['@CODE: [*a[+a+]*]', 'a1'],
            ['@CODE: [~[*a[+a+]*]~]', 'a1'],
            ['@CODE: [~[*a[+a+]*]~]', 'a1'],
            ['@CODE: [+a+][!setPlaceholder? &name=`a` &value=`3`!][+a+]', '13'],
            ['@CODE: {{chunk1}}', '<h1>1</h1>'],
            ['@CODE: {{chunk1? &c=`1`}}', '<h1>11</h1>'],
        ];
    }

    /**
     * @dataProvider providerParse
     */
    public function testParse($input, $result) {
        $output = $this->parser->renderTemplate($input);
        $this->assertEquals($result, $output);
    }

    public function testSetPlaceholder() {
        $this->parser->renderTemplate('@CODE: [!setPlaceholder? &name=`c` &value=`3`!]');
        $output = $this->parser->renderTemplate('@CODE: [+c+]');
        $this->assertEquals(3, $output);
    }

}