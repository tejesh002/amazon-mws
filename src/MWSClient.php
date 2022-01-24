<?php
namespace MWS;

use Exception;
use MWSEndPoint;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use function GuzzleHttp\json_encode;

class MWSClient{

    const SIGNATURE_METHOD = 'HmacSHA256';
    const SIGNATURE_VERSION = '2';
    const DATE_FORMAT = "Y-m-d\TH:i:s.\\0\\0\\0\\Z";
    const APPLICATION_NAME = 'MCS/MwsClient';

    private $config = [
        'Seller_Id' => null,
        'Access_Key_ID' => null,
        'Secret_Access_Key' => null,
        'MWSAuthToken' => null,
        'ServiceURL'=>null,
        'Application_Version' => '0.0.*'
    ];

    protected $debugNextFeed = false;
    protected $client = NULL;

    public function __construct(array $config)
    {
        foreach($config as $key => $value) {
            if (array_key_exists($key, $this->config)) {
                $this->config[$key] = $value;
            }
        }
        $required_keys = [
            'Seller_Id', 'Access_Key_ID', 'Secret_Access_Key','ServiceURL'
        ];

        foreach ($required_keys as $key) {
            if(is_null($this->config[$key])) {
                throw new Exception('Required field ' . $key . ' is not set');
            }
        }
        $this->config['Application_Name'] = self::APPLICATION_NAME;
        $this->config['Region_Host'] = explode("https://",$this->config['ServiceURL'])[1];
        $this->config['Region_Url'] = $this->config['ServiceURL'];
    }


    public function GetReportList($ReportTypeList = [])
    {
        return $this->request('GetReportList', $ReportTypeList);
    }
    
    public function UpdateReportAcknowledgements($ReportRequestId,$acknowledge=true)
    {
        $result = $this->request('UpdateReportAcknowledgements',[
            'ReportIdList.Id.1'=>$ReportRequestId,
            'Acknowledged'=>$acknowledge
        ]);

        return $result;
    }

    private function xmlToArray($xmlstring)
    {
        return json_decode(json_encode(simplexml_load_string($xmlstring)), true);
    }

    public function GetReportRequest($ReportRequestId)
    {
        $result = $this->request('GetReportRequestList', [
            'ReportRequestIdList.Id.1' => $ReportRequestId
        ]);

        if (isset($result['GetReportRequestListResult']['ReportRequestInfo']))
        {
            return $result['GetReportRequestListResult']['ReportRequestInfo'];
        }
        return $result;
    }

    public function GetReportListByNextToken($Token)
    {
        return $this->request('GetReportListByNextToken',["NextToken"=>$Token]);
       
    }
    
    public function GetReport($ReportId)
    {
        
        $result = $this->request('GetReport',[
            'ReportId' => $ReportId
        ]);
        $respone = explode("\n", $result);
        $result = array();
        $header = str_getcsv($respone[5],",",'"');
        unset($header[count($header)-1]);
        for($i=6;$i<count($respone)-1;$i++)
        {
            $data = str_getcsv($respone[$i],",",'"');
            array_push($result,array_combine($header,$data));
        }
        return $result;
    }

    private function request($endPoint, array $query = [], $body = null, $raw = false)
    {
        $endPoint = MWSEndPoint::get($endPoint);
        $merge = [
            'Timestamp' => gmdate(self::DATE_FORMAT, time()),
            'AWSAccessKeyId' => $this->config['Access_Key_ID'],
            'Action' => $endPoint['action'],
            'SellerId' => $this->config['Seller_Id'],
            'SignatureMethod' => self::SIGNATURE_METHOD,
            'SignatureVersion' => self::SIGNATURE_VERSION,
            'Version' => $endPoint['date'],
        ];

        $query = array_merge($merge, $query);

       
        if (!is_null($this->config['MWSAuthToken']) and $this->config['MWSAuthToken'] != "") {
            $query['MWSAuthToken'] = $this->config['MWSAuthToken'];
        }

        if (isset($query['MarketplaceId'])) {
            unset($query['MarketplaceId.Id.1']);
        }

        if (isset($query['MarketplaceIdList.Id.1'])) {
            unset($query['MarketplaceId.Id.1']);
        }

        try{

            $headers = [
                'Accept' => 'application/xml',
                'x-amazon-user-agent' => $this->config['Application_Name'] . '/' . $this->config['Application_Version']
            ];

            $requestOptions = [
                'headers' => $headers,
                'body' => $body
            ];

            ksort($query);

            $query['Signature'] = base64_encode(
                hash_hmac(
                    'sha256',
                    $endPoint['method']
                    . "\n"
                    . $this->config['Region_Host']
                    . "\n"
                    . $endPoint['path']
                    . "\n"
                    . http_build_query($query, null, '&', PHP_QUERY_RFC3986),
                    $this->config['Secret_Access_Key'],
                    true
                )
            );

            $requestOptions['query'] = $query;
            // return $query;
            
            if($this->client === NULL) {
                $this->client = new Client();
            }

            $response = $this->client->request(
                $endPoint['method'],
                $this->config['Region_Url'] . $endPoint['path'],
                $requestOptions
            );
           

            $body = (string) $response->getBody();
            
            if ($raw) {
                return $body;
            } else if (strpos(strtolower($response->getHeader('Content-Type')[0]), 'xml') !== false) {
                return $this->xmlToArray($body);
            } else {
                return $body;
            }

        } catch (BadResponseException $e) {
            if ($e->hasResponse()) {
                $message = $e->getResponse();
                $message = $message->getBody();
                if (strpos($message, '<ErrorResponse') !== false) {
                    $error = simplexml_load_string($message);
                    $message = $error->Error->Message;
                }
                $message = $e->getResponse()->getBody();
            } else {
                $message = 'An error occured';
            }
            throw new Exception($message);
        }
    }
    
    public function setClient(Client $client) {
        $this->client = $client;
    }
}
