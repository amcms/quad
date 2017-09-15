<?php

use PHPUnit\Framework\TestCase;

class QuadTest extends TestCase {

    private $quad;

    public function setUp() {
        $this->quad = new \Amcms\Quad\Quad();
    }

    public function testLoadTemplate() {
        $this->assertEquals('[+a+]', $this->quad->loadTemplate(__DIR__ . '/templates/templateshort.tpl'));

        $this->assertEquals('[+a+]', $this->quad->loadTemplate('@CODE: [+a+]'));
        $this->assertEquals('[+a+]', $this->quad->loadTemplate('@CODE [+a+]'));
        $this->assertEquals('[+a+]', $this->quad->loadTemplate('@CODE:[+a+]'));

        $this->expectException(\Amcms\Quad\Exceptions\FileNotFoundException::class);
        $this->quad->loadTemplate(__DIR__ . '/templates/notexiststemplate.tpl');
        $this->quad->loadTemplate(__DIR__ . '/templates/');
        $this->quad->loadTemplate(__DIR__ . '/templates');

        $this->expectException(\Amcms\Quad\Exceptions\UnknownBindingException::class);
        $this->quad->loadTemplate('@UNKNOWN: [+a+]');
    }

    public function testGetCompiledTemplateName() {
        $this->quad->setOption('cache', __DIR__ . '/cache');
        $this->assertEquals(__DIR__ . '/cache/d/f/dfeab1de57b435942d72fd2eef60b735ad864ee7b35692d09d05d0536ffce292.php', $this->quad->getCompiledTemplateName('template.tpl'));
    }

    public function testCreateDirectories() {
        @rmdir(__DIR__ . '/cache/d/f');
        @rmdir(__DIR__ . '/cache/d');
        $this->quad->createDirectories(__DIR__ . '/cache/d/f/test.php');
        $this->assertDirectoryExists(__DIR__ . '/cache/d/f');

        rmdir(__DIR__ . '/cache/d/f');
        $this->quad->createDirectories(__DIR__ . '/cache/d/f/test.php');
        $this->assertDirectoryExists(__DIR__ . '/cache/d/f');

        rmdir(__DIR__ . '/cache/d/f');
        rmdir(__DIR__ . '/cache/d');
    }

    public function testCompile() {
        $this->quad->setOption('templates', __DIR__ . '/templates');
        $this->quad->setOption('cache', __DIR__ . '/cache');

        $compiled = $this->quad->compile('template.tpl');
        $expected = __DIR__ . '/cache/d/f/dfeab1de57b435942d72fd2eef60b735ad864ee7b35692d09d05d0536ffce292.php';
        $this->assertEquals($expected, $compiled);
        unlink($compiled);

        $compiled = $this->quad->compile('@CODE: [+a+]');
        $expected = __DIR__ . '/cache/2/4/24c1b62c83d25a30f23b4eeb853f3ea83ed6f253cd4d8f74bab6755663148e0c.php';
        $this->assertEquals($expected, $compiled);
        unlink($compiled);

        $this->quad->setOption('cache', false);
        $compiled = $this->quad->compile('[+a+]');
        $this->assertEquals("<?php\necho \$api->getPlaceholder('a');\n", $compiled);
    }

}