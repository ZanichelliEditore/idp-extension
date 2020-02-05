# Zanichelli IDP Laravel packages

## How to integrate library in your project

### Step 1 - OAuth key creation

Login into [Bitbucket](https://bitbucket.org), click on your badge (bottom-left), choose "Bitbucket settings" and then "OAuth";
To add a consumer click on "Create consumer" and fill following infos:

- Name (choose one)
- Callback URL (add a fake url like http://www.example.com)
- On permission boxes, select "Read only" on "Projects"
  A couple of consumer keys (Key and Secret) where generated when save the consumer.

Keep in mind this key for further use.

### Step 2 - Add dependency on `composer.json`

Add this infos before `'require'` array :

```php
    "repositories": [
        {
            "type": "git-bitbucket",
            "url": "https://bitbucket.org/zanichelli/zanichelli-packages"
        }
    ],
```

Add this line to `'require'` array:

```php
    "zanichelli/zanichelli-idp": "dev-master"
```

**`Note:`you should use tag instead of branch-name (e.g. _"zanichelli/zanichelli-idp": v2.0.0_ )**

Add this line to `'classmap'` array:

```php
    "vendor/zanichelli"
```

### Step 3 - Run composer update

Go to a prompt or a terminal and cd into project directory;
Then run `composer update`
During the execution composer ask for consumer key and secret generated above.
**Note:** if a token is request please ignore pressing enter giving nothing.

### Step 4 - .env file

Add this lines at bottom of your .env file:

```
  IDP_URL=https://idp.zanichelli.it/loginForm
  IDP_TOKEN_URL=https://idp.zanichelli.it/v1/user
  IDP_LOGOUT_URL=https://idp.zanichelli.it/v1/logout
```

If you need to use your own login form (instead of the IDP one), please add this line too:

```
  IDP_LOGIN_URL=https://idp.zanichelli.it/v2/login
```

### Step 5 - Adding IDP middleware

Open your project folder and go to `App\Http\Middleware` folder, then add a class named `IdpMiddleware` that exetend `IDP`
Class must implement following methods:

- `retrievePermissions`: this method take userId and roles array as input, here role-based permissions must be retrieved to output an array of strings with permissions;

- `addExtraParametersToUser`: this method allow you to add extra parameters to the user object given as input.

for a basic use, class source code smell like the following:

```php
  namespace App\Http\Middleware;

  use Closure;
  use App\Grant;
  use Zanichelli\IdentityProvider\Models\ZUser;
  use Zanichelli\IdentityProvider\Middleware\IdpMiddleware as IDP;

  class IdpMiddleware extends IDP {
      /**
      * Returns the array with permissions
      *
      * @param $userId
      * @param array $roles
      * @return array
      */
      protected function retrievePermissions($userId, array $roles)
      {
          $permissions = [];
          foreach($roles as $role){
              $permission = Grant::where('role_id', $role->roleId)
                                      ->where('department_id', $role->departmentId)
                                      ->pluck('grant')->toArray();
              $permissions = array_merge($permissions, $permission);
          }
          return $permissions;
      }
      /**
      * Returns a ZUser after adding extra parameters. Otherwise return $user
      *
      * @param $user
      * @return ZUser
      */
      protected function addExtraParametersToUser(ZUser &$user){
        //
      }
  }

```

After class creation, add in `kernel.php` file the new middleware class in `'$routeMiddleware'` array:

```php
  'idp' => \App\Http\Middleware\IdpMiddleware::class,
```

### Step 6 - AuthServiceProvider

Add in `AuthServiceProvicer` class, on `App\Http\Middleware` folder, following uses:

```php
  use Illuminate\Support\Facades\Auth;
  use Zanichelli\IdentityProvider\Guards\ZGuard;
  use Zanichelli\IdentityProvider\Providers\ZAuthServiceProvider;
```

And the following code to `boot()` method:

```php
  Auth::provider('z-provider', function ($app, array $config){
      return new ZAuthServiceProvider();
   });

  Auth::extend('z-session', function ($app, $name, array $config){
    return ZGuard::create($this->app['session.store'], Auth::createUserProvider($config['provider']));
  });
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
- `Auth::logout()`: logout a user, returns `true` or `false`
