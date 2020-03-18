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
 * Description of BillingDatabase
 * 
 * Class for working whith BGBilling Database
 *
 * @author sgilyin
 */

class BGBilling {
    public static function executeRequest($query) {
        $mysqli = new mysqli(BGB_HOST, BGB_USER, BDB_PASSWORD, BGB_DB);
        $mysqli->set_charset('utf8');
        $result = $mysqli->query($query);
        $mysqli->close();
        switch (strtok($query," ")){
            case 'INSERT':
                return $mysqli->errno;
            case 'UPDATE':
                return $mysqli->errno;
            default:
                return $result;
        }
    }

    public static function getContracts($contractsType) {
        return static::executeRequest(static::getQuery($contractsType));
    }

    private static function getQuery($contractsType) {
        switch($contractsType){
            case "Ethernet":
                $query = "
SELECT tbl_inet_date.cid, CONCAT(tbl_street.title, ' д. ', tbl_house.house, CONCAT_WS( ' кв. ',tbl_house.frac, IF(tbl_flat.flat='',NULL,tbl_flat.flat))) AS 'address', tbl_phone.val AS 'phone', tbl_inet_date.val AS 'date', tbl_fio.val AS 'fio'
FROM contract AS tbl_contract
LEFT JOIN contract_parameter_type_6 AS tbl_inet_date ON (tbl_contract.id=tbl_inet_date.cid) AND (tbl_inet_date.pid=41)
LEFT JOIN contract_parameter_type_1 AS tbl_btrx ON (tbl_contract.id=tbl_btrx.cid) AND (tbl_btrx.pid=43)
LEFT JOIN contract_parameter_type_1 AS tbl_phone ON (tbl_contract.id=tbl_phone.cid) AND (tbl_phone.pid=2)
LEFT JOIN contract_parameter_type_1 AS tbl_fio ON (tbl_contract.id=tbl_fio.cid) AND (tbl_fio.pid=1)
LEFT JOIN contract_parameter_type_2 AS tbl_flat ON (tbl_contract.id=tbl_flat.cid)
LEFT JOIN address_house AS tbl_house ON (tbl_flat.hid=tbl_house.id)
LEFT JOIN address_street AS tbl_street ON (tbl_house.streetid=tbl_street.id)
WHERE tbl_contract.date2 IS NULL AND tbl_btrx.val IS NULL AND tbl_contract.fc=0 AND tbl_inet_date.val >= CURDATE() AND tbl_contract.gr&(1<<21) > 0 AND NOT tbl_contract.gr&(1<<39) > 0
            ";
               break;
            case "PON":
                $query = "
SELECT tbl_inet_date.cid, CONCAT(tbl_street.title, ' д. ', tbl_house.house, CONCAT_WS( ' кв. ',tbl_house.frac, IF(tbl_flat.flat='',NULL,tbl_flat.flat))) AS 'address', tbl_phone.val AS 'phone', tbl_inet_date.val AS 'date', tbl_fio.val AS 'fio'
FROM contract AS tbl_contract
LEFT JOIN contract_parameter_type_6 AS tbl_inet_date ON (tbl_contract.id=tbl_inet_date.cid) AND (tbl_inet_date.pid=41)
LEFT JOIN contract_parameter_type_1 AS tbl_btrx ON (tbl_contract.id=tbl_btrx.cid) AND (tbl_btrx.pid=43)
LEFT JOIN contract_parameter_type_1 AS tbl_phone ON (tbl_contract.id=tbl_phone.cid) AND (tbl_phone.pid=2)
LEFT JOIN contract_parameter_type_1 AS tbl_fio ON (tbl_contract.id=tbl_fio.cid) AND (tbl_fio.pid=1)
LEFT JOIN contract_parameter_type_2 AS tbl_flat ON (tbl_contract.id=tbl_flat.cid)
LEFT JOIN address_house AS tbl_house ON (tbl_flat.hid=tbl_house.id)
LEFT JOIN address_street AS tbl_street ON (tbl_house.streetid=tbl_street.id)
WHERE tbl_contract.date2 IS NULL AND tbl_btrx.val IS NULL AND tbl_contract.fc=0 AND tbl_inet_date.val >= CURDATE() AND tbl_contract.gr&(1<<21) > 0 AND tbl_contract.gr&(1<<39) > 0
            ";
                break;
            case "TVEnable":
                $query = "
SELECT tbl_tv_date.cid, CONCAT(tbl_street.title, ' д. ', tbl_house.house, CONCAT_WS( ' кв. ',tbl_house.frac, IF(tbl_flat.flat='',NULL,tbl_flat.flat))) AS 'address', tbl_phone.val AS 'phone', tbl_tv_date.val AS 'date', tbl_fio.val AS 'fio'
FROM contract AS tbl_contract
LEFT JOIN contract_parameter_type_6 AS tbl_tv_date ON (tbl_contract.id=tbl_tv_date.cid) AND (tbl_tv_date.pid=40)
LEFT JOIN contract_parameter_type_1 AS tbl_btrx ON (tbl_contract.id=tbl_btrx.cid) AND (tbl_btrx.pid=44)
LEFT JOIN contract_parameter_type_1 AS tbl_phone ON (tbl_contract.id=tbl_phone.cid) AND (tbl_phone.pid=2)
LEFT JOIN contract_parameter_type_1 AS tbl_fio ON (tbl_contract.id=tbl_fio.cid) AND (tbl_fio.pid=1)
LEFT JOIN contract_parameter_type_2 AS tbl_flat ON (tbl_contract.id=tbl_flat.cid)
LEFT JOIN address_house AS tbl_house ON (tbl_flat.hid=tbl_house.id)
LEFT JOIN address_street AS tbl_street ON (tbl_house.streetid=tbl_street.id)
WHERE tbl_contract.date2 IS NULL AND tbl_btrx.val IS NULL AND tbl_contract.fc=0 AND tbl_tv_date.val >= CURDATE() AND tbl_contract.gr&(1<<11) > 0 AND NOT tbl_contract.gr&(1<<39) > 0
            ";
                break;
            case "TVDisable":
                $query = "
SELECT tbl_contract.id AS 'cid', CONCAT(tbl_street.title, ' д. ', tbl_house.house, CONCAT_WS( ' кв. ',tbl_house.frac, IF(tbl_flat.flat='',NULL,tbl_flat.flat))) AS 'address'
FROM contract AS tbl_contract
LEFT JOIN contract_parameter_type_1 AS tbl_btrx ON (tbl_contract.id=tbl_btrx.cid) AND (tbl_btrx.pid=45)
LEFT JOIN contract_parameter_type_2 AS tbl_flat ON (tbl_contract.id=tbl_flat.cid)
LEFT JOIN address_house AS tbl_house ON (tbl_flat.hid=tbl_house.id)
LEFT JOIN address_street AS tbl_street ON (tbl_house.streetid=tbl_street.id)
WHERE tbl_btrx.val IS NULL AND tbl_contract.fc=0 AND tbl_contract.gr&(1<<17) > 0 AND NOT tbl_contract.gr&(1<<39) > 0
            ";
                break;
            default :
                break;
    }
    return $query;
    }
}