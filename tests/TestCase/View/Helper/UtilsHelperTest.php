<?php

declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\UtilsHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;

class UtilsHelperTest extends TestCase
{
  protected UtilsHelper $Utils;

  public function setUp(): void
  {
    parent::setUp();
    $view = new View();
    $this->Utils = new UtilsHelper($view);
  }

  public function testFormatCpf(): void
  {
    $this->assertEquals('123.456.789-01', $this->Utils->formatCpf('12345678901'));
    $this->assertEquals('123.456.789-01', $this->Utils->formatCpf('123.456.789-01'));
    $this->assertEquals('123', $this->Utils->formatCpf('123'));
  }

  public function testFormatCEP(): void
  {
    $this->assertEquals('12.345-678', $this->Utils->formatCEP('12345678'));
    $this->assertEquals('12.345-678', $this->Utils->formatCEP('12.345-678'));
    $this->assertEquals('123', $this->Utils->formatCEP('123'));
  }

  public function testMonthName(): void
  {
    $this->assertEquals('Janeiro', $this->Utils->monthName(1));
    $this->assertEquals('Dezembro', $this->Utils->monthName(12));
    $this->assertEquals('Maio', $this->Utils->monthName('05'));
    $this->assertEquals('', $this->Utils->monthName(13));
  }
}
