<?php
/* Diretório Base */
$SERVER_DIRECTORY = "metasearch_bci";

/* Tradução */

$language = "pt_BR";
putenv("LANG=".$language);
setlocale(LC_ALL, $language);

$domain = "messages";
bindtextdomain($domain, "locale");
textdomain($domain);

?>
