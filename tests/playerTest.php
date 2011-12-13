<?php
/**
 * PHPUnit Player Tests
 *
 * @link https://github.com/stjohnjohnson/ScrabblerBot
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

date_default_timezone_set('UTC');

/** Scrabbler Move */
require_once 'src/move.php';
/** Scrabbler Player */
require_once 'src/player.php';
/** Scrabbler Lexicon */
require_once 'src/lexicon.php';
/** Scrabbler Board */
require_once 'src/board.php';

/** PHPUnit Test Case */
require_once 'PHPUnit/Framework/TestCase.php';

use \Scrabbler\Move,
    \Scrabbler\Player,
    \Scrabbler\Lexicon,
    \Scrabbler\Board;

class PlayerTest extends PHPUnit_Framework_TestCase {
  private static $_VALIDWORDS = array('DOGGED','BOSS','GOB','DOGGEDLY','SUBWAY',
      'SUBWAYS','ZVIEW','ZVIEX','OX','WHAT','nope','dog','dead','dread','TAG');
  /**
   * @test
   * @group Player
   * @group Player.Construct
   */
  public function Construct() {
    $lexicon = new Lexicon();
    foreach (self::$_VALIDWORDS as $word) {
      $this->assertTrue($lexicon->addWord($word), $word);
    }

    $player = new Player(array('lexicon' => $lexicon));
    $this->assertEquals('HighestScore', $player->options['method']);

    return $player;
  }
  /**
   * @test
   * @group Player
   * @group Player.ChooseAction
   * @depends Construct
   */
  public function ChooseAction($raw) {
    $player = clone $raw;

    // No moves, trade
    $move = $player->chooseAction(array());
    $this->assertEquals(Move::fromTrade(), $move);

    // Pick best move
    $board = new Board();
    $moveA = Move::fromString('KITTEN H5', $board);
    $moveB = Move::fromString('DOG H5', $board);
    $move = $player->chooseAction(array($moveA, $moveB));
    $this->assertEquals($moveA, $move);

    return $player;
  }

  /**
   * @test
   * @group Player
   * @group Player.HighestLowestScore
   */
  public function HighestLowestScore() {
    $board = new Board();
    $moveA = Move::fromString('KITTEN H5', $board);
    $moveB = Move::fromString('DOG H5', $board);

    // A > B
    $this->assertEquals($moveA, Player::HighestScore($moveA, $moveB));
    $this->assertGreaterThan($moveB->score, $moveA->score);
    $this->assertEquals($moveB, Player::LowestScore($moveA, $moveB));
    $this->assertLessThan($moveA->score, $moveB->score);

    // B > A
    $moveB = Move::fromString('QUILT H5', $board);
    $this->assertEquals($moveB, Player::HighestScore($moveA, $moveB));
    $this->assertGreaterThan($moveA->score, $moveB->score);
    $this->assertEquals($moveA, Player::LowestScore($moveA, $moveB));
    $this->assertLessThan($moveB->score, $moveA->score);

    // A = B
    $moveB = Move::fromString('KITERS H5', $board);
    $this->assertEquals($moveA, Player::HighestScore($moveA, $moveB));
    $this->assertEquals($moveA->score, $moveB->score);
    $this->assertEquals($moveA, Player::LowestScore($moveA, $moveB));
    $this->assertEquals($moveA->score, $moveB->score);
  }

  /**
   * @test
   * @group Player
   * @group Player.MostWords
   */
  public function MostWords() {
    $board = new Board();
    $moveA = Move::fromString('CAT H5', $board);
    $moveA->words = 2;
    $moveB = Move::fromString('DOG H5', $board);
    $moveB->words = 1;

    // A > B
    $this->assertEquals($moveA, Player::MostWords($moveA, $moveB));
    $this->assertGreaterThan($moveB->words, $moveA->words);

    // B > A
    $moveB = Move::fromString('QUILT H5', $board);
    $moveB->words = 3;
    $this->assertEquals($moveB, Player::MostWords($moveA, $moveB));
    $this->assertGreaterThan($moveA->words, $moveB->words);

    // A = B
    $moveB = Move::fromString('KITERS H5', $board);
    $moveB->words = 2;
    $this->assertEquals($moveA, Player::MostWords($moveA, $moveB));
    $this->assertEquals($moveA->words, $moveB->words);
  }

  /**
   * @test
   * @group Player
   * @group Player.MostLeastLetters
   */
  public function MostLeastLetters() {
    $board = new Board();
    $moveA = Move::fromString('KITTEN H5', $board);
    $moveB = Move::fromString('DOGG(ED) H5', $board);

    // A > B
    $this->assertEquals($moveA, Player::MostLetters($moveA, $moveB));
    $this->assertGreaterThan($moveB->used, $moveA->used);
    $this->assertEquals($moveB, Player::LeastLetters($moveA, $moveB));
    $this->assertLessThan($moveA->used, $moveB->used);

    // B > A
    $moveB = Move::fromString('NUCLEAR H5', $board);
    $this->assertEquals($moveB, Player::MostLetters($moveA, $moveB));
    $this->assertGreaterThan($moveA->used, $moveB->used);
    $this->assertEquals($moveA, Player::LeastLetters($moveA, $moveB));
    $this->assertLessThan($moveB->used, $moveA->used);

    // A = B
    $moveB = Move::fromString('KITERS H5', $board);
    $this->assertEquals($moveA, Player::MostLetters($moveA, $moveB));
    $this->assertEquals($moveA->used, $moveB->used);
    $this->assertEquals($moveA, Player::LeastLetters($moveA, $moveB));
    $this->assertEquals($moveA->used, $moveB->used);
  }

  /**
   * @test
   * @group Player
   * @group Player.LongestShortestWord
   */
  public function LongestShortestWord() {
    $board = new Board();
    $moveA = Move::fromString('KITTEN H5', $board);
    $moveB = Move::fromString('DOG H5', $board);

    // A > B
    $this->assertEquals($moveA, Player::LongestWord($moveA, $moveB));
    $this->assertGreaterThan($moveB->len, $moveA->len);
    $this->assertEquals($moveB, Player::ShortestWord($moveA, $moveB));
    $this->assertLessThan($moveA->len, $moveB->len);

    // B > A
    $moveB = Move::fromString('NUCLEAR H5', $board);
    $this->assertEquals($moveB, Player::LongestWord($moveA, $moveB));
    $this->assertGreaterThan($moveA->len, $moveB->len);
    $this->assertEquals($moveA, Player::ShortestWord($moveA, $moveB));
    $this->assertLessThan($moveB->len, $moveA->len);

    // A = B
    $moveB = Move::fromString('KITERS H5', $board);
    $this->assertEquals($moveA, Player::LongestWord($moveA, $moveB));
    $this->assertEquals($moveA->len, $moveB->len);
    $this->assertEquals($moveA, Player::ShortestWord($moveA, $moveB));
    $this->assertEquals($moveA->len, $moveB->len);
  }
}