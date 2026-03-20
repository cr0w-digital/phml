# </ phml

A lightweight PHP library for building HTML with functions and arrays.

## Installation

```bash
composer require cr0w/phml
```

## Quick start

```php
require 'vendor/autoload.php';

echo \phml\render(
    h('.card#main',
        h('h1', 'Hello, world!'),
        h('p', 'Built with phml.')
    )
);
```

```html
<div class="card" id="main">
  <h1>Hello, world!</h1>
  <p>Built with phml.</p>
</div>
```

## API

### `h(string $selector, mixed ...$args): array`

Builds an HTML node as a plain PHP array. The selector supports Emmet-style shorthand for tag, id, and classes.

```php
h('div')                          // <div></div>
h('p.lead', 'Hello')              // <p class="lead">Hello</p>
h('section.full.dark#hero')       // <section class="full dark" id="hero"></section>
h('.card#main')                   // <div class="card" id="main"></div>  (tag defaults to div)
```

An optional associative array as the first argument sets attributes:

```php
h('a', ['href' => '/about', 'class' => 'nav-link'], 'About')
// <a href="/about" class="nav-link">About</a>
```

Children can be strings, other nodes, arrays of nodes, or `false`/`null` (ignored):

```php
$items = array_map(fn($i) => h('li', $i), ['One', 'Two', 'Three']);

h('ul', $items)
// <ul><li>One</li><li>Two</li><li>Three</li></ul>

h('div', $isAdmin ? h('button', 'Delete') : null)
// <div></div>
```

---

### `render(mixed $node): string`

Serializes a node (or array of nodes) to an HTML string. Text content and attribute values are escaped automatically.

```php
render(h('p', 'Hello'))           // <p>Hello</p>
render(h('br'))                   // <br>  (void tags have no closing tag)
render(null)                      // ''
render(false)                     // ''
render([h('li', 'A'), h('li', 'B')]) // <li>A</li><li>B</li>  (fragment)
```

---

### `c(mixed ...$parts): string`

Composes a class string from mixed inputs. Useful for conditional classes.

```php
c('btn', 'btn-lg')
// 'btn btn-lg'

c('btn', $isLarge && 'btn-lg')
// 'btn' or 'btn btn-lg'

c('btn', ['btn-primary' => $isPrimary, 'btn-disabled' => $isDisabled])
// 'btn btn-primary'

c(['px-4', ['text-lg' => $big]])
// 'px-4' or 'px-4 text-lg'
```

---

### `e(mixed $value): string`

Escapes a value for safe HTML output. Called automatically by `render()` on all text content and attribute values. Use this when interpolating values outside of `render()`.

```php
echo '<title>' . e($pageTitle) . '</title>';
```

---

### `raw(string $html): RawHtml`

Marks a string as trusted HTML, bypassing escaping in `render()`. Use only for content you control.

```php
render(h('div', raw('<strong>trusted</strong>')))
// <div><strong>trusted</strong></div>
```

---

## Attributes

### Boolean attributes

`true` renders the attribute name only. `false` and `null` omit the attribute entirely.

```php
h('input', ['type' => 'checkbox', 'checked' => true, 'disabled' => false])
// <input type="checkbox" checked>
```

### Style array

An array value for `style` is converted to an inline style string. camelCase keys are kebab-cased automatically.

```php
h('div', ['style' => ['marginTop' => '1rem', 'color' => 'red']])
// <div style="margin-top: 1rem; color: red"></div>
```

### Prefix shorthands

Any attribute key ending in `-` with an array value is expanded as a prefix shorthand. This covers `data-`, `aria-`, `hx-` (HTMX), `x-` (Alpine), `v-` (Vue), or any other framework with no special casing required.

```php
h('div', ['data-' => ['userId' => 1, 'role' => 'admin']])
// <div data-user-id="1" data-role="admin"></div>

h('button', ['aria-' => ['label' => 'Close', 'expanded' => 'false']])
// <button aria-label="Close" aria-expanded="false"></button>

h('form', ['hx-' => ['post' => '/submit', 'swap' => 'outerHTML']])
// <form hx-post="/submit" hx-swap="outerHTML"></form>

h('div', ['x-' => ['data' => '{ open: false }', 'show' => 'open']])
// <div x-data="{ open: false }" x-show="open"></div>
```

camelCase subkeys are kebab-cased:

```php
h('div', ['hx-' => ['swapOob' => 'true']])
// <div hx-swap-oob="true"></div>
```

---

## Testing

```bash
composer install
composer test
```