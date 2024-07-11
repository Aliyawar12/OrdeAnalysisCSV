<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use League\Csv\Reader;

class ProcessCsvData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process CSV data and generate a new CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //  Read data from the provided CSV file
        $csvData = Storage::disk('local')->get('backendtask.csv');
    
        // Process the data to calculate the required information for each customer
        $reader = Reader::createFromString($csvData);
        $reader->setHeaderOffset(0); 
    
        $customerData = [];
    
        foreach ($reader as $row) {
            $orderDate = Carbon::createFromFormat('m/d/Y H:i', $row['Order_Date']); 
            $email = $row['Email_address'];
            $productQty = intval($row['product_qty']);
    
            if (!isset($customerData[$email])) {
                $customerData[$email] = [
                    'first_order_date' => $orderDate,
                    'last_order_date' => $orderDate,
                    'total_orders' => 0,
                    'total_product_quantities' => 0,
                ];
            }
    
            $customerData[$email]['total_orders']++;
            $customerData[$email]['total_product_quantities'] += $productQty;
    
            if ($orderDate->lt($customerData[$email]['first_order_date'])) {
                $customerData[$email]['first_order_date'] = $orderDate;
            }
    
            if ($orderDate->gt($customerData[$email]['last_order_date'])) {
                $customerData[$email]['last_order_date'] = $orderDate;
            }
        }
    
        // Generate a new CSV file with the calculated information
        $newCsvData = "Customer Email,First Order Date,Last Order Date,Days Difference,Total Number of Orders,Total Number of Product Quantities\n";
    
        foreach ($customerData as $email => $data) {
            $daysDifference = $data['last_order_date']->diffInDays($data['first_order_date']);
            $newCsvData .= "$email,{$data['first_order_date']},{$data['last_order_date']},$daysDifference,{$data['total_orders']},{$data['total_product_quantities']}\n";
        }
    
        // Write data to a new file
        Storage::disk('local')->put('new_filename.csv', $newCsvData);
    }
}
