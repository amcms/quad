# Quad

It's a template engine, based on MODX syntax

### Sample initialization

```php
use Amcms\Quad\Quad;

$parser = new Quad([
    'cache'     => __DIR__ . '/cache',
    'templates' => __DIR__ . '/templates',
]);

$parser->registerFunction(Quad::SNIPPET, function($name, $params, $cached) {...});
$parser->registerFunction(Quad::CHUNK, function($name, $params) {...});
$parser->registerFunction(Quad::DOCFIELD, function($name) {...});
$parser->registerFunction(Quad::PLACEHOLDER, function($name) {...});
$parser->registerFunction(Quad::SETTING, function($name) {...});

$code = $parser->render('main.tpl');

```

### And then in main.tpl:
```html
{{header}}
<h1 class="page-title">
    [*pagetitle*]
</h1>
<img src="[[phpthumb? &input=`[*image*]` &options=`w=330,h=420,zc=1,f=jpg`]]" alt="" class="img-fluid">
<div class="user-content">
    [*content*]
</div>
{{footer}}
```
