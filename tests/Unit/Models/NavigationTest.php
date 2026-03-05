<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_can_be_created()
    {
        $navigation = Navigation::create([
            'name' => 'Test Navigation',
            'slug' => 'test-navigation',
            'icon' => 'cil-test',
            'parent_id' => null,
            'active' => true,
            'show_in_menu' => true,
        ]);

        $this->assertInstanceOf(Navigation::class, $navigation);
        $this->assertEquals('Test Navigation', $navigation->name);
        $this->assertEquals('test-navigation', $navigation->slug);
    }

    public function test_navigation_has_parent_relationship()
    {
        $parent = Navigation::create([
            'name' => 'Parent Navigation',
            'slug' => 'parent',
            'icon' => 'cil-parent',
            'parent_id' => null,
            'active' => true,
            'show_in_menu' => true,
        ]);

        $child = Navigation::create([
            'name' => 'Child Navigation',
            'slug' => 'child',
            'icon' => 'cil-child',
            'parent_id' => $parent->id,
            'active' => true,
            'show_in_menu' => true,
        ]);

        $this->assertEquals($parent->id, $child->parent->id);
    }

    public function test_navigation_has_children_relationship()
    {
        $parent = Navigation::create([
            'name' => 'Parent Navigation',
            'slug' => 'parent',
            'icon' => 'cil-parent',
            'parent_id' => null,
            'active' => true,
            'show_in_menu' => true,
        ]);

        $child = Navigation::create([
            'name' => 'Child Navigation',
            'slug' => 'child',
            'icon' => 'cil-child',
            'parent_id' => $parent->id,
            'active' => true,
            'show_in_menu' => true,
        ]);

        $this->assertCount(1, $parent->children);
        $this->assertEquals($child->id, $parent->children->first()->id);
    }

    public function test_label_attribute()
    {
        $navigation = Navigation::create([
            'name' => 'Test Navigation',
            'slug' => 'test-navigation',
            'icon' => 'cil-test',
            'parent_id' => null,
            'active' => true,
            'show_in_menu' => true,
        ]);

        $this->assertEquals('Test Navigation', $navigation->label);
    }

    public function test_parent_navigation_attribute()
    {
        $parent = Navigation::create([
            'name' => 'Parent Navigation',
            'slug' => 'parent',
            'icon' => 'cil-parent',
            'parent_id' => null,
            'active' => true,
            'show_in_menu' => true,
        ]);

        $child = Navigation::create([
            'name' => 'Child Navigation',
            'slug' => 'child',
            'icon' => 'cil-child',
            'parent_id' => $parent->id,
            'active' => true,
            'show_in_menu' => true,
        ]);

        $this->assertEquals($parent->id, $child->parent_navigation->id);
    }

    public function test_load_tree_static_method()
    {
        // Create root navigation
        $root = Navigation::create([
            'name' => 'Root Navigation',
            'slug' => 'root',
            'icon' => 'cil-root',
            'parent_id' => null,
            'active' => true,
            'show_in_menu' => true,
        ]);

        // Create child navigation
        $child = Navigation::create([
            'name' => 'Child Navigation',
            'slug' => 'child',
            'icon' => 'cil-child',
            'parent_id' => $root->id,
            'active' => true,
            'show_in_menu' => true,
        ]);

        $tree = Navigation::loadTree();
        
        $this->assertCount(1, $tree);
        $this->assertEquals($root->id, $tree->first()->id);
    }

    public function test_load_tree_with_inactive_items()
    {
        // Create active root
        $activeRoot = Navigation::create([
            'name' => 'Active Root',
            'slug' => 'active-root',
            'icon' => 'cil-active',
            'parent_id' => null,
            'active' => true,
            'show_in_menu' => true,
        ]);

        // Create inactive root
        $inactiveRoot = Navigation::create([
            'name' => 'Inactive Root',
            'slug' => 'inactive-root',
            'icon' => 'cil-inactive',
            'parent_id' => null,
            'active' => false,
            'show_in_menu' => true,
        ]);

        $activeTree = Navigation::loadTree(true);
        $allTree = Navigation::loadTree(false);
        
        $this->assertCount(1, $activeTree);
        $this->assertCount(2, $allTree);
    }

    public function test_navigation_soft_deletes()
    {
        $navigation = Navigation::create([
            'name' => 'Test Navigation',
            'slug' => 'test-navigation',
            'icon' => 'cil-test',
            'parent_id' => null,
            'active' => true,
            'show_in_menu' => true,
        ]);

        $navigation->delete();

        $this->assertSoftDeleted('navigations', ['id' => $navigation->id]);
    }

    public function test_navigation_casts()
    {
        $navigation = Navigation::create([
            'name' => 'Test Navigation',
            'slug' => 'test-navigation',
            'icon' => 'cil-test',
            'parent_id' => null,
            'active' => true,
            'show_in_menu' => true,
        ]);

        $this->assertIsBool($navigation->active);
        $this->assertIsBool($navigation->show_in_menu);
        $this->assertInstanceOf(\Carbon\Carbon::class, $navigation->created_at);
    }
}
