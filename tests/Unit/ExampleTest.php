<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function testFullName()
    {
        $user = new User();
        $user->name = 'John';


        $this->assertEquals('John', $user->getFullName());
    }
}
