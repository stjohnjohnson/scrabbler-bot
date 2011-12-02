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

class ScrabblerTest extends PHPUnit_Framework_TestCase {
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
   * @group Move
   * @group Move.FromWord
   */
  public function Move_FromWord() {
    // Basic Move
    $move = Move::fromWord(1, 2, Move::DIR_ACROSS, '(CA)T');
    $this->assertEquals(1, $move->row);
    $this->assertEquals(2, $move->col);
    $this->assertEquals(Move::DIR_ACROSS, $move->direction);
    $this->assertEquals('CAT', $move->word);
    $this->assertEquals('(CA)T', $move->raw);
    $this->assertEquals(1, $move->words);
    $this->assertEquals(3, $move->len);
    $this->assertEquals(1, $move->used);
    $this->assertEquals(array('T'), $move->tiles);
    $this->assertEquals(0, $move->score);
    $this->assertEquals(array(), $move->multiples);
  }

  /**
   * @test
   * @group Move
   * @group Move.Position
   */
  public function Move_Position() {
    $move = Move::fromWord(1, 2, Move::DIR_ACROSS, 'ABC');
    $this->assertEquals('2C', $move->position());

    $move = Move::fromWord(1, 2, Move::DIR_DOWN, 'ABC');
    $this->assertEquals('C2', $move->position());

    // Check edge
    $move = Move::fromWord(14, 14, Move::DIR_ACROSS, 'ABC');
    $this->assertEquals('15O', $move->position());

    $move = Move::fromWord(14, 14, Move::DIR_DOWN, 'ABC');
    $this->assertEquals('O15', $move->position());

    // Check trade
    $move = Move::fromTrade('ABCDE');
    $this->assertFalse($move->position());
  }

  /**
   * @test
   * @group Move
   * @group Move.ToString
   */
  public function Move_ToString() {
    $move = Move::fromWord(1, 2, Move::DIR_ACROSS, 'ABC');
    $this->assertEquals('ABC 2C', (string)$move);

    $move = Move::fromWord(1, 2, Move::DIR_DOWN, 'C(a)NDY');
    $this->assertEquals('C(a)NDY C2', (string)$move);

    $move = Move::fromTrade('NCH?');
    $this->assertEquals('?CHN --', (string)$move);

    $move = Move::fromTrade('');
    $this->assertEquals('--', (string)$move);
  }

  /**
   * @test
   * @group Move
   * @group Move.FromString
   */
  public function Move_FromString() {
    $move = Move::fromString('ABC 2C');
    $this->assertEquals('ABC 2C', (string)$move);

    $move = Move::fromString('C(a)NDY C2');
    $this->assertEquals('C(a)NDY C2', (string)$move);

    try {
      $move = Move::fromString('C(a)NDY CC');

      $this->fail('Expected Exception');
    } catch (Exception $e) {
      $this->assertEquals('Invalid Placement', $e->getMessage());
    }

    $move = Move::fromString('?CHN --');
    $this->assertEquals('?CHN --', (string)$move);

    $move = Move::fromString('--');
    $this->assertEquals('--', (string)$move);
  }

  /**
   * @test
   * @group Move
   * @group Move.AssignScore
   */
  public function Move_AssignScore() {
    $board = new Board();

    // Starting Move (nothing complex + include bingo)
    $move = Move::fromWord(7, 7, Move::DIR_DOWN, 'COMRADE', $board);
    $this->assertEquals(7, $move->row);
    $this->assertEquals(7, $move->col);
    $this->assertEquals(Move::DIR_DOWN, $move->direction);
    $this->assertEquals('COMRADE', $move->word);
    $this->assertEquals('COMRADE', $move->raw);
    $this->assertEquals(1, $move->words);
    $this->assertEquals(7, $move->len);
    $this->assertEquals(7, $move->used);
    $this->assertEquals(array('A','C','D','E','M','O','R'), $move->tiles);
    $this->assertEquals(76, $move->score);
    $this->assertEquals(array(Board::BONUS_DOUBLE_LETTER, Board::BONUS_DOUBLE_WORD), $move->multiples);

    $board->play($move);

    // Basic move (no additional words)
    $move = Move::fromWord(8, 6, Move::DIR_ACROSS, 'L(O)BSTER', $board);
    $this->assertEquals(8, $move->row);
    $this->assertEquals(6, $move->col);
    $this->assertEquals(Move::DIR_ACROSS, $move->direction);
    $this->assertEquals('LOBSTER', $move->word);
    $this->assertEquals('L(O)BSTER', $move->raw);
    $this->assertEquals(1, $move->words);
    $this->assertEquals(7, $move->len);
    $this->assertEquals(6, $move->used);
    $this->assertEquals(array('B','E','L','R','S','T'), $move->tiles);
    $this->assertEquals(14, $move->score);
    $this->assertEquals(array(Board::BONUS_DOUBLE_LETTER, Board::BONUS_DOUBLE_LETTER, Board::BONUS_DOUBLE_LETTER), $move->multiples);

    $board->play($move);

    // Multiple Words (form 4 words)
    $move = Move::fromWord(9, 10, Move::DIR_ACROSS, 'OREs', $board);
    $this->assertEquals(9, $move->row);
    $this->assertEquals(10, $move->col);
    $this->assertEquals(Move::DIR_ACROSS, $move->direction);
    $this->assertEquals('OREs', $move->word);
    $this->assertEquals('OREs', $move->raw);
    $this->assertEquals(4, $move->words);
    $this->assertEquals(4, $move->len);
    $this->assertEquals(4, $move->used);
    $this->assertEquals(array('?','E','O','R'), $move->tiles);
    $this->assertEquals(9, $move->score);
    $this->assertEquals(array(Board::BONUS_TRIPLE_LETTER), $move->multiples);

    $board->play($move);

    // Multiple Multiples (I know I know - 8 letters is impossible)
    $move = Move::fromWord(14, 7, Move::DIR_ACROSS, 'SPLENDID', $board);
    $this->assertEquals(14, $move->row);
    $this->assertEquals(7, $move->col);
    $this->assertEquals(Move::DIR_ACROSS, $move->direction);
    $this->assertEquals('SPLENDID', $move->word);
    $this->assertEquals('SPLENDID', $move->raw);
    $this->assertEquals(2, $move->words);
    $this->assertEquals(8, $move->len);
    $this->assertEquals(8, $move->used);
    $this->assertEquals(array('D','D','E','I','L','N','P','S'), $move->tiles);
    $this->assertEquals(156, $move->score);
    $this->assertEquals(array(Board::BONUS_DOUBLE_LETTER, Board::BONUS_TRIPLE_WORD, Board::BONUS_TRIPLE_WORD), $move->multiples);

    $board->play($move);
  }

  /**
   * @test
   * @group Board
   * @group Board.Initialized
   */
  public function Board_Initialized() {
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
   * @group Board_SetGet
   * @depends Board_Initialized
   */
  public function Board_SetGet($board) {
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
   * @depends Board_SetGet
   */
  public function Board_Flipped($board) {
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
   * @group Board.Output
   */
  public function Board_Output() {
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
  }

  /**
   * @test
   * @group Board
   * @group Board.Anchors
   */
  public function Board_Anchors() {
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
  public function Board_CrossChecks() {
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
  public function Board_LetterPoints() {
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
  public function Board_WalkPlay() {
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
  }

  /**
   * @test
   * @group Board
   * @group Board.TopDownScore
   */
  public function Board_TopDownScore() {
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

  /**
   * @test
   * @group Lexicon
   * @group Lexicon.AddValidWord
   */
  public function Lexicon_AddValidWord() {
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
   * @depends Lexicon_AddValidWord
   */
  public function Lexicon_AddInvalidWord($lexicon) {
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
  public function Lexicon_FromFile() {
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
  public function Lexicon_NodeAt() {
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
   * @depends Lexicon_AddValidWord
   */
  public function Lexicon_FindWordsEmpty($lexicon) {
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
   * @depends Lexicon_AddValidWord
   */
  public function Lexicon_FindWordsComplex($lexicon) {
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
   * @depends Lexicon_AddValidWord
   */
  public function Lexicon_FindWordsSingle($lexicon) {
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
   * @depends Lexicon_AddValidWord
   */
  public function Lexicon_FindWordsEdge($lexicon) {
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

  /**
   * @test
   * @group Game
   * @group Game.Construct
   */
  public function Game_Construct() {
    // Setup test lexicon
    $filename = '/tmp/lexicon.list';
    $this->assertGreaterThan(0, file_put_contents($filename, implode(PHP_EOL, self::$_VALIDWORDS)),
                             'Unable to write to Temporary File: ' . $filename);

    // Instanciate new Object
    $game = new Game_Mock(array('log' => LOG_ERR, 'lexicon' => $filename));

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
   * @depends Game_Construct
   */
  public function Game_Log($game) {
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
   * @depends Game_Construct
   */
  public function Game_Command($game_raw) {
    // Don't affect the passed object
    $game = clone $game_raw;

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
   * @depends Game_Construct
   */
  public function Game_Trades($game_raw) {
    // Don't affect the passed object
    $game = clone $game_raw;

    // Generate trades
    $moves = $game->possibleTrades('', array('A','B','B'));

    $output = array('--', 'A --', 'B --', 'AB --', 'BB --', 'ABB --');
    $this->assertEquals(6, count($moves));
    foreach ($moves as $move) {
      $this->assertContains((string) $move, $output);
    }
  }
}

class Game_Mock extends Game {
  public function chooseAction(array $moves) {
    if (!empty($moves)) {
      return reset($moves);
    } else {
      return Move::fromTrade('');
    }
  }
}