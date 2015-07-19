A simplistic framework designed to include 1 file into your script to provide you with a full MVC patterned application. It is compatible with newer versions of PHP and also HHVM out of the box. The goal of slim is to keep PHP programming practices as simplistic as possible.  
  
__Setup__  
Out of the box, `acid` expects  you to be using a database, so it will require you to edit `lib/config.php` to provide it with database details. Other than that, there is no setup. You just include `lib/acid.php` at the start of all of your files and it will provide access to helpers for database access as well as templating.  
  
__Catalyst__  
`catalyst` is a light weight wrapper for MySQLi. It does quite a bit of lifting for you. It is included with `acid` and all the database models are intended to be stored in `lib/database`. `catalyst` makes very few assumptions about your database model. It is extremely flexible and can swiftly up development since it provides CRUD management while following an active record pattern.  
  
__Todo List__  
* Create a light weight and non-restricting database abstraction layer.
* Create documentation that includes practices and standards for slim.
