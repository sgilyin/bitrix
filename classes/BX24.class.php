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

/**
 * Class for Bitrix24
 * 
 * @author Sergey Ilyin <developer@ilyins.ru>
 */

class BX24 {

    /**
     * Execute method on Bitrix24
     * 
     * @param String $bx24Method
     * @param Array $bx24Data
     * @return json
     */
    public static function callMethod($bx24Method, $bx24Data) {

        $url = CRM_HOST.'/rest/1/'.CRM_SECRET."/{$bx24Method}";
        $result = cURL::executeRequest($url, $bx24Data, FALSE);
        return $result;
    }

    /**
     * Get params for task in Bitrix24
     * 
     * @param String $type
     * @return Object
     */
    public static function getParams($type) {
        $btrx = new stdClass();

        switch ($type) {
            case 'Ethernet':
                $btrx->title = 'Eth | Подключение | ';// Название задачи
                $btrx->responsible_id = 562;// Ответственный
                $btrx->accomplices = array(562,964);// Соисполнители
                $btrx->auditors = array(668,6768);// Наблюдатели
                $btrx->tags = array('Подключение','Ethernet','Internet');// Теги задачи
                $btrx->group_id = 18;// Группа "Ethernet"
                $btrx->pid = 43;// Поле задачи в Биллинге
                $btrx->deadline = 'N';
                break;
            case 'PON':
                $btrx->title = 'PON | Подключение | ';// Название задачи
                $btrx->responsible_id = 562;// Ответственный
                $btrx->accomplices = array(562,964,6904);// Соисполнители
                $btrx->auditors = array(668,6768);// Наблюдатели
                $btrx->tags = array('Подключение','PON','Internet');// Теги задачи
                $btrx->group_id = 22;// Группа "PON"
                $btrx->pid = 43;// Поле задачи в Биллинге
                $btrx->deadline = 'N';
                break;
            case 'TVEnable':
                $btrx->title = 'TV | Подключение | ';// Название задачи
                $btrx->responsible_id = 6880;// Ответственный
                $btrx->accomplices = array(964,6880,6906,6908);// Соисполнители
                $btrx->auditors = array(668,6768);// Наблюдатели
                $btrx->tags = array('Подключение','TV');// Теги задачи
                $btrx->group_id = 24;// Группа "TV"
                $btrx->pid = 44;// Поле задачи в Биллинге
                $btrx->deadline = 'Y';
                break;
            case 'TVDisable':
                $btrx->title = 'TV | Отключение | ';// Название задачи
                $btrx->responsible_id = 6880;// Ответственный
                $btrx->accomplices = array(6880,6910,6912);// Соисполнители
                $btrx->auditors = array(668,6768);// Наблюдатели
                $btrx->tags = array('Отключение','TV');// Теги задачи
                $btrx->group_id = 24;// Группа "TV"
                $btrx->pid = 45;// Поле задачи в Биллинге
                $btrx->deadline = 'Y';
                break;
        }
        return $btrx;
    }

    /**
     * Synchronize BGBilling with Bitrix24
     * 
     * @param String $type
     */
    public function syncBGBilling($type) {

        $contracts = BGBilling::getContracts($type);
        echo $contracts->num_rows;
        if ($contracts->num_rows>0){
            $btrx = static::getParams($type);
            $tasks = array();
            while ($contract = $contracts->fetch_object()){
                $bx24Data = http_build_query(
                    array(
                        'fields' => array(
                            'TITLE' => "{$btrx->title}{$contract->address}",
                            'CREATED_BY' => 1,
                            'RESPONSIBLE_ID' => $btrx->responsible_id,
                            'ACCOMPLICES' => $btrx->accomplices,
                            'AUDITORS' => $btrx->auditors,
                            'TAGS' => $btrx->tags,
                            'GROUP_ID' => $btrx->group_id,
                            'DEADLINE' => date('c',strtotime($contract->date.' 18:00:00')),
                            'START_DATE_PLAN' => date('c',strtotime($contract->date.' 08:00:00')),
                            'END_DATE_PLAN' => date('c',strtotime($contract->date.' 17:00:00')),
                            'DESCRIPTION' => "ФИО: {$contract->fio}\nТелефон: [URL=tel:{$contract->phone}]{$contract->phone}[/URL]\nДоговор в биллинге: {$contract->cid}",
                            'ALLOW_CHANGE_DEADLINE' => $btrx->deadline,
                        )
                    )
                );
                $bx_task = json_decode(static::callMethod('tasks.task.add.json', $bx24Data));
                $task = $bx_task->result->task->id;
                $tasks[$contract->cid] = $task;
            }
            foreach ($tasks as $cid => $task){
                BGBilling::executeRequest("INSERT INTO contract_parameter_type_1 (cid, pid, val) VALUES ('{$cid}', {$btrx->pid}, '{$task}')");
            }
        }
    }

    /**
     * Send message to Bitrix24
     * 
     * @param String $dialog_id
     * @param String $message
     * @return json
     */
    public static function sendMessage($dialog_id, $message) {

        $bx24Data = http_build_query(
                array(
                    'DIALOG_ID' => $dialog_id,
                    'MESSAGE' => $message,
                    )
                );
        return static::callMethod('im.message.add.json', $bx24Data);
    }

    /**
     * Send personal notify to Bitrix24 from admin
     * 
     * @param Integer $user_id
     * @param String $message
     * @return json
     */
    public static function notifyPersonal($user_id, $message) {
        $bx24Data = http_build_query(
                array(
                    'USER_ID' => $user_id,
                    'MESSAGE' => $message,
                    )
                );
        return static::callMethod('im.notify.personal.add.json', $bx24Data);
    }

    /**
     * Send personal notify to Bitrix24 from system
     * 
     * @param Integer $user_id
     * @param String $message
     * @return json
     */
    public static function notifySystem($user_id, $message) {
        $bx24Data = http_build_query(
                array(
                    'USER_ID' => $user_id,
                    'MESSAGE' => $message,
                    )
                );
        return static::callMethod('im.notify.system.add.json', $bx24Data);
    }

    /**
     * Delete task in Bitrix24
     * 
     * @param Integer $taskId
     * @return json
     */
    public static function taskDelete($taskId) {
        $bx24Data = http_build_query(
                array(
                    'taskId' => $taskId,
                    )
                );
        return static::callMethod('tasks.task.delete.json', $bx24Data);
    }

    /**
     * Set status "Complete" in Bitrix24
     * 
     * @param Integer $taskId
     * @return json
     */
    public static function taskComplete($taskId) {
        $bx24Data = http_build_query(
                array(
                    'taskId' => $taskId,
                    )
                );
        return static::callMethod('tasks.task.complete.json', $bx24Data);
    }

    /**
     * Add comment to task in Bitrix24
     * 
     * @param Integer $taskId
     * @param String $message
     * @return json
     */
    public static function taskComment($taskId, $message) {
        $bx24Data = http_build_query(
                array(
                    $taskId,
                    array(
                       'POST_MESSAGE' => $message,
                        ),
                    )
                );
        return static::callMethod('task.commentitem.add.json', $bx24Data);
    }

    /**
     * Delete old tasks
     */
    public function tasksDeleteOld() {
        $bx24Data = http_build_query(
                array(
                    'filter' => array(
                        'REAL_STATUS' => 5,
                        '<CLOSED_DATE' => date("Y-m-d",strtotime('Today -6 months')),
                        ),
                    'select' => array('ID'),
                    'limit' => 1000,
                    )
                );
        $btrx_task = json_decode(static::callMethod('tasks.task.list.json',$bx24Data));
        echo $btrx_task->total;
        for ($i = 0; $i < $btrx_task->total; $i++){
            if ($btrx_task->result->tasks[$i]->id){
                static::taskDelete($btrx_task->result->tasks[$i]->id);
            }
        }
        if ($i) {
            static::sendMessage('chat7344',"Удалено {$i} задач по сроку закрытия 6 месяцев");
        }
    }

    /**
     * Check expired task and send notify to responsible user
     */
    public function tasksCheckExpired() {
        $bx24Data = http_build_query(
                array(
                    'ORDER' => array(
                        'DEADLINE' => 'desc',
                        ),
                    'FILTER' => array(
                        'STATUS' => '-1',
                        ),
                    )
                );
        $btrx_task = json_decode(static::callMethod('task.item.list.json',$bx24Data));
        echo $btrx_task->total;
        for ($i = 0; $i < $btrx_task->total; $i++){
            static::sendMessage($btrx_task->result[$i]->RESPONSIBLE_ID,"Просроченная задача ".CRM_HOST."/company/personal/user/{$btrx_task->result[$i]->RESPONSIBLE_ID}/tasks/task/view/{$btrx_task->result[$i]->ID}/");
            static::sendMessage(668,"Просроченная задача ".CRM_HOST."/company/personal/user/668/tasks/task/view/{$btrx_task->result[$i]->ID}/");
        }
    }
}
