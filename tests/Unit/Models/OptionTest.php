<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Option;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_option_can_be_created()
    {
        $option = Option::create([
            'option_key' => 'test_option',
            'option_value' => 'test_value',
            'option_type' => 'string',
            'description' => 'Test option description',
        ]);

        $this->assertInstanceOf(Option::class, $option);
        $this->assertEquals('test_option', $option->option_key);
        $this->assertEquals('test_value', $option->option_value);
    }

    public function test_value_attribute_with_string_type()
    {
        $option = Option::create([
            'option_key' => 'string_option',
            'option_value' => 'test_string',
            'option_type' => 'string',
            'description' => 'String option',
        ]);

        $this->assertEquals('test_string', $option->value);
    }

    public function test_value_attribute_with_boolean_type()
    {
        $option = Option::create([
            'option_key' => 'boolean_option',
            'option_value' => 'true',
            'option_type' => 'boolean',
            'description' => 'Boolean option',
        ]);

        $this->assertTrue($option->value);
        $this->assertIsBool($option->value);
    }

    public function test_value_attribute_with_integer_type()
    {
        $option = Option::create([
            'option_key' => 'integer_option',
            'option_value' => '123',
            'option_type' => 'integer',
            'description' => 'Integer option',
        ]);

        $this->assertEquals(123, $option->value);
        $this->assertIsInt($option->value);
    }

    public function test_value_attribute_with_float_type()
    {
        $option = Option::create([
            'option_key' => 'float_option',
            'option_value' => '123.45',
            'option_type' => 'float',
            'description' => 'Float option',
        ]);

        $this->assertEquals(123.45, $option->value);
        $this->assertIsFloat($option->value);
    }

    public function test_value_attribute_with_json_type()
    {
        $jsonData = ['key1' => 'value1', 'key2' => 'value2'];
        $option = Option::create([
            'option_key' => 'json_option',
            'option_value' => json_encode($jsonData),
            'option_type' => 'json',
            'description' => 'JSON option',
        ]);

        $this->assertEquals($jsonData, $option->value);
        $this->assertIsArray($option->value);
    }

    public function test_set_value_attribute_with_boolean()
    {
        $option = new Option([
            'option_key' => 'test_boolean',
            'option_type' => 'boolean',
        ]);

        $option->value = true;
        $this->assertEquals('true', $option->option_value);

        $option->value = false;
        $this->assertEquals('false', $option->option_value);
    }

    public function test_set_value_attribute_with_json()
    {
        $option = new Option([
            'option_key' => 'test_json',
            'option_type' => 'json',
        ]);

        $jsonData = ['test' => 'data'];
        $option->value = $jsonData;
        $this->assertEquals(json_encode($jsonData), $option->option_value);
    }

    public function test_get_static_method()
    {
        Option::create([
            'option_key' => 'test_get',
            'option_value' => 'test_value',
            'option_type' => 'string',
            'description' => 'Test get option',
        ]);

        $value = Option::get('test_get');
        $this->assertEquals('test_value', $value);

        $defaultValue = Option::get('non_existent', 'default');
        $this->assertEquals('default', $defaultValue);
    }

    public function test_set_static_method()
    {
        $option = Option::set('test_set', 'test_value', 'string', 'Test set option');

        $this->assertInstanceOf(Option::class, $option);
        $this->assertEquals('test_set', $option->option_key);
        $this->assertEquals('test_value', $option->option_value);
        $this->assertEquals('string', $option->option_type);
        $this->assertEquals('Test set option', $option->description);
    }

    public function test_set_static_method_updates_existing()
    {
        // Create initial option
        Option::set('test_update', 'initial_value', 'string', 'Initial description');

        // Update the option
        $option = Option::set('test_update', 'updated_value', 'string', 'Updated description');

        $this->assertEquals('updated_value', $option->option_value);
        $this->assertEquals('Updated description', $option->description);
        
        // Verify only one record exists
        $this->assertEquals(1, Option::where('option_key', 'test_update')->count());
    }

    public function test_option_casts()
    {
        $option = Option::create([
            'option_key' => 'test_casts',
            'option_value' => 'test_value',
            'option_type' => 'string',
            'description' => 'Test casts',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $option->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $option->updated_at);
    }
}
