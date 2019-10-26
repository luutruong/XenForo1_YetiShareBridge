<?php

class Truonglv_YetiShareBridge_DevHelper_Config extends DevHelper_Config_Base
{
    protected $_dataClasses = array(
        'log' => array(
            'name' => 'log',
            'camelCase' => 'Log',
            'camelCasePlural' => false,
            'camelCaseWSpace' => 'Log',
            'camelCasePluralWSpace' => false,
            'fields' => array(
                'log_id' => array('name' => 'log_id', 'type' => 'uint', 'required' => true, 'autoIncrement' => true),
                'user_id' => array('name' => 'user_id', 'type' => 'uint', 'default' => 0),
                'method' => array('name' => 'method', 'type' => 'string', 'length' => 12, 'required' => true),
                'end_point' => array('name' => 'end_point', 'type' => 'string', 'length' => 100, 'required' => true),
                'request_data' => array('name' => 'request_data', 'type' => 'string', 'required' => true),
                'response_data' => array('name' => 'response_data', 'type' => 'string', 'required' => true),
                'response_code' => array('name' => 'response_code', 'type' => 'uint', 'required' => true, 'default' => 0),
                'log_date' => array('name' => 'log_date', 'type' => 'uint', 'default' => 0),
            ),
            'phrases' => array(),
            'title_field' => 'method',
            'primaryKey' => array('log_id'),
            'indeces' => array(
                'log_date' => array('name' => 'log_date', 'fields' => array('log_date'), 'type' => 'NORMAL'),
            ),
            'files' => array(
                'data_writer' => false,
                'model' => array('className' => 'Truonglv_YetiShareBridge_Model_Log', 'hash' => '3e5f79838e6cf144189cef095d614455'),
                'route_prefix_admin' => false,
                'controller_admin' => false,
            ),
        ),
    );
    protected $_dataPatches = array();
    protected $_exportPath = false;
    protected $_exportIncludes = array();
    protected $_exportExcludes = array();
    protected $_exportAddOns = array();
    protected $_exportStyles = array();
    protected $_options = array();

    /**
     * Return false to trigger the upgrade!
     **/
    protected function _upgrade()
    {
        return true; // remove this line to trigger update

        /*
        $this->addDataClass(
            'name_here',
            array( // fields
                'field_here' => array(
                    'type' => 'type_here',
                    // 'length' => 'length_here',
                    // 'required' => true,
                    // 'allowedValues' => array('value_1', 'value_2'),
                    // 'default' => 0,
                    // 'autoIncrement' => true,
                ),
                // other fields go here
            ),
            array('primary_key_1', 'primary_key_2'), // or 'primary_key', both are okie
            array( // indeces
                array(
                    'fields' => array('field_1', 'field_2'),
                    'type' => 'NORMAL', // UNIQUE or FULLTEXT
                ),
            ),
        );
        */
    }
}