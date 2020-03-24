<?php

/* 
 * Copyright (C) 2020 Sergey Ilyin <developer@ilyins.ru>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

include_once 'config.php';

spl_autoload_register(function ($class) {
    include 'classes/' . $class . '.class.php';
});

switch (filter_input(INPUT_SERVER, 'REQUEST_METHOD')){
    case 'GET':
        $inputRequest = INPUT_GET;
        break;
    case 'POST':
        $inputRequest = INPUT_POST;
        break;
}

switch (filter_input($inputRequest, 'cmd')) {
    case 'sync':
        BX24::syncBGBilling(filter_input($inputRequest, 'type'));
        break;
     case 'tasksDeleteOld':
        BX24::tasksDeleteOld();
        break;
    case 'tasksCheckExpired':
        BX24::tasksCheckExpired();
        break;
    case 'sendMessage':
        BX24::sendMessage(filter_input($inputRequest, 'dialog_id'), filter_input($inputRequest, 'message'));
        break;
    case 'notifyPersonal':
        BX24::notifyPersonal(filter_input($inputRequest, 'user_id'), filter_input($inputRequest, 'message'));
        break;
    case 'notifySystem':
        BX24::notifySystem(filter_input($inputRequest, 'user_id'), filter_input($inputRequest, 'message'));
        break;
    case 'taskComment':
        BX24::taskComment(filter_input($inputRequest, 'taskId'), filter_input($inputRequest, 'message'));
        break;
    case 'taskComplete':
        BX24::taskComplete(filter_input($inputRequest, 'taskId'));
        break;
    case 'taskDelete':
        BX24::taskDelete(filter_input($inputRequest, 'taskId'));
        break;
    default:
        break;
}
