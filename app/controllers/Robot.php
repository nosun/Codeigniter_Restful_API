<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Robot extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
    }

    function weather(){


    }

    function pm(){
        $this->load->helper(array('robot','logs'));
        $config['base_url'] = 'http://www.pm25.in/api/querys/';
        $config['app_key'] = 'ywZSKVxkFceeZXUZ4JBy';
        $config['point'] = 'all_cities.json';
        $config['log_path'] = 'www/logs/pm.log';
        $config['fail_path'] = 'www/logs/pm_fail.log';
        $config['mysql_fail_path'] = 'www/logs/pm_mysql_fail.log';
        $config['tb_air_log'] = 'api_air_log';

        $url = $config['base_url'] . $config['point'] . '?token=' . $config['app_key'];
        $data = json_decode(get_file($url));

        if (!empty($data)) {
            $insert = '';
            foreach ($data as $row) {
                $insert .= '("' .
                    $row->area . '","' .
                    $row->position_name . '","' .
                    $row->station_code . '",' .
                    $row->aqi . ',"' .
                    $row->primary_pollutant . '","' .
                    $row->quality . '",' .
                    time_format($row->time_point) . ',' .
                    $row->pm2_5 . ',' .
                    $row->pm2_5_24h . ',' .
                    $row->pm10 . ',' .
                    $row->pm10_24h . ',' .
                    $row->so2 . ',' .
                    $row->so2_24h . ',' .
                    $row->no2 . ',' .
                    $row->no2_24h . ',' .
                    $row->co . ',' .
                    $row->co_24h . ',' .
                    $row->o3 . ',' .
                    $row->o3_24h . ',' .
                    $row->o3_8h . ',' .
                    $row->o3_8h_24h . '),';
            }

            $insert{strlen($insert) - 1} = ';';
            $sql = 'insert into '.$config['tb_air_log'].'
                (area_name,position_name,station_code,aqi,primary_pollutant,quality,time_point,
        pm25,pm25_24h,pm10,pm10_24h,so2,so2_24h,no2,no2_24h,co,co_24h,o3,o3_24h,o3_8h,
        o3_8h_24h)values' . $insert;

            //设置数据库，并连接
            $mysqli = new mysqli($db['hostname'], $db['username'], $db['password'], $db['database']);
            $mysqli->set_charset($db['char_set']);
            if (mysqli_connect_error())
                die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
            $r = $mysqli->query($sql);
            mysqli_close($mysqli);

            //output log
            if ($r) {
                file_force_contents($config['log_path'], date('Y-m-d h:i:s', time()) . ' insert data succeed .' . "\n");
            } else {
                file_force_contents($config['mysql_fail_path'], date('Y-m-d h:i:s', time()) . $sql . "\n");
            }

        } else {
            // cannot get data
            file_force_contents($config['fail_path'], date('Y-m-d h:i:s', time()) . ' ' . $data . "\n");
        }


//2014-11-21T10:00:00Z to unix time
        function time_format($time)
        {
            $time{strlen($time) - 1} = '';
            $time{strlen($time) - 10} = ' ';
            $time = strtotime($time);
            return $time;
        }


    }
}