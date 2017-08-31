# Quad

It's a template engine, based on MODX syntax, with precompilation in php code

Sample initialization:

```php
class Parser extends Amcms\Quad\Quad {
    ...
}

$parser = new Parser([
    'cache'     => __DIR__ . '/cache',
    'templates' => __DIR__ . '/templates',
]);
```
then:
```php
$parser->renderTemplate('main.tpl');
$parser->renderTemplate('@CODE: <h3>[+pagetitle+]</h3>', ['pagetitle' => 'test']);
```
