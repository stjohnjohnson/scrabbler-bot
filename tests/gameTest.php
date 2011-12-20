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
/** Scrabbler Lexicon */
require_once 'src/lexicon.php';

/** PHPUnit Test Case */
require_once 'PHPUnit/Framework/TestCase.php';

use \Scrabbler\Move,
    \Scrabbler\Game,
    \Scrabbler\Board,
    \Scrabbler\Lexicon;

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
    $game = new Mock_Game_Simple(array('log' => LOG_ERR, 'lexicon' => $filename));

    // Check Objects
    $this->assertTrue($game->lexicon->isWord('SUBWAY'));
    $this->assertEquals(Board::BONUS_DOUBLE_WORD, $game->board->getAt(7, 7));
    $this->assertEmpty($game->rack);
    $this->assertContains('?', $game->bag);

    // Store lexicon
    $lexicon = $game->lexicon;

    // Try making a new Game with that lexicon
    $new = new Mock_Game_Simple(array('lexicon' => $lexicon));
    $this->assertTrue($game->lexicon->isWord('SUBWAY'));

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
    $this->assertEquals(6, count(array_keys($game->bag, 'T')));
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
    // Validate Bag (-1 A, -1C, -1T)
    $this->assertEquals(7, count(array_keys($game->bag, 'A')));
    $this->assertEquals(0, count(array_keys($game->bag, 'C')));
    $this->assertEquals(5, count(array_keys($game->bag, 'T')));

    // Test Last Move
    $move = $game->executeCommand('');
    $this->assertEquals(null, $move);
  }

  /**
   * @test
   * @group Game
   * @group Game.Execute
   * @depends Construct
   */
  public function Execute($raw) {
    $filename = '/tmp/phpunit-stream';
    file_put_contents($filename, implode(PHP_EOL, array('DOGDOGY:--','TZI:--','QQ:--')) . PHP_EOL . PHP_EOL);
    $file = fopen($filename, 'r');

    // Create game
    $game = clone $raw;

    // Run game
    ob_start();
    $game->execute($file);
    $output = explode(PHP_EOL, ob_get_clean());

    // Close file
    fclose($file);

    // Check output
    $this->assertEquals('HELLO', $output[0]);
    $this->assertEquals('DOG H8', $output[1]);
    $this->assertEquals('(D)OG 8H', $output[2]);
    $this->assertEquals('--', $output[3]);
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

  /**
   * @test
   * @group Game
   * @group Game.Simulate
   * @group Game.SimulateTrader
   */
  public function SimulateTrader() {
    // Setup Log Location
    $filename = '/tmp/phpunit.err';
    file_put_contents($filename, '');
    ini_set('error_log', $filename);

    // Create new games
    $game = new Mock_Game_Trader(array(
            'log' => Game::LOG_INFO,
        'lexicon' => new Lexicon()
    ));
    $opponent = new Mock_Game_Trader(array(
        'lexicon' => new Lexicon()
    ));

    // Basic trading match
    $game->simulate($opponent);
    $contents = file_get_contents($filename);
    $this->assertEquals(1, preg_match('/Game Starting/', $contents), $contents);
    $this->assertEquals(1, preg_match('/Game Ending: No More Moves/', $contents), $contents);
    $this->assertEquals(30, count(explode(PHP_EOL, $contents)));
  }

  /**
   * @test
   * @group Game
   * @group Game.Simulate
   * @group Game.SimulateBadMove
   */
  public function SimulateBadMove() {
    // Setup Log Location
    $filename = '/tmp/phpunit.err';
    file_put_contents($filename, '');
    ini_set('error_log', $filename);

    // Create new games
    $game = new Mock_Game_BadMove(array(
            'log' => Game::LOG_INFO,
        'lexicon' => new Lexicon()
    ));
    $opponent = new Mock_Game_Trader(array(
        'lexicon' => new Lexicon()
    ));

    // Basic trading match
    $game->simulate($opponent);
    $contents = file_get_contents($filename);
    $this->assertEquals(1, preg_match('/Game Starting/', $contents), $contents);
    $this->assertEquals(1, preg_match('/Error with Me/', $contents), $contents);
    $this->assertEquals(1, preg_match('/used in move but not in pool/', $contents), $contents);
    $this->assertEquals(25, count(explode(PHP_EOL, $contents)));
  }

  /**
   * @test
   * @group Game
   * @group Game.Simulate
   * @group Game.SimulateBadTrade
   */
  public function SimulateBadTrade() {
    // Setup Log Location
    $filename = '/tmp/phpunit.err';
    file_put_contents($filename, '');
    ini_set('error_log', $filename);

    // Create new games
    $game = new Mock_Game_Trader(array(
            'log' => Game::LOG_INFO,
        'lexicon' => new Lexicon()
    ));
    $opponent = new Mock_Game_BadTrade(array(
        'lexicon' => new Lexicon()
    ));

    // Basic trading match
    $game->simulate($opponent);
    $contents = file_get_contents($filename);
    $this->assertEquals(1, preg_match('/Game Starting/', $contents), $contents);
    $this->assertEquals(1, preg_match('/Error with Opp/', $contents), $contents);
    $this->assertEquals(1, preg_match('/used in move but not in pool/', $contents), $contents);
    $this->assertEquals(26, count(explode(PHP_EOL, $contents)));
  }

  /**
   * @test
   * @group Game
   * @group Game.Simulate
   * @group Game.SimulateSimple
   */
  public function SimulateSimple() {
    // Create Lexicon with all two letter words
    $lexicon = new Lexicon();
    foreach (range('A','Z') as $a) {
      foreach (range('A','Z') as $b) {
        $lexicon->addWord($a.$b);
      }
    }

    // Setup Log Location
    $filename = '/tmp/phpunit.err';
    file_put_contents($filename, '');
    ini_set('error_log', $filename);

    // Create new games
    $game = new Mock_Game_Simple(array(
            'log' => Game::LOG_INFO,
        'lexicon' => $lexicon
    ));
    $opponent = new Mock_Game_Trader(array(
        'lexicon' => $lexicon
    ));

    // Basic match
    $game->simulate($opponent);
    $contents = file_get_contents($filename);
    $this->assertEquals(1, preg_match('/Game Starting/', $contents), $contents);
    $this->assertEquals(1, preg_match('/Game Ending: No More Moves/', $contents), $contents);
    $this->assertGreaterThan(30, count(explode(PHP_EOL, $contents)));
  }

  /**
   * @test
   * @group Game
   * @group Game.Simulate
   * @group Game.SimulateOutOfLetters
   */
  public function SimulateOutOfLetters() {
    // Create Lexicon with all two letter words
    $lexicon = new Lexicon();
    foreach (range('A','Z') as $a) {
      foreach (range('A','Z') as $b) {
        $lexicon->addWord($a.$b);
      }
    }

    // Setup Log Location
    $filename = '/tmp/phpunit.err';
    file_put_contents($filename, '');
    ini_set('error_log', $filename);

    // Create new games
    $game = new Mock_Game_Simple(array(
            'log' => Game::LOG_INFO,
        'lexicon' => $lexicon
    ));
    $opponent = new Mock_Game_Skipper(array(
        'lexicon' => $lexicon
    ));
    $game->bag = array_slice($game->bag, 0, 15);

    // Basic match
    $game->simulate($opponent);
    $contents = file_get_contents($filename);
    $this->assertEquals(1, preg_match('/Game Starting/', $contents), $contents);
    $this->assertEquals(1, preg_match('/Game Ending: No More Moves/', $contents), $contents);
    $this->assertGreaterThan(30, count(explode(PHP_EOL, $contents)));
  }

  /**
   * @test
   * @group Game
   * @group Game.Simulate
   * @group Game.SimulateTradeTooMuch
   */
  public function SimulateTradeTooMuch() {
    // Create Empty Lexicon
    $lexicon = new Lexicon();

    // Setup Log Location
    $filename = '/tmp/phpunit.err';
    file_put_contents($filename, '');
    ini_set('error_log', $filename);

    // Create new games
    $game = new Mock_Game_Trader(array(
            'log' => Game::LOG_INFO,
        'lexicon' => $lexicon
    ));
    $opponent = new Mock_Game_Trader(array(
        'lexicon' => $lexicon
    ));
    $game->bag = array_slice($game->bag, 0, 15);

    // Basic match
    $game->simulate($opponent);
    $contents = file_get_contents($filename);
    $this->assertEquals(1, preg_match('/Game Starting/', $contents), $contents);
    $this->assertEquals(1, preg_match('/ERROR: P0	Unable to Trade while bag contains 1 tiles - need 7+/', $contents), $contents);
    $this->assertEquals(26, count(explode(PHP_EOL, $contents)));
  }
}

class Mock_Game_Simple extends Game {
  public function chooseAction(array $moves) {
    if (!empty($moves)) {
      return reset($moves);
    } else {
      return Move::fromTrade('');
    }
  }
}

class Mock_Game_Trader extends Game {
  public function chooseAction(array $moves) {
    return Move::fromTrade(implode('', array_slice($this->rack, 0, 2)));
  }
}

class Mock_Game_Skipper extends Game {
  public function chooseAction(array $moves) {
    return Move::fromTrade('');
  }
}

class Mock_Game_BadMove extends Game {
  public function chooseAction(array $moves) {
    return Move::fromString('BLAHBLAH A19');
  }
}

class Mock_Game_BadTrade extends Game {
  public function chooseAction(array $moves) {
    return Move::fromTrade('ZZQQ');
  }
}