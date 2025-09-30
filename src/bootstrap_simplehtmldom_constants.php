<?php
// Après require du fichier ci-dessus, les constantes doivent exister (souvent "const" dans le namespace).
// On les remonte en global si Parser.php les attend sans namespace.

if (defined('Weglot\\Vendor\\WGSimpleHtmlDom\\WG_DEFAULT_TARGET_CHARSET') && !defined('WG_DEFAULT_TARGET_CHARSET')) {
	define('WG_DEFAULT_TARGET_CHARSET', \Weglot\Vendor\WGSimpleHtmlDom\WG_DEFAULT_TARGET_CHARSET);
}
if (defined('Weglot\\Vendor\\WGSimpleHtmlDom\\WG_DEFAULT_BR_TEXT') && !defined('WG_DEFAULT_BR_TEXT')) {
	define('WG_DEFAULT_BR_TEXT', \Weglot\Vendor\WGSimpleHtmlDom\WG_DEFAULT_BR_TEXT);
}
if (defined('Weglot\\Vendor\\WGSimpleHtmlDom\\WG_DEFAULT_SPAN_TEXT') && !defined('WG_DEFAULT_SPAN_TEXT')) {
	define('WG_DEFAULT_SPAN_TEXT', \Weglot\Vendor\WGSimpleHtmlDom\WG_DEFAULT_SPAN_TEXT);
}
