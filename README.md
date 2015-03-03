modell
======

Simple model class for create/update/load data

> Under development


## Data table rules

- Primary key is auto incremented `id`
- Optional `created_at` and `updated_at` datetime columns


## Usage

Connect to database

```
Modell::$pdo = new PDO('mysql:host=HOST;dbname=DBNAME', 'USER', 'PASS');
```

Connect to memcache (optional)

```
Modell::$memcache = new Memcache;
Modell::$memcache->connect('localhost', 11211);
```

Create table `users` with primary key `id`, and model

```
class User extends Modell {
}
```

Run a simple query

```
$query = self::$pdo->prepare($sql);
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