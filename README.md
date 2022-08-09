# MariaDBDump

MariaDb veritabanı için sql scripti oluşturur.

## Veritabanı scripti oluşturulan veritabanı nesneleri
- Table
- Table Index(primary key, unique key, plan, fulltext)
- Table Constraints (foreign key, check constraint)
- View
- Procedure

```php
$options = [
    'database' => [
        'host' => '',
        'dbname' => '',
        'user' => '',
        'password' => ''
    ]
];

$mariadbdump = new Mariadbdump($options);

try
{
    echo $mariadbdump->dump(__DIR__ . '/backup/');
}
catch (SaveFileException $e)
{
    echo 'Veritabanı scripti dosyaya kaydedilemedi!';
}
catch (Exception $e)
{
    echo $e->getMessage();
}
```
