# WordPress Eloquent Models
===========================

The WordPress Eloquent Model component is a comprehensive toolkit providing an ORM and schema builder. It supports MySQL, Postgres, SQL Server and SQLite. It translates WordPress tables into [Eloquent-compatible models](https://laravel.com/docs/7.x/eloquent).

The library is ideal for use with Roots' Bedrock / Sage.

No more need to use the old moldy WP_Query class, we're entering the world of the future by producing readable and reusable code! Additional features are also available for a custom WordPress user experience.

The library ensuring Eloquent compatibility, you can consult the [ORM documentation](https://laravel.com/docs/7.x/eloquent) if you're feeling a bit lost :)

# Table of Contents

 - [Installation](#installation)
 - [Setup](#setup)
 - [Supported Models](#supported-models)
    - [Posts](#posts)
    - [Comments](#comments)
    - [Terms](#terms)
    - [Users](#users)
    - [Options](#options)
    - [Menus](#menus)
 - [Images](#images)
 - [Field Aliases](#field-aliases)
 - [Custom Scopes](#custom-scopes)
 - [Pagination](#pagination)
 - [Meta](#meta)
 - [Querying a Post from a custom field (Meta)](#querying-a-post-from-a-custom-field-meta)
 - [Advanced Custom Fields](#advanced-custom-fields)
 - [Table Creation](#table-creation)
 - [Advanced Queries](#advanced-queries)
 - [Custom Post Types](#custom-post-types)
 - [Custom Models](#custom-models)
    - [Eloquent Model Definition](#eloquent-model-definition)
    - [Queries on Custom Models](#queries-on-custom-models)
 - [Shortcode](#shortcode)
 - [Query Logs](#query-logs)

## Installation

The recommended installation method is [Composer](https://getcomposer.org/).

```
composer require amphibee/wordpress-eloquent-models
```

## Setup

The database connection (via $wpdb) is made on the first call to an Eloquent model.
If you need to retrieve the connection instance, simply run the following code (prefer using `use`):

```php
AmphiBee\Eloquent\Database::instance();
```

## Supported Models

### Posts

```php

use \AmphiBee\Eloquent\Model\Post;

// retrieve the post with ID 1
$post = Post::find(1);

// Available related data
$post->author;
$post->comments;
$post->terms;
$post->tags;
$post->categories;
$post->meta;

```

***Status***

By default, `Post` returns all articles regardless of their status. This can be modified via a [local scope](https://laravel.com/docs/7.x/eloquent#query-scopes) `published` to return only published articles.

```php
Post::published()->get();
```

It's also possible to define the status in question via the [local scope](https://laravel.com/docs/7.x/eloquent#query-scopes#query-scopes) `status`.

```php
Post::status('draft')->get();
```

***Post Types***

By default, `Post` returns all content types. This can be overridden via the [local scope](https://laravel.com/docs/7.x/eloquent#query-scopes#query-scopes) `type`.

```php
Post::type('page')->get();
```

### Comments

```php

use \AmphiBee\Eloquent\Model\Comment;

// retrieve the comment with ID 12345
$comment = Comment::find(12345);

// Available related data
$comment->post;
$comment->author;
$comment->meta

```

### Terms

In this version `Term` is accessible as a model but is only accessible through articles. However, it's sufficient to extend `Term` to apply it to other custom content types.

```php
$post->terms()->where('taxonomy', 'country');
```

### Users

```php

use \AmphiBee\Eloquent\Model\User;

// All users
$users = User::get();

// retrieve the user with ID 123
$user = User::find(123);

```

### Options

In WordPress, option retrieval is done with the `get_option` function. With Eloquent, to avoid unnecessary loading of the WordPress Core, you can use the `get` function of the `Option` model.

```php
$siteUrl = Option::get('siteurl');
```

You can also add other options:

```php
Option::add('foo', 'bar'); // stored as a string
Option::add('baz', ['one' => 'two']); // the array will be serialized
```

You can retrieve all options as an array (be careful with performance...):

```php
$options = Option::asArray();
echo $options['siteurl'];
```

You can also specify specific options to retrieve:

```php
$options = Option::asArray(['siteurl', 'home', 'blogname']);
echo $options['home'];
```

### Menus

To retrieve a menu from its slug, use the syntax below. Menu items will be returned in an `items` variable (it's a collection of `AmphiBee\Eloquent\Model\MenuItem` type objects).

Currently supported menu types are: Pages, Posts, Custom Links and Categories.

Once you have the `MenuItem` model, if you want to use the original instance (such as Page or Term, for example), just call the `MenuItem::instance()` method. The `MenuItem` object is just a post whose `post_type` equals `nav_menu_item`:

```php
$menu = Menu::slug('primary')->first();

foreach ($menu->items as $item) {
    echo $item->instance()->title; // if it's a Post
    echo $item->instance()->name; // if it's a Term
    echo $item->instance()->link_text; // if it's a Custom Link
}
```

The `instance()` method will return the corresponding objects:

- `Post` instance for a menu item of type `post`;
- `Page` instance for a menu item of type `page`;
- `CustomLink` instance for a menu item of type `custom`;
- `Term` instance for a menu item of type `category`.

#### Multi-level Menus

To handle multi-level menus, you can iterate to place them at the correct level, for example.

You can use the `MenuItem::parent()` method to retrieve the parent instance of the menu item:

```php
$items = Menu::slug('foo')->first()->items;
$parent = $items->first()->parent(); // Post, Page, CustomLink or Term (category)
```

To group menus by parent, you can use the `->groupBy()` method on the `$menu->items` collection, which will gather items according to their parent (`$item->parent()->ID`).

To learn more about the `groupBy()` method, [consult the Eloquent documentation](https://laravel.com/docs/5.4/collections#method-groupby).

## Field Aliases

The `Post` model supports aliases, so if you inspect a `Post` object you can find aliases in the static `$aliases` array (such as `title` for `post_title` and `content` for `post_content`).

```php
$post = Post::find(1);
$post->title === $post->post_title; // true
```

You can extend the `Post` model to create your own. Just add your aliases in the extended model, it will automatically inherit those defined in the `Post` model:

```php
class A extends \AmphiBee\Eloquent\Model\Post
{
    protected static $aliases = [
        'foo' => 'post_foo',
    ];
}

$a = A::find(1);
echo $a->foo;
echo $a->title; // retrieved from the Post model
```

## Custom Scopes

To order `Post` or `User` type models, you can use the `newest()` and `oldest()` scopes:

```php
$newest = Post::newest()->first();
$oldest = Post::oldest()->first();
```

## Pagination

To paginate results, simply use Eloquent's `paginate()` method:

```php
// Display posts with 5 items per page
$posts = Post::published()->paginate(5);
foreach ($posts as $post) {
    // ...
}
```

To display pagination links, use the `links()` method:

 ```php
 {{ $posts->links() }}
 ```

## Meta

All Eloquent models integrate WordPress metadata management.

Here's an example to retrieve metadata:

```php
// Retrieve a meta (here 'link') from the Post model (we could have used another model like User)
$post = Post::find(31);
echo $post->meta->link; // OR
echo $post->fields->link;
echo $post->link; // OR
```

To create or update a user's metadata, just use the `saveMeta()` or `saveField()` methods. They return a boolean like Eloquent's `save()` method.

```php
$post = Post::find(1);
$post->saveMeta('username', 'amphibee');
```

It's possible to save multiple metadata in a single call:

```php
$post = Post::find(1);
$post->saveMeta([
    'username' => 'amphibee',
    'url' => 'https://amphibee.fr',
]);
```

The library also provides the `createMeta()` and `createField()` methods, which work like the `saveX()` methods, but they are only used for creation and return the `PostMeta` type object created by the instance, instead of a boolean.

```php
$post = Post::find(1);
$postMeta = $post->createMeta('foo', 'bar'); // instance of PostMeta class
$trueOrFalse = $post->saveMeta('foo', 'baz'); // boolean
```

## Querying a Post from a custom field (Meta)

There are different ways to perform a query from metadata (meta) using scopes on a `Post` model (or any other model using the `HasMetaFields` trait):

To check that metadata exists, use the `hasMeta()` scope:
```
// Retrieve the first article that has the "featured_article" meta
$post = Post::published()->hasMeta('featured_article')->first();
```

If you want to target metadata with a specific value, it's possible to use the `hasMeta()` scope with a value.

```php
// Retrieve the first article that has a "username" meta and has the value "amphibee"
$post = Post::published()->hasMeta('username', 'amphibee')->first();
```

It's also possible to perform a query by defining multiple metadata and multiple associated values by passing an array of values to the `hasMeta()` scope:

```php
$post = Post::hasMeta(['username' => 'amphibee'])->first();
$post = Post::hasMeta(['username' => 'amphibee', 'url' => 'amphibee.fr'])->first();
// Or just by providing the metadata keys
$post = Post::hasMeta(['username', 'url'])->first();
```

If you need to match a case-insensitive string or a wildcard match, you can use the `hasMetaLike()` scope with a value. This will use the SQL `LIKE` operator, so it's important to use the wildcard operator '%'.

```php
// Will match: 'B Gosselet', 'B BOSSELET', and 'b gosselet'.
$post = Post::published()->hasMetaLike('author', 'B GOSSELET')->first();

// Using the % operator, the following results will be returned: 'N Leroy', 'N LEROY', 'n leroy', 'Nico Leroy' etc.
$post = Post::published()->hasMetaLike('author', 'N%Leroy')->first();
```

## Images

Retrieving an image from a `Post` or `Page` model.

```php
$post = Post::find(1);

// Retrieve an instance of AmphiBee\Eloquent\Model\Meta\ThumbnailMeta.
print_r($post->thumbnail);

// You must display the image instance to retrieve the original image url
echo $post->thumbnail;
```

To retrieve a specific image size, use the `->size()` method on the object and specify the size alias in the parameter (e.g. `thumbnail` or `medium`). If the thumbnail has been generated, the method returns an object with the metadata, otherwise, the original url is returned (WordPress behavior).

```php
if ($post->thumbnail !== null) {
    /**
     * [
     *     'file' => 'filename-300x300.jpg',
     *     'width' => 300,
     *     'height' => 300,
     *     'mime-type' => 'image/jpeg',
     *     'url' => 'http://localhost/wp-content/uploads/filename-300x300.jpg',
     * ]
     */
    print_r($post->thumbnail->size(AmphiBee\Eloquent\Model\Meta\ThumbnailMeta::SIZE_THUMBNAIL));

    // http://localhost/wp-content/uploads/filename.jpg
    print_r($post->thumbnail->size('invalid_size'));
}
```

## Advanced Custom Fields

The library provides almost all ACF fields (except the Google Map field). It allows retrieving fields optimally without going through the ACF module.

### Basic Usage

To retrieve a field value, it's sufficient to initialize a `Post` type model and invoke the custom field:

```php
$post = Post::find(1);
echo $post->acf->website_url; // returns the url provided in a field with the key website_url
```

### Performance

When using `$post->acf->website_url`, additional queries are executed to retrieve the field according to ACF's approach. It's possible to use a specific method to avoid these additional queries. It's sufficient to specify the custom content type used as a function:

 ```php
 // The method that performs additional queries
 echo $post->acf->author_username; // it's a field relative to User

 // Without additional query
 echo $post->acf->user('author_username');
 
 // Other examples without queries
echo $post->acf->text('text_field_name');
echo $post->acf->boolean('boolean_field_name');
 ```

 > Note: The method must be called in camel case format. For example, for the `date_picker` field type you must write `$post->acf->datePicker('fieldName')`. The library performs the conversion from camel case to snake case for you.

## Table Creation

Documentation coming soon

## Advanced Queries

The library being compatible with Eloquent, you can easily perform complex queries without considering the WordPress context.

For example, to retrieve customers whose age is greater than 40:

```PHP
$users = Capsule::table('customers')->where('age', '>', 40)->get();
```

## Custom Models

### Eloquent Model Definition

To add your own methods to an existing model, you can extend this model. For example, for the `User` model, you could produce the following code:

```php
namespace App\Model;

use \AmphiBee\Eloquent\Model\User as BaseUser;

class User extends BaseUser {

    public function orders() {
        return $this->hasMany('\App\Model\User\Orders');
    }

    public function current() {
        // functionality specific to the current user
    }

    public function favorites() {
        return $this->hasMany('Favorites');
    }

}
```

Another example would be to define a new taxonomy for an article, for example `country`

```php
namespace App\Model;

use \AmphiBee\Eloquent\Model\Post as BasePost;

class Post extends BasePost {

    public function countries() {
        return $this->terms()->where('taxonomy', 'country');
    }

}

Post::with(['categories', 'countries'])->find(1);
```

To access the model of a new content type, here's an example of what could be proposed:

```php
namespace App\Model;

class CustomPostType extends \AmphiBee\Eloquent\Model\Post {
    protected $post_type  = 'custom_post_type';

    public static function getBySlug(string $slug): self
    {
        return self::where('post_name', $slug)->firstOrfail();
    }
}

CustomPostType::with(['categories', 'countries'])->find(1);

```

### Queries on Custom Models

It's also possible to work with custom post types. You can use the `type(string)` method or create your own classes:

```php
// using the type() method
$videos = Post::type('video')->status('publish')->get();

// defining your own class
class Video extends AmphiBee\Eloquent\Model\Post
{
    protected $postType = 'video';
}
$videos = Video::status('publish')->get();
```

Using the `type()` method, the returned object will be of type `AmphiBee\Eloquent\Model\Post`. Using your own model, this allows going further in possibilities by being able to associate custom methods and properties and returning the result as a `Video` object for example.

Custom post type and metadata:

```php
// Retrieve 3 elements of a custom post type and retrieve metadata (address)
$stores = Post::type('store')->status('publish')->take(3)->get();
foreach ($stores as $store) {
    $storeAddress = $store->address; // option 1
    $storeAddress = $store->meta->address; // option 2
    $storeAddress = $store->fields->address; // option 3
}
```

## Shortcode

Implementation in progress

## Query Logs

The connection Capsule being directly linked to `wpdb`, all queries can be viewed in debug tools such as Query Monitor.