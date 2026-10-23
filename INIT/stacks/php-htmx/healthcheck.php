<?php
// Santé du conteneur (healthcheck Docker) : PHP doit répondre sur / avec un statut < 500.
// Un 401 ou un 404 est sain (l'app tourne) ; une erreur PHP ou un serveur muet ne l'est pas.
declare(strict_types=1);

$context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 4]]);
$body = @file_get_contents('http://127.0.0.1:8080/', false, $context);
$status = 0;
foreach ($http_response_header ?? [] as $line) {
    if (preg_match('#^HTTP/\S+ (\d{3})#', $line, $m)) {
        $status = (int) $m[1];
    }
}
exit($body !== false && $status > 0 && $status < 500 ? 0 : 1);
