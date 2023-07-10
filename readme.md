# PHP Chrome PDF

This package offers an interface for generating PDFs using Chrome.

### Install

```shell
composer require jaxwilko/php-chrome-pdf
```

### Usage

For basic usage, you can use the `make()` helper method:
```php
$pdf = \ChromePdf\ChromePdf::make($content);
// or printing to file
\ChromePdf\ChromePdf::make($content, '/path/to/out.pdf');
```

`ChromePdf` supports multiple input types, these include:
- `url` for rendering a website url (i.e. `https://example.com`)
- `file` for rendering a file
- `text` for rendering text content

```php
use ChromePdf\ChromePdf;

$chromePdf = new ChromePdf();

// Render text content (this is the default option)
$chromePdf->setInput($content);
$chromePdf->setInput($content, ChromePdf::INPUT_TEXT);

// Render a url
$chromePdf->setInput('https://example.com', ChromePdf::INPUT_URL);

// Render a file
$chromePdf->setInput('/path/to/example.html', ChromePdf::INPUT_FILE);

// Print PDF to file
$chromePdf->print(__DIR__ . '/example.pdf');

// Print PDF and get as binary
$pdf = $chromePdf->print();
```

If you need to customise the chrome flags, you can use the following methods:

```php
// Set a single flag
$chromePdf->setFlag('--example-flag');

// Set multiple flags
$chromePdf->setFlag([
    '--example-flag-a',
    '--example-flag-b',
]);

// Replace all flags
$chromePdf->setFlags([
    '--example-flag-a',
    '--example-flag-b',
    '--example-flag-c'
]);

// You can also retrieve flag via
$flags = $chromePdf->getFlags();
```