<?php

use sinri\ark\core\ArkHelper;

require_once __DIR__ . '/vendor/autoload.php';

ArkHelper::registerAutoload("sinri\\ark\\ldap", __DIR__ . '/src');