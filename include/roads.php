<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Roads {
    private $db = FALSE;
    
    private $threadsTbl = 'threads';

    public function __construct($appConfig) {

        $this->db = db::getInstance($appConfig['db']);
    }

    public function roads()
    {
        if (!$this->db) {
            return FALSE;
        }
        if ($result = $this->db->query("SELECT * FROM roads;")) {
            $roads = array();
            while( $row = $result->fetch_assoc() ){
                $roads[] = $row;
            }

            $result->free_result();
            return $roads;
        }
    }


    public function getKilometrs()
    {
        if (!$this->db) {
            return FALSE;
        }
        if ($result = $this->db->query("SELECT * FROM kilometrs;")) {
            $distance = array();
            while( $row = $result->fetch_assoc() ){
                $distance[] = $row;
            }

            $result->free_result();
            return $distance;
        }
    }

    public function updateKilometrs($kmId, $km, $data = NULL)
    {
        $query = "UPDATE kilometrs SET km='$km' WHERE kmid='$kmId'";
        $this->db->query($query);
    }

    public function getSetting()
    {
        if (!$this->db) {
            return FALSE;
        }
        if ($result = $this->db->query("SELECT * FROM setting;")) {
            $setting = array();
            while( $row = $result->fetch_assoc() ){
                $setting[] = $row;
            }

            $result->free_result();
            return $setting;
        }
    }

    /**
     * Список папок
     * @param integer $km
     * @return boolean|array
     */
    public function getDirectory($km = NULL)
    {
        $query = "SELECT * FROM `directory`";
        if ($km !== NULL) {
            $query .= " WHERE start<='$km' AND end>='$km'";
        }
        $result = $this->db->query($query);
        if ($result->num_rows == 0) {
            return FALSE;
        }
        while( $row = $result->fetch_assoc() ){
            $data[] = $row;
        }

        $result->free_result();
        return $data;
    }

    public function setSetting($Id, $value)
    {
        if (is_array($value)) {
            $value = serialize($value);
        }
        $this->db->query("UPDATE setting SET settingVal='{$value}' WHERE settingKey='{$Id}';");
    }

    public function getMaxLogId()
    {
        $result = $this->db->query("SELECT MAX(logid) AS maxid FROM logs;");
        $row = $result->fetch_assoc();
        return ($row['maxid'] === NULL ? 1 : $row['maxid'] + 1);

    }

    public function writeLog($lodId, $filename, $km)
    {
        //$values = array($lodId, $filename, time(), $km);
        $this->db->query("INSERT INTO logs(logid, filename, datetime, km) VALUES ($lodId, '$filename', " . time() . ", $km)");
        //var_dump($this->db->error);
        return $this->db->affected_rows;
    }
    
    public function setThread($fileName)
    {
        $tbl = $this->threadsTbl;
        $fileName = $this->db->real_escape_string($fileName);
        echo $fileName;
        $this->db->query("INSERT INTO `{$tbl}`(filepath, datetime) VALUES ('" . $fileName . "', '" . time() . "')");
        return $this->db->affected_rows;
    }
    
    public function searchInThread($fileName, $uptime = NULL)
    {
        $tbl = $this->threadsTbl;
        $fileName = $this->db->real_escape_string($fileName);
        $query = "SELECT * FROM `{$tbl}` WHERE filepath='" . $fileName . "'";
        if ($uptime !== NULL) {
            $query .= " AND datetime >= ".$uptime;
        }
        
        $result = $this->db->query($query);
        
        return ($result->num_rows > 0 ? TRUE : FALSE);
    }
    
    public function removeInThread($fileName)
    {
        $tbl = $this->threadsTbl;
        $fileName = $this->db->real_escape_string($fileName);
        $query = "DELETE FROM `{$tbl}` WHERE filepath='" . $fileName . "'";
                
        $this->db->query($query);
    }
}