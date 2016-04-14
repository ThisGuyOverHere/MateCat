<?php

/**
 * @group regression
 * @covers Database::useDb
 * User: dinies
 * Date: 12/04/16
 * Time: 16.49
 */
class UseDbTest extends AbstractTest
{
    protected $reflector;
    protected $property;

    public function setUp()
    {
        parent::setUp();
        $this->reflectedClass = Database::obtain();
        $this->reflector = new ReflectionClass($this->reflectedClass);
        $this->reflectedClass->close();
        $this->property = $this->reflector->getProperty('instance');
        $this->property->setAccessible(true);
        $this->property->setValue($this->reflectedClass, null);
    }

    public function tearDown()
    {
        $this->reflectedClass = Database::obtain("localhost", "unt_matecat_user", "unt_matecat_user", "unittest_matecat_local");
        $this->reflectedClass->close();
        startConnection();
    }

    /**
     * @group regression
     * @covers Database::useDb
     */
    public function test_useDb_check_private_variable(){
        /**
         * @var Database
         */
        $instance_after_reset = $this->reflectedClass->obtain("localhost", "unt_matecat_user", "unt_matecat_user", "unittest_matecat_local");
        $instance_after_reset->useDb("information_schema");
        $database = $this->reflector->getProperty('database');
        $database->setAccessible(true);
        $current_database_value = $database->getValue($instance_after_reset);
        $this->assertEquals("information_schema",$current_database_value);
    }
}