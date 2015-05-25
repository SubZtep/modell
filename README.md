Modell v1.0
============

A PHP class for easily create/update/load database entries. All you need to do is extend this class from your model and enjoy the benefits. It uses PDO connection but I only tested with MySql. I am going to explain the usage with a simple user class.

> Please write your tests well.


## Installation

Extend your `composer.json` file with the following:

```json
{
  "require": {
    "subztep/modell": "dev-master"
  }
}
```

## Create data table

Our user table will contains name and email. Each plural table require an `id` int primary key field. Optional `created_at` and `updated_at` datetime columns for log your updates, recommended.

```sql
CREATE TABLE `users` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `name` varchar(30) NOT NULL,
 `email` varchar(255) DEFAULT NULL,
 `created_at` datetime DEFAULT NULL,
 `updated_at` datetime DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

## Usage with PHP

Connect to database

```php
Modell::$pdo = new PDO('mysql:host=HOST;dbname=DBNAME;charset=utf8', 'USER', 'PASS');
```

Connect to memcache is *optional*. If connected, *Modell* cache your table's column details and make it faster.

```php
Modell::$memcache = new Memcache;
Modell::$memcache->connect('localhost', 11211);
```

Create table `users` with primary key `id`, and add *Modell* class to your project

```php
class User extends Modell {
}
```

Furthermore, you can run any sql query, connection in singleton.

```
$query = Modell::$pdo->prepare($sql);
```


## Examples

Create user

```
$user = new User();
$user->name = 'John Doe';
$user->save();
```

Load user by id

```
$user = new User(1);
echo $user->name;
```

Update user by id

```
$user = new User(1);
$user->name = 'John Roe';
$user->save();
```