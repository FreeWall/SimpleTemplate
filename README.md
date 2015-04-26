# SimpleTemplate
PHP template engine without using PHP in template files for security reasons and allowing editing templates to common users.

Example
=======

Create template engine instance and set array of variables to parsing the template:
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
		),
	)
);

$engine = new SimpleTemplate\Engine($variables);
$engine->loadTemplate("template.tpl");
echo $engine->getOutput();
```
Content of template file (template.tpl):
```html
<html>
<head>
	<title>{#meta[title]}</title>
	<meta name="description" content="{#meta[description]|truncate:160}"/>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
</head>
<body>
	<ul>
	[#articles]
		<li>
			<h2>{#title|truncate:20}</h2>
			Created: {#created|date:d.m.Y}<br />
			Views: {#views|number}
		</li>
	[/#articles]
	</ul>
</body>
</html>
```

HTML final output:
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
