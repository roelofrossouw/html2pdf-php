<?php
// The man page (man/html2pdf-php.7) documents every option the extension takes, and only those.
require __DIR__ . '/check.inc';

$page = file_get_contents(__DIR__ . '/../man/html2pdf-php.7');
check($page !== false, 'the man page is there');
preg_match('/^\.SH OPTIONS\n(.*?)^\.SH /ms', $page, $section);
preg_match_all('/^\.TP\n(.*)$/m', $section[1] ?? '', $labels);
$documented = [];
foreach ($labels[1] as $label) {
    preg_match_all('/\\\\fB(\w+)\\\\fR/', $label, $names);
    foreach ($names[1] as $name) $documented[] = $name;
}

// A value of the right kind for each option the extension knows.
$values = ['size' => 'A4', 'landscape' => false, 'margins' => ['top' => 10], 'header' => '<p>h</p>', 'footer' => '<p>f</p>',
           'css' => 'p {}', 'base_url' => __DIR__ . '/', 'media' => 'print', 'toc_page' => 0, 'toc_title' => 'Contents',
           'outline' => true, 'metadata' => ['Author' => 'test'], 'user_password' => '', 'owner_password' => '',
           'builtin_fonts' => false, 'split_boxes' => false, 'draw_margins' => false, 'image_resolution' => 150];
foreach ($values as $name => $value) {
    check(!throws(fn() => new SC\Html2Pdf('<p>x</p>', [$name => $value])), "the extension takes '$name'");
    check(in_array($name, $documented, true), "'$name' is in the man page");
}
foreach ($documented as $name) check(array_key_exists($name, $values), "'$name', in the man page, is an option");
check(str_contains($page, '.SH METHODS') && str_contains($page, 'pageCount'), 'the methods are there');
