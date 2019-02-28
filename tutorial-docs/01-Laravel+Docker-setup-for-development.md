## Laravel + Docker -- Setup for Development

### 目标
1. No Mamp or similar programs
2. No Vagrant or similar VM setups
3. No Globally installed PHP
4. No Globally installed Composer

#### Step 1: 下载最新的 Laravel 版本
```bash
$ mkdir learn-laravel-from-scratch
$ cd $_
$ curl -L https://github.com/laravel/laravel/archive/v5.8.0.tar.gz | tar xz
$ mv laravel-5.8.0 my-app
```

#### Step 2: 安装 Laravel 的依赖
```bash
cd my-app
docker run --rm --interactive --tty \
  --volume $PWD:/app \
  composer install
```

#### Step 3: Create docker-compose.yml
我们需要创建两个文件来定义我们的运行环境：开发和线上。

首先创建 `docker-compose.yml` 文件。大概是下面这个样子：

```dockerfile
version: '2'
services:
    #... our services will go here
```

##### 3.1 PHP-FPM
怎么执行我们的 PHP 代码那？我们需要定义一个服务，其大概是下面这个新子：

```dockerfile
app:
  build:
    context: ./
    dockerfile: app.dockerfile
  working_dir: /var/www
  volumes:
    - ./:/var/www
  environment:
    - "DB_PORT=3306"
    - "DB_HOST=database"
```
说明：
    - 在第 4 号，我们用 `app.dockerfile` 来创建执行 PHP 的镜像。
    - `/var/www` 是容器中代码运行的目录。
    - `./:/var/www` 将 php 代码挂载到 `/var/www` 中。

现在我们需要创建 `app.dockerfile`

```dockerfile

FROM php:7.1.26-fpm

RUN apt-get update && apt-get install -y libmcrypt-dev \
    mysql-client libmagickwand-dev --no-install-recommends \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && docker-php-ext-install mcrypt pdo_mysql
```
说明：
    - 这里用的是 `php:7.1.26-fpm`. 你也可使用其它版本。
    - 其它是基本的 CRUD 库，以及处理图片的 imagick 扩展.

##### 3.2 Nginx

接下来，我们需要配置一个服务器来处理静态资源文件以及处理转发 Laravel 的请求。

在 `docker-compose.yml` services 下增加一个 `web` 服务。

```
  web:
    build:
      context: ./
      dockerfile: web.dockerfile
    working_dir: /var/www
    volumes_from:
      - app
    ports:
      - 8080:80
```
说明：
    - `web.dockerfile` 来定义 web 服务。
    - `volumes_from` 从 `app` 服务中继承。
    - 将服务器的 80 端口映射出来 8080。

Next, 我们需要编写 `web.dockerfile` 文件。

```dockerfile
FROM nginx:1.14.2

ADD vhost.conf /etc/nginx/conf.d/default.conf
```

我们只是使用一个官方的镜像 nginx. 下面是我们的 nginx 配置文件`vhost.conf`。

```dockerfile
server {
    listen 80;
    index index.php index.html;
    root /var/www/public;

    location / {
        try_files $uri /index.php?$args;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
}
```
- 我们将 php 转发到了 `app:9000`。这是因为 Docker compose 默认其中的服务是可以相互通信的.
- 这个配置没有考虑性能和安全。

##### MySql
接下我们将配置数据库。在 PHP-FPM, Nginx 中我让容器可以直接访问我们本地文件，这样可以方便我们开发。
但是在使用数据库时，我们希望数据库能够得到持久化，这样在重启 Docker 的时候不会丢失数据。
（有测试、开发库的时候不需要这一项).

Docker 是可以访问外部的服务器的，但注意 localhost.

#### Starting the services


```bash
docker-compose build  # 第一次我们需要执行 docker-compose build
docker-compose up
```

#### Final Step

1. 环境配置文件

```bash
$ copy .env.example -> .env
```

2. Application Key & Optimize

第二步我们需要生成应用程序 key 和 优化。由于我们的 php 环境是在 docker 的容器内部的，所以我们的需要用 `docker-compose` 前缀来完成。即：

```bash
$ docker-compose exec app php artisan key:generate
$ docker-compose exec app php artisan optimize
```
到些为止，你就可以访问 http://0.0.0.0:8080 了.

通过 docker-compose 指定容器就可将我们的命令发送给容器执行，这样我们就可以不必为 php 版本烦心了。
以后你还可能需要执行下面的命令：
```bash
$ docker-compose exec app php artisan migrate --seed
```

我们可以配置一个 alias 来解决这个问题。

```
alias phpd='docker-compose exec app php'
alias phpda='docker-compose exec app php artisan '
```

TODO:
    我应该写个脚本，可以初始化任何 laravel 环境。

### 后记
我们可以把 dockerfile, vhost.conf 文件话在 docker 文件中，这样外面只保留一个 docker-compose.yml 文件。
这样管理起来更加方便。

不过要注意，dockerfile 中的相对目录是 docker-compose 中的这一层目录。

### References
1. [Laravel + Docker Part 1 — setup for Development](https://medium.com/@shakyShane/laravel-docker-part-1-setup-for-development-e3daaefaf3c)
2. [Composer ](https://hub.docker.com/_/composer)
3. [Do not recommend install composer in docker for production](https://github.com/docker-library/php/issues/344#issuecomment-265014805)
