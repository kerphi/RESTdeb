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
    $debpath = '/tmp/'.uniqid().'.deb';
    file_put_contents($debpath, $debbin);
    $output = array();
    $command1 = 'dpkg-deb -f '.$debpath.' 2>&1';
    $o = exec($command1, $output,  $ret1);
    if ($ret1 != 0) {
        return new Response(implode("\n", $output), 415);
    }

    // extract information from the debian deb filename
    $package = exec('dpkg-deb -f '.$debpath.' package', $output,  $ret);
    $version = exec('dpkg-deb -f '.$debpath.' version', $output,  $ret);
    $archi   = exec('dpkg-deb -f '.$debpath.' architecture', $output,  $ret);
    $name    = $package.'-'.$version.'_'.$archi.'.deb';
    rename($debpath, '/tmp/'.$name);
    $debpath = '/tmp/'.$name;

    // sign the package
    $output = array();
    $command12 = 'sudo dpkg-sig --sign builder '.$debpath.' 2>&1';
    $o = exec($command12, $output,  $ret12);
    if ($ret12 != 0) {
        return new Response(implode("\n", $output), 400);
    }

    // add the package to the repository
    $reppath = $GLOBALS['reppath'];
    $output = array();
    $command  = 'cd '.$reppath.' ; sudo reprepro --ask-passphrase -Vb . includedeb '.$GLOBALS['osrelease'].' '.$debpath;
    $o = exec($command, $output,  $ret);
    if ($ret != 0) {
        return new Response(implode("\n", $output), 500);
    }

    $r = new Response("Debian package $name added successfully", 201);
    $r->headers->set('Location', '/debian/'.$name);
    return $r;
}); 

$app->run(); 
