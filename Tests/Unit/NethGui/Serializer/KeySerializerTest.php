<?php
/**
 * @package Tests
 * @subpackage Unit
 */

/**
 * Test class for NethGui_Core_KeySerializer.
 * Generated by PHPUnit on 2011-03-24 at 15:57:08.
 *
 * @package Tests
 * @subpackage Unit
 */
class NethGui_Core_KeySerializerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var NethGui_Core_KeySerializer
     */
    protected $object;

    protected function setUp()
    {
        $this->database = $this->getMockBuilder('NethGui_Core_ConfigurationDatabase')
                ->disableOriginalConstructor()
                ->getMock();

        $this->object = new NethGui_Serializer_KeySerializer($this->database, 'TestKey');
    }

    public function testRead()
    {
        $this->database->expects($this->once())
            ->method('getType')
            ->with('TestKey')
            ->will($this->returnValue('VALUE'));

        $this->assertEquals('VALUE', $this->object->read());
    }

    public function testWriteValue()
    {
        $this->database->expects($this->once())
            ->method('setType')
            ->with('TestKey', 'VALUE')
            ->will($this->returnValue(TRUE));

        $this->object->write('VALUE');
    }

    public function testWriteDelete()
    {
        $this->database->expects($this->once())
            ->method('deleteKey')
            ->with('TestKey')
            ->will($this->returnValue(TRUE));

        $this->object->write(NULL);
    }

}

?>