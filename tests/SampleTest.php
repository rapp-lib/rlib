<?php

class SampleTest extends Test_App
{
    protected function setUp()
    {
    }
    /**
     * @group sample1
     */
    public function testSample1_1()
    {
        report("Test 1_1 OK.");
        $this->assertEquals(1, 1*1);
    }
    /**
     * @group sample1
     */
    public function testSample1_2()
    {
        report("Test 1_2 OK.");
    }
}
