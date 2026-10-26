# html2pdf-php

Turn HTML into a PDF straight from PHP. Give `SC\Html2Pdf` your HTML and you
get a PDF back, with pages broken between lines of text rather than through
them, and headers and footers with page numbers, a table of contents,
bookmarks, working links, your own fonts or a password if you want them.

```php
$pdf = new SC\Html2Pdf('<h1>Invoice</h1><p>Due in <b>30</b> days.</p>');

header('Content-Type: application/pdf');
echo $pdf->output();                    // the PDF as a string
```

It's a PHP extension, so it's quick: there's no browser or separate program
behind it. The HTML and CSS are read and laid out by
[litehtml](https://github.com/litehtml/litehtml), by Yuri Kobets
([tordex](https://github.com/tordex)), and the PDF is written by
[simply-cpp](https://github.com/roelofrossouw/simply-cpp)'s own PDF engine,
written from scratch in C++, as a standards-compliant PDF (ISO 32000-1, PDF
1.7). It's the PHP side of [html2pdf](https://github.com/roelofrossouw/html2pdf),
the command-line program.

## Everything it can do

```php
$pdf = new SC\Html2Pdf($html, [
    'header'    => '<p>#section# - page #Page# of #ToPage#</p>',
    'footer'    => '<p>Confidential</p>',
    'size'      => 'A4',                // A3, A4, A5, letter, legal, in any case
    'landscape' => false,
    'margins'   => ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 30, 'first_top' => 60], // mm; first_top null: as top
    'css'       => 'body { font-family: Roboto, sans-serif }',
    'base_url'  => 'https://www.example.com/',  // where relative images and stylesheets are found
    'toc_page'  => 2,                   // a table of contents on page 2
    'metadata'  => ['Title' => 'Invoice', 'Author' => 'Accounts'],
    'user_password' => 'open',          // and 'owner_password'
    'builtin_fonts' => true,            // PDF's built-in Helvetica, Times, Courier: nothing embedded
]);
$pdf->pageCount();
$pdf->save('/tmp/invoice.pdf');
```

In a header or footer, `#Page#` and `#ToPage#` become "page 3 of 7", and
`#section#`, `#subsection#` and `#subtitle#` show where the reader is. In the
content, each `<section>` starts a new page and gets a line in the table of
contents.

The options are [sc::html2pdf's](https://github.com/roelofrossouw/simply-cpp-html2pdf)
by name (`media`, `toc_title`, `outline`, `split_boxes`, `draw_margins` and
`image_resolution` too). One it doesn't know throws, so a typo shows straight
away. Every failure is an `SC\Exception` (an `Exception`).

The wiki's **[HTML to PDF](https://github.com/roelofrossouw/simply-cpp/wiki/HTML-to-PDF)**
guide shows what the HTML can do: page breaks, headers and footers, the table of
contents, links, fonts and more.

**IDEs:** `stubs/html2pdf.stub.php`, installed as
`/usr/share/doc/html2pdf-php/html2pdf.stub.php`, describes it all with types and
documentation. Add that folder to PhpStorm's include path (Settings > PHP >
Include Path) or to Intelephense's `intelephense.environment.includePaths`, and
`SC\Html2Pdf` and its options autocomplete.

## Installing

On Ubuntu (22.04, 24.04 and 26.04), from the simply-cpp apt repository:

```bash
curl -fsSL https://apt.roelof.co.za/setup.sh | bash
sudo apt -y install html2pdf-php
```

The package is built for the PHP each Ubuntu release ships (8.1, 8.3 and 8.5).
Installing it enables the extension for every SAPI (`phpenmod`) and restarts
php-fpm when it's running; removing it does the reverse.

On macOS, build it from source against Homebrew's PHP (below). It's then in
`cmake-build-local/html2pdf.so`: load it with `extension=/path/to/html2pdf.so`
in your `php.ini`.

## Building

`bash scripts/install.sh` builds, tests and packages it. On Ubuntu it installs
`php-dev` and `php-cli` when they're missing, and installs the extension; on
macOS it builds against Homebrew's PHP and doesn't install.
`-DHTML2PDF_PHP_CONFIG=/path/to/php-config` builds it for another PHP. It needs
[sc-html2pdf](https://github.com/roelofrossouw/simply-cpp-html2pdf).

## Licence

Apache-2.0; litehtml is BSD-3-Clause.
