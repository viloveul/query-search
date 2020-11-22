# Search Expression 

```php
$query = 'SELECT * FROM prefix_tbl /* where condition */';
$count = 'SELECT count(*) from prefix_tbl /* where condition */';
$config = [
	'order' => [
		'foo' => 'tbl.your',
		'bar' => 'tbl.name'
	],
	'filter' => [
		'tbl.your like {%foo}',
		'tbl.name = {bar}'
	]
];
$connection = new Viloveul\Query\Search\DoctrineConnection($doctrine);
$search = new Viloveul\Query\Search\Expression($query, $connection);
$search->withParameter(new Viloveul\Query\Search\Parameter($_GET));
$search->withPrefix('your_table_prefix_');
$search->withCount($count);
$search->configure($config);
$search->execute();

$data = $search->getData();
$total = $search->getTotal();
```

### FILTER
```
http://your.id/path?filter[foo]=keyword
http://your.id/path?filter[foo]=keyword&filter[bar]=other
```
or
```
http://your.id/path?filter_foo=keyword
http://your.id/path?filter_foo=keyword&filter_bar=other
```

### SORTING
```
http://your.id/path?sort=-foo,other
http://your.id/path?sort_asc=foo&sort_desc=bar,other
```

### PAGING
```
http://your.id/path?page=3
http://your.id/path?page[limit]=30&page[number]=2
http://your.id/path?page_limit=30&page_number=2
```