RESTdeb
=======

RESTdeb is a RESTful interface to debian repositories. You can use it to automate debian package publication. 
The code is written in PHP. It depends on [Silex framework](http://silex-project.org/) and on debian system commands (dpkg-deb and dpkg-scanpackages).

Installation
------------

```bash
apt-get install libapache2-mod-php5 dpkg dpkg-dev
wget http://silex-project.org/get/silex.phar -O /var/www/silex.phar
git clone git://github.com/kerphi/RESTdeb.git /tmp/RESTdeb
mv -f /tmp/RESTdeb/* /var/www/
mv -f /tmp/RESTdeb/.* /var/www/
echo "<?php \$GLOBALS['title'] = 'My debian repository';" > /var/www/config.php
```

TODO:
-----

* Add an Atom feed for packages browsing on the repository
