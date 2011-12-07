<?php
/**
 * PHPUnit Game Tests
 *
 * @link https://github.com/stjohnjohnson/ScrabblerBot
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

date_default_timezone_set('UTC');

/** Scrabbler Move */
require_once 'src/move.php';
/** Scrabbler Game */
require_once 'src/game.php';
/** Scrabbler Board */
require_once 'src/board.php';

/** PHPUnit Test Case */
require_once 'PHPUnit/Framework/TestCase.php';

use \Scrabbler\Move,
    \Scrabbler\Game,
    \Scrabbler\Board;

class GameTest extends PHPUnit_Framework_TestCase {
  private static $_VALIDWORDS = array('DOGGED','BOSS','GOB','DOGGEDLY','SUBWAY',
      'SUBWAYS','ZVIEW','ZVIEX','OX','WHAT','nope','dog','dead','dread','TAG');

  /**
   * Reset Error Log to STDERR
   */
  public function tearDown() {
    ini_set('error_log', '');
  }

  /**
   * @test
   * @group Game
   * @group Game.Construct
   */
  public function Construct() {
    // Setup test lexicon
    $filename = '/tmp/lexicon.list';
    $this->assertGreaterThan(0, file_put_contents($filename, implode(PHP_EOL, self::$_VALIDWORDS)),
                             'Unable to write to Temporary File: ' . $filename);

    // Instanciate new Object
    $game = new Mock(array('log' => LOG_ERR, 'lexicon' => $filename));

    // Check Objects
    $this->assertTrue($game->lexicon->isWord('SUBWAY'));
    $this->assertEquals(Board::BONUS_DOUBLE_WORD, $game->board->getAt(7, 7));
    $this->assertEmpty($game->rack);
    $this->assertContains('?', $game->bag);

    return $game;
  }

  /**
   * @test
   * @group Game
   * @group Game.Log
   * @depends Construct
   */
  public function Log($game) {
    // Setup Log Location
    $filename = '/tmp/phpunit.err';
    file_put_contents($filename, '');
    ini_set('error_log', $filename);

    // Will be in Log
    $game->log(LOG_EMERG, 'WHISKY');
    // Will NOT be in Log
    $game->log(LOG_DEBUG, 'ALPHA');
    // Will be in Log
    $game->log(LOG_CRIT, 'TANGO');
    // Will be in Log
    $game->log(LOG_ERR, 'FOXTROT');

    $contents = file_get_contents($filename);
    $this->assertEquals(1, preg_match('/WHISKY/', $contents));
    $this->assertEquals(0, preg_match('/ALPHA/', $contents));
    $this->assertEquals(1, preg_match('/TANGO/', $contents));
    $this->assertEquals(1, preg_match('/FOXTROT/', $contents));
  }

  /**
   * @test
   * @group Game
   * @group Game.Command
   * @depends Construct
   */
  public function Command($raw) {
    // Don't affect the passed object
    $game = clone $raw;

    // Test First Move
    $move = $game->executeCommand('ABCDEFG:');

    // Validate Move (no possible words)
    $this->assertTrue($move->is_trade);
    // Validate Rack
    $this->assertEquals('ABCDEFG', implode('', $game->rack));
    // Validate Bag (-1 A, -1C)
    $this->assertEquals(8, count(array_keys($game->bag, 'A')));
    $this->assertEquals(1, count(array_keys($game->bag, 'C')));
    // Validate Scores
    $this->assertEquals(0, $game->score_mine);
    $this->assertEquals(0, $game->score_opp);

    // Try another move with no letters and opp move
    $move = $game->executeCommand(':CAT H6');

    // Validate Move (one possible word)
    $this->assertFalse($move->is_trade);
    $this->assertEquals('(T)AG', $move->raw);
    $this->assertEquals('(T)AG 8H', (string)$move);
    $this->assertEquals(4, $move->score);

    // Validate Rack
    $this->assertEquals('BCDEF', implode('', $game->rack));
    // Validate Scores
    $this->assertEquals(4, $game->score_mine);
    $this->assertEquals(7, $game->score_opp);
    // Validate Bag (-1 A, -1C)
    $this->assertEquals(7, count(array_keys($game->bag, 'A')));
    $this->assertEquals(0, count(array_keys($game->bag, 'C')));
  }

  /**
   * @test
   * @group Game
   * @group Game.Trades
   * @depends Construct
   */
  public function Trades($raw) {
    // Don't affect the passed object
    $game = clone $raw;

    // Generate trades
    $moves = $game->possibleTrades('', array('A','B','B'));

    $output = array('--', 'A --', 'B --', 'AB --', 'BB --', 'ABB --');
    $this->assertEquals(6, count($moves));
    foreach ($moves as $move) {
      $this->assertContains((string) $move, $output);
    }

    // Check Trading
    $this->assertTrue($game->canTrade());
    // Reduce the bag to 14
    $game->bag = array_slice($game->bag, 0, 14);
    $this->assertTrue($game->canTrade());
    // Reduce the bag to 13
    $game->bag = array_slice($game->bag, 0, 13);
    $this->assertFalse($game->canTrade());
  }
}

class Mock extends Game {
  public function chooseAction(array $moves) {
    if (!empty($moves)) {
      return reset($moves);
    } else {
      return Move::fromTrade('');
    }
  }
}