RESTdeb
=======

RESTdeb is a RESTful interface to debian repositories. You can use it to automate debian package publication. 
The code is written in PHP. It depends on [Silex framework](http://silex-project.org/) and on debian system commands (dpkg-deb and dpkg-scanpackages).

Installation
------------

```bash
apt-get install apache2 libapache2-mod-php5 dpkg dpkg-dev php-pear reprepro dpkg-sig
echo 'suhosin.executor.include.whitelist="phar"' > /etc/php5/apache2/conf.d/restdeb.ini
wget http://silex-project.org/get/silex.phar -O /var/www/silex.phar
pear channel-discover pear.respear.net
pear install respear/atomwriter
cd /var/www
git init
git remote add origin git://github.com/kerphi/RESTdeb.git
git pull origin master
```

You also have to generate a gpg key thati RESTdeb will use to sign packages and repository:
https://wiki.debian.org/SettingUpSignedAptRepositoryWithReprepro

Configuration
-------------

/var/www/config.php example:

```php
<?php
$GLOBALS['title']     = "My debian repository";
$GLOBALS['reppath']   = "/var/www/debian";
$GLOBALS['osrelease'] = "stable";
```

/etc/sudoers.d/restdeb example:

```
www-data    ALL = NOPASSWD: /usr/bin/dpkg-sig
www-data    ALL = NOPASSWD: /usr/bin/reprepro
```

/var/www/debian/conf/distributions example:
```
Origin: debian.mydomain.com
Label: apt mydomain repository
Codename: stable
Architectures: i386 amd64
Components: main
Description: Apt repository for mydomain
DebOverride: override.stable
DscOverride: override.stable
SignWith: 71E3BE51
```

And for example just create a empty /var/www/debian/conf/override.stable file

Usage
-----

This example assumes that your HTTP server where is located your debian repository is http://myserver/debian/ and that this server is protected with login and password. mypackage.deb is the package you wish to deploy on this repository.

```bash
cat mypackage.deb | curl -u login:password -X POST --data-binary @- http://myserver/debian/
```
