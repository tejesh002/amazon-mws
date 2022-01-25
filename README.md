Amazon Settlement Package

composer.json
```
"require": {
  ...
  "mws/amazon-mws":"*@dev"
}
"repositories":[
  {
      "type":"git",
      "reference":"master",
      "url":"https://github.com/tejesh002/amazon-mws.git"
  }
]
```
 composer update

$config = array(
      'Seller_Id' =>  //merchant_id,
      'Access_Key_ID' => // Accesskey,
      'Secret_Access_Key' => //secret_key,
      'ServiceURL'=>. //service url. Example = https://mws.amazonservices.in for india,
      'Application_Version' => '1'
      )
      
$client = new MWSClient($config);


# GET_REPORT_LIST
```php
$reportlist = array(
                "MarketplaceId"=> //your marketplaceid,
                "ReportTypeList.Type.1"=> "_GET_FLAT_FILE_OFFAMAZONPAYMENTS_SETTLEMENT_DATA_",
                "AvailableFromDate"=> // $start_date,
                "AvailableToDate"=> // $end_date,
                "MaxCount"=>100
            );
$response = $client->GetReportList($reportlist);
```

$reportRequestId = $response['GetReportListResult']['ReportInfo']['ReportId']

if $response['GetReportListResult']['HasNext'] = True

$response = $client->GetReportListByNextToken($response["NextToken"])
get the reportrequestid

# GET REPORT REQUEST
```php
$report_request_response = $client->GetReportRequest($reportRequestId);
```

$reportid = $report_request_response['GeneratedReportId']


# GET SETTLEMENT RESPONSE 
```php
$settlement_Report = $client->GetReport($reportid)
```

# update acknowledge 
```php
$response = $client->UpdateReportAcknowledgements($reportid,$acknowledge=true)
```
$acknowledge = true // default if you can change the acknowledge

