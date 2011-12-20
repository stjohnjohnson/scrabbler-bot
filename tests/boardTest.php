<?php
/**
 * PHP Unit Tests
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
/** Scrabbler Game */
require_once 'src/game.php';

/** PHPUnit Test Case */
require_once 'PHPUnit/Framework/TestCase.php';

use \Scrabbler\Board,
    \Scrabbler\Move,
    \Scrabbler\Lexicon,
    \Scrabbler\Game;

class BoardTest extends PHPUnit_Framework_TestCase {
  private static $_VALIDWORDS = array('DOGGED','BOSS','GOB','DOGGEDLY','SUBWAY',
      'SUBWAYS','ZVIEW','ZVIEX','OX','WHAT','nope','dog','dead','dread','TAG');

  /**
   * @test
   * @group Board
   * @group Board.Initialized
   */
  public function Initialized() {
    $board = new Board();

    // Check that center is Double Word and Anchor
    $this->assertTrue($board->isAnchor(7, 7));
    $this->assertEquals(Board::BONUS_DOUBLE_WORD, $board->getAt(7, 7));

    // Check one of each
    $this->assertEquals(Board::BONUS_DOUBLE_WORD, $board->getAt(2, 2));
    $this->assertEquals(Board::BONUS_DOUBLE_LETTER, $board->getAt(0, 3));
    $this->assertEquals(Board::BONUS_TRIPLE_WORD, $board->getAt(7, 0));
    $this->assertEquals(Board::BONUS_TRIPLE_LETTER, $board->getAt(13, 5));

    return $board;
  }

  /**
   * @test
   * @group Board
   * @group Board.SetGet
   * @depends Initialized
   */
  public function SetGet($board) {
    // Set two words - Vertical & Horizontal
    $board->play(Move::fromWord(2, 4, Move::DIR_DOWN, 'CAT'));
    $board->play(Move::fromWord(9, 8, Move::DIR_ACROSS, 'DOG'));

    // Assert they are where they are supposed to be
    $this->assertEquals('C', $board->getAt(2, 4));
    $this->assertEquals('A', $board->getAt(3, 4));
    $this->assertEquals('T', $board->getAt(4, 4));

    $this->assertEquals('D', $board->getAt(9, 8));
    $this->assertEquals('O', $board->getAt(9, 9));
    $this->assertEquals('G', $board->getAt(9, 10));

    $this->assertTrue($board->isUsed(9, 8));
    $this->assertTrue($board->isUsed(9, 9));
    $this->assertTrue($board->isUsed(9, 10));

    // Check Specials
    $this->assertEquals(Board::BONUS_DOUBLE_WORD, $board->getAt(7, 7));
    $this->assertFalse($board->isUsed(7, 7));

    // Check outside bounds
    $this->assertFalse($board->getAt(-5, 0));
    $this->assertFalse($board->getAt(0, -5));
    $this->assertFalse($board->getAt(0, 99));
    $this->assertFalse($board->getAt(99, 0));
    $this->assertFalse($board->isUsed(99, 7));

    return $board;
  }

  /**
   * @test
   * @group Board
   * @group Board.Flipped
   * @depends SetGet
   */
  public function Flipped($board) {
    $board = clone $board;

    // Flip the board
    $board->flip();

    // Check new positions - Horizontal
    $this->assertEquals('C', $board->getAt(4, 2));
    $this->assertEquals('A', $board->getAt(4, 3));
    $this->assertEquals('T', $board->getAt(4, 4));

    // Vertical
    $this->assertEquals('D', $board->getAt(8, 9));
    $this->assertEquals('O', $board->getAt(9, 9));
    $this->assertEquals('G', $board->getAt(10, 9));
  }

  /**
   * @test
   * @group Board
   * @group Board.MoveAt
   * @depends SetGet
   */
  public function MoveAt($board) {
    $board = clone $board;

    // Add a basic word
    $board->play(Move::fromWord(3, 5, Move::DIR_DOWN, 'TOOL'));

    // Check for moves created
    $move = $board->getMoveAt(3, 4, Move::DIR_ACROSS);
    $this->assertEquals('AT', $move->word);

    $move = $board->getMoveAt(4, 4, Move::DIR_ACROSS);
    $this->assertEquals('TO', $move->word);

    $move = $board->getMoveAt(3, 4, Move::DIR_DOWN);
    $this->assertEquals('CAT', $move->word);

    $move = $board->getMoveAt(3, 5, Move::DIR_DOWN);
    $this->assertEquals('TOOL', $move->word);

    // Not used
    $move = $board->getMoveAt(3, 3, Move::DIR_ACROSS);
    $this->assertFalse($move);

    // No word
    $move = $board->getMoveAt(2, 4, Move::DIR_ACROSS);
    $this->assertFalse($move);
  }

  /**
   * @test
   * @group Board
   * @group Board.Output
   */
  public function Output() {
    $board = new Board();

    // Try with empty board
    $expected = <<<EOL
    _ A __ B __ C __ D __ E __ F __ G __ H __ I __ J __ K __ L __ M __ N __ O _
1 : [3W ][   ][   ][2L ][   ][   ][   ][3W ][   ][   ][   ][2L ][   ][   ][3W ]
2 : [   ][2W ][   ][   ][   ][3L ][   ][   ][   ][3L ][   ][   ][   ][2W ][   ]
3 : [   ][   ][2W ][   ][   ][   ][2L ][   ][2L ][   ][   ][   ][2W ][   ][   ]
4 : [2L ][   ][   ][2W ][   ][   ][   ][2L ][   ][   ][   ][2W ][   ][   ][2L ]
5 : [   ][   ][   ][   ][2W ][   ][   ][   ][   ][   ][2W ][   ][   ][   ][   ]
6 : [   ][3L ][   ][   ][   ][3L ][   ][   ][   ][3L ][   ][   ][   ][3L ][   ]
7 : [   ][   ][2L ][   ][   ][   ][2L ][   ][2L ][   ][   ][   ][2L ][   ][   ]
8 : [3W ][   ][   ][2L ][   ][   ][   ][2W^][   ][   ][   ][2L ][   ][   ][3W ]
9 : [   ][   ][2L ][   ][   ][   ][2L ][   ][2L ][   ][   ][   ][2L ][   ][   ]
10: [   ][3L ][   ][   ][   ][3L ][   ][   ][   ][3L ][   ][   ][   ][3L ][   ]
11: [   ][   ][   ][   ][2W ][   ][   ][   ][   ][   ][2W ][   ][   ][   ][   ]
12: [2L ][   ][   ][2W ][   ][   ][   ][2L ][   ][   ][   ][2W ][   ][   ][2L ]
13: [   ][   ][2W ][   ][   ][   ][2L ][   ][2L ][   ][   ][   ][2W ][   ][   ]
14: [   ][2W ][   ][   ][   ][3L ][   ][   ][   ][3L ][   ][   ][   ][2W ][   ]
15: [3W ][   ][   ][2L ][   ][   ][   ][3W ][   ][   ][   ][2L ][   ][   ][3W ]

EOL;
    $this->assertEquals($expected, (string)$board);

    // Add Word
    $board->play(Move::fromString('DOG H8'));

    // Check Anchors and Letters
    $expected = <<<EOL
    _ A __ B __ C __ D __ E __ F __ G __ H __ I __ J __ K __ L __ M __ N __ O _
1 : [3W ][   ][   ][2L ][   ][   ][   ][3W ][   ][   ][   ][2L ][   ][   ][3W ]
2 : [   ][2W ][   ][   ][   ][3L ][   ][   ][   ][3L ][   ][   ][   ][2W ][   ]
3 : [   ][   ][2W ][   ][   ][   ][2L ][   ][2L ][   ][   ][   ][2W ][   ][   ]
4 : [2L ][   ][   ][2W ][   ][   ][   ][2L ][   ][   ][   ][2W ][   ][   ][2L ]
5 : [   ][   ][   ][   ][2W ][   ][   ][   ][   ][   ][2W ][   ][   ][   ][   ]
6 : [   ][3L ][   ][   ][   ][3L ][   ][   ][   ][3L ][   ][   ][   ][3L ][   ]
7 : [   ][   ][2L ][   ][   ][   ][2L ][  ^][2L ][   ][   ][   ][2L ][   ][   ]
8 : [3W ][   ][   ][2L ][   ][   ][  ^][ D ][  ^][   ][   ][2L ][   ][   ][3W ]
9 : [   ][   ][2L ][   ][   ][   ][2L^][ O ][2L^][   ][   ][   ][2L ][   ][   ]
10: [   ][3L ][   ][   ][   ][3L ][  ^][ G ][  ^][3L ][   ][   ][   ][3L ][   ]
11: [   ][   ][   ][   ][2W ][   ][   ][  ^][   ][   ][2W ][   ][   ][   ][   ]
12: [2L ][   ][   ][2W ][   ][   ][   ][2L ][   ][   ][   ][2W ][   ][   ][2L ]
13: [   ][   ][2W ][   ][   ][   ][2L ][   ][2L ][   ][   ][   ][2W ][   ][   ]
14: [   ][2W ][   ][   ][   ][3L ][   ][   ][   ][3L ][   ][   ][   ][2W ][   ]
15: [3W ][   ][   ][2L ][   ][   ][   ][3W ][   ][   ][   ][2L ][   ][   ][3W ]

EOL;
    $this->assertEquals($expected, (string)$board);

    // Check Letters
    $expected = <<<EOL
    _ A __ B __ C __ D __ E __ F __ G __ H __ I __ J __ K __ L __ M __ N __ O _
1 : [   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ]
2 : [   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ]
3 : [   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ]
4 : [   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ]
5 : [   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ]
6 : [   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ]
7 : [   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ]
8 : [   ][   ][   ][   ][   ][   ][   ][ D ][   ][   ][   ][   ][   ][   ][   ]
9 : [   ][   ][   ][   ][   ][   ][   ][ O ][   ][   ][   ][   ][   ][   ][   ]
10: [   ][   ][   ][   ][   ][   ][   ][ G ][   ][   ][   ][   ][   ][   ][   ]
11: [   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ]
12: [   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ]
13: [   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ]
14: [   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ]
15: [   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ][   ]

EOL;
    $this->assertEquals($expected, $board->toString(false));
  }

  /**
   * @test
   * @group Board
   * @group Board.Anchors
   */
  public function Anchors() {
    $board = new Board();

    // Empty board has 7, 7 as an Anchor
    $this->assertTrue($board->isAnchor(7, 7));
    $this->assertFalse($board->isAnchor(7, 8));
    $this->assertFalse($board->isAnchor(7, 6));

    // Play a basic move
    $board->play(Move::fromWord(6, 7, Move::DIR_DOWN, 'DOG'));

    $this->assertFalse($board->isAnchor(6, 7));
    $this->assertFalse($board->isAnchor(7, 7));
    $this->assertFalse($board->isAnchor(8, 7));
    $this->assertTrue($board->isAnchor(5, 7));
    $this->assertTrue($board->isAnchor(6, 8));
    $this->assertTrue($board->isAnchor(6, 6));
    $this->assertTrue($board->isAnchor(7, 8));
    $this->assertTrue($board->isAnchor(7, 6));
    $this->assertTrue($board->isAnchor(8, 8));
    $this->assertTrue($board->isAnchor(8, 6));
    $this->assertTrue($board->isAnchor(9, 7));

    $this->assertFalse($board->isAnchor(-9, -7));
  }

  /**
   * @test
   * @group Board
   * @group Board.CrossChecks
   */
  public function CrossChecks() {
    $board = new Board();
    $lexicon = new Lexicon();
    $lexicon->addWord('BAG');
    $lexicon->addWord('GAB');

    // Add two basic words
    $board->play(Move::fromWord(7, 7, Move::DIR_ACROSS, 'GAB'));
    $board->play(Move::fromWord(9, 7, Move::DIR_ACROSS, 'BAG'));

    // Check letters in between
    $this->assertArrayHasKey('A', $board->getXChecks(8, 7, $lexicon));
    $this->assertEmpty($board->getXChecks(8, 8, $lexicon));
    $this->assertArrayHasKey('A', $board->getXChecks(8, 9, $lexicon));

    // Check caching
    $this->assertEmpty($board->getXChecks(8, 8, $lexicon));

    // Check Non-Anchor
    $this->assertEquals(null, $board->getXChecks(0, 0, $lexicon));
  }

  /**
   * @test
   * @group Board
   * @group Board.LetterPoints
   */
  public function LetterPoints() {
    $board = new Board();

    $this->assertEquals(0, $board->getLetterPoints('?'));
    $this->assertEquals(10, $board->getLetterPoints('Z'));
    $this->assertEquals(0, $board->getLetterPoints('>'));
  }

  /**
   * @test
   * @group Board
   * @group Board.WalkPlay
   */
  public function WalkPlay() {
    $board = new Board();

    // Check walk works
    $this->assertEquals(array(array(1,4),array(2,4),array(3,4)),
                        $board->walk(Move::fromString('ACT E2')));
    $this->assertEquals(array(array(1,4),array(1,5),array(1,6)),
                        $board->walk(Move::fromString('ACT 2E')));
    $this->assertEquals(array(),
                        $board->walk(Move::fromString('ACT --')));

    // Check play works
    $board->play(Move::fromString('PACT E2'));
    $this->assertEquals('P', $board->getAt(1, 4));
    $this->assertEquals('A', $board->getAt(2, 4));
    $this->assertEquals('C', $board->getAt(3, 4));
    $this->assertEquals('T', $board->getAt(4, 4));

    $board->play(Move::fromString('TR(A)CE 3C'));
    $this->assertEquals('T', $board->getAt(2, 2));
    $this->assertEquals('R', $board->getAt(2, 3));
    $this->assertEquals('A', $board->getAt(2, 4));
    $this->assertEquals('C', $board->getAt(2, 5));
    $this->assertEquals('E', $board->getAt(2, 6));

    $board->play(Move::fromString('(T)oP C3'));
    $this->assertEquals('T', $board->getAt(2, 2));
    $this->assertEquals('o', $board->getAt(3, 2));
    $this->assertEquals('P', $board->getAt(4, 2));

    // Check for invalid moves
    try {
      $board->play(Move::fromString('(K)ITE C2'));
      $this->fail('Expected Exception');
    } catch (Exception $e) {
      $this->assertEquals('Letter is incorrect, have: T want: I', $e->getMessage());
    }

    // Ensure letters get removed
    $pool = array('A','B','C','D');
    $board->play(Move::fromString('CAD A1'), $pool);
    $this->assertEquals(array('B'), $pool);

    // During trade as well
    $pool = array('A','B','C','D');
    $board->play(Move::fromString('BC --'), $pool);
    $this->assertEquals(array('A','D'), $pool);
  }

  /**
   * @test
   * @group Board
   * @group Board.ValidMove
   */
  public function ValidMove() {
    $board = new Board();
    $lexicon = new Lexicon();
    foreach (self::$_VALIDWORDS as $word) {
      $this->assertTrue($lexicon->addWord($word), $word);
    }

    $rack = str_split('ABCDEOG');

    // Check some basic failures
    // Not in the rack
    try {
      $board->isValidMove(Move::fromString('CAT H7'), $lexicon, $rack);
      $this->fail('Missed Exception');
    } catch (Exception $e) {
      $this->assertEquals("'T' used in move but not in pool", $e->getMessage());
    }
    try {
      $board->isValidMove(Move::fromString('?A --'), $lexicon, $rack);
      $this->fail('Missed Exception');
    } catch (Exception $e) {
      $this->assertEquals("'?' used in move but not in pool", $e->getMessage());
    }

    // Invalid Word
    try {
      $board->isValidMove(Move::fromString('CADEG H7'), $lexicon, $rack);
      $this->fail('Missed Exception');
    } catch (Exception $e) {
      $this->assertEquals("'CADEG' is not a word in the lexicon", $e->getMessage());
    }

    // Outside the grid
    try {
      $board->isValidMove(Move::fromString('DOG 7O'), $lexicon, $rack);
      $this->fail('Missed Exception');
    } catch (Exception $e) {
      $this->assertEquals("Move extends outside board limits (6,15)", $e->getMessage());
    }

    // Placed Not on an Anchor
    try {
      $board->isValidMove(Move::fromString('DOG G7'), $lexicon, $rack);
      $this->fail('Missed Exception');
    } catch (Exception $e) {
      $this->assertEquals('Move was not placed on an anchor', $e->getMessage());
    }

    // Valid trade
    $this->assertTrue($board->isValidMove(Move::fromString('DOG --'), $lexicon, $rack));

    // Check the first move on the center square
    $move = Move::fromString('DOG H7');
    $this->assertTrue($board->isValidMove($move, $lexicon, $rack));

    // Play move
    $board->play($move);

    // Now try incorrect word
    try {
      $board->isValidMove(Move::fromString('DOG I8'), $lexicon, $rack);
      $this->fail('Missed Exception');
    } catch (Exception $e) {
      $this->assertEquals("'OD' is not a word in the lexicon", $e->getMessage());
    }
  }

  /**
   * @test
   * @group Board
   * @group Board.TopDownScore
   */
  public function TopDownScore() {
    $board = new Board();

    // Add two basic words
    $board->play(Move::fromWord(7, 7, Move::DIR_ACROSS, 'ZIG'));
    $board->play(Move::fromWord(9, 7, Move::DIR_ACROSS, 'BAg'));

    // Check between the words
    $this->assertEquals(13, $board->getTopDownPoints(8, 7));
    $this->assertEquals(2, $board->getTopDownPoints(8, 8));
    $this->assertEquals(2, $board->getTopDownPoints(8, 9));

    // Check for taken words
    $this->assertFalse($board->getTopDownPoints(7, 8));

    // Check for no words
    $this->assertFalse($board->getTopDownPoints(7, 6));
  }
}