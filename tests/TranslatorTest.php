<?php

//require __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase {

    const SNIPPET     = 1;
    const CHUNK       = 2;
    const PLACEHOLDER = 3;
    const DOCFIELD    = 4;
    const SETTING     = 5;

    protected $translator;

    public function setUp() {
        $this->translator = new \Amcms\Quad\Translator;
    }

    public function providerParse() {
        return [
            ['[[snippet]]', 'cached snippet'],
            ['[!snippet!]', 'uncached snippet'],
            ['[[getParamName? &parameter=`1`]]', 'parameter'],
            ['[[sum? &param1=`1` &param2=`2`]]', 3],
            ['[!sum? &param1=`1` &param2=`2` &param3=`3`!]', 6],
            ['[[sum? &param1=`1` &param2=`[+param2+]`]]', 3],
            ['[[sum? &param1=`1` &param2=`[[sum? &param1=`1` &param2=`2`]]`]]', 4],
            ['[+param1+] + [+param2+]', '1 + 2'],
        ];
    }

    /**
     * @dataProvider providerParse
     */
    public function testParse($input, $result) {
        echo "\n" . $input . "\n";
        $output = $this->translator->parse($input);
        echo $output . "\n";
        $output = preg_replace('/^<\?php echo (.+); \?>$/', 'return implode([$1]);', $output);
        $output = eval($output);
        echo $output . "\n";

        $this->assertEquals($output, $result);
    }

    private function call(...$args) {
        switch ($args[0]) {
            case self::SNIPPET: {
                $name = "call" . ucfirst($args[1]);
                if (count($args) == 4) {
                    return $this->$name($args[2], $args[3]);
                } else {
                    return $this->$name([], $args[2]);
                }
            }

            case self::CHUNK: {
                $name = "getChunk" . ucfirst($args[1]);
                return $this->$name([], $args[2]);
            }

            case self::SETTING:
            case self::DOCFIELD:
            case self::PLACEHOLDER: {
                $name = "getField" . ucfirst($args[1]);
                return $this->$name();
            }
        }

        return 'unknown call';
    }

    private function getFieldParam1() {
        return 1;
    }

    private function getFieldParam2() {
        return 2;
    }

    private function callSnippet($params, $cached) {
        return ($cached ? 'cached' : 'uncached') . ' snippet';
    }

    private function callGetParamName($params, $cached) {
        $this->assertEquals(1, count($params));
        return key($params);
    }

    private function callSum($params, $cached) {
        $this->assertGreaterThan(0, count($params));
        return array_reduce($params, function($result, $item) {
            return $result += $item;;
        });
    }

}