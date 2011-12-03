<?php
/**
 * PHPUnit Listener (much better design)
 *
 * @link https://github.com/stjohnjohnson/ScrabblerBot
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

/** PHPUnit Test Listener */
require_once 'PHPUnit/Framework/TestListener.php';

class UnitListener implements PHPUnit_Framework_TestListener {
  private $_depth = 0;

  public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
  }

  public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
  }

  public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
  }

  public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
  }

  public function startTest(PHPUnit_Framework_Test $test) {
    echo "\n", str_repeat('  ', $this->_depth), $test->getName(), ' ';
    $this->_depth++;
  }

  public function endTest(PHPUnit_Framework_Test $test, $time) {
    $this->_depth--;
  }

  public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
    $name = $suite->getName();

    // This will match classes
    if (strpos($name, '::') === false && strpos($name, ' ') === false) {
      $name = substr($suite->getName(), 0, -4);
    }
    if ($this->_depth < 3) {
      echo "\n\n";
    } else {
      echo "\n";
    }

    echo str_repeat('  ', $this->_depth), $name;

    $this->_depth++;
  }

  public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
    $this->_depth--;
  }
}