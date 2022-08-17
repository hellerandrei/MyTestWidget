<?php

class WidgetApi
{
    private $subdomain        = 'hellerandrei1985';
    private $client_id        = '87b99d22-4c6c-4a17-978b-6ef26af05f5e';
    private $client_secret    = 'yGloysW6IvlG9TfYgaVKiFitCu14cuEV8xIQj8GzNRejbgQaXEk5J1NMvQSykgid';   
    private $redirect_uri     = 'https://adelaida.ua';   
    
    /**
     * Saving an access token to a file	 	  
     */
    private function saveAccessToken( $data = '' )
    {		
        $filename 	= 'includes/accessToken.txt';			
        file_put_contents($filename, $data, LOCK_EX);
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

        $link = 'https://' . $this->subdomain . '.amocrm.com/oauth2/access_token'; //Формируем URL для запроса

        /**
         * Нам необходимо инициировать запрос к серверу.
         * Воспользуемся библиотекой cURL (поставляется в составе PHP).
         * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
         */
        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
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

        try
        {
            $response = json_decode( $out, true );       
            // $access_token = $response['access_token']; 
            return $response;
        }
        catch(Exception $e)
        {
            return ([]);
        }

        /**
         * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
         * нам придётся перевести ответ в формат, понятный PHP
         */
               
        
        

        // $refresh_token = $response['refresh_token']; //Refresh токен
        // $token_type = $response['token_type']; //Тип токена
        // $expires_in = $response['expires_in']; //Через сколько действие токена истекает
    }	
                
                
    public function handler($GGet)
    {
        $data = array();        				
       
        $data['action'] 		= isset($GGet['action']) 		? trim($GGet['action']) : null;	 	
        $data['auth_code'] 		= isset($GGet['auth_code']) 	? trim($GGet['auth_code']) : null; 
       
        if( $data['action'] != 'get_access_token' && $data['action'] != 'read_leads' )
        {
            return json_encode( ['status' => 'fail','message' => 'This Action is Invalid'] );            
        }

        switch($data['action'])
        {            
            default;
            break;

            case 'get_access_token':                  
                
                if($data['auth_code'] == '')
                {
                    return json_encode( ['status' => 'fail','message' => 'There are missing fields! Please check your API Manual'] );                    
                }

                $results = $this->getAccessToken( $data['auth_code'] );
                
                if ( $results['access_token'] != '' )
                {
                    return json_encode( 
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


        }
    }

}
        
header('Content-Type: application/json; charset=utf-8');
header('content-encoding: gzip');

ob_start("ob_gzhandler");
       
    $connection = new WidgetApi();		
    $GGet 		= $_GET;		
    $response 	= $connection->handler($GGet);        
    echo $response;

ob_end_flush();	

?>