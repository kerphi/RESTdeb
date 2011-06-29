RESTdeb
=======

RESTdeb is a RESTful interface to debian repositories. You can use it to automate debian package publication. 
The code is written in PHP. It depends on [Silex framework](http://silex-project.org/) and on debian system commands (dpkg-deb and dpkg-scanpackages).

Installation
------------

```bash
apt-get install apache2 libapache2-mod-php5 dpkg dpkg-dev php-pear
wget http://silex-project.org/get/silex.phar -O /var/www/silex.phar
pear channel-discover pear.pxxo.net
pear install pxxo/atomwriter
cd /var/www
git init
git remote add origin git://github.com/kerphi/RESTdeb.git
git pull origin master
echo "<?php \$GLOBALS['title'] = 'My debian repository';" > /var/www/config.php
```

Usage
-----

This example assumes that your HTTP server where is located your debian repository is http://myserver/debian/ and that this server is protected with login and password. mypackage.deb is the package you wish to deploy on this repository.

```bash
cat mypackage.deb | curl -u login:password -X POST --data-binary @- http://myserver/debian/
```
