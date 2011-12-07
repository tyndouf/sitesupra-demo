<?php

namespace Supra\Tests\Validator;

use Supra\Validator\FilteredInput;

/**
 * Test class for FilteredInput.
 * Generated by PHPUnit on 2011-11-21 at 18:40:42.
 */
class FilteredInputTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @var FilteredInput
	 */
	protected $object;

	protected $post;
	
	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$this->post = array(
			'id' => '4',
			'string' => 'aa',
			'avatar' => null,
			//'name' => '',
			'emails' => array(
				'email@example.org',
				'email2@example.org',
				'email3@example.org',
				'email4@example.org',
				'email5',
			),
			'data' => array(
				'prop1' => 5
			),
			'contains' => array(
				'a',
				'b',
				'a',
				'c',
				'd',
			),
		);
		
		$this->object = new FilteredInput($this->post);
	}

	public function testToArray()
	{
		self::assertEquals($this->post, (array) $this->object);
	}
	
	public function testArrayAccess()
	{
		self::assertEquals($this->post['emails'][0], $this->object['emails'][0]);
		self::assertEquals(isset($this->post['emails'][0]), isset($this->object['emails'][0]));
		// This one fails, but must live with that (#41727)
//		self::assertEquals(isset($this->post['avatar']), isset($this->object['avatar']));
		self::assertEquals(empty($this->post['avatar']), empty($this->object['avatar']));
		self::assertEquals(isset($this->post['avatar'][0]), isset($this->object['avatar'][0]));
	}

	public function testHas()
	{
		self::assertTrue($this->object->has('id'));
		self::assertTrue($this->object->has('avatar'));
		self::assertFalse($this->object->has('name'));
		self::assertFalse($this->object->has('emails'));
	}

	public function testHasChild()
	{
		self::assertFalse($this->object->hasChild('id'));
		self::assertFalse($this->object->hasChild('avatar'));
		self::assertFalse($this->object->hasChild('name'));
		self::assertTrue($this->object->hasChild('data'));
		self::assertTrue($this->object->hasChild('emails'));
	}

	public function testGetNext()
	{
		$emails = $this->object->getChild('emails');
		$emailArray = array();
		
		while ($emails->valid()) {
			$emailArray[] = $emails->getNext();
		}
		
		self::assertEquals(array_values(iterator_to_array($emails)), $emailArray);
	}

	public function testGetChild()
	{
		$emails = $this->object->getChild('emails');
	}
	
	/**
	 * @expectedException \RuntimeException
	 */
	public function testGetChildFailure()
	{
		$emails = $this->object->getChild('avatar');
	}
	/**
	 * @expectedException \OutOfBoundsException
	 */
	public function testGetNextFailure()
	{
		$emails = $this->object->getChild('emails');
		$emailArray = array();
		
		while ($emails->valid()) {
			$emailArray[] = $emails->getNext();
		}
		
		$emails->getNext();
	}
	
	public function testIsInteger()
	{
		self::assertSame(4, $this->object->getValid('id', 'integer'));
	}
	
	/**
	 * @expectedException \Supra\Validator\Exception\ValidationFailure
	 */
	public function testIsNotInteger()
	{
		$this->object->getValid('string', 'integer');
	}
	
	public function testContains()
	{
		$input = $this->object->getChild('contains');
		
		self::assertEquals('a', $input->getNext());
		self::assertEquals('b', $input->getNext());
		self::assertEquals('a', $input->getNext());
		
		self::assertTrue($input->contains('a'));
		
		self::assertEquals('c', $input->getNext());
		
		self::assertFalse($input->contains('A'));
		
		self::assertEquals('d', $input->getNext());
		
		self::assertFalse($input->valid());
	}
	
	public function testEmptyIterator()
	{
		$array = array();
		$empty = new FilteredInput($array);
		
		self::assertFalse($empty->contains(1));
		self::assertFalse($empty->contains(null));
	}

}
