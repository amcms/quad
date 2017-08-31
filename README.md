# Quad

It's a template engine, based on MODX syntax

### Sample initialization

```php
use Amcms\Quad\Quad;

class Api implements Amcms\Quad\Api {
    ...
}

$api = new Api;

$parser = new Quad($api, [
    'cache'     => __DIR__ . '/cache',
    'templates' => __DIR__ . '/templates',
]);
```
then:
```php
return $parser->render('main.tpl');
return $parser->render('@CODE: <h3>[+pagetitle+]</h3>', ['pagetitle' => 'test']);
```
