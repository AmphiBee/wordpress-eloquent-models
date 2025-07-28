# WordPress Eloquent Models
===========================

El componente WordPress Eloquent Model es una caja de herramientas completa que proporciona un ORM y un generador de esquemas. Es compatible con MySQL, Postgres, SQL Server y SQLite. Traduce las tablas de WordPress en [modelos compatibles con Eloquent](https://laravel.com/docs/7.x/eloquent).

La biblioteca es ideal para usar con Bedrock / Sage de Roots.

¡Ya no es necesario usar la vieja clase obsoleta WP_Query, entramos en el mundo del futuro produciendo código legible y reutilizable! También están disponibles funcionalidades adicionales para una experiencia de uso personalizada para WordPress.

La biblioteca asegura compatibilidad con Eloquent, puedes consultar la [documentación del ORM](https://laravel.com/docs/7.x/eloquent) si te sientes un poco perdido :)

# Índice

 - [Instalación](#instalación)
 - [Configuración](#configuración)
 - [Modelos soportados](#modelos-soportados)
    - [Posts](#posts)
    - [Comments](#comments)
    - [Terms](#terms)
    - [Users](#users)
    - [Options](#options)
    - [Menus](#menus)
 - [Imágenes](#imágenes)
 - [Alias de campos](#alias-de-campos)
 - [Scopes personalizados](#scopes-personalizados)
 - [Paginación](#paginación)
 - [Meta](#meta)
 - [Consulta de un Post a partir de un campo personalizado (Meta)](#consulta-de-un-post-a-partir-de-un-campo-personalizado-meta)
 - [Advanced Custom Fields](#advanced-custom-fields)
 - [Creación de tabla](#creación-de-tabla)
 - [Consultas avanzadas](#consultas-avanzadas)
 - [Tipo de contenido personalizado](#tipo-de-contenido-personalizado)
 - [Modelos personalizados](#modelos-personalizados)
    - [Definición del modelo Eloquent](#definición-del-modelo-eloquent)
    - [Consultas en modelos personalizados](#consultas-en-modelos-personalizados)
 - [Shortcode](#shortcode)
 - [Logs de consultas](#logs-de-consultas)

## Instalación

El método de instalación recomendado es [Composer](https://getcomposer.org/).

```
composer require amphibee/wordpress-eloquent-models
```

## Configuración

La conexión a la base de datos (vía $wpdb) se efectúa en la primera llamada de un modelo Eloquent.
Si necesitas recuperar la instancia de conexión, simplemente ejecuta el siguiente código (privilegia el uso de `use`):

```php
AmphiBee\Eloquent\Database::instance();
```

## Modelos soportados

### Posts

```php

use \AmphiBee\Eloquent\Model\Post;

// recuperación del post con ID 1
$post = Post::find(1);

// Datos relacionados disponibles
$post->author;
$post->comments;
$post->terms;
$post->tags;
$post->categories;
$post->meta;

```

***Status***

Por defecto, `Post` retorna el conjunto de artículos sin importar su estado. Esto puede modificarse vía un [scope local](https://laravel.com/docs/7.x/eloquent#query-scopes) `published` para retornar solo los artículos publicados.

```php
Post::published()->get();
```

También es posible definir el estado en cuestión vía el [scope local](https://laravel.com/docs/7.x/eloquent#query-scopes#query-scopes) `status`.

```php
Post::status('draft')->get();
```

***Post Types***

Por defecto, `Post` retorna el conjunto de tipos de contenido. Esto puede sobrescribirse vía el [scope local](https://laravel.com/docs/7.x/eloquent#query-scopes#query-scopes) `type`.

```php
Post::type('page')->get();
```

### Comments

```php

use \AmphiBee\Eloquent\Model\Comment;

// recupera el comentario con ID 12345
$comment = Comment::find(12345);

// Datos relacionados disponibles
$comment->post;
$comment->author;
$comment->meta

```

### Terms

En esta versión `Term` es accesible como modelo pero solo es accesible a través de artículos. Sin embargo, es suficiente extender `Term` para aplicarlo a otros tipos de contenidos personalizados.

```php
$post->terms()->where('taxonomy', 'country');
```

### Users

```php

use \AmphiBee\Eloquent\Model\User;

// Todos los usuarios
$users = User::get();

// recupera el usuario con ID 123
$user = User::find(123);

```

### Options

En WordPress, la recuperación de opciones se efectúa con la función `get_option`. Con Eloquent, para prescindir de una carga innecesaria del Core WordPress, podrás usar la función `get` del modelo `Option`.

```php
$siteUrl = Option::get('siteurl');
```

También puedes agregar otras opciones:

```php
Option::add('foo', 'bar'); // almacenada como cadena de caracteres
Option::add('baz', ['one' => 'two']); // el array será serializado
```

Puedes recuperar el conjunto de opciones como array (cuidado con el rendimiento...):

```php
$options = Option::asArray();
echo $options['siteurl'];
```

También puedes especificar opciones específicas para recuperar:

```php
$options = Option::asArray(['siteurl', 'home', 'blogname']);
echo $options['home'];
```

### Menus

Para recuperar un menú a partir de su alias, usa la sintaxis siguiente. Los elementos del menú serán retornados en una variable `items` (es una colección de objetos de tipo `AmphiBee\Eloquent\Model\MenuItem`).

Los tipos de menú soportados actualmente son: Pages, Posts, Custom Links y Categories.

Una vez que tienes el modelo `MenuItem`, si deseas usar la instancia original (tales como Page o Term, por ejemplo), simplemente llama al método `MenuItem::instance()`. El objeto `MenuItem` es simplemente un post cuyo `post_type` es igual a `nav_menu_item`:

```php
$menu = Menu::slug('primary')->first();

foreach ($menu->items as $item) {
    echo $item->instance()->title; // si es un Post
    echo $item->instance()->name; // si es un Term
    echo $item->instance()->link_text; // si es un Custom Link
}
```

El método `instance()` retornará los objetos correspondientes:

- `Post` instance para un elemento de menú de tipo `post`;
- `Page` instance para un elemento de menú de tipo `page`;
- `CustomLink` instance para un elemento de menú de tipo `custom`;
- `Term` instance para un elemento de menú de tipo `category`.

#### Menús Multi-nivel

Para gestionar menús multi-nivel, puedes efectuar iteraciones para colocarlos en el nivel correcto, por ejemplo.

Puedes usar el método `MenuItem::parent()` para recuperar la instancia padre del elemento del menú:

```php
$items = Menu::slug('foo')->first()->items;
$parent = $items->first()->parent(); // Post, Page, CustomLink o Term (categoria)
```

Para agrupar los menús por padre, puedes usar el método `->groupBy()` en la colección `$menu->items`, que reunirá los elementos según su padre (`$item->parent()->ID`).

Para saber más sobre el método `groupBy()`, [consulta la documentación de Eloquent](https://laravel.com/docs/5.4/collections#method-groupby).

## Alias de campos

El modelo `Post` soporta alias, así que si inspeccionas un objeto `Post` podrás encontrar alias en el array estático `$aliases` (tales como `title` para `post_title` y `content` para `post_content`).

```php
$post = Post::find(1);
$post->title === $post->post_title; // true
```

Puedes extender el modelo `Post` para crear los tuyos propios. Solo agrega tus alias en el modelo extendido, heredará automáticamente los definidos en el modelo `Post`:

```php
class A extends \AmphiBee\Eloquent\Model\Post
{
    protected static $aliases = [
        'foo' => 'post_foo',
    ];
}

$a = A::find(1);
echo $a->foo;
echo $a->title; // recuperado desde el modelo Post
```

## Scopes personalizados

Para ordenar los modelos de tipo `Post` o `User`, puedes usar los scopes `newest()` y `oldest()`:

```php
$newest = Post::newest()->first();
$oldest = Post::oldest()->first();
```

## Paginación

Para paginar los resultados, simplemente usa el método `paginate()` de Eloquent:

```php
// Muestra los posts con 5 elementos por página
$posts = Post::published()->paginate(5);
foreach ($posts as $post) {
    // ...
}
```

Para mostrar los enlaces de paginación, usa el método `links()`:

 ```php
 {{ $posts->links() }}
 ```

## Meta

El conjunto de modelos Eloquent integra una gestión de metadatos de WordPress.

Aquí un ejemplo para recuperar metadatos:

```php
// Recupera un meta (aquí 'link') desde el modelo Post (podríamos haber usado otro modelo como User)
$post = Post::find(31);
echo $post->meta->link; // O
echo $post->fields->link;
echo $post->link; // O
```

Para crear o actualizar los metadatos de un usuario, simplemente usa los métodos `saveMeta()` o `saveField()`. Retornan un booleano al igual que el método `save()` de Eloquent.

```php
$post = Post::find(1);
$post->saveMeta('username', 'amphibee');
```

Es posible guardar varios metadatos en una sola llamada:

```php
$post = Post::find(1);
$post->saveMeta([
    'username' => 'amphibee',
    'url' => 'https://amphibee.fr',
]);
```

La biblioteca también pone a disposición los métodos `createMeta()` y `createField()`, que funcionan como los métodos `saveX()`, pero solo se usan para la creación y retornan el objeto de tipo `PostMeta` creado por la instancia, en lugar de un booleano.

```php
$post = Post::find(1);
$postMeta = $post->createMeta('foo', 'bar'); // instancia de la clase PostMeta
$trueOrFalse = $post->saveMeta('foo', 'baz'); // booleano
```

## Consulta de un Post a partir de un campo personalizado (Meta)

Existen diferentes formas de efectuar una consulta a partir de un metadato (meta) usando scopes en un modelo `Post` (o cualquier otro modelo que use el trait `HasMetaFields`):

Para verificar que un metadato existe, usa el scope `hasMeta()`:
```
// Recupera el primer artículo que tenga el meta "featured_article"
$post = Post::published()->hasMeta('featured_article')->first();
```

Si deseas apuntar a un metadato con un valor específico, es posible usar el scope `hasMeta()` con un valor.

```php
// Recupera el primer artículo que tenga un meta "username" y que tenga como valor "amphibee"
$post = Post::published()->hasMeta('username', 'amphibee')->first();
```

También es posible efectuar una consulta definiendo varios metadatos y varios valores asociados pasando un array de valores al scope `hasMeta()`:

```php
$post = Post::hasMeta(['username' => 'amphibee'])->first();
$post = Post::hasMeta(['username' => 'amphibee', 'url' => 'amphibee.fr'])->first();
// O simplemente proporcionando las claves de metadatos
$post = Post::hasMeta(['username', 'url'])->first();
```

Si necesitas hacer coincidir una cadena de caracteres insensible a mayúsculas o una correspondencia con caracteres comodín, puedes usar el scope `hasMetaLike()` con un valor. Esto usará el operador SQL `LIKE`, por lo que es importante usar el operador comodín '%'.

```php
// Coincidirá con: 'B Gosselet', 'B BOSSELET', y 'b gosselet'.
$post = Post::published()->hasMetaLike('author', 'B GOSSELET')->first();

// Usando el operador %, los siguientes resultados serán retornados: 'N Leroy', 'N LEROY', 'n leroy', 'Nico Leroy' etc.
$post = Post::published()->hasMetaLike('author', 'N%Leroy')->first();
```

## Imágenes

Recuperación de una imagen desde un modelo `Post` o `Page`.

```php
$post = Post::find(1);

// Recupera una instancia de AmphiBee\Eloquent\Model\Meta\ThumbnailMeta.
print_r($post->thumbnail);

// Debes mostrar la instancia de la imagen para recuperar la url de la imagen original
echo $post->thumbnail;
```

Para recuperar un tamaño de imagen específico, usa el método `->size()` en el objeto y especifica el alias de tamaño en el parámetro (ej. `thumbnail` o `medium`). Si la miniatura ha sido generada, el método retorna un objeto con los metadatos, de lo contrario, se devuelve la url original (comportamiento WordPress).

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

La biblioteca pone a disposición la casi totalidad de los campos ACF (a excepción del campo Google Map). Permite recuperar los campos de manera óptima sin pasar por el módulo ACF.

### Uso básico

Para recuperar un valor de un campo, es suficiente inicializar un modelo de tipo `Post` e invocar el campo personalizado:

```php
$post = Post::find(1);
echo $post->acf->website_url; // retorna la url proporcionada en un campo que tiene como clave website_url
```

### Rendimiento

Cuando se usa `$post->acf->website_url`, se ejecutan consultas adicionales para recuperar el campo según el enfoque de ACF. Es posible usar un método específico para evitar estas consultas adicionales. Es suficiente especificar el tipo de contenido personalizado usado como función:

 ```php
 // El método que efectúa consultas adicionales
 echo $post->acf->author_username; // es un campo relativo a User

 // Sin consulta adicional
 echo $post->acf->user('author_username');
 
 // Otros ejemplos sin consultas
echo $post->acf->text('text_field_name');
echo $post->acf->boolean('boolean_field_name');
 ```

 > PS: El método debe ser llamado en formato camel case. Por ejemplo, para el campo de tipo `date_picker` debes escribir `$post->acf->datePicker('fieldName')`. La biblioteca efectúa la conversión de camel case hacia snake case por ti.

## Creación de tabla

Documentación por venir

## Consultas avanzadas

La biblioteca siendo compatible con Eloquent, puedes sin problema efectuar consultas complejas sin tener en cuenta el contexto WordPress.

Por ejemplo, para recuperar los clientes cuya edad es superior a 40 años:

```PHP
$users = Capsule::table('customers')->where('age', '>', 40)->get();
```

## Modelos personalizados

### Definición del modelo Eloquent

Para agregar tus propios métodos a un modelo existente, puedes realizar "extends" de este modelo. Por ejemplo, para el modelo `User`, podrías producir el siguiente código:

```php
namespace App\Model;

use \AmphiBee\Eloquent\Model\User as BaseUser;

class User extends BaseUser {

    public function orders() {
        return $this->hasMany('\App\Model\User\Orders');
    }

    public function current() {
        // funcionalidad específica al usuario actual
    }

    public function favorites() {
        return $this->hasMany('Favorites');
    }

}
```

Otro ejemplo sería definir una nueva taxonomía a un artículo, por ejemplo `country`

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

Para acceder al modelo de un nuevo tipo de contenido, aquí un ejemplo de lo que podría proponerse:

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

### Consultas en modelos personalizados

También es posible trabajar con tipos de contenidos personalizados. Puedes usar el método `type(string)` o crear tus propias clases:

```php
// usando el método type()
$videos = Post::type('video')->status('publish')->get();

// definiendo su propia clase
class Video extends AmphiBee\Eloquent\Model\Post
{
    protected $postType = 'video';
}
$videos = Video::status('publish')->get();
```

Usando el método `type()`, el objeto retornado será de tipo `AmphiBee\Eloquent\Model\Post`. Usando su propio modelo, esto permite ir más lejos en las posibilidades pudiendo asociar métodos y propiedades personalizadas y retornando el resultado como objeto `Video` por ejemplo.

Tipo de contenido personalizado y metadatos:

```php
// Recuperación de 3 elementos de un tipo de contenido personalizado y recuperando un metadato (address)
$stores = Post::type('store')->status('publish')->take(3)->get();
foreach ($stores as $store) {
    $storeAddress = $store->address; // opción 1
    $storeAddress = $store->meta->address; // opción 2
    $storeAddress = $store->fields->address; // opción 3
}
```

## Shortcode

Implementación en curso

## Logs de consultas

La Cápsula de conexión estando directamente vinculada a `wpdb`, el conjunto de consultas podrá visualizarse en herramientas de debug tales como Query Monitor.