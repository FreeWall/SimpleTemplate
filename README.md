# SimpleTemplate
PHP template engine based on regular expressions.

Getting Started
===============

Create engine instance and set array of variables to parsing the template:
```php
$variables = array(
	"meta" => array(
		"title" => "Meta title",
		"description" => "Meta description"
	),
	"articles" => array(
		array(
			"title" => "Some article",
			"created" => time(),
			"views" => 4321
		),
		array(
			"title" => "Article title longer then 20 chars",
			"created" => time(),
			"views" => 62341
		)
	)
);

$engine = new SimpleTemplate\Engine($variables);
$engine->loadTemplate("template.tpl");
echo $engine->getOutput();
```
#### Template:
```html
<html>
<head>
	<title>{#meta[title]}</title>
	<meta name="description" content="{#meta[description]|truncate:160}"/>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
</head>
<body>
	{if #articles}
	<ul>
		[#articles]
		<li>
			<h2>{#title|truncate:20}</h2>
			Created: {#created|date:d.m.Y}<br />
			Views: {#views|number}
		</li>
		[/#articles]
	</ul>
	{/if}
</body>
</html>
```

#### HTML output:
```html
<html>
<head>
	<title>Meta title</title>
	<meta name="description" content="Meta description"/>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
</head>
<body>
	<ul>
		<li>
			<h2>Some article</h2>
			Created: 26.04.2015<br />
			Views: 4 321
		</li>
		<li>
			<h2>Article title…</h2>
			Created: 26.04.2015<br />
			Views: 62 341
		</li>
	</ul>
</body>
</html>
```

Filters
=======
SimpleTemplate allows using filters separated by vertical bar. Filters (or modifiers) are functions which format the data to a special form. Filters may have parameters separated by colon. Filters are derived from [Latte](https://github.com/nette/latte) template engine.

```html
{#title|truncate:20|upper}	<!-- prints upper case title truncated to 20 chars -->
```

##### List of filters:
 - upper
 - lower
 - firstUpper
 - firstLower
 - truncate (length, append = '…')
 - repeat (count)
 - date (format)
 - number (decimals = 0, dec_point = '.', thousands_sep = ' ')
 - toAscii
 - webalize

Conditions
==========
SimpleTemplate allows using conditions and ternary operators.

##### "Is set and not empty" conditions:
```html
{if #articles}
	<!-- #articles is not empty -->
{/if}
```

##### Values comparsions:
```html
{if #views > 5000}
	<!-- #views is larger than 5000 -->
{/if}
```

##### Variables comparsions:
```html
{if #views > #comments}
	<!-- #views is larger than #comments -->
{/if}
```

##### Ternary operators:
```html
{#articles ? Not empty : Empty}
```
```html
{#views > 5000 ? Large than 5000 : Smaller than 5000}
```

Cache
=====
SimpleTemplate allows caching templates to cache folder. Cache is enabled by default.

```php
$engine->setCache(true/false);
```
