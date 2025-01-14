# Zanichelli IDP Laravel Extension packages

This is Laravel package to use with laravel-jwt-idp (Github: https://github.com/ZanichelliEditore/laravel-jwt-idp).

## How to integrate package in your project

### Step 1 - Install by Composer

```bash
   composer require zanichelli/idp-extensions
```

**`Note:`you should use tag instead of branch-name (e.g. _"zanichelli/idp-extensions:V1.0.0"_ or _"zanichelli/idp-extensions:dev-{branch-name}"_ )**

### Step 2 - .env file

Add this lines at bottom of your .env file:

```
  IDP_URL=https://idp.zanichelli.it/loginForm
  IDP_TOKEN_URL=https://idp.zanichelli.it/v1/user
  IDP_LOGOUT_URL=https://idp.zanichelli.it/v1/logout
  IDP_COOKIE_NAME=token
```

If you need to use your own login form (instead of the IDP one), please add this line too:

```
  IDP_LOGIN_URL=https://idp.zanichelli.it/v4/login
```

### Step 3 - auth.php editing

Edit `config/auth.php` as follow:

- In `'defaults'` array change value of `'guard'` from `'web'` to `'z-session'`

### Step 4 - publish migrations

There are 2 migration from this package, Grants table and Sessions Table.

```bash
   php artisan vendor:publish
```

and select the "zanichelli/idp-extension" provider

### Step 4.A - publish migrations (BREAKING CHANGES) after v3.0.**\***

There are 3 migrations from this package:

- Grants table
- Sessions Table
- Grants table key changes (Change role_id and department_id to **role_name** and **department_name**).

```bash
   php artisan vendor:publish
```

Using the command below will only apply the changes about role_id and department_id

```bash
   php artisan vendor:publish --tag=grants-by-name-instead-of-id
```

Use

```bash
   php artisan vendor:publish --tag=grants-by-name-instead-of-id --force
```

if you need to overwrite grants table changes migration.

### Step 5 - create route middleware and protect your routes

In Kernel.php file add "idp" in your routeMiddleware

```php
'idp' => \Zanichelli\IdpExtension\Http\Middleware\IdpMiddleware::class,
```

The default behaviour also retrieves the user's permissions (`with_permissions`) and remove token from query params (`without_token_url`)
You can specify different configuration like this:
Avoid to remove token from url
```php
  Route::group(['middleware'=>'idp:with_permissions,with_token_url'],function(){
    Route::get('/', function(){
      return view('home');
    });
  });
```
Avoid to retrieve permission
```php
  Route::group(['middleware'=>'idp:without_permissions'],function(){
    Route::get('/', function(){
      return view('home');
    });
  });
```
Avoid to remove token from url and retrieve permission
```php
  Route::group(['middleware'=>'idp:without_permissions,with_token_url'],function(){
    Route::get('/', function(){
      return view('home');
    });
  });
```

Add to your route file (tipically `web.php`) the new middleware `idp`; code smells like this:

```php
  Route::group(['middleware'=>'idp'],function(){
    Route::get('/', function(){
      return view('home');
    });
  });
```

Alternatively, a second middleware reads the cookie and, if found, retrieves the user's data and adds it to the request

```php
'idp.user' => \Zanichelli\IdpExtension\Http\Middleware\AddIdpUserDataMiddleware::class,
```

### Extends IDP middleware

In order to edit retrive permissions or add extra parameter to user object you can extend default class IDP Middleware.

Class must implement following methods:

- `retrievePermissions`: this method take userId and roles array as input, here role-based permissions must be retrieved to output an array of strings with permissions;

- `addExtraParametersToUser`: this method allow you to add extra parameters to the user object given as input.

After class creation, add in `kernel.php` file the new middleware class in `'$routeMiddleware'` array:

```php
  'idp' => \App\Http\Middleware\IdpMiddleware::class,
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
- `Auth::hasUser()`: returns `true` if there's a ZUser in our current session, `false` otherwise
- `Auth::setUser($ZUser)`: sets a `Zuser` in session
- `Auth::attempt($credentials, $remember)`: try to login with IDP without using the login form, if success returns `true`, otherwise `false`
- `Auth::logout()`: logout a user, return `redirect`
