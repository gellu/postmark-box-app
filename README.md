# Postmark box app #

RESTful API for creating dynamic mailboxes with [Postmark](http://postmarkapp.com) integration built with [Slim](http://www.github.com/codeguy/Slim)

## Installation ##

1. Launch `php composer.phar install` to install Slim framework
2. Copy `app/config-env-dist.php` to `app/config-env.php`
3. Fill in `app/config-env.php`
4. Create DB according to db-schema.sql

## API Authorization ##

Api authorizes with App Key (tabel: app, colum: app_key) which has to be passed with every request as `GET` parameter `app_key`
Example: `[your_api_uri]?app_key=[your_app_key]`

## Methods ##

**/box/create**

description: Create new conversation between emails (use body template)
method: `POST`
params: `sender` - sender email, `receiver` - receiver email, `body` - email body

**/box/send**

description: Parse and forward Postmark inbound message
method: `POST`
params:
body: Postmark inbound webhook content (json)