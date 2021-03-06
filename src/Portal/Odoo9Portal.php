<?php
/**
 * @license MIT
 * @website http://freshcoders.nl
 */

 namespace FreshCoders\JST\Portal;

use Carbon\Carbon;
use OdooClient\Client;

/**
 * Export the timesheet data to Odoo
 *
 * @author Nick Dekker <nick@freshcoders.nl>
 */
class Odoo9Portal
{
    private $_odooSheetModel;
    
    public $context;

    public function __construct($settings)
    {
        // Set the odoo model name object for hr_timesheet_sheet.sheet
        $this->_odooSheetModel = 'hr_timesheet_sheet.sheet';
        
        $this->client = new Client(
            $settings['odoo_host'] . '/xmlrpc/2',
            $settings['odoo_db'],
            $settings['odoo_user'],
            $settings['odoo_pass']
        );

        $this->employees = $settings['user_mapping'];
    }

    public function export($timesheets)
    {
        foreach ($this->getEmployeeIds() as $user => $employeeId) {
            $state = $this->createTimesheet($timesheets[$user], $employeeId);
            if (!$state) {
                echo "Something went wrong creating sheet for $user \n";
            }
        }
    }

    public function createTimesheet($timesheet, $employeeId)
    {
        $sheetData = [];

        $userId = $this->getUserId($employeeId);
        foreach ($timesheet as $date => $duration) {
            $sheetData[] = [
                0,
                false,
                [
                    "date" => $date,
                    "is_timesheet" => true,
                    'unit_amount' => $duration,
                    "amount"=> 0,
                    "account_id" => 1,
                    "name" => '/',
                    'user_id' => $userId
                ]
                ];
        }
        

        $data = [
            "employee_id" => $employeeId,
            "date_from" => Carbon::parse('first day of last month')->format('Y-m-d'),
            "date_to" => Carbon::parse('last day of last month')->format('Y-m-d'),
            'name' => false,
            'department_id' => 3,
            'company_id' => 1,
            "timesheet_ids" => $sheetData,
            'message_follower_ids' => false,
            'message_ids' => false,
        ];
        
        $id = $this->client->create($this->_odooSheetModel, $data);
        return (bool) $id;
    }

    public function getUserId($uid)
    {
        // todo: should call on user from odoo
        $map = [
            8 => 13,
            14 => 26,
            15 => 27
        ];
        return $map[$uid];
    }

    public function getEmployeeIds()
    {
        return $this->employees;
    }
}
