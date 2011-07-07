<?php

require_once __DIR__.'/config.php'; 
require_once __DIR__.'/silex.phar'; 
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application(); 

$app->get('/', function() { 

    $deblist = glob('/var/www/debian/*.deb');

    $xmlWriter = new XMLWriter();
    $xmlWriter->openUri('php://output');
    $xmlWriter->setIndent(true);
    include_once("ATOMWriter.php");
    $f = new ATOMWriter($xmlWriter, true);
    $f->startFeed('urn:restdeb')
        ->writeStartIndex(1)
        ->writeItemsPerPage(10)
        ->writeTotalResults(count($deblist))
        ->writeTitle($GLOBALS['title']);

    foreach($deblist as $deb) {
        $updatedate = filemtime($deb);
        $debname    = basename($deb);
        $f->startEntry("urn:restdeb:".$debname, $updatedate)
            ->writeTitle($debname)
            ->writeLink($debname, 'application/octet-stream')
            ->endEntry();
        $f->flush();
    }

    $f->endFeed();
    $f->flush();  

    $r = new Response('', 200);
    $r->headers->set('Content-Type', 'application/atom+xml; charset=UTF-8');
    return $r; 
}); 

$app->post('/', function() use ($app) { 
    $request = $app['request'];
 
    // get the deb binary
    $debbin = $request->getContent();
    if (!$debbin) {
        return new Response("Input data is empty", 400);
    }
    file_put_contents('/tmp/debbin', $debbin);
    $output = array();
    $command1 = 'dpkg-deb -f /tmp/debbin 2>&1';
    $o = exec($command1, $output,  $ret1);
    if ($ret1 != 0) {
        return new Response(implode("\n", $output), 415);
    }

    // build debian deb filename
    $package = exec('dpkg-deb -f /tmp/debbin package', $output,  $ret);
    $version = exec('dpkg-deb -f /tmp/debbin version', $output,  $ret);
    $archi   = exec('dpkg-deb -f /tmp/debbin architecture', $output,  $ret);
    $name    = $package.'-'.$version.'_'.$archi.'.deb';

    // create the package
    $debpath = '/var/www/debian/'.$name;
    $ret = file_put_contents($debpath, $debbin);
    unlink('/tmp/debbin'); // cleanup
    if ($ret === FALSE) {
        return new Response('Unable to write on '.$debpath, 507);
    }

    // reindex the debian repository
    $output = array();
    $command2  = 'cd /var/www ; /usr/bin/dpkg-scanpackages debian /dev/null > /var/www/debian/Packages';
    $o = exec($command2, $output,  $ret);
    if ($ret != 0) {
        return new Response(implode("\n", $output), 500);
    }

    unlink('/var/www/debian/Packages.gz');
    $command32 = '/bin/gzip /var/www/debian/Packages';
    $output = array();
    $o = exec($command32, $output, $ret);
    if ($ret != 0) {
        return new Response(implode("\n", $output), 500);
    }

    $r = new Response("Debian package $name added successfully", 201);
    $r->headers->set('Location', '/debian/'.$name);
    return $r;
}); 

$app->run(); 
