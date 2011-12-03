<?php
/**
 * PHPUnit Lexicon Tests
 *
 * @link https://github.com/stjohnjohnson/ScrabblerBot
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

date_default_timezone_set('UTC');

/** Scrabbler Board */
require_once 'src/board.php';
/** Scrabbler Move */
require_once 'src/move.php';
/** Scrabbler Lexicon */
require_once 'src/lexicon.php';

/** PHPUnit Test Case */
require_once 'PHPUnit/Framework/TestCase.php';

use \Scrabbler\Board,
    \Scrabbler\Move,
    \Scrabbler\Lexicon;

class LexiconTest extends PHPUnit_Framework_TestCase {
  private static $_VALIDWORDS = array('DOGGED','BOSS','GOB','DOGGEDLY','SUBWAY',
      'SUBWAYS','ZVIEW','ZVIEX','OX','WHAT','nope','dog','dead','dread','TAG');
  /**
   * @test
   * @group Lexicon
   * @group Lexicon.AddValidWord
   */
  public function AddValidWord() {
    $lexicon = new Lexicon();
    foreach (self::$_VALIDWORDS as $word) {
      $this->assertTrue($lexicon->addWord($word), $word);
      $this->assertTrue($lexicon->isWord($word), $word);
    }

    return $lexicon;
  }

  /**
   * @test
   * @group Lexicon
   * @group Lexicon.AddInvalidWord
   * @depends AddValidWord
   */
  public function AddInvalidWord($lexicon) {
    $badwords = array(
      'A','I','Q','B','ABCDEFGHIJLMNOPQRSTUVWXYZ'
    );

    foreach ($badwords as $word) {
      $this->assertFalse($lexicon->addWord($word), $word);
      $this->assertFalse($lexicon->isWord($word), $word);
    }

    return $lexicon;
  }

  /**
   * @test
   * @group Lexicon
   * @group Lexicon.FromFile
   */
  public function FromFile() {
    $filename = '/tmp/lexicon.sample.list';

    $this->assertGreaterThan(0, file_put_contents($filename, implode(PHP_EOL, self::$_VALIDWORDS)),
                             'Unable to write to Temporary File: ' . $filename);

    $lexicon = Lexicon::fromFile($filename);
    foreach (self::$_VALIDWORDS as $word) {
      $this->assertTrue($lexicon->isWord($word), $word);
    }

    return $lexicon;
  }

  /**
   * @test
   * @group Lexicon
   * @group Lexicon.NodeAt
   */
  public function NodeAt() {
    $lexicon = new Lexicon();

    // Check empty root
    $this->assertEquals(new stdClass(), $lexicon->nodeAt(''));
    $this->assertFalse($lexicon->nodeAt('A'));

    // Add Sample Words
    $this->assertTrue($lexicon->addWord('AB'));
    $this->assertTrue($lexicon->addWord('AC'));
    $this->assertTrue($lexicon->addWord('ABC'));
    $this->assertTrue($lexicon->addWord('BA'));

    // Check root
    $node = $lexicon->nodeAt('');
    $this->assertFalse(isset($node->final));
    $this->assertTrue(isset($node->A));
    $this->assertTrue(isset($node->B));
    $this->assertFalse(isset($node->C));

    // Check Connection A
    $node = $lexicon->nodeAt('A');
    $this->assertFalse(isset($node->final));
    $this->assertFalse(isset($node->A));
    $this->assertTrue(isset($node->B));
    $this->assertTrue(isset($node->C));

    // Check Connection AB
    $node = $lexicon->nodeAt('AB');
    $this->assertTrue(isset($node->final));
    $this->assertFalse(isset($node->A));
    $this->assertFalse(isset($node->B));
    $this->assertTrue(isset($node->C));

    // Check case-insensitivity
    $this->assertEquals($lexicon->nodeAt('A'), $lexicon->nodeAt('a'));
  }

  /**
   * @test
   * @group Lexicon
   * @group Lexicon.FindWordsEmpty
   * @depends AddValidWord
   */
  public function FindWordsEmpty($lexicon) {
    $board = new Board();
    $moves = $lexicon->findWords($board, str_split('SSUBWA?'));
    // Reduce Moves to Strings + Score
    array_walk($moves, function (&$move) { $move = (string) $move . ' ' . $move->score; });
    sort($moves);

    // Ensure we found all the possible starting words
    $this->assertEquals(array('BoSS 8E 10','BoSS 8F 10','BoSS 8G 10','BoSS 8H 10',
        'BoSS H5 10','BoSS H6 10','BoSS H7 10','BoSS H8 10','SUBWAy 8C 22',
        'SUBWAy 8D 22','SUBWAy 8E 20','SUBWAy 8F 20','SUBWAy 8G 20','SUBWAy 8H 22',
        'SUBWAy H3 22','SUBWAy H4 22','SUBWAy H5 20','SUBWAy H6 20','SUBWAy H7 20',
        'SUBWAy H8 22','SUBWAyS 8B 78','SUBWAyS 8C 74','SUBWAyS 8D 74','SUBWAyS 8E 72',
        'SUBWAyS 8F 74','SUBWAyS 8G 72','SUBWAyS 8H 74','SUBWAyS H2 78','SUBWAyS H3 74',
        'SUBWAyS H4 74','SUBWAyS H5 72','SUBWAyS H6 74','SUBWAyS H7 72','SUBWAyS H8 74'), $moves);
  }

  /**
   * @test
   * @group Lexicon
   * @group Lexicon.FindWordsComplex
   * @depends AddValidWord
   */
  public function FindWordsComplex($lexicon) {
    // Add some basic plays
    $board = new Board();
    $board->play(Move::fromString('DoGGED H7'));
    $board->play(Move::fromString('BoSS 8G'));
    $board->play(Move::fromString('GOB 10H'));

    // Find next set of moves
    $moves = $lexicon->findWords($board, str_split('UVWXYZ?'));

    // Reduce Moves to Strings + Score
    array_walk($moves, function (&$move) { $move = (string) $move . ' ' . $move->score; });
    sort($moves);

    // Ensure we found all the possible next moves
    $this->assertEquals(array('(DoGGED)lY H7 13','(S)U(B)WaY J8 13','ZVi(E)X 11E 55'), $moves);
  }

  /**
   * @test
   * @group Lexicon
   * @group Lexicon.FindWordsSingle
   * @depends AddValidWord
   */
  public function FindWordsSingle($lexicon) {
    // Add a basic play
    $board = new Board();
    $board->play(Move::fromString('SUBWAY A4'));

    // Find next set of moves
    $moves = $lexicon->findWords($board, str_split('SUBWAYZ'));

    // Reduce Moves to Strings + Score
    array_walk($moves, function (&$move) { $move = (string) $move . ' ' . $move->score; });
    sort($moves);

    // Ensure we found all the possible next moves
    $this->assertEquals(array('(S)UBWAY 4A 28','(S)UBWAYS 4A 30','(SUBWAY)S A4 15','SUBWAY 10A 39'), $moves);
  }

  /**
   * @test
   * @group Lexicon
   * @group Lexicon.FindWordsEdge
   * @depends AddValidWord
   */
  public function FindWordsEdge($lexicon) {
    // Add a basic play
    $board = new Board();
    $board->play(Move::fromWord(10, 0, Move::DIR_DOWN, 'DOG'));

    // Find next set of moves
    $moves = $lexicon->findWords($board, str_split('EDARMZG'));

    // Reduce Moves to Strings + Score
    array_walk($moves, function (&$move) { $move = (string) $move . ' ' . $move->score; });
    sort($moves);

    // Ensure we found all the possible next moves
    $this->assertEquals(array('(D)EAD 11A 6','(D)READ 11A 14'), $moves);
  }
}