--TEST--
PEAR_REST->deletePackageREST()
--FILE--
<?php
require dirname(dirname(__FILE__)) . '/setup.php.inc';
require_once 'System.php';
System::mkdir(array('-p', $rdir . '/r/foo'));
System::mkdir(array('-p', $rdir . '/p/foo'));
touch($rdir . '/r/foo/1.0.0.xml');
touch($rdir . '/r/foo/v2.1.0.0.xml');
touch($rdir . '/r/foo/package.1.0.0.xml');
touch($rdir . '/r/foo/deps.1.0.0.txt');
touch($rdir . '/p/foo/info.xml');
// test
$rest->deletePackageREST('Foo');
$phpunit->assertNoErrors('after');
$phpunit->assertFileNotExists($rdir . '/r/foo/1.0.0.xml', '1.0.0.xml');
$phpunit->assertFileNotExists($rdir . '/r/foo/v2.1.0.0.xml', 'v2.1.0.0.xml');
$phpunit->assertFileNotExists($rdir . '/r/foo/package.1.0.0.xml', 'package.1.0.0.xml');
$phpunit->assertFileNotExists($rdir . '/r/foo/deps.1.0.0.txt', 'deps.1.0.0.txt');
$phpunit->assertFileNotExists($rdir . '/p/foo/info.xml', 'info.xml');
$phpunit->assertFileNotExists($rdir . '/r/foo', 'r/foo');
$phpunit->assertFileNotExists($rdir . '/p/foo', 'p/foo');
?>
===DONE===
--CLEAN--
<?php require dirname(dirname(__FILE__)) . '/teardown.php.inc'; ?>
--EXPECT--
===DONE===