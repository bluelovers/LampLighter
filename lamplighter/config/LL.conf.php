<?php

$conf_overwrite = Config::$Overwrite;
Config::$Overwrite = false;

Config::Set('app.language', 'en_US');
Config::Set('output.text_only', false);
Config::Set('output.newline', ( defined('MESSAGE_NEWLINE') ) ? constant('MESSAGE_NEWLINE') : '<br />');
Config::Set('output.message_newline', config::Get('output.newline') );

Config::Set('debug.message_newline', Config::Get('output.message_newline') );		
Config::Set('db.auto_connect_w', true);

Config::Set('views.action_key_ucfirst', false);
Config::Set('views.subdir_name_lowercase', false);
Config::Set('views.subdir_name_pluralize', false);
Config::Set('views.filename_lowercase', false);
Config::Set('views.filename_pluralize', false);

Config::$Overwrite = $conf_overwrite;

?>
