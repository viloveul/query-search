# Search Expression 

```php
$query = 'SELECT * FROM tprefix_tbl /* where condition */';
$count = 'SELECT count(*) from tprefix_tbl /* where condition */';
$order = [
  'foo' => 'tbl.your',
  'bar' => 'tbl.name'
];
$filter = [
  'tbl.your like {%foo}',
  'tbl.name = {bar}'
];

$search = new Viloveul\Search\Expression($doctrine, $query);
$search->withPrefix('your_table_prefix_');
$search->withCount($count);
$search->withFilter($filter);
$search->withOrder($sort);
$search->withParameter(new Viloveul\Search\Parameter($_GET));
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