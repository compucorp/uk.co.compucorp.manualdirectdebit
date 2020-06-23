<?php

use Civi\Test;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * An abstract BaseHeadlessTest class.
 */
abstract class BaseHeadlessTest extends PHPUnit_Framework_TestCase
  implements HeadlessInterface, TransactionalInterface, Test\HookInterface {

  /**
   * Sets up Headless, use stock schema,, install extensions.
   */
  public function setUpHeadless() {
    return Test::headless()
      ->install('uk.co.compucorp.membershipextras')
      ->installMe(__DIR__)
      ->apply();
  }

}
