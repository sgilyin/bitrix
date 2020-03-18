<?php

/*
 * Copyright (C) 2020 sgilyin
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
 * Description of BX24
 * 
 * Class for working whith Bitrix24
 *
 * @author sgilyin
 */
class BX24 {
    public static function callMethod($bx24Method,$bx24Data) {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_POST => TRUE,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_URL => CRM_HOST.'/rest/1/'.CRM_SECRET.'/'.$bx24Method,
                CURLOPT_POSTFIELDS => $bx24Data,
            )
        );
        $result_curl = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($result_curl);
        return $result;
    }

    public static function getParams($type) {
        $btrx = new stdClass();
        switch ($type) {
            case "Ethernet":
                $btrx->title = "Ethernet - Подключение: ";// Название задачи
                $btrx->responsible_id = 562;// Ответственный Сычев (562)
                $btrx->accomplices = array(562,724,964);// Соисполнители Сычев (562), Скрынников (724), Касса (964)
                $btrx->auditors = array(668);// Наблюдатели Козлов (668)
                $btrx->tags = array('Подключение','Ethernet');// Теги задачи
                $btrx->group_id = 18;// Группа "Ethernet"
                $btrx->pid = 43;// Поле задачи в Биллинге
                break;
            case "PON":
                $btrx->title = "PON - Подключение: ";// Название задачи
                $btrx->responsible_id = 562;// Ответственный Сычев (562)
                $btrx->accomplices = array(562,724,964);// Соисполнители Осипов (18), Сычев (562), Скрынников (724), Касса (964)
                $btrx->auditors = array(668);// Наблюдатели Козлов (668)
                $btrx->tags = array('Подключение','PON');// Теги задачи
                $btrx->group_id = 22;// Группа "PON"
                $btrx->pid = 43;// Поле задачи в Биллинге
                break;
            case "TVEnable":
                $btrx->title = "TV - Подключение: ";// Название задачи
                $btrx->responsible_id = 8;// Ответственный Сычев (562)
                $btrx->accomplices = array(8);// Соисполнители Осипов (18), Сычев (562), Скрынников (724), Касса (964)
                $btrx->auditors = array(8);// Наблюдатели Козлов (668)
                $btrx->tags = array('Подключение','TV');// Теги задачи
                $btrx->group_id = 24;// Группа "TV"
                $btrx->pid = 44;// Поле задачи в Биллинге
                break;
            case "TVDisable":
                $btrx->title = "TV - Отключение: ";// Название задачи
                $btrx->responsible_id = 8;// Ответственный Сычев (562)
                $btrx->accomplices = array(8);// Соисполнители Осипов (18), Сычев (562), Скрынников (724), Касса (964)
                $btrx->auditors = array(8);// Наблюдатели Козлов (668)
                $btrx->tags = array('Отключение','TV');// Теги задачи
                $btrx->group_id = 24;// Группа "TV"
                $btrx->pid = 45;// Поле задачи в Биллинге
                break;
        }
        return $btrx;
    }

    public static function syncBGBilling($type) {
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
                            'DESCRIPTION' => "ФИО: {$contract->fio}<br>Телефон: <a href='tel:{$contract->phone}'>{$contract->phone}</a><br>Договор в биллинге: {$contract->cid}",
                        )
                    )
                );
                $bx_task = static::callMethod('tasks.task.add.json', $bx24Data);
                $task = $bx_task->result->task->id;
                $tasks[$contract->cid] = $task;
            }
            foreach ($tasks as $cid => $task){
                BGBilling::executeRequest("INSERT INTO contract_parameter_type_1 (cid, pid, val) VALUES ('{$cid}', {$btrx->pid}, '{$task}')");
            }
        }
    }

    public static function chatMessage($chat_id,$message) {
        $bx24Data = http_build_query(
                array(
                    'CHAT_ID' => $chat_id,
                    'MESSAGE' => $message,
                    )
                );
        return static::callMethod('im.message.add.json', $bx24Data);
    }
}
