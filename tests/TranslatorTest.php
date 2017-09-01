<?php

//require __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class Parser extends \Amcms\Quad\Quad {
    public function getField($name) {
        return 'VALUE OF FIELD "' . $name . '"';
    }

    public function getConfig($name) {
        return 'VALUE OF SETTING "' . $name . '"';
    }

    public function makeUrl($id) {
        return 'LINK FOR "' . $id . '"';
    }
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
            ['@CODE: [[getParam? &p=`1` &what=`name`]]', 'p'],
            ['@CODE: [[getParam? &p=`1` &what=`value`]]', 1],
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
            ['@CODE: [+a:add=`3`+]', 4],
            ['@CODE: [+a:add=`[+b+]`+]', 3],
            ['@CODE: [+a:add=`[+b:add=`[+a+]`+]`+]', 4],
            ['@CODE: [+a:add=`[+b:add=`[+a+]`+]`:add=`[+a+]`+]', 5],
            ['@CODE: [+a:add=`[+b:add=`[+a+]`+]`:add=`[+a:add=`[[getParam:add=`[+a+]`? &p=`3` &what=`value`]]`+]`+]', 9],
        ];
    }

    /**
     * @dataProvider providerParse
     */
    public function testParse($input, $result) {
        echo "\n" . $input . "\n";
        $output = $this->parser->renderTemplate($input);
        echo $output . "\n";

        $this->assertEquals($output, $result);
    }

}