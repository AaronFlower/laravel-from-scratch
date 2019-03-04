# Architecture Concepts

## 1. Request Lifecycle

### 1.1 Introduction

工欲善其事，必先利其器。这就是工具的重要性。但是首先你要知道工具到底是怎么工作的不是吗？

### 1.2 Lifecycle Overview

#### 首先

所有的请求触达的入口文件是 `public/index.php`。`index.php` 文件代码不多，只是加载框架的入口点。`index.php` 加载 Composer 生成的 autoloader 定义，然后从 `bootstrap/app.php` 中获取一个实例。请求触达后，Laravel 首先就是创建一个服务容器的实例。(The first action taken by Laravel itself is to create an instance of the application / service container).

#### 然后，HTTP / Console Kernels

接下来，request 会被转发到 HTTP kernel 或 console kernel 中，这取决于进入应用的 request 类型。我们现在可以只关注 `app/Http/Kernel.php`。

HTTP Kernel 继承自 `Illuminate\Foundation\Http\Kernel` 类，这个类定义了一个 `bootstrapper` 数组，在每一个请求转发进来之前都会执行。在每一个请求被 handle 之前，这些 bootstrappers 都会被执行，用来配置 **error handling, logging, 探测应用程序环境, 执行其它任务**。

HTTP Kernel 也定义了一系列的 middleware, 在请求通过时执行。这些中间件可以用来处理 HTTP session, 验证 CSRF token 等。

HTTP Kernel 中定义了一个非常简单的 `handle` 函数：接入一个请求 Request 然后返回一个 Response。 可以把 Kernel 想像成一个黑盒，输入 HTTP requests 然后返回 HTTP responses.

#### Service Providers

在 Kernel Bootstrapping 阶段最重要的一步是加载 service providers. 这些 Providers 配置在 `config/app.php` 文件中的`providers` 数组中。

Service Provider 有 `register` 和 `boot` 两个函数。
	- `register()` ：在加载 service providers 时，首先调用所有 providers 上的 `register` 函数。
	- `boot()`：当所有的 providers 都注册后才会去执行 `boot` 方法。

Service Providers 主要负责 bootstrapping 时框架所需要的组件，如：database, queue, validation, routing 组件等。 Service Providers 是 Laravel bootstrap 阶段中最重要的一步。

#### *最后*， Dispatch Request

**最后**，当应用程序自启动后并且注册完所有的 service providers, Request 就会被提交给对应的路由，然后路由将 Request 分发给对应的 controller 同时也会执行相应的路由中间件 middleware。

### Focus On Service Providers

Service Providers 是启动 Laravel 应用的关键。首先启动应用实例，然后注册 service providers, 最后 Request 被转发到相应的路由。整个流程就是这么简单。

真正的理解 Laravel 应用是怎么通过 Service Providers 构建启动是非常有用的。默认的 service providers 是保存在 `app/Providers` 目录中。

默认 `AppServiceProvider` 是空的，你可以这里写自己的 bootstrapping 进行 service container 的绑定。

************


## 2. Service Container, 服务容器

### 2.1 Introduction

Laravel Service 容器是一个用来管理类依赖和执行依赖注入的强有力工具 (class dependencies and performing dependency injection)。 Dependency injection 的意思是：当当前的类需要依赖其它类时，可以通过构造函数或其它方法如 setters 将依赖的类注入进来。

### 2.2 Binding
#### 2.2.1 Binding Basics
所有的服务容器绑定都需要在 service providers 中注册，下面就介绍怎么使用。
#### 2.2.2 Simple Bindings 简单绑定
在 service provider 中，你可以通过 `$this->app` 来访问容器(container)。我们可以通过`bind` 方法来注册一个绑定。如：

```php
$this->app->bind('HelpSpot\API`, function ($app) {
    return new HelpSpot\API($app->make('HttpClient'));
});
```
#### 2.2.3 Binding A Singleton 绑定一个单例实例
当把一个类或接口绑定的容器时，使用 `singleton`方法，那么这个类只会被解析一次，即只会返回同一个单例实例。

```php
$this->app->singleton('HelpSpot\API', function ($app) {
    return new HelpSpot\API($app->make('HttpClien'));
});
```

#### 2.2.4 Binding Instances
```php
$api = new HelpSpot\API(new HttpClient);
$this->app->instance('HelpSpot\API', $api);
```

#### 2.2.5 Binding Primitives (绑定常量)
`app()` 和 `$this->app` 是同一个实例，用那一个都可以。

```php
app()->bind('foo', function () {
    return 'bar';
});
$this->app->bind('foo', function () {
    return 'New Bar';
});
```

绑定完之后，如何使用那，我们可以通过下的方法来使用：

```php
dd(app('foo'));
```

## 3. Service Providers, 服务提供者

### 后记：关于Bootstrapping 的小故事。

![Bootstrapping](./bootstrapping.tif)

Bootstrap 的故事来源于：“掉进悬崖时拉自己的辫子把自己拉上来；在篱笆面前拉自己的靴子把自己拉过篱笆“。Bootstrapping 原义是说，想做更好的自己，但方法却不对（Bootstrap as a metaphor, meaning to better oneself by one's own unaided efforts）。其衍生出的意思是说，一个人在自己变强大的过程中，不需要外力的帮助。

#### 应用
- 计算机
    - 软件加载和执行：通过简单的程序启动，在启动后加载执行更大、更复杂的程序进入下一个状态。
    - Installer: 安装程序，现在好多安装程序就只是一个小的文件，然后打开个文件之后，再去下载更新其它依赖进行安装，这个过程也称之为 bootstrapping process.
    - 人工智能和机器学习：（Bootstrapping aggregating and Intelligence explosion) , Bootrapping 是一个通过迭代来提高分类器性能的技术。可以迭代自己进化，recursive self-improvement。

- 统计学
    - Boostrapping 是一种抽样方法，用来获得估计。

- 商业
    - Startups : 初创公司，通过自己的营利重新投资成长发展。这样在发展的过程中更有节制，另外 bootstapping 也使得 startups 关注的是客户而不是投资者。
    - Leveraged buyouts, 杠杆收购；融资买入；在收购的过程中大部分的资金是通过杠杆融资进来的，自己仅有一小部分资金。

- 生物学
    - Bootstrapping