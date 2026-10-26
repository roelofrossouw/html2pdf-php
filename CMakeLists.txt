cmake_minimum_required(VERSION 3.22)
project(html2pdf-php HOMEPAGE_URL "https://github.com/roelofrossouw/html2pdf-php" LANGUAGES CXX)
include(cmake/sc_bootstrap.cmake)
include(GNUInstallDirs)
set(CMAKE_CXX_STANDARD 20)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

option(HTML2PDF_PHP_BUILD_TESTS "Build html2pdf-php tests" ${PROJECT_IS_TOP_LEVEL})
# Installing writes into PHP's own directories. On macOS those belong to Homebrew, and install.sh
# installs with sudo, so it is off there by default.
if (APPLE)
    option(HTML2PDF_PHP_INSTALL "Install the extension into PHP's directories" OFF)
else ()
    option(HTML2PDF_PHP_INSTALL "Install the extension into PHP's directories" ON)
endif ()

get_sc_version()
message(STATUS "html2pdf-php Version: ${SC_VERSION}")

################
# Dependencies #
################
# The extension is a thin wrapper: sc-html2pdf's shared library (its runtime package) does the work.
find_or_install_package(sc-core simply-cpp-dev simply-cpp 1.20.0)
find_or_install_package(sc-html2pdf simply-cpp-html2pdf-dev simply-cpp-html2pdf 1.4.3)

## PHP ##
# An extension is built for one PHP version: the one each Ubuntu release ships (jammy 8.1, noble
# 8.3, resolute 8.5), through its php-config; elsewhere (macOS) Homebrew's. HTML2PDF_PHP_CONFIG
# chooses another.
find_program(HTML2PDF_PHP_CONFIG NAMES php-config)
if (NOT HTML2PDF_PHP_CONFIG AND NOT APPLE)
    message(STATUS "No php-config found, installing php-dev and php-cli")
    execute_process(COMMAND sudo apt-get install -y php-dev php-cli)
    unset(HTML2PDF_PHP_CONFIG CACHE)
    find_program(HTML2PDF_PHP_CONFIG NAMES php-config)
endif ()
if (NOT HTML2PDF_PHP_CONFIG)
    message(FATAL_ERROR "No php-config found. Install php-dev (apt) or php (brew), or pass -DHTML2PDF_PHP_CONFIG=/path/to/php-config")
endif ()
function(html2pdf_php_config option output)
    execute_process(COMMAND "${HTML2PDF_PHP_CONFIG}" ${option}
            OUTPUT_VARIABLE value OUTPUT_STRIP_TRAILING_WHITESPACE COMMAND_ERROR_IS_FATAL ANY)
    set(${output} "${value}" PARENT_SCOPE)
endfunction()
html2pdf_php_config(--version PHP_VERSION_FULL)
html2pdf_php_config(--includes PHP_INCLUDES)
html2pdf_php_config(--extension-dir PHP_EXTENSION_DIR)
html2pdf_php_config(--php-binary PHP_BINARY)
string(REGEX MATCH "^[0-9]+\\.[0-9]+" PHP_MINOR "${PHP_VERSION_FULL}")
# PHP's module API, which an extension must match: the extension directory's name (20250925).
get_filename_component(PHP_API "${PHP_EXTENSION_DIR}" NAME)
separate_arguments(PHP_INCLUDES UNIX_COMMAND "${PHP_INCLUDES}")
message(STATUS "Building for PHP ${PHP_VERSION_FULL} (module API ${PHP_API}, ${HTML2PDF_PHP_CONFIG})")

#############
# Extension #
#############
# html2pdf.so, loaded by PHP with dlopen(): no "lib" prefix, no link against PHP (its symbols
# resolve at load time; macOS needs that said). It links the modules' shared libraries themselves,
# not their CMake targets, which would also bring the static libraries under them into it.
add_library(html2pdf MODULE src/html2pdf_php.cpp src/html2pdf.cpp src/php_glue.h)
target_compile_options(html2pdf PRIVATE ${PHP_INCLUDES})
target_compile_definitions(html2pdf PRIVATE HTML2PDF_PHP_VERSION="${SC_VERSION}")
foreach (module sc-core sc-html2pdf)
    target_link_libraries(html2pdf PRIVATE $<TARGET_LINKER_FILE:sc::${module}-shared>)
    target_include_directories(html2pdf PRIVATE $<TARGET_PROPERTY:sc::${module}-shared,INTERFACE_INCLUDE_DIRECTORIES>)
endforeach ()
set_target_properties(html2pdf PROPERTIES PREFIX "" SUFFIX ".so")
if (APPLE)
    target_link_options(html2pdf PRIVATE -undefined dynamic_lookup)
endif ()

# Turned on the Debian way: mods-available/html2pdf.ini, enabled for every SAPI by phpenmod.
configure_file(html2pdf.ini.in html2pdf.ini @ONLY)
# Stubs for IDEs (PhpStorm, Intelephense): what the extension declares, documented.
install(FILES stubs/html2pdf.stub.php DESTINATION ${CMAKE_INSTALL_DATADIR}/doc/html2pdf-php)
install(FILES LICENSE DESTINATION ${CMAKE_INSTALL_DATADIR}/doc/html2pdf-php RENAME copyright)
install(FILES NOTICE DESTINATION ${CMAKE_INSTALL_DATADIR}/doc/html2pdf-php)
if (HTML2PDF_PHP_INSTALL)
    install(TARGETS html2pdf LIBRARY DESTINATION "${PHP_EXTENSION_DIR}")
    install(FILES "${CMAKE_CURRENT_BINARY_DIR}/html2pdf.ini" DESTINATION "/etc/php/${PHP_MINOR}/mods-available")
endif ()

#########
# Tests #
#########
if (HTML2PDF_PHP_BUILD_TESTS)
    enable_testing()
    # Each tests/*.php run by the PHP it was built for, with only this extension loaded (-n).
    file(GLOB HTML2PDF_PHP_TESTS CONFIGURE_DEPENDS ${CMAKE_CURRENT_SOURCE_DIR}/tests/*.php)
    foreach (test ${HTML2PDF_PHP_TESTS})
        get_filename_component(name "${test}" NAME_WE)
        add_test(NAME test-php-${name}
                COMMAND "${PHP_BINARY}" -n -d "extension=$<TARGET_FILE:html2pdf>" "${test}"
                WORKING_DIRECTORY "${CMAKE_CURRENT_SOURCE_DIR}/tests")
        set_tests_properties(test-php-${name} PROPERTIES LABELS unit FAIL_REGULAR_EXPRESSION "FAILED")
    endforeach ()
endif ()

#############
# Packaging #
#############
if (APPLE)
    set(HTML2PDF_PHP_CODENAME apple)
    set(CPACK_GENERATOR TGZ)
elseif (UNIX)
    execute_process(COMMAND lsb_release -sc OUTPUT_VARIABLE HTML2PDF_PHP_CODENAME OUTPUT_STRIP_TRAILING_WHITESPACE)
    set(CPACK_GENERATOR DEB)
endif ()
# Enabled for PHP's SAPIs on install and php-fpm restarted (when it runs), the same on upgrade
# and removal.
foreach (script postinst prerm postrm)
    configure_file(packaging/${script}.in packaging/${script} @ONLY FILE_PERMISSIONS OWNER_READ OWNER_WRITE OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
endforeach ()
set(CPACK_DEBIAN_PACKAGE_CONTROL_EXTRA
        "${CMAKE_CURRENT_BINARY_DIR}/packaging/postinst;${CMAKE_CURRENT_BINARY_DIR}/packaging/prerm;${CMAKE_CURRENT_BINARY_DIR}/packaging/postrm")
set(CPACK_PACKAGE_NAME html2pdf-php)
set(CPACK_PACKAGE_VERSION "${SC_VERSION}~${HTML2PDF_PHP_CODENAME}")
set(CPACK_PACKAGE_CONTACT "Roelof Rossouw")
set(CPACK_PACKAGE_DESCRIPTION_SUMMARY "HTML to PDF for PHP: the html2pdf extension (SC\\\\Html2Pdf)")
set(CPACK_DEBIAN_PACKAGE_MAINTAINER "Roelof Rossouw <roelof@roelof.co.za>")
set(CPACK_DEBIAN_PACKAGE_DESCRIPTION "HTML to PDF for PHP ${PHP_MINOR}: the html2pdf extension (SC\\\\Html2Pdf), on litehtml and simply-cpp's PDF engine, for Ubuntu ${HTML2PDF_PHP_CODENAME}")
set(CPACK_DEBIAN_PACKAGE_SECTION php)
set(CPACK_DEBIAN_FILE_NAME DEB-DEFAULT)
set(CPACK_DEBIAN_PACKAGE_HOMEPAGE "${PROJECT_HOMEPAGE_URL}")
# phpapi-<api> is what Ubuntu's PHP provides for its module API: the package can't go into another.
# The modules' packages are named, not left to dpkg-shlibdeps: on a build server the libraries it
# links are the deploys' own copies in /usr/local/lib, which no package owns.
set(CPACK_DEBIAN_PACKAGE_DEPENDS "phpapi-${PHP_API}, php${PHP_MINOR}-common, simply-cpp (>= 1.20.0~), simply-cpp-html2pdf (>= 1.4.3~)")
# sc-phplib's sc extension declares the same classes: PHP can load only one of the two.
set(CPACK_DEBIAN_PACKAGE_CONFLICTS "simply-cpp-phplib")
set(CPACK_SET_DESTDIR ON)
set(CPACK_PACKAGE_DIRECTORY "${CMAKE_BINARY_DIR}")
include(CPack)
