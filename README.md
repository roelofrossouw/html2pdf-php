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

## Installing

### Ubuntu (22.04, 24.04 and 26.04)

From the simply-cpp apt repository:

```bash
curl -fsSL https://apt.roelof.co.za/setup.sh | bash
sudo apt -y install html2pdf-php
```

The package is built for the PHP each Ubuntu release ships (8.1, 8.3 and 8.5).
Installing it enables the extension for every SAPI (`phpenmod`) and restarts
php-fpm when it's running; removing it does the reverse. Using Apache's
mod_php instead? Reload Apache once: `sudo systemctl reload apache2`.

Check that PHP has it:

```bash
php --ri html2pdf                       # "html2pdf => enabled" and its version
```

### macOS

Build it against Homebrew's PHP, with the libraries from the simply-cpp tap:

```bash
curl -fsSL https://apt.roelof.co.za/setup.sh | bash    # adds the tap
brew install php simply-cpp-html2pdf
git clone https://github.com/roelofrossouw/html2pdf-php && cd html2pdf-php
bash scripts/install.sh                 # builds and tests it
```

The extension is then `cmake-build-local/html2pdf.so`: load it with
`extension=/full/path/to/html2pdf.so` in your `php.ini` (`php --ini` shows
which one).

## Using it

`SC\Html2Pdf` has four methods:

| | |
|---|---|
| `new SC\Html2Pdf(string $html, array $options = [])` | Lays the HTML out, so the page count is known straight away. |
| `$pdf->output(): string` | The PDF, to send to a browser or store. |
| `$pdf->save(string $filename): void` | Writes the PDF to a file, replacing one of that name. |
| `$pdf->pageCount(): int` | How many pages it has. |

A report with a header, footer, table of contents and a few settings:

```php
$pdf = new SC\Html2Pdf($html, [
    'header'    => '<p>#section# - page #Page# of #ToPage#</p>',
    'footer'    => '<p>Confidential</p>',
    'size'      => 'A4',
    'margins'   => ['top' => 10, 'first_top' => null],  // mm; first_top null: the same as top
    'css'       => 'body { font-family: Roboto, sans-serif }',
    'base_url'  => 'https://www.example.com/',  // where relative images and stylesheets are found
    'toc_page'  => 2,                           // a table of contents on page 2
    'metadata'  => ['Title' => 'Invoice', 'Author' => 'Accounts'],
]);
$pdf->save('/tmp/report.pdf');
```

In a header or footer, `#Page#` and `#ToPage#` become "page 3 of 7", and
`#section#`, `#subsection#` and `#subtitle#` show where the reader is. In the
content, each `<section>` starts a new page and gets a line in the table of
contents; `page-break-before: always` and `page-break-inside: avoid` work as in
print CSS.

### All the options

| Option | | Default |
|---|---|---|
| `size` | The paper: A3, A4, A5, letter or legal, in any case. | A4 |
| `landscape` | Turn the pages sideways. | false |
| `margins` | `top`, `right`, `bottom`, `left` and `first_top` (the first page's top: room for a letterhead) in mm. | 10, 10, 10, 30, 60 |
| `header`, `footer` | HTML above and below the content of every page. | none |
| `css` | A stylesheet for the content, header and footer. | none |
| `base_url` | What relative links, images and stylesheets are found against: a folder, file or URL. | none |
| `media` | What `@media` rules match: print or screen. | print |
| `toc_page` | Put a table of contents on this page; 0 for none. | 0 |
| `toc_title` | The table of contents' title. | Table of Contents |
| `outline` | The reader's bookmarks: the table of contents' entries, else the h1 to h3 headings. | true |
| `metadata` | The document's information: Title, Author, Subject, Keywords, Creator, or your own. | the title from `<title>` |
| `user_password` | The password to open it; readers who use it may only print. | none |
| `owner_password` | The password that allows everything. | none |
| `builtin_fonts` | PDF's own Helvetica, Times and Courier: nothing embedded, smaller files. | false |
| `split_boxes` | Cut boxes across pages as browsers print them, rather than moving one whole. | false |
| `draw_margins` | Outline the margins, header and footer, while you get a layout right. | false |
| `image_resolution` | PNGs written at most this many pixels per inch; 0 keeps them as they are. | 150 |

An option it doesn't know throws, so a typo shows straight away. `man
html2pdf-php` has all of this too, once it's installed, and the wiki's
**[HTML to PDF](https://github.com/roelofrossouw/simply-cpp/wiki/HTML-to-PDF)**
guide shows what the HTML can do.

### When something goes wrong

Every failure throws an `SC\Exception` (an `Exception`), with a message saying
what was wrong:

```php
try {
    $pdf = new SC\Html2Pdf($html, ['size' => $size]);
    $pdf->save($path);
} catch (SC\Exception $error) {
    error_log('html2pdf: ' . $error->getMessage());   // "SC\Html2Pdf: unknown option 'sise'"
}
```

### In your IDE

`stubs/html2pdf.stub.php`, installed as
`/usr/local/share/doc/html2pdf-php/html2pdf.stub.php`, describes it all with
types and documentation. Add that folder to PhpStorm's include path (Settings >
PHP > Include Path) or to Intelephense's `intelephense.environment.includePaths`,
and `SC\Html2Pdf` and its options autocomplete.

## html2pdf-php and sc-phplib

html2pdf-php is the HTML-to-PDF part of simply-cpp's larger `sc` extension
(the `simply-cpp-phplib` package), on its own. Both declare the same
`SC\Html2Pdf`, so code written for one runs with the other, but PHP can load
only one of them: the packages can't be installed together.

## Building

`bash scripts/install.sh` builds, tests and packages it. On Ubuntu it installs
`php-dev` and `php-cli` when they're missing, and installs the extension; on
macOS it builds against Homebrew's PHP and doesn't install.
`-DHTML2PDF_PHP_CONFIG=/path/to/php-config` builds it for another PHP. It needs
[sc-html2pdf](https://github.com/roelofrossouw/simply-cpp-html2pdf). The tests
(`tests/*.php`) run with only this extension loaded.

## Licence

Apache-2.0; litehtml is BSD-3-Clause.
