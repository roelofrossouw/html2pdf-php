// The html2pdf extension: SC\Html2Pdf and SC\Exception, and phpinfo()'s line. The public part
// of sc-phplib's sc extension, with the same names, so code runs with either (one at a time).

#include "php_glue.h"

#include <ext/standard/info.h>

namespace sc::php {
    zend_class_entry *exception_class = nullptr;
}

static PHP_MINIT_FUNCTION(html2pdf) {
    zend_class_entry exception;
    INIT_NS_CLASS_ENTRY(exception, "SC", "Exception", nullptr);
    sc::php::exception_class = zend_register_internal_class_ex(&exception, zend_ce_exception);
    sc::php::html2pdf_area().startup();
    return SUCCESS;
}

static PHP_MINFO_FUNCTION(html2pdf) {
    php_info_print_table_start();
    php_info_print_table_row(2, "html2pdf", "enabled");
    php_info_print_table_row(2, "Version", HTML2PDF_PHP_VERSION);
    php_info_print_table_row(2, "Provides", "SC\\Html2Pdf");
    php_info_print_table_end();
}

static zend_module_entry html2pdf_module_entry = {
    STANDARD_MODULE_HEADER,
    "html2pdf",
    nullptr,
    PHP_MINIT(html2pdf),
    nullptr,
    nullptr,
    nullptr,
    PHP_MINFO(html2pdf),
    HTML2PDF_PHP_VERSION,
    STANDARD_MODULE_PROPERTIES
};

extern "C" ZEND_DLEXPORT zend_module_entry *get_module() { return &html2pdf_module_entry; }
