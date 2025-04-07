# 思维脉络 Cognitive Nexus - 后端

[![PHP 8.1](https://img.shields.io/badge/PHP-8.1-blue.svg)](https://www.php.net/)

这是 [思维脉络](https://github.com/CognitiveNexus) 项目的后端仓库，基于 FlightPHP 构建。

## 运行环境

-   **PHP 8.1** 或更高版本

## 快速开始

本项目主要面向 Ubuntu 22.04 LTS、macOS 15.3 Sequoia 编写，同样适用于其他 Linux 或 Unix 系操作系统。本文档中的指令以 Ubuntu 为例，其他系统可参考相关文档进行安装和配置。

### 1. 克隆项目

```bash
git clone git@github.com:CognitiveNexus/CognitiveNexus-Backend
cd CognitiveNexus-Backend
```

### 2. 安装并配置 PHP

1.  安装 `php-fpm`：

    ```bash
    sudo apt-get install php8.1-fpm php8.1-curl php8.1-pgsql php8.1-uuid composer
    ```

2.  安装 Composer 依赖：

    ```bash
    composer install
    ```

3.  配置 HTTP 服务器：

    在测试环境中，可以直接运行 `./run-dev.sh` 启动 PHP 内置的 HTTP 服务器。但请注意，该服务器不得在生产环境中使用。

    生产环境推荐使用 Nginx 作为 HTTP 服务器，配置如下：

    ```
    server {
        listen 80 default_server;
        listen [::]:80 default_server;
        rewrite ^(.*)$ https://$host;       # 强制使用 HTTPS
    }

    server {
        listen 443 ssl default_server;
        listen [::]:443 ssl default_server;
        
        # 添加 SSL 证书相关配置项

        server_name cognitive-nexus.com;
        root /path/to/frontend;             # 指向构建好的前端页面
        index index.html index.php;

        location / {
            try_files $uri /index.html;
        }

        location /api/ {
            root /path/to/backend/public;   # 指向后端 public/ 文件夹
            try_files $uri /index.php$is_args$query_string;
        }

        location ~ \.php$ {
            root /path/to/backend/public;   # 指向后端 public/ 文件夹
            include snippets/fastcgi-php.conf;
            fastcgi_buffering off;
            fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        }
    }
    ```

4.  配置环境变量：

    -   重命名 .env.example 为 .env
    -   根据需要填写环境变量值
    -   ENVIRONMENT 项填写 `development` 时，可以享受开发环境的便利（如，允许所有 CORS 请求、抛出的错误直接显示在请求中），但这些便利是牺牲安全性的，因此在生产环境中部署时，请务必将 ENVIRONMENT 项改为 `production`。

### 3. 启动服务

完成上述配置后，启动 HTTP 服务器即可访问服务。

## 项目结构

```
CognitiveNexus-Backend
├── .env                        # 环境变量配置
├── app/
│   ├── controllers/
│   │   ├── AIController.php    # AI 相关
│   │   ├── AuthController.php  # 用户相关
│   │   └── CodeController.php  # CodeRunner 相关
│   ├── init.php
│   ├── middleware/
│   │   └── AuthMiddleware.php  # 用户认证中间件
│   └── routes.php              # 路由配置
├── composer.json
├── composer.lock
├── public/
│   └── index.php               # 入口文件
├── README.md
├── storage/
└── vendor/                     # Composer 依赖
```
