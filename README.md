# Quad

<img src="https://travis-ci.org/amcms/quad.svg?branch=master">
<img src="https://img.shields.io/badge/PHP-%3E=5.6-green.svg?php=5.6">

It's a template engine, based on MODX syntax, with precompilation in php code

Sample initialization:

```php
class Parser extends Amcms\Quad\Quad {
    public function getField($name, $binding = null, $binding_arg = null) {
        return ...
    }

    public function getConfig($name) {
        return ...
    }

    public function makeUrl($id) {
        return ...
    }
}

$parser = new Parser([
    'cache'     => __DIR__ . '/cache',
    'templates' => __DIR__ . '/templates',
    'chunks'    => __DIR__ . '/chunks',
]);
```
then:
```php
// render __DIR__ . '/templates/main.tpl
$parser->renderTemplate('main.tpl');

// render __DIR__ . '/chunks/chunk.tpl
$parser->parseChunk('chunk', ['a' => 1]);

// render inline template
$parser->renderTemplate('@CODE: <h3>[+pagetitle+]</h3>', ['pagetitle' => 'test']);
```
