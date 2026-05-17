<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->member = User::factory()->create([
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_categories_index(): void
    {
        $category = BookCategory::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('categories.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Categories/Index')
            ->has('categories.data')
            ->has('rootCategories')
        );
    }

    public function test_member_can_view_categories_index(): void
    {
        $category = BookCategory::factory()->create();

        $response = $this->actingAs($this->member)
            ->get(route('categories.index'));

        $response->assertOk();
    }

    public function test_guest_cannot_view_categories_index(): void
    {
        $response = $this->get(route('categories.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('categories.store'), [
                'name' => 'Fiction',
                'slug' => 'fiction',
                'description' => 'Fiction books',
                'is_active' => true,
                'sort_order' => 0,
            ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('book_categories', [
            'name' => 'Fiction',
            'slug' => 'fiction',
        ]);
    }

    public function test_admin_can_create_category_with_parent(): void
    {
        $parent = BookCategory::factory()->create(['name' => 'Fiction']);

        $response = $this->actingAs($this->admin)
            ->post(route('categories.store'), [
                'parent_id' => $parent->id,
                'name' => 'Science Fiction',
                'slug' => 'science-fiction',
                'is_active' => true,
                'sort_order' => 0,
            ]);

        $response->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('book_categories', [
            'parent_id' => $parent->id,
            'name' => 'Science Fiction',
        ]);
    }

    public function test_category_slug_is_auto_generated_if_not_provided(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('categories.store'), [
                'name' => 'Science Fiction',
                'is_active' => true,
                'sort_order' => 0,
            ]);

        $response->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('book_categories', [
            'name' => 'Science Fiction',
            'slug' => 'science-fiction',
        ]);
    }

    public function test_member_cannot_create_category(): void
    {
        $response = $this->actingAs($this->member)
            ->post(route('categories.store'), [
                'name' => 'Fiction',
                'is_active' => true,
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_category(): void
    {
        $category = BookCategory::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin)
            ->patch(route('categories.update', $category), [
                'name' => 'New Name',
                'slug' => 'new-name',
                'is_active' => true,
                'sort_order' => 0,
            ]);

        $response->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('book_categories', [
            'id' => $category->id,
            'name' => 'New Name',
        ]);
    }

    public function test_admin_cannot_set_category_as_its_own_parent(): void
    {
        $category = BookCategory::factory()->create();

        $response = $this->actingAs($this->admin)
            ->patch(route('categories.update', $category), [
                'parent_id' => $category->id,
                'name' => $category->name,
                'is_active' => true,
                'sort_order' => 0,
            ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_admin_cannot_set_descendant_as_parent(): void
    {
        $parent = BookCategory::factory()->create();
        $child = BookCategory::factory()->create(['parent_id' => $parent->id]);
        $grandchild = BookCategory::factory()->create(['parent_id' => $child->id]);

        $response = $this->actingAs($this->admin)
            ->patch(route('categories.update', $parent), [
                'parent_id' => $grandchild->id,
                'name' => $parent->name,
                'is_active' => true,
                'sort_order' => 0,
            ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_admin_can_delete_category_without_books(): void
    {
        $category = BookCategory::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('categories.destroy', $category));

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('book_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_admin_cannot_delete_category_with_books(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->create(['book_category_id' => $category->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('categories.destroy', $category));

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('book_categories', [
            'id' => $category->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_cannot_delete_category_with_children(): void
    {
        $parent = BookCategory::factory()->create();
        BookCategory::factory()->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('categories.destroy', $parent));

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('book_categories', [
            'id' => $parent->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_toggle_category_active_status(): void
    {
        $category = BookCategory::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->post(route('categories.toggle-active', $category));

        $response->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('book_categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_reorder_categories(): void
    {
        $category1 = BookCategory::factory()->create(['sort_order' => 0]);
        $category2 = BookCategory::factory()->create(['sort_order' => 1]);

        $response = $this->actingAs($this->admin)
            ->post(route('categories.reorder'), [
                'categories' => [
                    ['id' => $category1->id, 'sort_order' => 1],
                    ['id' => $category2->id, 'sort_order' => 0],
                ],
            ]);

        $response->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('book_categories', [
            'id' => $category1->id,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('book_categories', [
            'id' => $category2->id,
            'sort_order' => 0,
        ]);
    }

    public function test_category_name_must_be_unique_within_same_parent(): void
    {
        $parent = BookCategory::factory()->create();
        BookCategory::factory()->create([
            'parent_id' => $parent->id,
            'name' => 'Fiction',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('categories.store'), [
                'parent_id' => $parent->id,
                'name' => 'Fiction',
                'is_active' => true,
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_category_name_can_be_same_in_different_parents(): void
    {
        $parent1 = BookCategory::factory()->create(['name' => 'Parent 1']);
        $parent2 = BookCategory::factory()->create(['name' => 'Parent 2']);

        BookCategory::factory()->create([
            'parent_id' => $parent1->id,
            'name' => 'Fiction',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('categories.store'), [
                'parent_id' => $parent2->id,
                'name' => 'Fiction',
                'is_active' => true,
                'sort_order' => 0,
            ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHasNoErrors();
    }

    public function test_can_view_category_with_books(): void
    {
        $category = BookCategory::factory()->create();
        Book::factory()->count(3)->create(['book_category_id' => $category->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('categories.show', $category));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Categories/Show')
            ->has('category')
            ->has('category.books')
        );
    }

    public function test_category_search_works(): void
    {
        BookCategory::factory()->create(['name' => 'Science Fiction']);
        BookCategory::factory()->create(['name' => 'Fantasy']);

        $response = $this->actingAs($this->admin)
            ->get(route('categories.index', ['search' => 'Science']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('categories.data', 1)
        );
    }

    public function test_can_filter_categories_by_active_status(): void
    {
        BookCategory::factory()->create(['is_active' => true]);
        BookCategory::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin)
            ->get(route('categories.index', ['active' => '1']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('categories.data', 1)
        );
    }
}
