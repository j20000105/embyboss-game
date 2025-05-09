## 依赖
本项目依赖`php` `mysql` `redis`，当前 docker 中已经内置 php 环境以及 redis，mysql 需要自行配置

## 权限
uid=33(www-data) gid=33(www-data) groups=33(www-data) \
因为 nginx 和 php 使用 www-data 这个用户执行，所以需要宿主上的项目也需要使用该用户存储 \
如果当前目录不是 www-data 用户，则需要更改
```
sudo chown www-data:www-data -R ./
```

## 拷贝默认配置
```
cp .env.example .env
```

## 修改配置文件 [.env]

### 修改数据库
以下部分需要修改为可以正常连接到数据库的信息
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monitor
DB_USERNAME=root
DB_PASSWORD=
```

### 修改TG等配置
```
TELEGRAM_BOT_TOKEN=发送通知用的BOT
TELEGRAM_WEBHOOK_URL=https://回调地址/telegram/随机字符串/webhook # 必须使用https，部署完成后可人工访问一下该地址，确认是否联通
TELEGRAM_WEBHOOK_DISGUISE=随机字符串 # 与上面地址中的保持一致，防止被别人猜到发送假消息
TELEGRAM_GROUP_ID=群组ID
TELEGRAM_ADMIN_IDS=管理员TG_ID，多个使用英文逗号分割

COIN_NAME=花瓣 # 金币名称
GAME_FEE_RATE=10 # 手续费率
```

## 部署
进入 docker 专用目录
```
cd docker
```
注意：后续所有的命令均需要在 **docker** 目录下执行

当前默认端口号为 8999，如果需要变更端口号，请修改 `compose.yaml` 文件中的 `ports` 部分

启动容器
```
docker compose up -d
```

## 初始化依赖
只需执行一次
```
docker compose exec -it -w /var/www/html/embyboss-game php composer install --no-dev -vvv
```

## 初始化密钥
只需执行一次
```
docker compose exec -it -w /var/www/html/embyboss-game php php artisan key:generate
```

## 初始化数据库
第一次部署必须执行，后续每次有新的数据库变更时执行
```
docker compose exec -it -w /var/www/html/embyboss-game php php artisan migrate
```
需要选择 Yes 来确认

## 生成缓存
```
docker compose exec -it -w /var/www/html/embyboss-game php php artisan config:cache
docker compose exec -it -w /var/www/html/embyboss-game php php artisan route:cache
docker compose exec -it -w /var/www/html/embyboss-game php php artisan view:cache
```
注意：每次修改 `.env` 文件后，都需要执行上述 `config:cache` 命令，否则修改后的配置不会生效

## 设置 Bot 回调地址
```
docker compose exec -it -w /var/www/html/embyboss-game php php artisan telegram:webhook --setup
```

## 检查部署是否成功
访问 `http://127.0.0.1:8999`，如果返回 `ok` 则表示部署成功
