<?php

$console = new ConsoleKit\Console();
$console->addCommand('CreateCommand');
$console->addCommand('ImportCommand');
$console->addCommand('ProcessCommand');
$console->run();