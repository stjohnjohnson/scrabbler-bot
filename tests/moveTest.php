<?php
/**
 * PHPUnit Move Tests
 *
 * @link https://github.com/stjohnjohnson/ScrabblerBot
 * @author St. John Johnson <st.john.johnson@gmail.com>
 */

date_default_timezone_set('UTC');

/** Scrabbler Board */
require_once 'src/board.php';
/** Scrabbler Move */
require_once 'src/move.php';

/** PHPUnit Test Case */
require_once 'PHPUnit/Framework/TestCase.php';

use \Scrabbler\Board,
    \Scrabbler\Move;

class MoveTest extends PHPUnit_Framework_TestCase {
  /**
   * @test
   * @group Move
   * @group Move.FromWord
   */
  public function FromWord() {
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
  public function Position() {
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
  public function ToString() {
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
  public function FromString() {
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
  public function AssignScore() {
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
}