# CodeIgniter 3 System Customization

This is my customization system in Codeigniter 3

Just replace all folder in system

Feature :
1. Room (Views Layout)

2. Middleware

3. Route Group & Route Id

# Documentation Room Feature

### Declare a room
```sh
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Welcome to CodeIgniter</title>
</head>
<body>
<?=$room->declare('content')?>
</body>
</html>
```
The declare method only have one argument - the name of room, That way any child views know what to name the opened room .

### Extend a view
```sh
<?=$room->extend('welcome')?>
```
You must put the extend after all room has opened

### Open & close Room
```sh
<?=$room->open('content')?>
Hello World
<?=$room->close()?>
```
The open method only have one argument - the name of room, and close method dont need any argument. That way any room with similiar name to parent view will be replaced
```sh
<?=$room->open('content')?>
Hello World
<?=$room->close()?>

<?=$room->extend('welcome')?>
```

### Rendering the view with Room
if you don't change the name of library :
```sh
public function index(){
  $this->room->load('content');
}
```

if you want to pass a data to View :
```sh
public function index(){
  $data = [
    'hello'=> "World"
  ];
  $this->room->load('content', $data);
}
```

### Access data in room
In CI3 native, you'll access data with $hello, but we must using a different way 
```sh
<?=$room->open('content')?>
Hello <?=$room->data('hello')?>
<?=$room->close()?>

<?=$room->extend('welcome')?>
```

### Including room partials
Room partials is make a new viewm, this code similiar feature to Rendering view
h1.html
```sh
<h1>Title For website</h1>
```
content.html
```sh
<?=$room->open('content')?>
<?=$room->include('h1')?>
<?=$room->close()?>

<?=$room->extend('welcome')?>
```
If you are include a view, the new view will have different data so you can passing data from content.html to h1.html
h1.html
```sh
<h1><?=$room->data('title')?></h1>
```
content.html
```sh
<?=$room->open('content')?>
<?=$room->include('h1', ['title=>"Title For website"])?>
<?=$room->close()?>

<?=$room->extend('welcome')?>
```

# Documentation Middleware Feature
Create middlewares folder in application

Example IsLoginMiddleware.php
```sh
<?php
class IsLoginMiddleware extends Middleware{
	
	public function run(): bool
	{
		$params = $this->getParams();
		if($params['auth'] == true)
		{
			return !isset($_SESSION) && ($_SESSION['logged_in'] == false); 
		}
		else if($params['auth'] == false)
		{
			return isset($_SESSION) && ($_SESSION['logged_in'] == true);
		}
	}

	public function post_run($sucess)
	{
		$params = $this->getParams();
		if($params['auth'] == true)
		{
			if(!$sucess)
			{
				echo "login";
			}
			return true;
		}
		else if($params['auth'] == false)
		{
			if(!$sucess)
			{
				echo "not login";
			}
			return true;
		}
	}
}
```
Method post_run() will get called after method run(), and params $success in post_run is return value from run()


# Documentation Route Group Feature
Example routes.php
```sh
$route['dashboard/'] = [
	'id'=>'dashboard',
	'middleware'=>['IsLoginMiddleware', 'DashboardMiddleware'],
	'params' => [
		'IsLoginMiddleware'=> [
			'auth'=>true
		]
	],
	'child'=> [
		'admin'=>[
			'id'=>'admin',
			'middleware'=>['AdminMiddleware', '-DashboardMiddleware'],
			'child' => 'dashboard/admin/index'
		],
		'staff'=>[
			'id'=>'admin',
			'middleware'=>['StaffMiddleware'],
			'params'=>[
				'IsLoginMiddleware'=>[
					'auth'=>false
				]
			]
		]
	]
];
```

# Todos

 - More efficient & effective code
 - Data conditional rendering

License
----

MIT


# Don't hesitate to contact me if you need help
Email : ichsann.saidd@gmail.com

Instagram : said_nrs

Facebook : https://www.facebook.com/telorjan/

Because my english is so bad, i think i only accept project from indonesian people :(





