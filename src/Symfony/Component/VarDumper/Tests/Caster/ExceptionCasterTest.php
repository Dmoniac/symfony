<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\VarDumper\Tests\Caster;

use Symfony\Component\VarDumper\Caster\ExceptionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class ExceptionCasterTest extends \PHPUnit_Framework_TestCase
{
    use VarDumperTestTrait;

    private function getTestException()
    {
        return new \Exception('foo');
    }

    protected function tearDown()
    {
        ExceptionCaster::$srcContext = 1;
        ExceptionCaster::$traceArgs = true;
    }

    public function testDefaultSettings()
    {
        $e = $this->getTestException(1);

        $expectedDump = <<<'EODUMP'
Exception {
  #message: "foo"
  #code: 0
  #file: "%sExceptionCasterTest.php"
  #line: 25
  -trace: {
    %sExceptionCasterTest.php:25: {
      24: {
      25:     return new \Exception('foo');
      26: }
    }
    %sExceptionCasterTest.php:%d: {
      %d: {
      %d:     $e = $this->getTestException(1);
      %d: 
      args: {
        1
      }
    }
%A
EODUMP;

        $this->assertDumpMatchesFormat($expectedDump, $e);
    }

    public function testSeek()
    {
        $e = $this->getTestException(2);

        $expectedDump = <<<'EODUMP'
{
  %sExceptionCasterTest.php:25: {
    24: {
    25:     return new \Exception('foo');
    26: }
  }
  %sExceptionCasterTest.php:%d: {
    %d: {
    %d:     $e = $this->getTestException(2);
    %d: 
    args: {
      2
    }
  }
%A
EODUMP;

        $this->assertStringMatchesFormat($expectedDump, $this->getDump($e, 'trace'));
    }

    public function testNoArgs()
    {
        $e = $this->getTestException(1);
        ExceptionCaster::$traceArgs = false;

        $expectedDump = <<<'EODUMP'
Exception {
  #message: "foo"
  #code: 0
  #file: "%sExceptionCasterTest.php"
  #line: 25
  -trace: {
    %sExceptionCasterTest.php:25: {
      24: {
      25:     return new \Exception('foo');
      26: }
    }
    %sExceptionCasterTest.php:%d: {
      %d: {
      %d:     $e = $this->getTestException(1);
      %d:     ExceptionCaster::$traceArgs = false;
    }
%A
EODUMP;

        $this->assertDumpMatchesFormat($expectedDump, $e);
    }

    public function testNoSrcContext()
    {
        $e = $this->getTestException(1);
        ExceptionCaster::$srcContext = -1;

        $expectedDump = <<<'EODUMP'
Exception {
  #message: "foo"
  #code: 0
  #file: "%sExceptionCasterTest.php"
  #line: 25
  -trace: {
    %sExceptionCasterTest.php: 25
    %sExceptionCasterTest.php: %d
%A
EODUMP;

        $this->assertDumpMatchesFormat($expectedDump, $e);
    }

    public function testHtmlDump()
    {
        $e = $this->getTestException(1);
        ExceptionCaster::$srcContext = -1;

        $h = fopen('php://memory', 'r+b');
        $cloner = new VarCloner();
        $cloner->setMaxItems(1);
        $dumper = new HtmlDumper($h);
        $dumper->setDumpHeader('<foo></foo>');
        $dumper->setDumpBoundaries('<bar>', '</bar>');
        $dumper->dump($cloner->cloneVar($e)->withRefHandles(false));
        $dump = stream_get_contents($h, -1, 0);
        fclose($h);

        $expectedDump = <<<'EODUMP'
<foo></foo><bar><span class=sf-dump-note>Exception</span> {<samp>
  #<span class=sf-dump-protected title="Protected property">message</span>: "<span class=sf-dump-str title="3 characters">foo</span>"
  #<span class=sf-dump-protected title="Protected property">code</span>: <span class=sf-dump-num>0</span>
  #<span class=sf-dump-protected title="Protected property">file</span>: "<span class=sf-dump-str title="%d characters">%sExceptionCasterTest.php</span>"
  #<span class=sf-dump-protected title="Protected property">line</span>: <span class=sf-dump-num>25</span>
  -<span class=sf-dump-private title="Private property defined in class:&#10;`Exception`">trace</span>: {<samp>
    <span class=sf-dump-meta title="Stack level %d.">%sExceptionCasterTest.php</span>: <span class=sf-dump-num>25</span>
     &hellip;12
  </samp>}
</samp>}
</bar>
EODUMP;

        $this->assertStringMatchesFormat($expectedDump, $dump);
    }
}
