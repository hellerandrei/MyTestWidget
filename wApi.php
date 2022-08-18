<?php

class WidgetApi
{
    private $subdomain        = 'hellerandrei1985';
    private $client_id        = 'ca43a219-234f-424e-b8d3-682d7065ceaa';
    private $client_secret    = 'trqIETfL7vkYjdbwsfqjAIttiScZCwJ7hTFJ79KELiomDg4VaxXfV59Ksv6xwhhM';   
    private $redirect_uri     = 'https://www.adelaida.ua';  
    private $authHeader       = '';
    private $filename         = 'accessToken.txt';
    
    /**
     * Saving an access token to a file	 	  
     */
    private function saveAccessToken( $data = '' )
    {		
        $filename 	= '';			
        file_put_contents( $this->filename, $data, LOCK_EX );
    }

    /**
     * Reading an access token to a file	 	  
     */
    private function readAccessToken()
    {	        			
        return file_get_contents( $this->filename );
    }
    
    

    /**
     * Connect to amoCRM 	  
     */
    private function connect( $link, $headers, $postData )
    {        
        // echo $link.";\n";
        $headers[] = 'Content-Type:application/json';
        // print_r($headers);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $link);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);        

        if ( is_array($postData) )
        {            
            // print_r($postData);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData));
        }
        $out    = curl_exec($curl);
        $code   = curl_getinfo($curl, CURLINFO_HTTP_CODE);   

        curl_close($curl);
        
        // print_r($out);

        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        if ($code < 200 || $code > 204)
        {
            $hint = isset($errors[$code]) ? $code. ' -> '. $errors[$code] : $code. ' -> Undefined error';
            return (['hint' => $hint]);
        }
       
        try
        {
            return json_decode( $out, true );           
        }
        catch(Exception $e)
        {
            return (['hint' => $e->getMessage()]);
        }

    }
    
    
    /**
     * Get an access token	 	  
     */     
    private function getAccessToken( $authCode )
    {
        /** Соберем данные для запроса */
        $data = [
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type'    => 'authorization_code',
            'code'          => $authCode,
            'redirect_uri'  => $this->redirect_uri
        ];

        $link = 'https://' . $this->subdomain . '.amocrm.com/oauth2/access_token'; 
        
        $result = $this->connect( $link, [], $data );        
        return $result;
    }	                
                
    public function handler($GGet)
    {
        $data = array();        				
       
        $data['action'] 		= isset($GGet['action']) 		? trim($GGet['action'])    : null;	 	
        $data['auth_code'] 		= isset($GGet['auth_code']) 	? trim($GGet['auth_code']) : null;   
        $data['list_name'] 		= isset($GGet['list_name']) 	? trim($GGet['list_name']) : null; 
        $data['lead_id'] 		= isset($GGet['lead_id']) 	    ? trim($GGet['lead_id'])  : null; 
       
        if ( !in_array( $data['action'], ['get_access_token', 'get_products', 'insert_list', 'get_orders'] ) ) 
        {
            return json_encode( ['status' => 'fail','message' => 'This Action is Invalid'] );            
        }

        // Пытаемся прочитать ранее сохраненный токен для работы с api crm.
        if ( $this->authHeader == '' )
        {    
            $token = $this->readAccessToken();
            if ( $token != '' )
                $this->authHeader = ['Authorization: Bearer ' . $this->readAccessToken()];            
        }

        switch($data['action'])
        {            
            default;
            break;

            // Получение постоянного access token для работы с api
            case 'get_access_token':                  
                
                if($data['auth_code'] == '')
                {
                    return json_encode( ['status' => 'fail','message' => 'There are missing fields! Please check your API Manual'] );                    
                }

                $results = $this->getAccessToken( $data['auth_code'] );
                
                if ( $results['access_token'] != '' )
                {
                    // Созраняем в файл
                    $this->saveAccessToken($results['access_token']);
                    return json_encode
                    ( 
                        [
                            'status' 	    => 'success',
                            'action' 	    => $data['action'],
                            'access_token'	=> $results['access_token']
                        ]
                    );
                }
                else
                    return json_encode( ['status' => 'fail','message' => 'Access Token is empty', 'hint' => $results['hint'] ] );                
            break;


            // Добавление списка по названию
            case 'insert_list':
                if ( $this->authHeader == '')
                    return json_encode( ['status' => 'fail', 'action'=>$data['action'], 'message' => 'Access Token is empty'] );                                  
             
                if ( $data['list_name'] == '' )
                    return json_encode( ['status' => 'fail', 'action'=>$data['action'], 'message' => 'List name is empty'] );                

                $catalogs['add'] = [['name' => $data['list_name'] ]];
                
                $link    = 'https://' . $this->subdomain . '.amocrm.com/api/v2/catalogs';                        
                $results = $this->connect( $link,  $this->authHeader, $catalogs ); 

                return json_encode
                ( 
                    [
                        'status'    => 'success',
                        'action' 	=> $data['action'],
                        'results'   => $results
                    ]
                );
            break;



            // Получение товаров и их отправка в crm
            case 'get_orders':
                if ( $this->authHeader == '')
                    return json_encode( ['status' => 'fail', 'action'=>$data['action'], 'message' => 'Access Token is empty'] );                                  
             
                if ( $data['lead_id'] == '' )
                    return json_encode( ['status' => 'fail', 'action'=>$data['action'], 'message' => 'Lead id is empty'] );                

                $link       = 'https://' . $this->subdomain . '.amocrm.com/api/v4/leads/' . $data['lead_id'] . '?with=catalog_elements'; 
                $results    = $this->connect( $link,  $this->authHeader, nil );
                
                try
                {
                    $products   = $results['_embedded']['catalog_elements'];
                    
                    $i = 0;
                    foreach( $products as $product )
                    {
                        $catalog_id     = $product['metadata']['catalog_id'];                
                        $product_id     = $product['id'];
                        $quantity       = $product['metadata']['quantity'];

                        $link           = 'https://' . $this->subdomain . '.amocrm.com/api/v4/catalogs/' . $catalog_id . '/elements/' . $product_id;
                        $results        = $this->connect( $link,  $this->authHeader, nil );
                        $product_name   = $results['name']; 
                        
                        $orders[$i]['name']       = $product_name;
                        $orders[$i]['quantity']   = $quantity; 
                        $i++;                   
                    }                

                    return json_encode
                    ( 
                        [
                            'status' 	    => 'success',
                            'action' 	    => $data['action'],
                            'lead_id'       => $data['lead_id'],
                            'orders'	    => $orders
                        ]
                    );
                }
                catch(Exception $e)
                {
                    return json_encode
                    ( 
                        [
                            'status' 	    => 'fail',
                            'action' 	    => $data['action'],
                            'message'	    => $e->getMessage()
                        ]
                    );
                }


            break;      


            
        }
    }

}
        
header('Content-Type: application/json; charset=utf-8');
header('content-encoding: gzip');
header('Access-Control-Allow-Origin: *');

ob_start("ob_gzhandler");
       
    $connection = new WidgetApi();		
    $GGet 		= $_GET;		
    $response 	= $connection->handler($GGet);        
    echo $response;

ob_end_flush();	

?>