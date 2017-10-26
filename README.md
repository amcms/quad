# Quad

<img src="https://travis-ci.org/amcms/quad.svg?branch=master"> <img src="https://img.shields.io/badge/PHP-%3E=5.6-green.svg?php=5.6">

It's a template engine, based on MODX syntax, with precompilation in php code

## 1. Установка

Для установки выполните команду
```
composer require "amcms\quad"
```

## 2. Начало использования

Так как шаблонизатор не реализует некоторую логику получения полей, нужно создать новый класс, наследуемый от Amcms\Quad\Quad и в дальнейшем использовать его:
```php
class Api extends Amcms\Quad\Quad {
    public function getField($name, $binding = null, $binding_arg = null) {
        // получение поля документа
    }

    public function getConfig($name) {
        // получение системной настройки
    }

    public function getLang($name) {
        // получение локализованных значений
    }

    public function makeUrl($id) {
        // создание url из идентификатора документа
    }
    
    public function runSnippet($name, $params = [], $cached = true) {
        $result = parent::runSnippet($name, $params, $cached);

        if ($cached) {
            // дополнительные действия по кешированию сниппета
        }

        return $result;
    }
}

$api = new Api([
    'templates' => __DIR__ . '/templates', // размещение шаблонов
    'chunks'    => __DIR__ . '/chunks',    // размещение чанков
    'cache'     => __DIR__ . '/cache',     // размещение скомпилированных шаблонов (или false)
]);
```

После инициализации система должна зарегистрировать все используемые сниппеты и модификаторы:
```php
$api->registerSnippet('snippet_name', function($params) {
    // код сниппета
});

$api->registerFilter('filter_name', function($params) {
    // код модификатора
})
```

## 3. Базовые теги

`[+placeholder+]` - Вывод плейсхолдера

`[*field*]` - Вывод поля документа

`[(setting)]` - Вывод системной опции

`[%lang%]` - Вывод локализованного значения

`{{chunk}}` - Вывод чанка

`[[snippet]]`, `[!snippet!]` - Выполнение сниппета и вывод результата

`[-comment-]` - Комментарий

### 3.1. Плейсхолдеры

Плейсхолдеры могут быть установлены глобально с помощью метода `Quad::setPlaceholder`, либо переданы массивом в функцию `Quad::renderTemplate` в качестве второго параметра - тогда они будут видимы только в пределах соответствующего им шаблона/чанка.
При выводе сначала проверяется наличие локальных плейсхолдеров, затем глобальных.

### 3.2. Поля текущего документа

Шаблонизатор не предусматривает логику работы с документами, поэтому система должна сама реализовывать метод `Quad::getField`.
Также возможо указание биндинга выводимого поля, для вывода полей документа, который не является текущим.
Формат вызова в этом случае такой:
```
[*field@binding(binding_value)*]
```

Например:

```
[*pagetitle@parent*] - получение заголовка родительского документа;
[*pagetitle@26*] - получение заголовка документа под номером 26;
[*pagetitle@uparent(2)*] - получение заголовка родителя второго уровня;
```

Сама логика получения полей документов также должна быть реализована использующей системой.

### 3.3. Системные настройки

Шаблонизатор не предусматривает логику получения системных настроек, поэтому система должна сама реализовывать метод `Quad::getConfig`.

### 3.4. Локализация

Система должна реализовывать получение локализованных значений через метод `Quad::getLang`. Для склонения значений можно использовать модификатор `key`.

Таким образом, источник значений и вывод могут выглядеть так:

```php
...
$lang = [
   1 => 'пользователь',
   2 => 'пользователя',
   ...
];
...
```

```
[%user:key=`2`%] // выведет "пользователя"
```

### 3.5. Чанки

По сути, чанки - это те же шаблоны, с некоторыми отличиями:
- обычно содержат небольшие по размеру участки кода, для многократного использования, либо для семантического разбиения;
- могут размещаться в отдельной папке;

Синтаксис вывода чанка из шаблона следующий:
```
{{chunk_name}} - простой вывод;
{{chunk_name? &param1=`value1` &param2=`value2`}} - вывод с параметрами
```

Аналогичные вызовы api-метода будут выглядеть так:
```php
$api->parseChunk('chunk_name');
$api->parseChunk('chunk_name', ['param1' => 'value1', 'param2' => 'value2']);
```

### 3.6. Сниппеты

Шаблонизатор реализует два способа вызова сниппета - кешированный и некешированный.
Кеширование результата ложится на использующую систему.

Пример вызова:
```
[!snippet? &param1=`value1` &param2=`value2`!]
```

### 3.7. Комментарии

Тег `[- ... -]` может быть использован для текстовых многострочных комментариев,
содержимое тега будет проигнорировано при компиляции.

Также, если нужно закомментировать вызов сниппета или чанка, достаточно перед именем сниппета/чанка
поставить знак `-`. Например:
```
[!-snippet? &param1=`value1` &param2=`value2`!]
```

## 4. Inline-шаблоны

Чтобы передать шаблон в сниппет напрямую, а не сохраняя в чанк, можно использовать приставку `@CODE`:

```
[[snippet? &tpl=`@CODE: [+a+]`]]
```

В inline-шаблонах не поддерживаются управляющие структуры и вложенные inline-шаблоны.
В процессе компиляции inline-шаблоны проверяются наравне с другими элементами

## 5. Фильтры, модификаторы

Фильтры и модификаторы служат для быстрого изменения вывода путем применения к нему различных функций, например, частыми задачами являются экранирование, проверка на пустоту, форматирование и т.п.

Примеры применения фильтра:
```
[+value:escape+]
[+value:append=`test`+] - модификатор с параметром
[+value:append=`test`:escape+] - применение нескольких фильтров
[*pagetitle@uparent(2):escape*] - применение модификатора совместно с привязкой поля

[[snippet:escape? &param=`value`]] - применение модификатора к результату работы сниппета
```

<table>
<tr><th colspan="3">Фильтры</th></tr>
<tr><th>Название</th><th>Параметр</th><th>Назначение</th></tr>
<tr><td>contains</td><td></td><td></td></tr>
<tr><td>containsnot</td><td></td><td></td></tr>
<tr><td>empty</td><td></td><td></td></tr>
<tr><td>!empty, notempty</td><td></td><td></td></tr>
<tr><td>gt, isgt, greaterthan, isgreaterthan</td><td></td><td></td></tr>
<tr><td>gte, isgte, ge, eg, equalorgreaterthan, greaterthanorequalto</td><td></td><td></td></tr>
<tr><td>in, inarray, in_array</td><td></td><td></td></tr>
<tr><td>is, eq, equals, equalto, isequal, isequalto</td><td>string</td><td>Возвращает результат сравнения значения с параметром</td></tr>
<tr><td>lt, islt, lessthan, lowerthan, islessthan, islowerthan</td><td></td><td></td></tr>
<tr><td>lte, islte, le, el, lessthanorequalto, equaltoorlessthan</td><td></td><td></td></tr>
<tr><td>ne, neq, not, isnot, isnt, notequals, notequalto</td><td></td><td></td></tr>
<tr><td>notin, !in, !inarray, notinarray, !in_array</td><td></td><td></td></tr>
<tr><th colspan="3">Модификаторы</th></tr>
<tr><th>Название</th><th>Параметр</th><th>Назначение</th></tr>
<tr><td>abs</td><td></td><td></td></tr>
<tr><td>add, incr, increment, plus</td><td></td><td></td></tr>
<tr><td>after, append</td><td></td><td></td></tr>
<tr><td>capitalize, cap</td><td></td><td></td></tr>
<tr><td>ceil</td><td></td><td></td></tr>
<tr><td>dateformat, date_format, dateFormat, formatDate, format_date, date</td><td></td><td></td></tr>
<tr><td>div, divide</td><td></td><td></td></tr>
<tr><td>ellipsis</td><td></td><td></td></tr>
<tr><td>e, esc, escape</td><td></td><td></td></tr>
<tr><td>floor</td><td></td><td></td></tr>
<tr><td>html_decode, decode_html, html_entity_decode</td><td></td><td></td></tr>
<tr><td>htmlent, htmlentities</td><td></td><td></td></tr>
<tr><td>htmlspecial, htmlspecchars, htmlspecialchars, hsc</td><td></td><td></td></tr>
<tr><td>limit</td><td></td><td></td></tr>
<tr><td>lcase, lowercase, strtolower, tolower, lower</td><td></td><td></td></tr>
<tr><td>ltrim</td><td></td><td></td></tr>
<tr><td>mod, modulus</td><td></td><td></td></tr>
<tr><td>moneyformat, money_format</td><td></td><td></td></tr>
<tr><td>mpe, multiply</td><td></td><td></td></tr>
<tr><td>nl2br</td><td></td><td></td></tr>
<tr><td>numberformat, number_format, numberFormat</td><td></td><td></td></tr>
<tr><td>before, prepend</td><td></td><td></td></tr>
<tr><td>replace</td><td></td><td></td></tr>
<tr><td>reverse, strrev</td><td></td><td></td></tr>
<tr><td>round</td><td></td><td></td></tr>
<tr><td>rtrim</td><td></td><td></td></tr>
<tr><td>spam_protect, spamprotect</td><td></td><td></td></tr>
<tr><td>strip</td><td></td><td></td></tr>
<tr><td>stripString, stripstring, stripstr, strip_string, strip_str</td><td></td><td></td></tr>
<tr><td>striptags, stripTags, notags, strip_tags</td><td></td><td></td></tr>
<tr><td>strtotime, totime</td><td></td><td></td></tr>
<tr><td>subtract, decr, decrement, minus</td><td></td><td></td></tr>
<tr><td>trim</td><td></td><td></td></tr>
<tr><td>ucfirst</td><td></td><td></td></tr>
<tr><td>ucwords</td><td></td><td></td></tr>
<tr><td>ucase, uppercase, strtoupper, toupper, upper</td><td></td><td></td></tr>
<tr><td>urldecode, url_decode, decode_url</td><td></td><td></td></tr>
<tr><td>urlencode, url_encode, encode_url</td><td></td><td></td></tr>
<tr><td>wordwrap</td><td></td><td></td></tr>
<tr><td>wordwrapcut</td><td></td><td></td></tr>
<tr><th colspan="3">Геттеры</th></tr>
<tr><th>Название</th><th>Параметр</th><th>Назначение</th></tr>
<tr><td>default, ifempty, isempty</td><td></td><td></td></tr>
<tr><td>else</td><td></td><td></td></tr>
<tr><td>first</td><td></td><td></td></tr>
<tr><td>ifnotempty, isnotempty</td><td></td><td></td></tr>
<tr><td>key</td><td></td><td></td></tr>
<tr><td>last</td><td></td><td></td></tr>
<tr><td>length, len, strlen</td><td></td><td></td></tr>
<tr><td>then</td><td></td><td></td></tr>
</table>

## 6. Управляющие структуры

Управляющие структуры недоступны в inline-шаблонах и внутренних вызовах.

### 6.1. Цикл for

```
{% for `item` in [[getItems]] %}
    [+item+]
    [+item_iteration+] - Номер итерации, начиная с 1
    [+item_index+] - Номер итерации, начиная с 0
{% endfor %}
```

### 6.2. Условие if

```
{% if [+value:is=`test`+] %}
    value is test
{% elseif [+value:is=`test2`+] %}
    value is test2
{% else %}
    value is not test
{% endif %}
```

### 6.3. Выбор switch

```
{% switch [+value+] %}
    {% case `test` %}
        value is test
    {% endcase %}

    {% default %}
        value is not test
    {% enddefault %}
{% endswitch %}
```
