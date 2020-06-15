# Zanichelli IDP Laravel Extension packages

## How to integrate package in your project

### Step 1 - Install by Composer

```bash
   composer require zanichelli/idp-extensions
```

**`Note:`you should use tag instead of branch-name (e.g. _"zanichelli/idp-extensions:dev-V1.0.0"_ )**


### Step 4 - .env file

Add this lines at bottom of your .env file:

```
  IDP_URL=https://idp.zanichelli.it/loginForm
  IDP_TOKEN_URL=https://idp.zanichelli.it/v1/user
  IDP_LOGOUT_URL=https://idp.zanichelli.it/v1/logout
```

If you need to use your own login form (instead of the IDP one), please add this line too:

```
  IDP_LOGIN_URL=https://idp.zanichelli.it/v3/login
```

### Step 5 - Extends IDP middleware

In order to edit retrive permissions or add extra parameter to user object you can extend default class IDP Middleware.

Class must implement following methods:

- `retrievePermissions`: this method take userId and roles array as input, here role-based permissions must be retrieved to output an array of strings with permissions;

- `addExtraParametersToUser`: this method allow you to add extra parameters to the user object given as input.

After class creation, add in `kernel.php` file the new middleware class in `'$routeMiddleware'` array:

```php
  'idp' => \App\Http\Middleware\IdpMiddleware::class,
```


### Step 7 - auth.php editing

Edit `config/auth.php` as follow:

- In `'defaults'` array change value of `'guard'` from `'web'` to `'z-session'`

- Add new guards into `'guards'` array in `config/auth.php` file:

  ```php
    'z-session' => [
        'driver' => 'z-session',
        'provider' => 'z-provider'
    ]
  ```

- Add new provider into `'providers'` array after `'users'`:

  ```php
    'z-provider' => [
        'driver' => 'z-provider'
    ]
  ```

### Step 8 - create a Grant model

Go to a prompt or a terminal and cd into project directory;
Then run `php artisan make:model Grant`;
After that add `protected $timestamps = false;` to your brand new class;

### Step 9 - create a migration for grants table

Go to a prompt or a terminal and cd into project directory;
Then run `php artisan make:migration create_grants_table`;
after that in `up()` function update the code as follow:

```php
  public function up()
  {
      Schema::create('grants', function (Blueprint $table) {
          $table->increments('id');
          $table->integer('role_id');
          $table->integer('department_id')->nullable();
          $table->text('grant');
      });
  }
```

### Step 10 - run migrations

Go to a prompt or a terminal and cd into project directory;
Then run `php artisan migrate` to upgrade your database schema
with new migration created above

### Final step - protect your routes

Add to your route file (tipically `web.php`) the new middleware `idp`; code smells like this:

```php
  Route::group(['middleware'=>'idp'],function(){
    Route::get('/', function(){
      return view('home');
    });
  });
```

## Logout idp

Create a logout route inside `web.php` file using a **_logout_** method inside the controller.
Implement the code as follow:

```php
  Route::group(['middleware'=>'idp'],function(){
    Route::get('logout',  'LoginController@logout');
  });
```

Then define **`logout`**:

```php
use use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
  ...

  public function logout()
  {
    return Auth::logout();
  }
}
```

# Basics

With this integration you could use some Laravel's feature that allows to handle users and their authentication.
`Auth` is authtentication class that Laravel ships for this purpose and allow access to following methods:

- `Auth::check()`: returns `true` if a user is authenticated, `false` otherwise
- `Auth::guest()`: returns `true` if a user is guest, `false` otherwise
- `Auth::user()`: returns a `ZUser` class instance, `null` otherwise
- `Auth::id()`: returns `userId` if authtenticated, `null` otherwise
- `Auth::setUser($ZUser)`: sets a `Zuser` in session
- `Auth::attempt($credentials, $remember)`: try to login with IDP without using the login form, if success returns `true`, otherwise `false`
- `Auth::logout()`: logout a user, return `redirect`
