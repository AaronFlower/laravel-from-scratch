## Why you can access Request instance.

在配置 `routes/web` 路由，我们新增一个路由：

```php
Route::get('/twitter', 'UserController@twitter');
```

然后在对应的 `UserController.php` 的 `twitter` 方法中，我们可以写下面的代码：

```php
public function twitter(Twitter $twitter, Request $request) {
    dump($request);
    dump($twitter);
    return;
}
```

我们可以直接访问: `$twitter, $request` 实例，这是为什么那？

这就是 Laravel 中最重要的一个概念，即容器的概念，因为 Twitter, Request 已经被注册进容器了。当你访问时，容器会直接通过注册进来的实例给你返回出来。

下面是 Twitter 类注册的方法：
### 1. 首先创建 Twitter 服务
```php
<?php

namespace App\Services;

class Twitter {
    protected $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }
}

```

### 2. 然后，在 AppServiceProvider 中的 `register` 函数中将对应的 Service 给注册进来

*Register any application services.*

```php
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Twitter::class, function () {
            return new Twitter('my-api-key');
        });
    }

```

### 3. 使用

可以在 Controller 的函数原型中直接使用：
```php
public function twitter(Twitter $twitter, Request $request) {}
```

针对 Twitter, Facebook, Google+ 我们可以通过创建一个 `SocialServiceProvider` 然后在里面注册对应的服务。*注意:* 别忘记了在 `config/app.php` 的反这个 Provider 加入进来到 providers 中。


